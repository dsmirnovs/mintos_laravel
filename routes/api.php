<?php

use App\Http\Controllers\TransferController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
Route::group([
    'middleware' => [
        'mintos_api_key' // check api key
    ]
], function () {
    Route::get('/client-accounts/{client_id}', [TransferController::class, 'showAccounts']);
    Route::get('/transaction-history/{account_id}', [TransferController::class, 'showTransactions']);
});
Route::middleware(['mintos_api_key'])->post('/transfer', [TransferController::class, 'transferFunds']);
