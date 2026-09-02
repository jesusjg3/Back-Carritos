<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Shift;

class ShiftSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $shifts = [
            [
                'name' => 'Mañana',
                'start_time' => '07:00:00',
                'end_time' => '12:00:00',
                'is_active' => true,
            ],
            [
                'name' => 'Tarde',
                'start_time' => '12:00:00',
                'end_time' => '17:00:00',
                'is_active' => true,
            ],
            [
                'name' => 'Noche',
                'start_time' => '17:00:00',
                'end_time' => '22:00:00',
                'is_active' => true,
            ],
        ];

        foreach ($shifts as $shift) {
            Shift::updateOrCreate(
                ['name' => $shift['name']],
                $shift
            );
        }
    }
}
