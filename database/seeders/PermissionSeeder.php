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
            ['name' => 'manage_users', 'description' => 'Permite crear, editar y eliminar usuarios del sistema (Conductores y Pasajeros)'],
            ['name' => 'manage_admins', 'description' => 'Permite crear, editar y suspender a otros administradores'],
            ['name' => 'manage_vehicles', 'description' => 'Permite gestionar la flota de vehículos'],
            ['name' => 'manage_destinations', 'description' => 'Permite gestionar las paradas y destinos'],
            ['name' => 'view_history', 'description' => 'Permite visualizar el historial completo de viajes'],
            ['name' => 'manage_shifts', 'description' => 'Permite gestionar los horarios de conductores'],
            ['name' => 'manage_assignments', 'description' => 'Permite gestionar las asignaciones de vehículos a conductores'],
            ['name' => 'manage_events', 'description' => 'Permite gestionar los eventos de la universidad'],
            ['name' => 'manage_disconnects', 'description' => 'Permite gestionar las solicitudes de desconexión'],
            ['name' => 'view_audit_log', 'description' => 'Permite consultar la auditoría de acciones administrativas'],
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
