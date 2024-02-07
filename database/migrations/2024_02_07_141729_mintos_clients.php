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
        Schema::create('mintos_clients', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('unique_client_id')->unique();
            $table->string('name', 100);
            $table->string('surname', 100);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::drop('mintos_clients');
    }

};
