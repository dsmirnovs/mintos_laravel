<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int|mixed $account_id
 * @property int|mixed $client_id
 * @property mixed|string $operation_type
 * @property float|mixed $operation_amount
 * @property mixed|string $operation_currency
 * @property float|mixed $amount_before
 * @property float|mixed $amount_after
 * @property false|mixed|string $rate_information
 */
class CustomerAccountsTransactions extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     * @var string
     */
    protected $table = 'mintos_clients_accounts_transactions';

    /**
     * Return transaction history with possibility to use limits and offsets
     * @param int $accountId
     * @param int $offset
     * @param int $limit
     * @return string
     */
    public function getTransactionsByAccountId(int $accountId, int $offset, int $limit): string {
        $transactions = $this::query()->where('account_id', $accountId);
        if(!empty($limit)) {
            if(!empty($offset)) {
                $transactions = $transactions->skip($offset)->take($limit);
            } else {
                $transactions = $transactions->take($limit);
            }
        }
        $transactions = $transactions->get();
        if(!$transactions->count()) {
            return json_encode(['success' => false, 'message' => 'There is no transactions history for: '.$accountId]);
        }
        return $transactions;
    }
}
