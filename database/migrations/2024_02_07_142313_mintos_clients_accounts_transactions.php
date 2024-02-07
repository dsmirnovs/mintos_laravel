<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mintos_clients_accounts_transactions', function (Blueprint $table) {
            $table->id();

            $table->tinyInteger('client_id');
            $table->foreign('client_id')
                ->references('unique_client_id')->on('mintos_clients')
                ->onDelete('cascade');

            $table->tinyInteger('account_id');
            $table->foreign('account_id')
                ->references('unique_account_id')->on('mintos_clients_accounts')
                ->onDelete('cascade');

            $table->string('operation_type');
            $table->string('operation_amount');
            $table->string('operation_currency');

            $table->float('amount_before');
            $table->float('amount_after');

            $table->json('rate_information');

            $table->dateTime('updated_at');
            $table->dateTime('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::drop('mintos_clients_accounts_transactions');
    }
};
