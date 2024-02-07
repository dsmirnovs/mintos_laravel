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
        Schema::create('mintos_clients_accounts', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('unique_account_id')->unique();
            $table->tinyInteger('unique_client_id');
            $table->foreign('unique_client_id')
                ->references('unique_client_id')->on('mintos_clients')
                ->onDelete('cascade');
            $table->string('account_currency', 3);
            $table->float('account_balance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::drop('mintos_clients_accounts');
    }
};
