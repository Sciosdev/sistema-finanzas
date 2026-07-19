# Manual operativo del asesor financiero para agentes

Esta es la guía canónica para que Codex u otro agente autorizado pueda leer,
auditar e interpretar las finanzas del propietario sin entrar a cPanel, sin
conocer la contraseña de la aplicación y sin modificar datos.

La guía de instalación y operación humana está en
[`docs/api-asesor-financiero.md`](api-asesor-financiero.md). Este documento se
concentra en el trabajo del agente: cómo consultar, qué significa cada dato,
cómo detectar inconsistencias y cómo convertir el snapshot en recomendaciones
prudentes y verificables.

## 1. Reglas no negociables

Todo agente debe cumplir lo siguiente:

1. La integración es **exclusivamente de lectura**.
2. Solo se consulta al usuario definido por `FINANCE_OWNER_EMAIL`.
3. Nunca pedir, mostrar, copiar ni guardar el token en una conversación,
   captura, issue, commit, log o comando visible.
4. Nunca reutilizar los tokens de cPanel o de despliegue.
5. Ejecutar una consulta nueva antes de analizar; los saldos cambian.
6. Indicar la fecha y hora de generación del snapshot.
7. Separar movimientos reales de ingresos y egresos proyectados.
8. No presentar ingresos esperados como dinero disponible.
9. Explicar cada recomendación importante con importes, fechas y categorías.
10. Reportar datos dudosos, duplicados o inconsistentes antes de emitir una
    recomendación que dependa de ellos.
11. No avergonzar al usuario ni convertir una heurística en una certeza.
12. Una solicitud de análisis no autoriza pagos, ediciones, despliegues ni
    ninguna otra escritura.

## 2. Arquitectura y frontera de confianza

```text
MySQL de producción
        |
        | consultas Eloquent de solo lectura
        v
FinanceAdvisorSnapshotService
        |
        | JSON sin identificadores internos
        v
GET /api/finance/advisor/snapshot
        |
        | HTTPS + Bearer token exclusivo
        v
tools/finance-advisor.ps1
        |
        | descifra con Windows DPAPI solo en memoria
        v
Agente local autorizado
```

Componentes principales:

- Ruta: `routes/api.php`.
- Middleware del token:
  `app/Http/Middleware/EnsureFinanceAdvisorApiToken.php`.
- Controlador: `app/Http/Controllers/Finance/FinanceAdvisorApiController.php`.
- Constructor del snapshot:
  `app/Services/Finance/FinanceAdvisorSnapshotService.php`.
- Cliente local: `tools/finance-advisor.ps1`.
- Configuración: `config/finance.php` → `advisor`.
- Pruebas: `tests/Feature/Finance/FinanceAdvisorApiTest.php`.

### Garantías implementadas

- El endpoint solo expone `GET`/`HEAD`; `POST` responde `405`.
- No existe parámetro para elegir usuario. Un `user_id` enviado en la URL se
  ignora.
- El propietario se resuelve mediante `FINANCE_OWNER_EMAIL`.
- El token debe tener al menos 32 caracteres.
- La comparación del token usa `hash_equals`.
- El límite es de 12 solicitudes por minuto.
- Las respuestas exitosas usan `Cache-Control: no-store, private`.
- Los campos `id` y `*_id` se eliminan recursivamente del snapshot.
- El token, el correo del propietario y las contraseñas no forman parte de la
  respuesta.
- El acceso se registra únicamente con metadatos operativos; los errores no
  escriben importes, descripciones ni el mensaje interno de la excepción.
- El cliente local guarda el token cifrado con Windows DPAPI y refuerza los
  permisos del archivo con `icacls`.

Estas garantías reducen el riesgo, pero el JSON sigue conteniendo información
financiera privada. Debe tratarse como información sensible.

## 3. Puesta en marcha

### 3.1 Requisitos en producción

El `.env` de producción debe contener:

```dotenv
FINANCE_OWNER_EMAIL=CORREO_DEL_PROPIETARIO
FINANCE_ADVISOR_API_TOKEN=SECRETO_ALEATORIO_INDEPENDIENTE
FINANCE_ADVISOR_HISTORY_DAYS=90
FINANCE_ADVISOR_HORIZON_DAYS=45
FINANCE_ADVISOR_TRANSACTION_LIMIT=60
FINANCE_ADVISOR_INCLUDE_DESCRIPTIONS=true
```

