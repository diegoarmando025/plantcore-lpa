# PlantCore LPA

Sistema de auditorías por capas (Layered Process Audits) para operaciones industriales. Proyecto de portafolio desarrollado en Laravel y AdminLTE, con datos de demostración ficticios.

## Funcionalidades

- Planeación individual y masiva de auditorías diarias o semanales, con validación de duplicados.
- Cuestionarios por nivel LPA (1 a 5), administración por supervisor y protección de preguntas de plantillas utilizadas.
- Calendario mensual y ejecución de auditorías con respuestas **Cumple / No cumple / No aplica**.
- Generación de acciones correctivas por incumplimiento, responsables, fechas compromiso y seguimiento de vencimientos.
- Indicadores de conformidad y cumplimiento por nivel y por responsable; exportaciones CSV.
- Control de acceso por rol y por planta, con pruebas automatizadas de aislamiento.

## Tecnologías

PHP 8.4+, Laravel 13, SQLite (desarrollo/pruebas), Blade, Bootstrap 5, AdminLTE 4, Vite, PHPUnit y GitHub Actions.

## Instalación local

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Configura `DB_CONNECTION=sqlite` en `.env` y crea `database/database.sqlite` si no existe. Después:

```bash
php artisan migrate
php artisan db:seed --class=LpaSeeder
npm ci
npm run build
php artisan test
php artisan serve
```

Para iniciar sesión, establece una contraseña local al usuario de demostración `supervisor@lpa.example.invalid` mediante `php artisan tinker`:

```php
$user = App\Models\User::where('email', 'supervisor@lpa.example.invalid')->firstOrFail();
$user->password = 'USA-UNA-CONTRASENA-SEGURA-SOLO-LOCAL';
$user->save();
```

Abre `http://127.0.0.1:8000/login`. No se publica ninguna contraseña funcional de demostración.

## Rutas principales

| Función | Ruta |
|---|---|
| Auditorías | `/audits` |
| Programación masiva | `/audits/batch` |
| Calendario | `/calendar` |
| Cuestionarios | `/templates` |
| Acciones correctivas | `/actions` |
| Indicadores | `/reports` |
| Exportaciones | `/exports/audits`, `/exports/actions` |

## Calidad y seguridad

Las pruebas cubren programación, ejecución, acciones, permisos por rol, aislamiento entre plantas, indicadores y exportaciones. Las salidas CSV se protegen contra fórmulas inyectadas. Ejecuta las pruebas antes de publicar nuevas versiones.

## Alcance

Demostración funcional de arquitectura y flujos industriales. No incluye información, código ni reglas propietarias de clientes. No está configurado para producción: antes de un despliegue real se requiere configuración segura del entorno, HTTPS, respaldo, monitorización y revisión de acceso.
