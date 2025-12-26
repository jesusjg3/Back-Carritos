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
        // IDs must match constants in App\Models\State
        // const REQUESTED = 1;
        // const ACCEPTED = 2;
        // const FINISHED = 3;

        State::firstOrCreate(['id' => 1], ['state_name' => 'SOLICITADO']);
        State::firstOrCreate(['id' => 2], ['state_name' => 'ACEPTADO']);
        State::firstOrCreate(['id' => 3], ['state_name' => 'FINALIZADO']);

        // You can add more states if needed, e.g. CANCELADO
        State::firstOrCreate(['id' => 4], ['state_name' => 'CANCELADO']);
    }
}

