<?php

namespace Tests\Feature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransferMoneyTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        // seed the database
        $this->artisan('db:seed');
    }

    public function test_api_without_key()
    {
        $response = $this->get('api/client-accounts/1');
        $response->assertStatus(403);
    }

    public function test_accounts_with_api_key()
    {
        $response = $this->get('api/client-accounts/1', ['x-api-key' => getenv('MINTOS_API_KEY')]);
        $response->assertStatus(200);
    }

    public function test_transactions_with_api_key()
    {
        $response = $this->get('api/transaction-history/1', ['x-api-key' => getenv('MINTOS_API_KEY')]);
        $response->assertStatus(200);
    }


    public function test_correct_make_transfer()
    {
        $testData = [
            'from_account' => 1,
            'to_account' => 5,
            'currency' => 'GBP',
            'amount' => 1,
        ];
        $response = $this->post('api/transfer', $testData, ['x-api-key' => getenv('MINTOS_API_KEY')]);
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'The money was successfully transferred',
            ]);
    }

    public function test_is_transaction_table_are_filling()
    {
        $testData = [
            'from_account' => 1,
            'to_account' => 5,
            'currency' => 'GBP',
            'amount' => 1,
        ];
        $response = $this->post('api/transfer', $testData, ['x-api-key' => getenv('MINTOS_API_KEY')]);
        $this->assertDatabaseCount('mintos_clients_accounts_transactions', 2);
    }

    public function test_make_transfer_invalid_currency()
    {
        $testData = [
            'from_account' => 1,
            'to_account' => 5,
            'currency' => 'USD',
            'amount' => 1,
        ];
        $response = $this->post('api/transfer', $testData, ['x-api-key' => getenv('MINTOS_API_KEY')]);
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Exchanged currency:USD does not belong to any account.',
            ]);
    }

    public function test_make_transfer_invalid_account()
    {
        $testData = [
            'from_account' => 1,
            'to_account' =>95,
            'currency' => 'USD',
            'amount' => 1,
        ];
        $response = $this->post('api/transfer', $testData, ['x-api-key' => getenv('MINTOS_API_KEY')]);
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'One or all of the requested accounts were not found.You can check accounts here:/client-accounts/{client_id}',
            ]);
    }

    public function test_make_transfer_not_enough_money()
    {
        $testData = [
            'from_account' => 1,
            'to_account' =>5,
            'currency' => 'GBP',
            'amount' => 100,
        ];
        $response = $this->post('api/transfer', $testData, ['x-api-key' => getenv('MINTOS_API_KEY')]);
        $response->assertStatus(200)
            ->assertJsonFragment([
                'success' => false,
            ]);
    }


}
