# Sistema de Finanzas

App Laravel de finanzas personales. Se despliega en **HostGator** (hosting
compartido, **sin SSH/terminal**). El despliegue se dispara **desde la propia
app**: Seguridad → **Despliegue desde GitHub**, que hace backup, `git pull`,
limpieza de caché y migraciones en un solo paso.

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

1. `git push` a `main` (GitHub). La rama está fija en el `.env` del servidor; no
   se despliega desde otra.
2. En la app → Seguridad → **Despliegue desde GitHub** → marcar la casilla de
   confirmación → **"Actualizar producción"**. Ese botón hace, en orden:
   backup → *Update from Remote* → `optimize:clear` → `migrate --force`.
   Si el backup falla, **no toca el código**.
3. En esa misma pantalla, confirmar que "Commit instalado" y "Versión local"
   coinciden con lo que acabas de subir.

**No hace falta entrar a cPanel ni limpiar caché aparte**: ese botón ya lo hace.
cPanel → Git Version Control queda solo como respaldo manual si la pantalla
falla; en ese caso sí hay que limpiar caché después en Seguridad →
Mantenimiento, o la versión y las vistas compiladas no se refrescan.

> Solo cambios de assets del tema (`public/build/`, vía `npm run build`) o de
> dependencias (`vendor/`, vía `composer install`) requieren reconstruir/subir
> esos artefactos. Cambios en `.php`/`.blade.php`/`config` solo necesitan el
> despliegue normal.

## Pruebas

```
php artisan test --filter=Finance
```
Usan SQLite (`:memory:`); no requieren la base MySQL local.

## Asesor financiero para agentes

La app expone un snapshot privado de solo lectura para revisiones financieras.
Antes de consultarlo o dar recomendaciones, leer completo:

- `docs/manual-asesor-financiero-agentes.md` — procedimiento canónico para
  agentes, semántica del JSON, seguridad, fórmulas y formato de respuesta.
- `docs/api-asesor-financiero.md` — instalación, configuración y rotación.

Reglas mínimas:

- usar `tools/finance-advisor.ps1 -Action snapshot`;
- nunca abrir `credential.json` ni pedir/mostrar el token;
- no guardar o publicar el JSON;
- distinguir ingresos reales de esperados;
- reportar posibles duplicados o fechas dudosas;
- explicar consejos con importes y fechas;
- no realizar escrituras como parte de una revisión.
