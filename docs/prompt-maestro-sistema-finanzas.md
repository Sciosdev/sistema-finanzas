# Prompt Maestro del Sistema de Finanzas

## Objetivo

Este sistema sirve para controlar finanzas personales con lógica de conciliación diaria: saber en qué se gasta, qué ingresos se esperan, qué obligaciones están pendientes, cuánto dinero existe realmente en tarjetas/efectivo, cómo van créditos y rentas, y qué decisiones ayudan a dejar de vivir al día y ahorrar.

## Problema que Resuelve

El Excel original funcionaba porque cuadraba contra la realidad: tarjetas + efectivo debían coincidir con ingresos, egresos, rendimientos y cortes. El sistema conserva esa idea, pero agrega captura diaria, métricas, reportes, respaldos, papelera temporal y módulos separados para obligaciones, créditos, rentas e importación histórica.

## Módulos

- **Resumen/Dashboard:** muestra ingresos reales, egresos, saldo real, saldo proyectado, obligaciones, cortes, próximos pagos, próximos ingresos, San Juan, recordatorios y focos de gasto.
- **Movimientos:** captura ingresos, egresos, rendimientos, transferencias y ajustes. Permite editar, borrar con deshacer y exportar.
- **Flujo planeado:** obligaciones del mes, pagos fijos, pagos externos y pagos a crédito. Los vencidos pendientes no desaparecen.
- **Créditos/Tarjetas:** compras a meses, mensualidades, pagos registrados y abonos libres que reducen el saldo real sin marcar mensualidades como pagadas.
- **Ingresos esperados:** ingresos planeados, rentas, pagos parciales y abonos ligados a movimientos reales.
- **San Juan/Rentas:** contratos, rentas esperadas, ingresos recibidos, gastos de la casa, utilidad y movimientos relacionados.
- **Reportes:** análisis por categoría, concepto, periodo y exportación.
- **Recordatorios:** refrendo, verificación, carro, moto y otros pagos recurrentes.
- **Seguridad/Backups:** respaldos de BD, respaldo completo, backup externo, papelera temporal y fallas controladas.
- **Importación histórica:** importa CSV revisado de 2025/2026 sin inventar datos y respetando conciliación.
- **Corrector mensual:** sugiere correcciones de textos, categorías y personas sin aplicar cambios automáticos riesgosos.

## Reglas Contables Usadas

- **Ingreso:** dinero que entra realmente o que se espera recibir.
- **Egreso:** gasto real registrado.
- **Rendimiento:** interés diario de NU/MPW u otra cuenta.
- **Saldo real:** dinero contado o consultado realmente en tarjetas y efectivo.
- **Saldo proyectado:** saldo real menos obligaciones pendientes.
- **Obligación:** pago planeado, mensualidad de crédito o pendiente vencido.
- **Utilidad:** ingresos menos egresos asociados, especialmente en San Juan.
- **Conciliación:** comparación entre lo esperado por registros y lo real contado en tarjetas/efectivo.
- **Pago parcial:** abono a un ingreso esperado/renta sin duplicar la renta mensual.
- **Abono libre:** pago a crédito/tarjeta que reduce el saldo general sin pagar una mensualidad concreta.

## Cálculos Importantes

- **Ingresos reales:** suma de movimientos tipo ingreso y rendimientos del periodo.
- **Ingresos esperados:** suma de ingresos planeados no recibidos o parcialmente recibidos.
- **Ingresos proyectados:** ingresos reales + saldo pendiente de ingresos esperados.
- **Egresos:** suma de movimientos tipo egreso.
- **Saldo pendiente de ingreso:** monto esperado - abonos recibidos.
- **Saldo real de crédito:** total original - mensualidades pagadas - abonos libres.
- **Utilidad San Juan:** rentas e ingresos San Juan menos gastos San Juan.
- **Diferencia de conciliación:** saldo real contado menos saldo esperado por movimientos/cortes.

## Reglas de Seguridad

- No borrar historial financiero.
- No inventar movimientos.
- No importar registros con diferencia de conciliación distinta de 0.
- Todo cambio masivo debe tener vista previa o confirmación.
- Hacer backup antes de operaciones peligrosas.
- Mantener todo filtrado por `user_id`.
- No subir `.env`, dumps, credenciales ni datos bancarios sensibles.

## Cómo debe actuar Codex en cambios futuros

1. Leer el módulo afectado antes de editar.
2. Mantener cambios pequeños y verificables.
3. Priorizar lógica financiera correcta sobre diseño.
4. No rehacer módulos completos si ya existen.
5. Agregar pruebas cuando el cambio toque cálculos, borrados, restauraciones o importaciones.
6. Ejecutar `php artisan route:list`, `php artisan test` y build de Vite cuando sea posible.
7. Explicar migraciones antes si son destructivas; migraciones aditivas pequeñas son aceptables cuando el usuario ya aprobó la función.

## Comandos Útiles

```powershell
& 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe' artisan migrate
& 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe' artisan route:list
& 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe' artisan test
& 'C:\laragon\bin\nodejs\node-v22\node.exe' node_modules\vite\bin\vite.js build
```

## Pendientes que Dependen del Usuario

- Configurar `FINANCE_EXTERNAL_BACKUP_PATH` en `.env`.
- Probar instalación PWA en el teléfono con HTTPS o localhost.
- Definir si se usarán notificaciones push reales con VAPID.
- Elegir destino final de despliegue: Ubuntu privado por Tailscale, Cloudflare Tunnel o hosting tradicional.
- Revisar archivos históricos reales 2025/2026 para ajustar columnas si aparece un formato distinto.