Límites efectivos:

| Variable | Valor predeterminado | Mínimo | Máximo |
| --- | ---: | ---: | ---: |
| `FINANCE_ADVISOR_HISTORY_DAYS` | 90 | 30 | 365 |
| `FINANCE_ADVISOR_HORIZON_DAYS` | 45 | 7 | 60 |
| `FINANCE_ADVISOR_TRANSACTION_LIMIT` | 60 | 0 | 100 |

Después de cambiar el `.env` se debe limpiar la caché de Laravel. En
**Seguridad → Mantenimiento → Asesor financiero local** deben aparecer:

- `API lectura: activa`;
- el endpoint `/api/finance/advisor/snapshot`;
- las ventanas configuradas;
- el estado de inclusión de descripciones.

La interfaz nunca muestra el token.

### 3.2 Configuración local soportada en Windows

Desde la raíz del repositorio:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\tools\finance-advisor.ps1 -Action setup
```

El prompt del token es oculto. El archivo resultante vive fuera del repositorio:

```text
%LOCALAPPDATA%\FinanzasAdvisor\credential.json
```

El archivo contiene la URL, la fecha de creación y el token cifrado. El token
solo puede descifrarse con el perfil de Windows que lo protegió. No debe copiarse
a otro usuario, equipo o repositorio.

Comandos auxiliares:

```powershell
# Mostrar la ubicación de la credencial, no su contenido
powershell -NoProfile -ExecutionPolicy Bypass -File .\tools\finance-advisor.ps1 -Action config-path

# Consultar el snapshot
powershell -NoProfile -ExecutionPolicy Bypass -File .\tools\finance-advisor.ps1 -Action snapshot
```

### 3.3 Otros sistemas operativos

El contrato HTTPS puede ser consumido desde Linux o macOS, pero el cliente
incluido usa DPAPI y por tanto es específico de Windows. Un cliente alternativo
debe:

- obtener el token desde el llavero del sistema o un gestor de secretos;
- enviar `Authorization: Bearer ...` únicamente por HTTPS;
- no incluir el secreto como argumento literal del proceso;
- no guardar el JSON en archivos persistentes por defecto;
- borrar el secreto de memoria/entorno al terminar;
- respetar el límite de solicitudes.

No se considera aceptable guardar el token en un script, alias, `.env` local del
repositorio, archivo Markdown o historial del shell.

## 4. Consulta segura por un agente

El camino recomendado es ejecutar el cliente incluido. El agente no necesita
leer `credential.json` ni construir el encabezado de autorización.

```powershell
$raw = & powershell -NoProfile -ExecutionPolicy Bypass `
    -File .\tools\finance-advisor.ps1 `
    -Action snapshot

$response = ($raw -join "`n") | ConvertFrom-Json
```

Reglas durante la consulta:

- mantener el JSON en memoria;
- no redirigirlo a un archivo dentro del repositorio;
- no imprimir el snapshot completo en la respuesta al usuario;
- extraer únicamente los datos necesarios;
- no repetir consultas si el snapshot actual es suficiente;
- si la salida de una herramienta se trunca, volver a consultar una selección
  compacta, no revelar el token ni desactivar controles.

Ejemplo de extracción compacta:

```powershell
$snapshot = $response.snapshot

