# Siguientes pasos

## Estado actual - 2026-06-22

El entorno local ya esta funcionando con Laragon:

- PHP: `C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe`
- Composer: `C:\laragon\bin\composer\composer.phar`
- MySQL: `sistema_finanzas`
- URL local: `http://127.0.0.1:8000/finanzas`
- Usuario inicial: `test@example.com`
- Password inicial: `password`

Composer quedo configurado con plataforma PHP `8.3.30` para evitar resolver dependencias que pidan PHP 8.4 antes de subir a HostGator.

## Para probar localmente

Si el servidor local no esta prendido:

```bash
cd D:\Github\Finanzas\sistema-finanzas
C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe artisan serve --host=127.0.0.1 --port=8000
```

Para volver a correr pruebas:

```bash
cd D:\Github\Finanzas\sistema-finanzas
C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe artisan test
```

## Para HostGator sin SSH

HostGator tiene PHP 8.3 y Percona/MySQL 5.7, suficiente para Laravel.

Como no hay terminal:

- Subir el proyecto con `vendor` ya generado.
- Usar los assets existentes de Velok en `public/build`.
- Crear base de datos y usuario desde cPanel.
- Configurar `.env` con los datos reales de MySQL.
- Ejecutar migraciones por instalador web protegido o importar SQL por phpMyAdmin.
- Apuntar el dominio o subdominio a la carpeta `public`.

## Siguiente bloque de desarrollo

- Instalador web seguro para correr migraciones sin SSH.
- Importador inicial desde los Excel.
- Edicion de movimientos, pagos y catalogos.
- Reportes por semana, mes, categoria, cuenta, persona e inmueble San Juan.
- Exportacion a Excel/PDF.
- Respaldo automatico de base de datos.
