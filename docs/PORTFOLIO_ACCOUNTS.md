# Acceso de portafolio a una sola instalación — LPA

**No duplicar aplicaciones ni bases de datos.** Se utiliza la instalación
actual, la base actual y la planta ficticia existente (`DEMO-PUBLIC`).

Se crean dos cuentas de sesión para el `/login` normal:
- **Propietario:** la cuenta indicada desde SSH recibe `is_portfolio_owner=1`
  y rol `supervisor` en todas las plantas que ya existen al ejecutar el comando.
  Si ese correo ya existía, la contraseña se conserva.
- **Administrador demo:** cuenta de evaluación, rol `supervisor` únicamente
  en `DEMO-PUBLIC`. Si ya tenía asignaciones a otras plantas, se eliminan.
  Puede crear y gestionar registros ficticios de su planta según las
  funciones y permisos implementados en la aplicación.

La aplicación **no tiene rutas de gestión de usuarios**, por lo que un
administrador demo no puede crear, eliminar ni promover usuarios desde el web.
El modelo `User` protege la cuenta propietaria contra eliminación y
desprotección mediante Eloquent; no es una protección frente a alguien que
tenga acceso de administrador a la base de datos, servidor o código.

No se publican contraseñas en GitHub ni dentro del ZIP. No se requiere cambiar
el `.env`. Para elegir credenciales, usa el comando interactivo desde SSH,
**después de crear un respaldo verificado y ejecutar la nueva migración**:

```bash
cd /home/u641948971/domains/devalejandre.com/apps/lpa
/opt/alt/php84/usr/bin/php artisan migrate:status
/opt/alt/php84/usr/bin/php artisan migrate --force
/opt/alt/php84/usr/bin/php artisan optimize:clear
/opt/alt/php84/usr/bin/php artisan portfolio:provision-access
```

El comando pregunta correos y contraseñas (16 caracteres como mínimo),
pide confirmación antes de elevar una cuenta ya existente y antes de
restringir un administrador ya existente, y confirma antes de aplicar cambios.

**Requisito:** debe existir la planta ficticia `DEMO-PUBLIC` del demo público.
Si no existe, comprueba primero si ya se ha ejecutado el seeder
`LpaPublicDemoSeeder` en esta instalación. Ejecútalo solo tras verificar que
contiene exclusivamente datos de ejemplo y después del respaldo.

**Limitaciones importantes:**
- El administrador demo comparte registros de la planta de ejemplo con
  quienes utilicen esa misma cuenta. Los visitantes pueden alterar los
  datos de ejemplo y las cifras públicas. No hay aislamiento por visitante
  ni restablecimiento automático en esta versión.
- El propietario recibe membresías en las plantas **existentes**; si se
  crea una planta nueva más adelante, habrá que asignarle acceso.
- Para mostrar el login y un flujo de usuario/contraseña completo, publica
  solo las credenciales de la cuenta demo, nunca las del propietario.
- Como las aplicaciones tienen bases de datos separadas, se ejecuta el
  comando una vez en cada instalación y se mantienen ambas cuentas en
  sus respectivas bases. Sigue habiendo una sola instancia de cada sistema.
- Quien tenga credenciales de SSH o acceso SQL directo puede sobrepasar
  estas restricciones. Eso requiere proteger el hosting y sus respaldos.
