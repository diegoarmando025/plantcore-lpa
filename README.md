# PlantCore LPA

Sistema de auditorías por capas (Layered Process Audits) para operaciones industriales. Proyecto de portafolio desarrollado en Laravel y AdminLTE, con datos de demostración ficticios.

## Funcionalidades

- Planeación individual y masiva de auditorías diarias o semanales, con validación de duplicados.
- Cuestionarios por nivel LPA (1 a 5), administración por supervisor y protección de preguntas de plantillas utilizadas.
- Calendario mensual y ejecución de auditorías con respuestas **Cumple / No cumple / No aplica**.
- Generación de acciones correctivas por incumplimiento, responsables, fechas compromiso y seguimiento de vencimientos.
- Indicadores de conformidad y cumplimiento por nivel y por responsable; exportaciones CSV.
- Control de acceso por rol y por planta, con pruebas automatizadas de aislamiento.

## Documentación técnica

- [Arquitectura, modelo de datos, permisos y límites](docs/ARCHITECTURE.md)
- [Guía para evaluar el sistema y obtener capturas reales](docs/REVIEWER_GUIDE.md)

## Tecnologías

PHP 8.4, Laravel 13, SQLite (desarrollo/pruebas), Blade, Bootstrap 5, AdminLTE 4, Vite, PHPUnit y GitHub Actions.

## Instalación local

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Configura `DB_CONNECTION=sqlite` en `.env` y crea `database/database.sqlite` si no existe. En PowerShell puedes usar `Copy-Item .env.example .env` en lugar de `cp`. Después:

```bash
php artisan migrate
php artisan db:seed --class=LpaPortfolioSeeder
npm ci
npm run build
php artisan test
php artisan serve
```

## Escenario reproducible

`php artisan db:seed --class=LpaPortfolioSeeder` crea una planta ficticia, dos cuestionarios (niveles 1 y 2), seis auditorías (cuatro completadas, dos programadas) y una acción correctiva vencida. Es seguro ejecutarlo de nuevo: no duplica los registros de demostración y no modifica otras plantas. El seeder solo funciona en entornos `local` y `testing`; **no ejecutes `migrate:fresh` sobre una base con datos que quieras conservar**.

Para iniciar sesión, elige una contraseña **solo para tu instalación local** al usuario `demo.supervisor@lpa.example.invalid`:

```bash
php artisan tinker
```

```php
$user = App\Models\User::where('email', 'demo.supervisor@lpa.example.invalid')->firstOrFail();
$user->password = 'REEMPLAZA-CON-UNA-CONTRASENA-LOCAL-SEGURA';
$user->save();
```

El usuario auditor es `demo.auditor@lpa.example.invalid` y también recibe una contraseña aleatoria sin publicar. Abre `http://127.0.0.1:8000/login`. No se incluyen credenciales predecibles en Git.

Una evaluación de principio a fin: entra como supervisor, revisa `/audits`, abre una auditoría programada y complétala, consulta el indicador de conformidad en `/reports` y administra el hallazgo en `/actions`.

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

## Verificación local y automatizada

```bash
php artisan test
npm run build
```

GitHub Actions ejecuta instalación de dependencias, compilación de activos y pruebas sobre cada push o pull request a `main`. El escenario de demostración tiene una prueba que comprueba repetibilidad, aislamiento por planta y acceso a auditorías, reportes y acciones.

## Calidad y seguridad

Las pruebas cubren programación, ejecución, acciones, permisos por rol, aislamiento entre plantas, indicadores y exportaciones. Las salidas CSV se protegen contra fórmulas inyectadas. Ejecuta las pruebas antes de publicar nuevas versiones.

## Otro sistema industrial del portafolio

[PlantCore Traceability — aplicación web y API REST](https://github.com/diegoarmando025/industrial-traceability-api): registro de cajas, validación de lecturas, control de liberación e historial por usuario. Es un repositorio independiente; no comparte datos de ejecución con PlantCore LPA.

## Alcance

Demostración funcional de arquitectura y flujos industriales. No incluye información, código ni reglas propietarias de clientes. No está configurado para producción: antes de un despliegue real se requiere configuración segura del entorno, HTTPS, respaldo, monitorización y revisión de acceso.