[ordered]@{
    generated_at = $snapshot.generated_at
    accounts = $snapshot.accounts
    current_month = $snapshot.current_month
    projection_summary = $snapshot.cash_flow_projection.summary
    decision_headline = $snapshot.decision_plan.headline
    decision_money_plan = $snapshot.decision_plan.money_plan
    current_week = $snapshot.weekly_envelope.current_week
    trends = @($snapshot.spending_trends | Select-Object -First 15)
    credits = $snapshot.credits
    signals = $snapshot.advisory_signals
} | ConvertTo-Json -Depth 30
```

El agente debe validar antes de interpretar:

```text
ok = true
snapshot.read_only = true
snapshot.scope.owner_only = true
snapshot.schema_version = versión soportada
snapshot.generated_at = reciente
```

Al presentar importes, formatear siempre a dos decimales. Algunas versiones de
PowerShell convierten números JSON a punto flotante y pueden imprimir artefactos
como `1471.490000000000009`; eso no representa precisión financiera adicional.

## 5. Contrato HTTP

Solicitud:

```http
GET /api/finance/advisor/snapshot
Authorization: Bearer FINANCE_ADVISOR_API_TOKEN
Accept: application/json
```

Respuesta exitosa:

```json
{
  "ok": true,
  "snapshot": {
    "schema_version": "1.0",
    "generated_at": "FECHA_ISO_8601",
    "read_only": true,
    "scope": {
      "owner_only": true
    }
  }
}
```

Estados relevantes:

| HTTP | Significado | Acción |
| ---: | --- | --- |
| `200` | Snapshot construido | Validar metadatos y analizar |
| `401` | Token incorrecto | Repetir `setup` con el token vigente |
| `405` | Método no permitido | Usar exclusivamente `GET` |
| `429` | Demasiadas consultas | Esperar; no crear un bucle |
| `500` | Falló la construcción | Revisar logs sin exponer datos |
| `503` | Token/propietario no configurado o propietario inexistente | Revisar `.env` y limpiar caché |

## 6. Esquema del snapshot

### 6.1 Metadatos

| Campo | Significado |
| --- | --- |
| `schema_version` | Versión del contrato JSON, no de la app |
| `generated_at` | Momento exacto de la lectura |
| `application_version` | Versión visible del sistema |
| `currency` | Actualmente `MXN` |
| `timezone` | Zona horaria usada para fechas |
| `read_only` | Debe ser `true` |
| `scope` | Ventanas, límites y garantía de propietario único |
| `interpretation_notes` | Reglas mínimas que el agente debe obedecer |

Si `generated_at` es anterior a una captura o pago reciente mencionado por el
usuario, el agente debe obtener otro snapshot.

### 6.2 `accounts`

- `total_balance`: suma del saldo esperado de las cuentas activas.
- `items`: nombre, tipo, saldo, límite de crédito y estado del ciclo.

El saldo no viene de una API bancaria en tiempo real. Se calcula desde el último
corte conciliado —o el saldo inicial si no hay corte— más los movimientos
registrados. Por eso:

- revisar `cash_flow_projection.baseline_cut_date`;
- tomar en serio `no_baseline_cut` y `stale_baseline`;
- no interpretar el límite de una tarjeta como efectivo disponible;
- no sumar nuevamente los saldos individuales a `total_balance`.

### 6.3 `current_month`

Campos centrales:

| Campo | Interpretación correcta |
| --- | --- |
| `income` | Ingresos reales registrados |
| `yields` | Rendimientos reales registrados |
| `total_income` | `income + yields` |
| `expenses` | Egresos reales registrados |
| `net_cash_flow` | `total_income - expenses` antes de obligaciones pendientes |
| `pending_payments` | Pagos y mensualidades aún pendientes del mes |
| `pending_expected_income` | Ingresos esperados aún no recibidos |
| `projected_total_income` | Reales + esperados; no es efectivo disponible |
| `unknown_expenses` | Gastos marcados como desconocidos |
| `obligation_totals` | Totales por estado/origen del resumen mensual |
| `next_payments` | Próximas obligaciones, máximo 20 |
| `next_expected_incomes` | Próximos cobros, máximo 20 |
| `important_expense_concepts` | Coincidencias temáticas por palabras clave |
| `spending_opportunities` | Simulación simple de recorte del 10% |
| `credit_line` | Uso de líneas de crédito por cuenta |

`net_cash_flow` no significa “dinero libre”. Para hablar de dinero libre se
deben restar pagos pendientes, mensualidades, gasto básico y colchón.

Los conceptos importantes son etiquetas por búsqueda de texto. Un movimiento
puede coincidir con más de un concepto —por ejemplo, “JAPAM San Juan” puede
contar en Servicios y San Juan—. Por tanto:

- no sumar los conceptos para reconstruir el gasto total;
- usarlos como focos de investigación;
- usar `spending_trends` para totales no solapados por categoría.

### 6.4 `cash_flow_projection`

La proyección predeterminada abarca 45 días, configurable entre 7 y 60.

Tiene dos pistas:

```text
saldo seguro del día =
    saldo seguro anterior
    - pagos
    - mensualidades
    - cargos de tarjeta

saldo proyectado del día =
    saldo proyectado anterior
    + ingresos esperados
    - pagos
    - mensualidades
    - cargos de tarjeta
