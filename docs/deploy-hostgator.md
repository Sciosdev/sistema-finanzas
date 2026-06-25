# Deploy empaquetado para HostGator

Esta guía describe cómo generar y subir un paquete de producción al hosting
compartido de HostGator (`https://finanzas.xaanal.com`), que **no tiene terminal
SSH** y por lo tanto no puede ejecutar `composer install` ni `npm run build`.

## Por qué un paquete empaquetado

- `vendor/` no se instala en el servidor (no hay `composer install`): debe viajar dentro del ZIP.
- `public/build/` está en `.gitignore` y no se compila en el servidor: debe viajar dentro del ZIP.

Por eso el backup normal **no** sirve para desplegar (excluye `vendor/` y `public/build/`).
El comando `finance:build-release` arma un ZIP que **sí** incluye ambos.

## 1. Preparar el build en local

Desde tu máquina (Laragon/local), antes de empaquetar:

```bash
composer install --no-dev --optimize-autoloader
npm install
npm run build
```

Esto deja `vendor/autoload.php` y `public/build/manifest.json` actualizados.

> El comando **no** ejecuta estos pasos por ti: solo valida que sus resultados
> existan. Si falta `vendor/` o `public/build/`, el comando aborta con un mensaje claro.

## 2. Generar el ZIP

```bash
php artisan finance:build-release
```

Opciones:

- `--output=RUTA` — carpeta destino del ZIP. Por defecto: `storage/app/releases/`.

El archivo se llama:

```
release-finanzas-hostgator-YYYYMMDD-HHMMSS.zip
```

y queda, por defecto, en `storage/app/releases/`.

## 3. Qué incluye y qué excluye el ZIP

**Incluye:** `app/`, `bootstrap/` (sin caché compilada), `config/`, `database/`
(migraciones, factories, seeders), `public/` (incluido `public/build/`),
`resources/`, `routes/`, `vendor/`, `composer.json`, `composer.lock`, `artisan`,
`.env.example` y `DEPLOY_HOSTGATOR.md`.

**Excluye:** el `.env` real, `.git/`, `node_modules/`, todo `storage/`, la caché de
`bootstrap/cache/`, backups (`finance-backups/`), dumps `.sql` y bases `.sqlite`,
y archivos `.tmp`/`.temp`. No viajan tokens ni credenciales.

## 4. Subir y activar en HostGator

Los pasos detallados van dentro del ZIP en `DEPLOY_HOSTGATOR.md`. Resumen:

1. **Backup primero** desde la app (Seguridad → Backup completo).
2. Subir el ZIP por **cPanel → Administrador de archivos** y extraerlo en la carpeta del sitio.
3. **No sobrescribir** el `.env` de producción.
4. Verificar permisos de escritura de `storage/` y `bootstrap/cache/`.
5. Si hay migraciones nuevas, ejecutar por **Cron** de una sola vez:
   `php /home/USUARIO/ruta/artisan migrate --force`
6. Limpiar caché por **Cron**: `php /home/USUARIO/ruta/artisan optimize:clear`
7. Abrir `/finanzas/diagnostico` y revisar todo en verde.
8. Ante un error 500 con el diagnóstico caído, usar `/_health/triage?key=TOKEN`.
9. Confirmar que `public/build/manifest.json` exista en el servidor.

## 5. Notas

- El comando está pensado para correr **en local**, no en el servidor.
- No requiere `npm run build` como parte del empaquetado: solo valida que el build
  ya exista. Si cambiaste assets, recompila **antes** de empaquetar (paso 1).
- El ZIP se guarda bajo `storage/app/`, que está fuera de control de versiones, así
  que no se sube por Git accidentalmente.
