# Despliegue desde la app y API

Este módulo reemplaza el clic manual en **cPanel → Git Version Control →
Update from Remote**. Usa la API oficial UAPI de cPanel y nunca ejecuta
comandos proporcionados por el navegador o por un agente.

## Configuración única en producción

1. En cPanel abre **Security → Manage API Tokens** y crea un token exclusivo
   para el despliegue. Guárdalo en ese momento: cPanel no vuelve a mostrarlo.
2. Identifica la URL/hostname de cPanel, el usuario de la cuenta y la ruta
   absoluta que aparece como **Repository Path** en Git Version Control.
3. Genera un secreto aleatorio independiente de al menos 32 caracteres para
   autenticar a los agentes.
4. Agrega al `.env` de producción:

```dotenv
FINANCE_CPANEL_URL=https://HOSTNAME-DE-CPANEL:2083
FINANCE_CPANEL_USERNAME=USUARIO_CPANEL
FINANCE_CPANEL_API_TOKEN=TOKEN_GENERADO_EN_CPANEL
FINANCE_CPANEL_REPOSITORY_ROOT=/home/USUARIO/RUTA_DEL_REPOSITORIO
FINANCE_CPANEL_BRANCH=main

FINANCE_DEPLOY_API_TOKEN=SECRETO_ALEATORIO_DE_64_CARACTERES
```

5. En **Seguridad → Mantenimiento**, limpia la caché una última vez. El panel
   mostrará `cPanel: configurado` y `API agentes: activa`.

Los secretos solo viven en `.env`. Nunca deben enviarse a GitHub, guardarse en
la base de datos ni escribirse en archivos de documentación.

## Botón dentro de la app

El usuario definido por `FINANCE_OWNER_EMAIL` puede entrar a **Seguridad →
Mantenimiento → Despliegue desde GitHub** y pulsar **Actualizar producción**.

El flujo es fijo:

1. Verificar que cPanel reconoce el repositorio configurado.
2. Crear un paquete de backup antes de cambiar código.
3. Ejecutar `VersionControl::update` para `origin/main`.
4. Limpiar las cachés de Laravel.
5. Aplicar únicamente las migraciones pendientes con `migrate --force`.
6. Consultar de nuevo el commit instalado y registrar el resultado.

Si el backup falla, el código no se actualiza. Dos despliegues no pueden
ejecutarse simultáneamente.

## API para agentes

Todas las llamadas usan HTTPS y:

```http
Authorization: Bearer FINANCE_DEPLOY_API_TOKEN
Accept: application/json
```

### Consultar estado

```http
GET /api/finance/deployment/status
```

Para omitir el caché de estado de 30 segundos:

```http
GET /api/finance/deployment/status?refresh=1
```

La respuesta muestra configuración, versión local, rama y commit instalado,
pero nunca devuelve ninguno de los dos tokens.

### Desplegar

```http
POST /api/finance/deployment/deploy
Content-Type: application/json
Idempotency-Key: deploy-IDENTIFICADOR-UNICO

{"confirm": true}
```

`Idempotency-Key` debe tener entre 8 y 100 caracteres y usar letras, números,
punto, guion o guion bajo. Si un agente repite la misma solicitud por un
timeout de red, obtiene el resultado anterior en vez de iniciar otro
despliegue. Para reintentar conscientemente después de una falla debe usar una
clave nueva.

Estados HTTP principales:

- `200`: despliegue completado o resultado idempotente recuperado.
- `401`: token de agente incorrecto.
- `409`: ya existe un despliegue en curso.
- `422`: falta confirmación o `Idempotency-Key` válido.
- `502`: cPanel o un paso posterior falló.
- `503`: falta configurar cPanel o el token de agentes.

La consulta de estado está limitada a 30 solicitudes por minuto y el despliegue
a tres. La API no acepta `command`, `branch`, `repository_root`, URL ni ninguna
otra instrucción operativa.

## Rotación y revocación

- Para revocar acceso a los agentes, cambia `FINANCE_DEPLOY_API_TOKEN` y limpia
  la caché.
- Para revocar el acceso de la app a cPanel, elimina el token desde **Manage API
  Tokens**, crea otro, actualiza `FINANCE_CPANEL_API_TOKEN` y limpia la caché.
- Conviene asignar expiración al token de cPanel y rotarlo periódicamente.
