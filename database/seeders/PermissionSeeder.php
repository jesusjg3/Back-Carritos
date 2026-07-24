<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\User;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            ['name' => 'view_dashboard', 'description' => 'Permite ver el dashboard principal'],
            ['name' => 'view_driver_reports', 'description' => 'Permite ver el reporte de conductores'],
            ['name' => 'view_route_reports', 'description' => 'Permite ver el reporte de rutas'],
            ['name' => 'view_passenger_reports', 'description' => 'Permite ver el reporte de pasajeros'],
            ['name' => 'manage_users', 'description' => 'Permite crear, editar y eliminar usuarios del sistema'],
        ];

        foreach ($permissions as $perm) {
            Permission::updateOrCreate(['name' => $perm['name']], $perm);
        }

        // Asignar todos los permisos al usuario superadmin (asumimos id = 1)
        $superAdmin = User::find(1);
        if ($superAdmin) {
            $allPermissionIds = Permission::pluck('id')->toArray();
            $superAdmin->permissions()->sync($allPermissionIds);
        }
    }
}