```

- **Seguro:** ignora todos los ingresos futuros.
- **Proyectado:** incluye ingresos esperados asignados a una fecha.

El escenario seguro es una prueba de estrés, no una predicción de que todos los
ingresos fallarán. El escenario proyectado tampoco es garantía: depende de que
los ingresos se cobren en las fechas registradas.

Niveles exactos de riesgo:

| Riesgo | Regla |
| --- | --- |
| `critical` | `closing_projected < 0` |
| `high` | `closing_projected < buffer` |
| `medium` | el proyectado cubre el buffer, pero `closing_safe < buffer` |
| `ok` | ambos escenarios respetan el buffer |

Campos de resumen:

- `min_safe_balance` / `min_safe_date`;
- `min_projected_balance` / `min_projected_date`;
- `first_risky_date`;
- `max_risk`;
- totales de ingresos, pagos, mensualidades y cargos;
- ingresos esperados vencidos;
- pagos vencidos detectados dinámicamente;
- saldos al final del horizonte.

`event_days` omite días sin eventos y sin riesgo. No debe interpretarse como un
calendario completo.

Advertencias conocidas:

| Código | Interpretación |
| --- | --- |
| `no_baseline_cut` | No existe corte que ancle el saldo |
| `stale_baseline` | El último corte tiene más de siete días |
| `next_month_flow_empty` | No hay pagos planeados del siguiente mes |

### 6.5 `decision_plan`

El plan de decisiones usa actualmente un horizonte de **30 días**, aunque
`cash_flow_projection` pueda usar 45. Por eso sus reservas o faltantes no tienen
que ser idénticos al mínimo de la proyección.

Bloques:

- `headline`: diagnóstico rápido y nivel.
- `buffer`: gasto básico histórico y colchones recomendados.
- `current_window`: desde hoy hasta el día anterior al siguiente ingreso.
- `after_next_income_window`: siguiente tramo, si existe.
- `money_plan`: reservas, dinero para vivir, ahorro posible y faltante.
- `savings_guidance`: si conviene ahorrar ahora o estabilizar primero.
- `credit_payoff_strategy`: obligaciones por cuenta y acciones sugeridas.
- `actions`: pagar hoy, reservar, esperar, revisar después del ingreso o
  conseguir dinero.
- `category_budget`: presupuesto por categoría para la ventana.
- `timeline_messages`: mensajes humanos del plan.
- `warnings`: advertencias del motor.

Fórmula principal de una ventana:

```text
disponible bruto =
    saldo inicial
    + ingresos dentro de la ventana
    - pagos dentro de la ventana
    - mensualidades dentro de la ventana
    - colchón
    - reserva de obligaciones posteriores dentro del horizonte

disponible para vivir = max(0, disponible bruto)
faltante = max(0, -disponible bruto)
```

El `money_plan.shortfall` es conservador: toma el máximo entre el faltante de la
ventana, la necesidad mínima de vida y la necesidad de efectivo de la
proyección. No debe compararse directamente con una sola fecha sin explicar
esta diferencia.

### 6.6 `weekly_envelope`

Reparte el dinero para vivir entre semanas y categorías con base en el patrón
del mes anterior.

- `week_cap`: tope de la semana.
- `spent_total`: gasto de la semana.
- `remaining_total`: remanente semanal.
- `tradeoff_active`: el tope total ya se consumió.
- `categories[].own_remaining`: remanente de la categoría.
- `categories[].effective_remaining`: remanente considerando el tope global.
- `pattern_advice`: rubros dominantes del mes anterior.
- `messages`: explicación del motor.

Un tope semanal de cero significa que no hay gasto discrecional disponible sin
romper pagos o colchón. No significa recomendar que el usuario deje de comer,
transportarse o cubrir una necesidad básica; significa que el plan necesita
recortes, ingresos adelantados o una decisión explícita sobre obligaciones.

### 6.7 `monthly_history`

Totales reales por mes dentro de la ventana histórica:

- ingresos;
- rendimientos;
- gastos;
- flujo neto.

El primer mes puede ser parcial si la ventana empieza a mitad del mes y el
último mes siempre está incompleto hasta el cierre. Evitar comparar un mes
completo con uno parcial sin aclararlo.

### 6.8 `spending_trends`

Agrupa egresos reales por categoría. Para cada categoría incluye:

- gasto actual y cantidad de movimientos;
- proyección lineal al cierre del mes;
- gasto del mes anterior hasta el mismo número de día;
- promedio de los tres meses completos anteriores;
- cambio porcentual;
- gasto dentro de toda la ventana histórica;
- clasificación discrecional.

Fórmulas:

```text
proyección del mes =
    gasto actual / días transcurridos * días del mes

