# Guía de evaluación — PlantCore LPA

## Preparación

Requisitos: PHP 8.4, Composer, Node.js 22 y npm. Trabaja sobre una base de datos de demostración **separada** de cualquier instalación existente.

```bash
git clone https://github.com/diegoarmando025/plantcore-lpa.git
cd plantcore-lpa
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed --class=LpaPortfolioSeeder
npm ci
npm run build
php artisan test
php artisan serve
```

En PowerShell, usa `Copy-Item .env.example .env` en lugar de `cp` si prefieres. Si la instalación no creó el archivo SQLite, crea `database/database.sqlite` antes de migrar. El repositorio no publica una contraseña funcional: elige una local para `demo.supervisor@lpa.example.invalid` con `php artisan tinker`, siguiendo el [README](../README.md).

## Recorrido funcional

| Paso | Ruta | Qué debe comprobarse |
| --- | --- | --- |
| 1 | `/audits` | Auditorías programadas y completadas con indicadores y filtros |
| 2 | `/templates` | Cuestionarios por área y nivel LPA, cada uno con tres preguntas |
| 3 | `/calendar` | Auditorías distribuidas por día; cambia de mes si el actual no coincide con el escenario |
| 4 | `/audits` | Abre una auditoría programada; responde Cumple, No cumple o No aplica |
| 5 | `/actions` | Revisa el hallazgo ficticio vencido y su fecha compromiso |
| 6 | `/reports` | Contrasta cumplimiento del plan y conformidad; filtra por periodo |
| 7 | `/audits/batch` | Programa varias fechas y comprueba que no se dupliquen al repetir la operación |
| 8 | `/exports/summary` | Descarga el CSV de resumen y ábrelo con Excel o un editor de texto |

Las fechas del seeder son relativas al día de ejecución, para que el escenario siga teniendo auditorías próximas y acciones vencidas. Las operaciones del paso 4 modifican datos locales; utiliza una copia de prueba.

## Qué demuestra el código

- **Autorización por planta y rol:** `app/Support/LpaAccess.php`.
- **Finalización transaccional:** `LpaController::complete()` y creación de `CorrectiveAction` para respuestas no conformes.
- **Programación masiva:** `LpaBatchController::store()`, validaciones, límite de 60 auditorías y omisión de duplicados.
- **Reportes y exportaciones:** `LpaController::report()` y controladores `Lpa*ExportController`.
- **Pruebas automatizadas:** `tests/Feature`, incluyendo acceso restringido a otras plantas y datos de evaluación repetibles.

## Capturas recomendadas para el portafolio

Las imágenes deben obtenerse de la **aplicación ejecutándose**, no de maquetas generadas: listado con auditorías, calendario, formulario de preguntas, seguimiento de acciones y reporte por nivel. Mantén visibles solo datos ficticios y evita capturas con excepciones, consola, datos personales o credenciales.

## Alcance

Esta es una aplicación demostrativa funcional. No afirma cumplimiento con una certificación industrial específica ni sustituye la aprobación de seguridad y procesos para su uso real.
