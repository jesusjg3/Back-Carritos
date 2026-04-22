# Back-Carritos Backend

[![Laravel Logo](https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg)](https://laravel.com)

This is the backend API for the Carritos mobile application, built with Laravel.

## Prerequisites

- PHP >= 8.2
- Composer
- PostgreSQL

## Installation & Setup

Follow these steps to set up the project locally:

1. **Clone the repository**

    ```bash
    git clone <repository-url>
    cd Back-Carritos
    ```

2. **Environment Configuration**
    Copy the example environment file and configure it:

    ```bash
    cp .env.example .env
    ```

    Update the `.env` file with your database credentials and Reverb (WebSocket) host.
    **IMPORTANT:** Set `REVERB_HOST` to your local machine's IP address (e.g., 192.168.x.x) if you are testing with a mobile device or other computers on the network.

    ```ini
    DB_CONNECTION=pgsql
    DB_HOST=127.0.0.1
    DB_PORT=5432
    DB_DATABASE=carritos
    DB_USERNAME=your_username
    DB_PASSWORD=your_password

    # Reverb Configuration (WebSockets)
    REVERB_HOST=localhost # CHANGE THIS to your local IP
    REVERB_PORT=8080
    REVERB_SCHEME=http
    ```

    **Note:** Make sure to create a PostgreSQL database named `carritos` before running migrations.

3. **Install PHP Dependencies**

    ```bash
    composer install
    ```

4. **Database Permissions (Important)**
    Ensure your database user has the necessary permissions on the `public` schema. If you encounter permission errors, run:

    ```bash
    sudo -u postgres psql -d carritos -c 'GRANT ALL ON SCHEMA public TO <your_username>;'
    ```

5. **Generate Application Key**

    ```bash
    php artisan key:generate
    ```

6. **Generate JWT Secret**
    Required for authentication:

    ```bash
    php artisan jwt:secret
    ```

7. **Run Migrations & Seeders**
    Create tables and populate initial data (roles, etc.):

    ```bash
    php artisan migrate
    php artisan db:seed
    ```

### Test Users

Use these accounts to log in after running the seeders:

- **Admin**: `admin@test.com` / `12345678`
- **Pasajero**: `pasajero@test.com` / `12345678`
- **Conductor**: `conductor@test.com` / `12345678`

## Useful Commands

- **Run Server**: `php artisan serve --host=yourlocalip` (Change to your local IP)
- **Start Reverb (WebSockets)**: `php artisan reverb:start --host=yourlocalip --port=8080`
- **Tinker**: `php artisan tinker`
- **Route List**: `php artisan route:list`

## Troubleshooting

- **Undefined table "rols"**: Ensure you have run `php artisan migrate`.
- **JWTException: Secret is not set**: Run `php artisan jwt:secret`.
- **Permission denied for schema public**: Grant schema permissions to your DB user (see Step 4).

## Funcionalidades Clave

### Geofencing (Límite de Zona de Servicio)
El backend incluye una doble validación de ubicación para evitar que usuarios soliciten viajes fuera de la universidad. 
- **Validación Backend:** En `StoreTripRequest`, la fórmula de Haversine valida estrictamente que la latitud y longitud de origen del pasajero se encuentren dentro de un **radio de 1.5 kilómetros** del punto central del campus. Si el viaje se origina fuera de este límite, el servidor rechaza la petición con un error HTTP 422.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
