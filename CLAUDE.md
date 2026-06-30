# Sistema de Finanzas

App Laravel de finanzas personales. Se despliega en **HostGator** (hosting
compartido, **sin SSH/terminal**) mediante **Git Version Control de cPanel**
(`git pull` desde GitHub) y acciones de mantenimiento dentro de la propia app
(Seguridad → Mantenimiento).

## Versionado — IMPORTANTE para agentes

Hay una **versión visible** de la app que sirve para confirmar a simple vista que
un despliegue llegó al servidor. Vive en un solo lugar:

- **Fuente única:** `config/finance.php` → clave `'version'` (formato semver `MAJOR.MINOR.PATCH`).
- Se muestra en el **menú lateral** (`resources/views/layouts/partials/main-nav.blade.php`)
  y en el **footer** (`resources/views/layouts/partials/footer.blade.php`), ambos
  vía `config('finance.version')`.

**Regla: en CUALQUIER cambio que se vaya a desplegar, SUBE la versión** en
`config/finance.php` antes de hacer commit. Así el usuario verifica el deploy
comparando el número en pantalla.

Cómo incrementar:
- **PATCH** (`1.4.1` → `1.4.2`): arreglos de bugs / cambios menores.
- **MINOR** (`1.4.x` → `1.5.0`): funcionalidad nueva sin romper nada.
- **MAJOR** (`1.x` → `2.0.0`): cambios grandes o que rompen compatibilidad.

Nunca bajes el número (no regresar a una versión ya desplegada).

## Despliegue (recordatorio)

1. `git push` a `main` (GitHub).
2. En cPanel → Git Version Control → "Update from Remote" (jala el commit al servidor).
3. En la app → Seguridad → Mantenimiento → **"Limpiar caché"** (`optimize:clear`).
   Esto es **obligatorio** para que se refresquen vistas compiladas Y la config
   (la versión vive en config, así que sin limpiar caché no se actualiza).
4. Confirmar que el número de versión en pantalla coincide con `config/finance.php`.

> Solo cambios de assets del tema (`public/build/`, vía `npm run build`) o de
> dependencias (`vendor/`, vía `composer install`) requieren reconstruir/subir
> esos artefactos. Cambios en `.php`/`.blade.php`/`config` solo necesitan el
> pull + limpiar caché.

## Pruebas

```
php artisan test --filter=Finance
```
Usan SQLite (`:memory:`); no requieren la base MySQL local.
