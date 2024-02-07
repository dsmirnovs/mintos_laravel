<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MintosClientsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('mintos_clients')->insert([
            [
                'unique_client_id' => 1,
                'name' => 'John',
                'surname' => 'Travolta',
            ],
            [
                'unique_client_id' => 2,
                'name' => 'Emily',
                'surname' => 'Clarke',
            ],
            [
                'unique_client_id' => 3,
                'name' => 'Sidney',
                'surname' => 'Crosby',
            ]
        ]);
    }
}