cambio porcentual =
    (actual o proyectado - base) / base * 100
```

Advertencias:

- la proyección lineal puede exagerar un gasto único realizado al principio;
- una categoría nueva o renombrada puede tener promedio histórico cero;
- el promedio incluye meses sin gasto en esa categoría;
- `is_discretionary` usa palabras clave, no juicio humano;
- validar con movimientos recientes antes de decir “deja de gastar en X”.

### 6.9 `credits`

Por crédito:

- total original;
- total pagado;
- saldo pendiente;
- mensualidades pendientes;
- importe vencido;
- próxima fecha e importe;
- cuenta, categoría y estado.

El saldo pendiente se calcula como:

```text
total original
- pagos aplicados a mensualidades
- abonos libres
```

Una fecha nula, una cuenta nula o un estado que contradice la fecha es un dato
que debe revisarse. El agente no debe corregirlo automáticamente.

### 6.10 `recent_transactions`

Incluye hasta el límite configurado, ordenadas de más reciente a más antigua:

- fecha;
- tipo;
- importe;
- descripción opcional;
- categoría y grupo;
- cuenta y origen;
- marcas de desconocido, San Juan y renta.

No es un historial completo ni debe usarse para afirmar que no ocurrió algo
fuera de esas filas. Cuando las descripciones están desactivadas, el agente debe
trabajar con categorías y agregados, sin pedir que se reactive la opción salvo
que el usuario lo decida.

### 6.11 `advisory_signals`

Son heurísticas priorizadas, no órdenes:

| Tipo | Qué detecta |
| --- | --- |
| `cash_flow_risk` | Algún día del horizonte entra en riesgo |
| `survival_shortfall` | No alcanza para pagos, vida básica y colchón |
| `overdue_obligations` | Obligaciones vencidas en el resumen |
| `unknown_expenses` | Gastos sin identificar |
| `weekly_limit_reached` | Tope semanal consumido |
| `weekly_category_over_envelope` | Categoría excedió su sobre |
| `category_acceleration` | Categoría acelera contra su historial |
| `overdue_credit` | Mensualidades de crédito vencidas |

La aceleración se emite cuando:

```text
promedio anterior > 0
proyección actual >= promedio anterior * 1.25
proyección actual - promedio anterior >= 300
```

El agente debe explicar el dato subyacente y no limitarse a repetir el mensaje.

## 7. Método obligatorio de análisis

### Paso 1: establecer alcance

Confirmar que el usuario pidió análisis. Si solo pregunta por configuración,
estado o seguridad, no consultar datos financieros innecesariamente.

### Paso 2: obtener una lectura fresca

Ejecutar una sola vez `-Action snapshot`. Verificar `generated_at`,
`schema_version`, `read_only` y `owner_only`.

### Paso 3: auditar calidad de datos

Antes de aconsejar, buscar:

- corte inexistente o viejo;
- ingresos/pagos sin fecha;
- ingresos vencidos;
- créditos con fecha o cuenta nula;
- obligaciones con estado pendiente pero fecha vencida;
- conceptos que parecen duplicados;
- una misma obligación en pago planeado y mensualidad;
- diferencias entre `current_month.next_payments`,
  `cash_flow_projection.event_days` y `decision_plan.actions`;
- flujo del siguiente mes vacío;
- gasto nuevo con poco historial.

Una diferencia puede provenir de reglas legítimas: ciclo de tarjeta, cargo
automático, deduplicación o diferentes horizontes. El agente debe decir
“revisar” o “posible duplicado”, no afirmar un bug sin inspección adicional.

### Paso 4: separar realidad de expectativa

Construir cuatro cifras:

1. saldo actual conciliado;
2. ingresos reales cobrados;
3. egresos reales realizados;
4. ingresos esperados aún no cobrados.

Nunca sumar el punto 4 al saldo disponible en el lenguaje de la respuesta.

### Paso 5: medir el margen actual

Métricas recomendadas:

```text
absorción del ingreso =
    expenses / total_income * 100

margen real =
    total_income - expenses

