<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MintosClientsAccountsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('mintos_clients_accounts')->insert([
            [
                'unique_client_id' => 1,
                'unique_account_id' => 1,
                'account_currency' => 'EUR',
                'account_balance' => 31.00,
            ],
            [
                'unique_client_id' => 1,
                'unique_account_id' => 2,
                'account_currency' => 'USD',
                'account_balance' => 31.00,
            ],
            [
                'unique_client_id' => 1,
                'unique_account_id' => 3,
                'account_currency' => 'JOD',
                'account_balance' => 31.00,
            ],
            [
                'unique_client_id' => 2,
                'unique_account_id' => 4,
                'account_currency' => 'EUR',
                'account_balance' => 00.00,
            ],
            [
                'unique_client_id' => 1,
                'unique_account_id' => 5,
                'account_currency' => 'GBP',
                'account_balance' => 00.00,
            ]
        ]);
    }
}
