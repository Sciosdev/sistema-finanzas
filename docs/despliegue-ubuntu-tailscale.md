# Despliegue en Ubuntu sin IP Fija

Esta guía deja dos caminos prácticos para correr el sistema en una computadora con Ubuntu sin IP estática. No sustituye backups: antes de migrar, genera backup de BD y backup completo desde Seguridad.

## Opción A: Tailscale

Recomendado para uso personal y privado desde tus propios dispositivos.

### Ventajas

- No necesitas IP fija.
- El sistema queda dentro de tu red privada Tailscale.
- Menor exposición pública.
- Puedes entrar desde teléfono/laptop si ambos están en tu Tailnet.

### Limitaciones

- Cada dispositivo que entre debe tener Tailscale o acceso autorizado.
- Para PWA/notificaciones reales conviene HTTPS con certificado válido.
- No es ideal si muchas personas externas necesitan acceso.

### Paquetes Base

```bash
sudo apt update
sudo apt install nginx mysql-server php8.3 php8.3-cli php8.3-fpm php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip php8.3-bcmath unzip git
```

Instala Composer según la guía oficial de Composer.

### Base de Datos

```bash
sudo mysql
CREATE DATABASE finanzas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'finanzas_user'@'localhost' IDENTIFIED BY 'CAMBIA_ESTA_CLAVE';
GRANT ALL PRIVILEGES ON finanzas.* TO 'finanzas_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### Variables `.env`

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://finanzas.tu-tailnet.ts.net
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=finanzas
DB_USERNAME=finanzas_user
DB_PASSWORD=CAMBIA_ESTA_CLAVE
FINANCE_EXTERNAL_BACKUP_PATH=/home/axel/backups-finanzas
```

No subas `.env` a GitHub.

### Permisos y Laravel

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R ug+rw storage bootstrap/cache
```

### Scheduler/Cron

```bash
crontab -e
* * * * * cd /var/www/finanzas && php artisan schedule:run >> /dev/null 2>&1
```

### Nginx Básico

```nginx
server {
    listen 80;
    server_name finanzas.tu-tailnet.ts.net;
    root /var/www/finanzas/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }

    location ~ /\. {
        deny all;
    }
}
```

### Tailscale

```bash
curl -fsSL https://tailscale.com/install.sh | sh
sudo tailscale up
tailscale status
```

Para HTTPS privado, evalúa:

```bash
sudo tailscale cert finanzas.tu-tailnet.ts.net
```

## Opción B: Cloudflare Tunnel

Útil si quieres entrar con un dominio normal sin abrir puertos ni tener IP fija.

### Ventajas

- No requiere IP fija.
- Puede usar HTTPS público con Cloudflare.
- No necesitas abrir puertos en el módem.

### Riesgos

- El sistema queda accesible por internet si no restringes bien.
- Debe tener login fuerte.
- Conviene usar Cloudflare Access o reglas de acceso por correo/dispositivo.

### Pasos Generales

1. Compra o usa un dominio en Cloudflare.
2. Instala `cloudflared` en Ubuntu.
3. Crea un túnel hacia `http://localhost`.
4. Apunta un subdominio, por ejemplo `finanzas.tudominio.com`.
5. Activa HTTPS.
6. Protege el acceso con Cloudflare Access si el sistema es personal.

Ejemplo conceptual:

```bash
cloudflared tunnel login
cloudflared tunnel create finanzas
cloudflared tunnel route dns finanzas finanzas.tudominio.com
cloudflared tunnel run finanzas
```

## Advertencias Importantes

- No expongas el sistema públicamente sin login fuerte.
- No subas `.env`, dumps ni backups a repositorios.
- Usa HTTPS.
- Haz backup antes de migrar.
- En producción usa `APP_ENV=production` y `APP_DEBUG=false`.
- Si es solo para ti, Tailscale suele ser la opción más segura y tranquila.
- Revisa permisos de `storage` y `bootstrap/cache`.
- Prueba restaurar la BD en una copia antes de depender de un solo respaldo.