mejora mínima para no quedar negativo =
    max(0, -min_projected_balance)

mejora para conservar colchón =
    max(0, configured_buffer - min_projected_balance)
```

Si `total_income` es cero, no calcular porcentaje.

“Mejora” puede venir de cobrar antes, cancelar gasto no esencial o reprogramar
una obligación con acuerdo del acreedor. No implica recomendar otro crédito.

### Paso 6: construir la línea de tiempo

Usar `event_days` y responder:

- ¿cuándo aparece el primer riesgo?;
- ¿cuál es el saldo mínimo proyectado?;
- ¿qué pagos lo provocan?;
- ¿qué ingreso permite recuperarse?;
- ¿qué parte depende de ingresos sin fecha o inciertos?;
- ¿qué ocurre si el ingreso se retrasa?

### Paso 7: ordenar obligaciones

Orden prudente:

1. vencidos reales y servicios esenciales;
2. pagos que vencen antes del siguiente ingreso;
3. mensualidades mínimas para evitar mora;
4. gasto básico y colchón;
5. obligaciones posteriores reservadas;
6. abonos extraordinarios;
7. gasto discrecional.

Si no hay tasa/CAT en el snapshot, no afirmar cuál deuda es más cara. Para pagos
extra, pedir o consultar ese dato antes de priorizar por interés.

### Paso 8: diagnosticar gasto

Combinar:

- categorías no solapadas de `spending_trends`;
- conceptos temáticos de `important_expense_concepts`;
- movimientos recientes de mayor importe;
- frecuencia de transacciones;
- si el gasto es personal, productivo, reembolsable o de San Juan.

Un gasto de trabajo no debe tratarse automáticamente como consumo personal.
Preguntar o señalar si falta conocer su reembolso/retorno.

### Paso 9: revisar deuda futura

Separar:

- mensualidades inmediatas;
- saldo total de largo plazo;
- compras discrecionales aún pendientes;
- crédito que empieza en meses futuros;
- pagos que ya fueron realizados.

No sumar el saldo original y el saldo pendiente. No tratar el límite de crédito
como ingreso.

### Paso 10: emitir recomendaciones

Cada recomendación debe incluir:

- acción;
- cantidad o categoría;
- plazo;
- razón basada en el snapshot;
- dependencia o incertidumbre relevante.

Cuando sea útil, indicar confianza:

- **alta:** movimiento real, saldo con corte reciente o vencimiento inequívoco;
- **media:** proyección que depende de ingresos fechados pero no cobrados;
- **baja:** fecha nula, corte viejo, categoría recién creada, concepto solapado o
  posible duplicado.

Ejemplo correcto:

> Pausa compras no esenciales hasta el siguiente ingreso confirmado. La
> categoría va por encima de su promedio y la proyección cae por debajo del
> colchón antes de esa fecha.

Ejemplo incorrecto:

> Estás gastando fatal; cancela todo.

## 8. Formato recomendado de respuesta

Una revisión completa debería seguir este orden:

1. **Conclusión directa:** estable, apretado o crítico, sin dramatizar.
2. **Foto actual:** saldo, ingresos reales, gastos reales y margen.
3. **Línea de tiempo:** primer día de riesgo, saldo mínimo y recuperación.
4. **Qué detener/reducir:** máximo tres focos, con importes.
5. **Qué mantener:** necesidades y gastos productivos justificados.
6. **Pagos prioritarios:** fechas e importes.
7. **Meta concreta:** cuánto mejorar el flujo y antes de qué fecha.
8. **Datos por verificar:** duplicados, fechas nulas o estados dudosos.
9. **Horizonte siguiente:** obligaciones próximas que ya deben reservarse.
10. **Alcance:** recordar que la consulta no modificó nada.

Plantilla:

```text
Lectura generada: [fecha y hora].

Diagnóstico:
[Una frase clara.]

Datos:
- Saldo actual: [...]
- Ingreso real: [...]
- Gasto real: [...]
- Margen: [...]
- Obligaciones pendientes: [...]

Riesgo de flujo:
[Fecha, saldo mínimo, ingreso del que depende.]

Acciones:
1. [...]
2. [...]
3. [...]

Revisar en el sistema:
- [...]

