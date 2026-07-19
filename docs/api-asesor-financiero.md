# Asesor financiero de solo lectura

Esta integración permite que una sesión local de Codex consulte un snapshot
financiero del propietario y explique riesgos, tendencias y límites de gasto.
No permite crear, editar, pagar ni eliminar registros.

Esta guía cubre la instalación y operación. Todo agente que vaya a interpretar
los datos debe leer primero el manual canónico:

- [`docs/manual-asesor-financiero-agentes.md`](manual-asesor-financiero-agentes.md)

## Contenido del snapshot

`GET /api/finance/advisor/snapshot` devuelve:

- saldos conciliados de cuentas activas;
- resumen del mes y obligaciones pendientes;
- proyección conservadora de los próximos 45 días;
- próximo ingreso, colchón, dinero diario y plan de decisiones;
- sobre semanal y disponible efectivo por categoría;
- tendencias por categoría contra los tres meses anteriores;
- créditos, saldo pendiente y próxima mensualidad;
- hasta 60 movimientos recientes con descripción;
- señales priorizadas para flujo, vencimientos y gasto acelerado.

La API siempre consulta al usuario definido por `FINANCE_OWNER_EMAIL`. No acepta
`user_id`, instrucciones, comandos ni operaciones de escritura.

## Configuración única en producción

Genera un secreto aleatorio nuevo de al menos 32 caracteres. No reutilices los
tokens de cPanel o despliegue.

Agrega al `.env` de producción:

```dotenv
FINANCE_ADVISOR_API_TOKEN=SECRETO_ALEATORIO_INDEPENDIENTE
FINANCE_ADVISOR_HISTORY_DAYS=90
FINANCE_ADVISOR_HORIZON_DAYS=45
FINANCE_ADVISOR_TRANSACTION_LIMIT=60
FINANCE_ADVISOR_INCLUDE_DESCRIPTIONS=true
```

Después limpia la caché desde **Seguridad → Mantenimiento**. La ruta está
limitada a 12 solicitudes por minuto y responde con `Cache-Control: no-store`.

Para ocultar las descripciones de los movimientos recientes sin desactivar el
asesor:

```dotenv
FINANCE_ADVISOR_INCLUDE_DESCRIPTIONS=false
```

## Configuración local cifrada

El cliente local usa Windows DPAPI: el token queda cifrado para el usuario de
Windows actual y fuera del repositorio, en:

```text
%LOCALAPPDATA%\FinanzasAdvisor\credential.json
```

Desde PowerShell, en la raíz del proyecto:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\tools\finance-advisor.ps1 -Action setup
```

Pega `FINANCE_ADVISOR_API_TOKEN` cuando aparezca el prompt oculto. No lo pegues
en el comando ni en una conversación.

Para comprobar la integración:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\tools\finance-advisor.ps1 -Action snapshot
```

El comando imprime únicamente el JSON financiero; nunca imprime el token.
Una sesión local de Codex puede ejecutar el mismo comando para revisar las
finanzas cuando el usuario lo solicite.

El agente debe mantener el JSON en memoria, mostrar únicamente los importes
necesarios y no guardarlo en el repositorio, archivos temporales o servicios
externos. El protocolo detallado, fórmulas, señales y formato de respuesta están
en `docs/manual-asesor-financiero-agentes.md`.

## Ejemplos de solicitudes a Codex

- `Revisa mis finanzas y dime en qué debo dejar de gastar esta semana.`
- `¿Puedo gastar $2,000 en ropa sin comprometer mis pagos?`
- `Compara mis categorías contra los últimos tres meses.`
- `Dime qué debo pagar antes del siguiente ingreso.`
- `Detecta movimientos raros o categorías aceleradas.`

Los consejos deben distinguir dinero real de ingresos proyectados, explicar los
importes utilizados y priorizar pagos, créditos y colchón antes de gasto
discrecional.

## Rotación y revocación

Para revocar acceso local:

1. cambia `FINANCE_ADVISOR_API_TOKEN` en producción;
2. limpia la caché;
3. ejecuta nuevamente el cliente con `-Action setup`.

Eliminar el archivo cifrado local también impide que futuras sesiones consulten
el snapshot desde esa cuenta de Windows.
