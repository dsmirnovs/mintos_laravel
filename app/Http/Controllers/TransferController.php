<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerAccountsTransactions;
use Illuminate\Auth\GenericUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\CustomerAccounts;
use Illuminate\Support\Facades\Validator;

class TransferController extends Controller
{
    public CustomerAccounts $customerAccountsModel;
    public CustomerAccountsTransactions $customerAccountsTransactionsModel;
    public Customer $customerModel;

    public function __construct()
    {
        $this->customerAccountsModel = new CustomerAccounts();
        $this->customerAccountsTransactionsModel = new CustomerAccountsTransactions();
        $this->customerModel = new Customer();
    }

    /**
     * Show on air programmes - return in json
     * @param int $channelNr
     * @return string
     */
    public function showAccounts(int $clientId): string {
        if(!$this->customerModel->isCustomerExists($clientId)) {
            return json_encode(['success' => false, 'message' => 'Customer with id '.$clientId.' doesnt exists']);
        }
        return $this->customerAccountsModel->getAccountsByCustomerId($clientId);
    }


    public function showTransactions(Request $request, $accountId) {
        $offset = $request->get('offset') ?? 0;
        $limit = $request->get('limit') ?? 0;
        return $this->customerAccountsTransactionsModel->getTransactionsByAccountId($accountId, $offset, $limit);

    }

    /**
     * @param Request $request
     * @return string
     */
    public function transferFunds(Request $request) : string {
        $validator = Validator::make($request->all(), [
            'from_account' => 'required',
            'to_account' => 'required',
            'currency' => 'required|max:3',
            'amount' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }
        return $this->customerAccountsModel->makeTransfer($request);

    }
}
