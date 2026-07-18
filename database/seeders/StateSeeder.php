<?php

namespace Database\Seeders;

use App\Models\State;
use Illuminate\Database\Seeder;

class StateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        State::firstOrCreate(['id' => 1], ['state_name' => 'SOLICITADO']);
        State::firstOrCreate(['id' => 2], ['state_name' => 'ACEPTADO']);
        State::firstOrCreate(['id' => 3], ['state_name' => 'TERMINADO']);
        State::firstOrCreate(['id' => 4], ['state_name' => 'INICIADO']);
        State::firstOrCreate(['id' => 5], ['state_name' => 'CANCELADO']);
    }
}

