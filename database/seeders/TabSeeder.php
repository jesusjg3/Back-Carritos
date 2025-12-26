<?php

namespace Database\Seeders;

use App\Models\Rol;
use App\Models\Tabs;
use Illuminate\Database\Seeder;

class TabSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener IDs de roles dinámicamente
        $adminRol = Rol::where('rol_name', 'admin')->first();
        $pasajeroRol = Rol::where('rol_name', 'pasajero')->first();
        $conductorRol = Rol::where('rol_name', 'conductor')->first();

        // Tabs para ADMIN
        if ($adminRol) {
            $tabs = [
                ['tab_name' => 'Dashboard', 'tab_icon' => 'view-dashboard', 'tab_order' => 1],
                ['tab_name' => 'Usuarios', 'tab_icon' => 'account-group', 'tab_order' => 2],
                ['tab_name' => 'Configuración', 'tab_icon' => 'cog', 'tab_order' => 3],
            ];
            foreach ($tabs as $tab) {
                Tabs::firstOrCreate(
                    ['rol_id' => $adminRol->id, 'tab_name' => $tab['tab_name']],
                    $tab
                );
            }
        }

        // Tabs para PASAJERO
        if ($pasajeroRol) {
            $tabs = [
                ['tab_name' => 'Inicio', 'tab_icon' => 'home', 'tab_order' => 1],
                ['tab_name' => 'Pedir Carrera', 'tab_icon' => 'car', 'tab_order' => 2],
                ['tab_name' => 'Configuración', 'tab_icon' => 'cog', 'tab_order' => 3],
            ];
            foreach ($tabs as $tab) {
                Tabs::firstOrCreate(
                    ['rol_id' => $pasajeroRol->id, 'tab_name' => $tab['tab_name']],
                    $tab
                );
            }
        }

        // Tabs para CONDUCTOR
        if ($conductorRol) {
            $tabs = [
                ['tab_name' => 'Inicio', 'tab_icon' => 'home', 'tab_order' => 1], // Mapa de solicitudes o botón de "Conectarse"
                ['tab_name' => 'Configuración', 'tab_icon' => 'cog', 'tab_order' => 3],
            ];
            foreach ($tabs as $tab) {
                Tabs::firstOrCreate(
                    ['rol_id' => $conductorRol->id, 'tab_name' => $tab['tab_name']],
                    $tab
                );
            }
        }
    }
}

