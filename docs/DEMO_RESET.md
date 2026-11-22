# Restablecimiento controlado LPA

Usa la misma instalación y base de datos. Solo afecta la planta ficticia DEMO-PUBLIC.

El comando elimina auditorías, respuestas, acciones, cuestionarios y preguntas de esa planta, y vuelve a crear el escenario mediante el seeder existente. No elimina usuarios, contraseñas, membresías ni registros de otras plantas. Rechaza un reset si hay referencias a sus cuestionarios desde otra planta.

**Antes de ejecutarlo:** verificar un respaldo recuperable de MariaDB y confirmar que no hay visitantes operando el demo.

Desde la carpeta privada de LPA:

1. Ejecutar `php artisan portfolio:reset-demo --dry-run` para revisar cantidades.
2. Ejecutar `php artisan portfolio:reset-demo` desde SSH interactivo.
3. Confirmar el respaldo y escribir `RESTABLECER DEMO-PUBLIC`.
4. Comprobar /guia, /audits, /actions y /reports.

La operación se ejecuta en una transacción. No se habilita por HTTP ni se programa automáticamente. Un visitante puede perder los cambios hechos sobre la planta demo. Las operaciones concurrentes de otras rutas no están bloqueadas: usar una ventana sin visitantes.