Esta fue una consulta de solo lectura; no se modificaron registros.
```

## 9. Cómo responder preguntas frecuentes

### “¿Puedo gastar X?”

1. Restar `X` al mínimo proyectado.
2. Comprobar el colchón.
3. Verificar pagos antes del siguiente ingreso.
4. No contar ingresos esperados no confirmados.
5. Responder sí, no o sí con límite/condición.

### “¿En qué debo dejar de gastar?”

1. Priorizar categorías discrecionales aceleradas.
2. Revisar movimientos recientes de mayor importe y alta frecuencia.
3. Separar gasto productivo/reembolsable.
4. Proponer un plazo y una meta, no una prohibición indefinida.

### “¿Qué pago primero?”

1. Confirmar vencidos reales.
2. Ordenar por fecha anterior al siguiente ingreso.
3. Cubrir mínimos y servicios esenciales.
4. Si se busca prepagar deuda, solicitar tasa/CAT.

### “¿Mis finanzas están muy mal?”

Evaluar por separado:

- capacidad de generar ingresos;
- absorción del ingreso por gastos;
- liquidez y calendario;
- carga mensual de deuda;
- confiabilidad de ingresos esperados;
- calidad del registro.

Evitar reducir el diagnóstico a una sola cifra.

## 10. Límites del análisis

El snapshot no contiene:

- movimientos bancarios que aún no fueron capturados;
- una consulta bancaria en tiempo real;
- tasas, CAT o costo financiero de todos los créditos;
- contratos completos o consecuencias de incumplimiento;
- datos fiscales suficientes para asesoría tributaria;
- certeza de que un ingreso esperado será cobrado;
- rendimiento/retorno comprobado de un gasto de trabajo.

Por ello un agente no debe:

- recomendar una inversión específica solo con este snapshot;
- decidir refinanciamiento o prepago por tasa si la tasa no está disponible;
- dar conclusiones fiscales, legales o contractuales como definitivas;
- afirmar fraude únicamente por un movimiento inusual;
- prometer un saldo futuro.

Si la respuesta depende de leyes, impuestos, reglas financieras, tasas o
condiciones actuales, consultar una fuente oficial vigente y citarla. Para
México se deben priorizar autoridades como CONDUSEF, SAT, Banco de México o la
institución financiera correspondiente.

El análisis sí puede ayudar a organizar flujo, detectar presiones, preparar
preguntas para el banco/contador y comparar escenarios explícitos.

## 11. Privacidad y manejo de datos

Nunca:

- pegar el token en el chat;
- ejecutar un comando que contenga el token literal;
- abrir o imprimir `credential.json`;
- publicar el snapshot en GitHub;
- incluir nombres, movimientos o importes reales en documentación o tests;
- enviar el JSON a otro servicio sin autorización expresa;
- dejar copias en `storage`, `docs`, el escritorio o una carpeta temporal;
- citar en la respuesta más movimientos personales de los necesarios.

Sí se permite:

- mantener la respuesta en memoria durante la sesión;
- mostrar al usuario sus propios importes relevantes;
- usar datos sintéticos en pruebas y documentación;
- resumir categorías sin revelar descripciones cuando no hacen falta.

Si el usuario comparte accidentalmente un token, recomendar rotarlo. No volver a
repetirlo en la conversación.

## 12. Solución de problemas

### `No existe configuración local`

Ejecutar `-Action setup` desde el mismo perfil de Windows.

### Error al descifrar DPAPI

La credencial fue creada por otro usuario/perfil o equipo. Ejecutar nuevamente
`setup`; no intentar descifrarla manualmente.

### `401 unauthorized`

El token local no coincide con producción. Rotar/reconfigurar y ejecutar
`setup`. Nunca pedir al usuario que lo pegue en el chat.

### `503 not_configured`

Falta un token válido en producción o la configuración sigue en caché. Revisar
`.env` y limpiar caché.

### `503 owner_not_configured` / `owner_not_found`

Revisar `FINANCE_OWNER_EMAIL` y la existencia del usuario.

### `429`

Se excedieron 12 solicitudes por minuto. Esperar y reutilizar la lectura que ya
existe. No automatizar reintentos agresivos.

### Salida demasiado grande o truncada

Consultar otra vez solo después de que sea necesario y seleccionar campos en
memoria. No aumentar el límite de movimientos para compensar una extracción
ineficiente.

### Descripciones en `null`

`FINANCE_ADVISOR_INCLUDE_DESCRIPTIONS=false`. Trabajar con categorías y
agregados; la privacidad fue una decisión del operador.

### Saldos que no coinciden con el banco

Revisar el último corte, su antigüedad y movimientos posteriores. El sistema no
consulta bancos en tiempo real.

### Diferencia entre resumen, proyección y plan

Comprobar:

- horizonte mensual vs 45 días vs 30 días;
- estados almacenados vs vencimiento calculado por fecha;
- ciclos y ventanas de cargo automático;
- pagos vinculados o deduplicados;
- ingresos vencidos no contados;
- próxima mensualidad fuera del horizonte.

Reportar la discrepancia con los nombres, fechas e importes mínimos necesarios.

## 13. Rotación y revocación

Para rotar:

1. crear un token nuevo e independiente;
2. reemplazar `FINANCE_ADVISOR_API_TOKEN` en producción;
3. limpiar la caché;
4. ejecutar `tools/finance-advisor.ps1 -Action setup`;
5. verificar con una sola consulta.

Para revocar:

1. eliminar/cambiar `FINANCE_ADVISOR_API_TOKEN`;
2. limpiar la caché;
3. eliminar la credencial local si ya no se usará.

Rotar este token no debe afectar los tokens de cPanel o despliegue.

## 14. Validación técnica

Prueba específica:

```powershell
& 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe' `
    artisan test tests\Feature\Finance\FinanceAdvisorApiTest.php
