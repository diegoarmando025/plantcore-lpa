# Evaluación interactiva — una sola instalación

La aplicación ofrece un botón **Entrar como administrador demo** en `/login`
y una guía de recorrido privada `/guia`.

- Usa la instalación y base de datos actuales; no se duplican los sistemas.
- El visitante entra sin conocer una contraseña compartida. El servidor
  autentica una cuenta predefinida que está limitada exclusivamente a la
  planta ficticia `DEMO-PUBLIC`.
- El propietario conserva su inicio de sesión tradicional. Su cuenta no se
  utiliza para la demostración.
- La guía muestra información **realmente consultada de la BD** dentro de las
  plantas a las que el usuario tiene acceso; no usa cifras inventadas.
- Formularios, reportes, escaneos y acciones existentes siguen siendo los
  mismos; no se han creado pantallas simuladas nuevas.

## Activación segura tras actualizar con ZIP

1. Exporta/verifica el respaldo MariaDB y los archivos que se reemplazarán.
2. Extrae el ZIP dentro de la carpeta Laravel privada
   `/home/u641948971/domains/devalejandre.com/apps/lpa`.
3. Ejecuta la migración nueva con PHP 8.4 y limpia las cachés:

```bash
cd /home/u641948971/domains/devalejandre.com/apps/lpa
/opt/alt/php84/usr/bin/php artisan migrate --force
/opt/alt/php84/usr/bin/php artisan optimize:clear
/opt/alt/php84/usr/bin/php artisan portfolio:provision-access
```

**IMPORTANTE:** vuelve a ejecutar `portfolio:provision-access` con los MISMOS
dos correos que configuraste anteriormente. Confirma la cuenta demo existente:
este comando marca el usuario demo y no cambia su contraseña. Si intentas
indicar un propietario distinto o un demo diferente del ya marcado, el comando
falla. El usuario administrador demo debe pertenecer únicamente a la planta
ficticia `DEMO-PUBLIC` con rol `supervisor`. Nunca incluyas credenciales
en `.env`, el ZIP o GitHub.

En `/login`, prueba la opción de administrador demo desde un navegador privado.
El servidor debe llevar a `/guia`; desde allí deben abrirse los módulos reales.
Verifica después que tu cuenta privada siga funcionando.

## Alcance honesto

La cuenta demo es compartida por todos los visitantes: su actividad escribe
en la base actual y puede modificar los ejemplos y reportes. El sistema no
cuenta aún con separación por visitante ni restauración automática. Por eso
solo debes usar datos completamente ficticios en la planta demo y nunca
asignar dicha cuenta a otras plantas. La cuenta demo puede ejecutar acciones
irreversibles sobre esos datos ficticios, como liberar una caja o completar
una auditoría.

La cuenta demo no tiene acceso SQL ni panel de usuarios, pero este diseño
NO sustituye al aislamiento multiempresa requerido para un SaaS comercial.
