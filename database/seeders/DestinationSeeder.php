<?php

namespace Database\Seeders;

use App\Models\Destination;
use Illuminate\Database\Seeder;

class DestinationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $destinations = [
            [
                'name' => 'Canchas Multiples',
                'latitude' => -0.9536723592246519,
                'longitude' => -80.74678015046278,
                'address' => 'Canchas Multiples, Campus',
                'description' => 'Canchas para deportes múltiples',
                'is_active' => true,
            ],
            [
                'name' => 'Paraninfo y primera puerta',
                'latitude' => -0.9545685412300675,
                'longitude' => -80.74643247292981,
                'address' => 'Paraninfo y primera puerta, Campus',
                'description' => 'Paraninfo e ingreso principal',
                'is_active' => true,
            ],
            [
                'name' => 'Bienestar',
                'latitude' => -0.9544367542757695,
                'longitude' => -80.74523991745617,
                'address' => 'Bienestar, Campus',
                'description' => 'Centro de bienestar estudiantil',
                'is_active' => true,
            ],
            [
                'name' => 'Coliseo',
                'latitude' => -0.953740330735064,
                'longitude' => -80.74596353488339,
                'address' => 'Coliseo, Campus',
                'description' => 'Coliseo cubierto',
                'is_active' => true,
            ],
            [
                'name' => 'Bloque B-06 y B-07',
                'latitude' => -0.9528792303863934,
                'longitude' => -80.74644199016474,
                'address' => 'Bloque B-06 y B-07, Campus',
                'description' => 'Bloques académicos B-06 y B-07',
                'is_active' => true,
            ],
            [
                'name' => 'Bloque B-08',
                'latitude' => -0.9516896358524245,
                'longitude' => -80.74612620969583,
                'address' => 'Bloque B-08, Campus',
                'description' => 'Bloque académico B-08',
                'is_active' => true,
            ],
            [
                'name' => 'Bloque B-14, B-15 y B-16',
                'latitude' => -0.9510772974749268,
                'longitude' => -80.74564775440099,
                'address' => 'Bloque B-14, B-15 y B-16, Campus',
                'description' => 'Bloques académicos B-14, B-15 y B-16',
                'is_active' => true,
            ],
            [
                'name' => 'Tercera puerta',
                'latitude' => -0.950630800689669,
                'longitude' => -80.74609750237191,
                'address' => 'Tercera puerta, Campus',
                'description' => 'Tercera entrada al campus',
                'is_active' => true,
            ],
            [
                'name' => 'Bloques B-10, B-11 y B-12',
                'latitude' => -0.9503023065788718,
                'longitude' => -80.74556801185716,
                'address' => 'Bloques B-10, B-11 y B-12, Campus',
                'description' => 'Bloques académicos B-10, B-11 y B-12',
                'is_active' => true,
            ],
            [
                'name' => 'Bloque B-16 e idiomas',
                'latitude' => -0.9524199767676826,
                'longitude' => -80.7449077435085,
                'address' => 'Bloque B-16 e idiomas, Campus',
                'description' => 'Bloque B-16 y departamento de idiomas',
                'is_active' => true,
            ],
            [
                'name' => 'Guardería',
                'latitude' => -0.9536223280885031,
                'longitude' => -80.74544999283266,
                'address' => 'Guardería, Campus',
                'description' => 'Guardería universitaria',
                'is_active' => true,
            ],
            [
                'name' => 'Bloque B-22',
                'latitude' => -0.9532683201891373,
                'longitude' => -80.74466213645806,
                'address' => 'Bloque B-22, Campus',
                'description' => 'Bloque académico B-22',
                'is_active' => true,
            ],
            [
                'name' => 'Segunda puerta',
                'latitude' => -0.9531047100014944,
                'longitude' => -80.74384429589658,
                'address' => 'Segunda puerta, Campus',
                'description' => 'Segunda entrada al campus',
                'is_active' => true,
            ],
            [
                'name' => 'Bloques B-17, B-18 y B-24',
                'latitude' => -0.9523613423911151,
                'longitude' => -80.7438670551939,
                'address' => 'Bloques B-17, B-18 y B-24, Campus',
                'description' => 'Bloques académicos B-17, B-18 y B-24',
                'is_active' => true,
            ],
            [
                'name' => 'Bloques B-19 y B-25',
                'latitude' => -0.9516179746095584,
                'longitude' => -80.74390119414119,
                'address' => 'Bloques B-19 y B-25, Campus',
                'description' => 'Bloques académicos B-19 y B-25',
                'is_active' => true,
            ],
            [
                'name' => 'Bloque B-21 y cancha',
                'latitude' => -0.9507873747059681,
                'longitude' => -80.74315393054388,
                'address' => 'Bloque B-21 y cancha, Campus',
                'description' => 'Bloque B-21 y cancha de juego',
                'is_active' => true,
            ],
            [
                'name' => 'Estadio',
                'latitude' => -0.9529739037613345,
                'longitude' => -80.74594280834604,
                'address' => 'Estadio, Campus',
                'description' => 'Estadio universitario',
                'is_active' => true,
            ],
        ];

        foreach ($destinations as $destination) {
            Destination::create($destination);
        }
    }
}