```

Suite completa:

```powershell
& 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe' `
    artisan test --filter=Finance
```

Verificar la ruta:

```powershell
& 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe' `
    artisan route:list --name=api.finance.advisor.snapshot
```

Comprobar sintaxis del cliente:

```powershell
$null = [scriptblock]::Create(
    (Get-Content -Raw .\tools\finance-advisor.ps1)
)
```

Las pruebas deben cubrir al menos:

- token independiente;
- propietario obligatorio;
- aislamiento de otro usuario;
- método de solo lectura;
- ausencia de identificadores/token/correo;
- ocultamiento opcional de descripciones;
- tendencias y señales;
- ausencia de escrituras;
- uso de DPAPI por el cliente;
- existencia de este manual.

## 15. Mantenimiento del contrato

Al cambiar el snapshot:

1. conservar compatibilidad o subir `schema_version`;
2. documentar todo campo nuevo;
3. actualizar la extracción recomendada;
4. agregar pruebas de aislamiento y privacidad;
5. comprobar que no aparezcan IDs internos;
6. no registrar contenido financiero en logs;
7. subir `config/finance.php` → `version`;
8. ejecutar la suite financiera completa.

Un cambio incompatible incluye renombrar/eliminar campos, alterar unidades o
cambiar la semántica de saldo seguro/proyectado. Agregar un campo opcional puede
mantener la misma versión de esquema.

## 16. Prompt de arranque para otro agente

```text
Lee docs/manual-asesor-financiero-agentes.md completo.
El usuario autoriza una revisión financiera de solo lectura.
Consulta un snapshot fresco con tools/finance-advisor.ps1 -Action snapshot.
No abras credential.json, no pidas ni muestres el token y no guardes el JSON.
Valida generated_at, read_only, owner_only y schema_version.
Separa ingresos reales de esperados, construye la línea de tiempo de flujo,
revisa vencidos, posibles duplicados, categorías aceleradas y deuda futura.
Explica recomendaciones con importes y fechas, señala incertidumbres y no
realices ninguna escritura.
```

## 17. Checklist rápido

Antes de responder:

- [ ] ¿La lectura es reciente?
- [ ] ¿`read_only` y `owner_only` son verdaderos?
- [ ] ¿Separé dinero cobrado de dinero esperado?
- [ ] ¿Revisé advertencias del corte y del siguiente mes?
- [ ] ¿Encontré el primer día de riesgo y el saldo mínimo?
- [ ] ¿Expliqué el horizonte usado?
- [ ] ¿Revisé vencidos, fechas nulas y posibles duplicados?
- [ ] ¿Evité sumar conceptos solapados?
- [ ] ¿Separé gasto personal de gasto productivo/reembolsable?
- [ ] ¿No traté el límite de crédito como dinero?
- [ ] ¿Cada consejo tiene importe, fecha o condición?
- [ ] ¿Evité mostrar información innecesaria?
- [ ] ¿Recordé que no se modificó nada?
