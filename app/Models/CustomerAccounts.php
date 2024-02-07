<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * @property int $channel_id
 * @property string $name
 * @property string $begin_date_time
 * @property string $end_date_time
 */
class CustomerAccounts extends Model
{
    use HasFactory;
    public $timestamps = false;
    const API_KEY = '7aef10713fb3af5f92e4ab61';

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
     * Error catching was 'dirty' but by fastest way
     * @param Request $request
     * @return string
     */
    public function makeTransfer(Request $request) : string {

        $amount = $request->input('amount');
        $from = $request->input('from_account');
        $to = $request->input('to_account');
        $currency = $request->input('currency');

        //check if provided accounts exists
        $accounts = $this::query()
            ->where('unique_account_id', $from)
            ->orWhere('unique_account_id', $to)
            ->get();

        if($accounts->count() !== 2) {
            return json_encode(
                [
                    'success' => false,
                    'message' => 'Cant find these accounts.You can check accounts here:/client-accounts/{client_id}'
                ]
            );
        }

        foreach ($accounts as $account) {
            $clientIds[] = $account->unique_client_id;
            if($currency == $account->account_currency) {
                $primaryCurrency = $account->account_currency;
            } else {
                $changeToCurrency = $account->account_currency;
            }
        }

        if(!isset($primaryCurrency)) {
            return json_encode(
                [
                    'success' => false,
                    'message' => 'Exchanged currency:'.$currency.' do not belongs to provided currencies in account.'
                ]
            );
        }
        if(!isset($changeToCurrency)) {
            return json_encode(
                [
                    'success' => false,
                    'message' => 'Cant find change currency'
                ]
            );
        }
        if(!isset($clientIds) || count(array_unique($clientIds)) > 1) {
            return json_encode(
                [
                    'success' => false,
                    'message' => 'Accounts belongs to different customer'
                ]
            );
        }

        $accounts = $this::query()
            ->where('unique_account_id', $from)
            ->first();
        if($accounts->account_currency != $currency) {
            $rate = $this->getRate($primaryCurrency, $changeToCurrency);
            if($rate['success'] === false) {
                return json_encode(
                    [
                        'success' => false,
                        'message' => $rate['message']
                    ]
                );
            }
            $result = $amount * $rate['rate'];
        } else {
            //emulate
            $rate = [
                'success' => true,
                'rate' => 1
            ];
            $result = $amount;
        }

        if($accounts->account_balance < $result) {
            return json_encode(
                [
                    'success' => false,
                    'message' => 'Sorry...money is not enough...you are trying to send: '
                        .$amount.' '.$primaryCurrency
                        . ' it equals to '.$result.' '.$changeToCurrency.
                        ' but you have only '.$accounts->account_balance.' '.$changeToCurrency
                ]
            );
        }

        $balanceBefore = $accounts->account_balance;
        $accounts->account_balance = $accounts->account_balance - $result;
        $accounts->save();

        $this->saveToHistory($accounts->unique_account_id, $accounts->unique_client_id, 'send', $amount,
            $currency, $balanceBefore, $accounts->account_balance, $rate);

        $accounts = $this::query()
            ->where('unique_account_id', $request->input('to_account'))
            ->first();
        $balanceBefore = $accounts->account_balance;
        if($accounts->account_currency != $currency) {
            $rate = $this->getRate($currency, $accounts->account_currency);
            if($rate['success'] === false) {
                return json_encode(
                    [
                        'success' => false,
                        'message' => $rate['message']
                    ]
                );
            }
            $result = $amount * $rate['rate'];
        } else {
            //emulate
            $rate = [
                'success' => true,
                'rate' => 1
            ];
            $result = $amount;
        }
        $accounts->account_balance = $accounts->account_balance + $result;
        $accounts->save();

        $this->saveToHistory($accounts->unique_account_id, $accounts->unique_client_id, 'receiced', $amount,
            $currency, $balanceBefore, $accounts->account_balance, $rate);

        return json_encode([
            'success' => true,
            'message' => 'the money transfer was successful'
            ]);

    }

    /**
     * Save changed data to history table
     * @param int $accountId
     * @param int $clientId
     * @param string $type
     * @param float $amount
     * @param string $currency
     * @param float $balanceBefore
     * @param float $balanceAfter
     * @param array $rate
     * @return void
     */
    public function saveToHistory(int $accountId, int $clientId, string $type, float $amount, string $currency,
                                  float $balanceBefore, float $balanceAfter, array $rate): void
    {
        $transactionHistory = new CustomerAccountsTransactions();
        $transactionHistory->account_id = $accountId;
        $transactionHistory->client_id = $clientId;
        $transactionHistory->operation_type = $type;
        $transactionHistory->operation_amount = $amount;
        $transactionHistory->operation_currency = $currency;
        $transactionHistory->amount_before = $balanceBefore;
        $transactionHistory->amount_after = $balanceAfter;
        $transactionHistory->rate_information = json_encode($rate);
        $transactionHistory->save();
    }


    /**
     * @param string $currency
     * @param string $toCurrency
     * @return array
     */
    public function getRate(string $currency, string $toCurrency): array
    {
        $json = file_get_contents("https://v6.exchangerate-api.com/v6/".$this::API_KEY."/latest/".$currency);
        $obj = json_decode($json, true);
        if(isset($obj["conversion_rates"][$toCurrency])) {
            return [
                'success' => true,
                'rate' => $obj["conversion_rates"][$toCurrency]
            ];
        }
        return [
            'success' => false,
            'message' => $obj["error_type"]
        ];
    }
}
