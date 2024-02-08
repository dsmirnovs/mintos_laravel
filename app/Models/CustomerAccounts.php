<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
class CustomerAccounts extends Model
{
    use HasFactory;

    public $timestamps = false;

    /**
     * API KEY FOR SERVICE - https://app.exchangerate-api.com/
     * VALID 14 DAYS FROM 7.02.2024
     */
    const API_KEY = '7aef10713fb3af5f92e4ab61';
    const API_BASE_URL = 'https://v6.exchangerate-api.com/v6/';

    /**
     * The table associated with the model.
     * @var string
     */
    protected $table = 'mintos_clients_accounts';

    /**
     * Return all accounts belongs to customerID
     * @param int $customerId
     * @return string
     */
    public function getAccountsByCustomerId(int $customerId): string {
        $accounts = $this::query()
            ->where('unique_client_id', $customerId)
            ->get();
        if(!$accounts->count()) {
            return json_encode(['success' => false, 'message' => 'There is no accounts that belongs to customer: '.$customerId]);
        }
        return $accounts;
    }

    /**
     * Function will transfer money from one customer account to another by rates + add record to history table
     * All fields are already validated in controller.
     * Error catching was 'dirty' but by the fastest way
     * @param Request $request
     * @return string
     */
    public function makeTransfer(Request $request) : string {

        $amount = $request->input('amount');
        $from = $request->input('from_account');
        $to = $request->input('to_account');
        $currency = strtoupper($request->input('currency'));

        $accounts = $this::query()
            ->where('unique_account_id', $from)
            ->orWhere('unique_account_id', $to)
            ->get();

        //ACCOUNTS ERROR
        if($accounts->count() !== 2) {
            return json_encode(
                [
                    'success' => false,
                    'message' => 'One or all of the requested accounts were not found.You can check accounts here:/client-accounts/{client_id}'
                ]
            );
        }

        foreach ($accounts as $account) {
            if($currency == $account->account_currency) {
                if (isset($primaryCurrency)) {
                    //same currencies from and to
                    $changeToCurrency = $account->account_currency;
                }
                $primaryCurrency = $account->account_currency;
            } else {
                $changeToCurrency = $account->account_currency;
            }
        }

        //CURRENCY ERROR 1
        if(!isset($primaryCurrency)) {
            return json_encode(
                [
                    'success' => false,
                    'message' => 'Exchanged currency:'.$currency.' does not belong to any account.'
                ]
            );
        }

        //CURRENCY ERROR 2
        if(!isset($changeToCurrency)) {
            return json_encode(
                [
                    'success' => false,
                    'message' => 'Сould not determine the exchange currency'
                ]
            );
        }

        $accountsFrom = $this::query()
            ->where('unique_account_id', $from)
            ->first();
        $resultFrom = $this->calculateExchange($accountsFrom, $amount, $primaryCurrency, $changeToCurrency);

        //RATE ERROR
        if($resultFrom['success'] === false) {
            return json_encode($resultFrom);
        }

        //BALANCE ERROR
        if($accountsFrom->account_balance < $resultFrom['result']) {
            $exchangeMsg = '';
            if($primaryCurrency != $accountsFrom->account_currency) {
                $exchangeMsg = '('.$resultFrom['result'].$accountsFrom->account_currency.') ';
            }
            return json_encode(
                [
                    'success' => false,
                    'message' => 'There are insufficient funds in your account.'
                        .'You are trying to send: '.$amount.' '.$primaryCurrency.' '.$exchangeMsg
                        .'but on your account only '.$accountsFrom->account_balance.' '.$accountsFrom->account_currency
                ]
            );
        }

        //PROCESSED
        $fromBalanceBefore = $accountsFrom->account_balance;
        $accountsFrom->account_balance = $accountsFrom->account_balance - $resultFrom['result'];

        $accountsTo = $this::query()
            ->where('unique_account_id', $request->input('to_account'))
            ->first();
        $result = $this->calculateExchange($accountsTo, $amount, $primaryCurrency, $changeToCurrency);
        //RATE ERROR
        if($result['success'] === false) {
            return json_encode($result);
        }
        $toBalanceBefore = $accountsTo->account_balance;
        $accountsTo->account_balance = $accountsTo->account_balance + $result['result'];

        //ALL PASSED TRY TO SAVE DATA
        $accountsFrom->save();
        $this->saveToHistory($accountsFrom, 'send', $amount, $currency, $fromBalanceBefore, $resultFrom['rate']);

        $accountsTo->save();
        $this->saveToHistory($accountsTo, 'received', $amount, $currency, $toBalanceBefore, $result['rate']);

        return json_encode(['success' => true, 'message' => 'The money was successfully transferred']);
    }

    /**
     * Save changed data to history table
     * @param object $accounts
     * @param string $type
     * @param float $amount
     * @param string $currency
     * @param float $balanceBefore
     * @param array $rate
     * @return void
     */
    public function saveToHistory(object $accounts, string $type, float $amount, string $currency,
                                  float $balanceBefore, array $rate): void
    {
        $transactionHistory = new CustomerAccountsTransactions();
        $transactionHistory->account_id = $accounts->unique_account_id;
        $transactionHistory->client_id = $accounts->unique_client_id;
        $transactionHistory->operation_type = $type;
        $transactionHistory->operation_amount = $amount;
        $transactionHistory->operation_currency = $currency;
        $transactionHistory->account_currency = $accounts->account_currency;
        $transactionHistory->amount_before = $balanceBefore;
        $transactionHistory->amount_after = $accounts->account_balance;
        $transactionHistory->rate_information = json_encode($rate);
        $transactionHistory->save();
    }


    /**
     * Get rate from external api
     * @param string $currency
     * @param string $toCurrency
     * @return array
     */
    public function getRate(string $currency, string $toCurrency): array
    {
        $currency = strtoupper($currency);
        $toCurrency = strtoupper($toCurrency);
        $context = stream_context_create(array(
            'http' => array('ignore_errors' => true),
        ));
        $json = file_get_contents($this::API_BASE_URL.$this::API_KEY."/latest/".$currency, false, $context);
        $obj = json_decode($json, true);
        if($obj['result'] === 'error') {
            return [
                'success' => false,
                'message' => $obj['error-type']
            ];
        }

        if(isset($obj["conversion_rates"][$toCurrency])) {
            return [
                'success' => true,
                'rate' => $obj["conversion_rates"][$toCurrency]
            ];
        } else {
            return [
                'success' => false,
                'message' => $toCurrency.' currency is not supported'
            ];
        }
    }

    /**
     * Calculate needed exchange
     * @param object $accounts
     * @param float $amount
     * @param string $primaryCurrency
     * @param string $changeToCurrency
     * @return array
     */
    public function calculateExchange(object $accounts, float $amount, string $primaryCurrency, string $changeToCurrency) : array{
        if($accounts->account_currency != $primaryCurrency) {
            $rate = $this->getRate($primaryCurrency, $changeToCurrency);
            if($rate['success'] === false) {
                return ['success' => false, 'message' => $rate['message']];
            }
            return [
                'success' => true,
                'result' => round($amount * $rate['rate'],2),
                'rate' => $rate
            ];
        }
        return [
            'success' => true, 'result' => $amount, 'rate' => [
                'success' => true,
                'rate' => 1
            ]
        ];
    }
}
