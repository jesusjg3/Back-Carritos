# Carritos — API backend

API REST para la gestión de transporte interno universitario. Está construida con Laravel 12, PHP 8.2+, PostgreSQL, JWT y Laravel Reverb.

## Requisitos

- PHP 8.2 o superior con extensión PDO PostgreSQL.
- Composer.
- PostgreSQL 14 o superior.
- Node.js y npm si se desea ejecutar herramientas frontend desde el proyecto Laravel.

## Instalación local

```bash
cd Back-Carritos
composer install
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
```

Crea la base de datos `carritos` en PostgreSQL y completa las credenciales en `.env`. Luego ejecuta:

```bash
php artisan migrate
php artisan db:seed
```

Si cambias variables de entorno con la configuración cacheada, ejecuta `php artisan config:clear` antes de reiniciar los servicios.

El seeder crea roles, permisos, destinos, vehículos, horarios y datos de demostración.

## Ejecución

En terminales separadas:

```bash
php artisan serve --host=0.0.0.0 --port=8000
php artisan reverb:start --host=0.0.0.0 --port=8080
php artisan queue:work
```

Para consultar las rutas disponibles:

```bash
php artisan route:list --path=api
```

## Configuración importante

- `CAMPUS_GEOFENCE_ENABLED` activa o desactiva la geocerca. En producción debe estar en `true`; puede permanecer en `false` durante pruebas desde fuera del campus.
- `CAMPUS_CENTER_LAT`, `CAMPUS_CENTER_LNG` y `CAMPUS_RADIUS_KM` controlan el centro y el radio permitido en kilómetros. La API rechaza solicitudes cuyo origen esté fuera del radio cuando la geocerca está activa.
- `OSRM_URL` configura el servicio de cálculo de rutas.
- `REVERB_HOST`, `REVERB_PORT` y `REVERB_SCHEME` configuran los WebSockets.
- La ubicación y las estadísticas se transmiten por canales privados; Reverb debe estar levantado y el usuario debe conservar un token activo.
- `QUEUE_CONNECTION=database` requiere ejecutar el worker para procesar notificaciones push.
- Nunca se deben publicar `.env`, secretos JWT, credenciales de servicios externos ni llaves de Firebase.

## Usuarios de demostración

Después de ejecutar los seeders:

| Rol | Correo | Contraseña |
| --- | --- | --- |
| Administrador | `admin@test.com` | `12345678` |
| Pasajero | `pasajero@test.com` | `12345678` |
| Conductor | `conductor@test.com` | `12345678` |

Son credenciales de desarrollo; deben cambiarse antes de cualquier despliegue.

## Pruebas

```bash
php artisan test
```

Para una comprobación rápida de geocerca, rutas protegidas, estados y calificaciones compartidas:

```bash
php artisan test --filter='SecurityRegressionTest|TripRatingServiceTest'
```

Los archivos `.rest` contienen escenarios manuales para autenticación y ciclo de viajes. Los endpoints de prueba de broadcast no forman parte de la API y no deben agregarse nuevamente a producción.

## Arquitectura resumida

- `app/Http`: controladores, Form Requests y middleware.
- `app/Services`: reglas de negocio y coordinación de casos de uso.
- `app/Repositories`: consultas y persistencia.
- `app/Models`: entidades, relaciones y estados.
- `app/Events`: eventos de Reverb para actualizaciones en tiempo real.
- `app/Jobs`: tareas asíncronas, principalmente notificaciones push.
- `database`: migraciones y seeders reproducibles.
- `routes/api.php`: contrato HTTP de la aplicación.

En viajes compartidos, el conductor envía una sola calificación y comentario; el backend los aplica a todos los pasajeros que fueron dejados en destino.

## Seguridad

El registro público crea exclusivamente pasajeros. La administración de usuarios, vehículos, destinos, horarios, asignaciones, eventos, reportes y quejas requiere autenticación, usuario activo y el permiso correspondiente.
