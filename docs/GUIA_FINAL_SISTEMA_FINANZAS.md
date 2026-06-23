# Guía Final del Sistema de Finanzas

## Prompt maestro

Este proyecto es un sistema privado de finanzas personales construido en Laravel con plantilla Velok. Reemplaza el flujo manual de los Excel `Gastos 2026.xlsx` y `Nuevos Gastos 2026 v4 CORREGIDO.xlsx`.

El objetivo principal es capturar ingresos, egresos, rendimientos, cortes reales, pagos planeados, créditos, rentas de San Juan y recordatorios, para saber qué dinero hay realmente, qué falta por pagar, qué ingresos se esperan, dónde se está gastando más y qué se puede mejorar para ahorrar.

La regla central sigue siendo la del Excel original:

```text
Sobrante esperado = ingresos reales + rendimientos - egresos reales
Total real corte = suma de saldos reales capturados
Resta corte = sobrante esperado - total real corte
Cuánto me falta = total real corte - obligaciones pendientes del mes
```

Si la resta del corte es `0.00`, el sistema cuadra contra el dinero real disponible en efectivo y tarjetas/cuentas.

## Módulos

### Resumen

Ruta: `/finanzas`

Muestra ingresos reales, egresos reales, sobrante esperado, total real del corte, pendiente por pagar, vencido pendiente, créditos del mes, pagos planeados, saldos por cuenta, próximos ingresos, próximos pagos, últimos movimientos, gasto por categoría y recordatorios.

### Movimientos

Ruta: `/finanzas/movimientos`

Captura ingresos, gastos, rendimientos, transferencias y ajustes. Permite editar y borrar con deshacer temporal. Los rendimientos NU/MPW se registran como movimientos tipo rendimiento.

### Cortes

Ruta: `/finanzas/cortes`

Guarda saldos reales por cuenta y fecha. Es la parte que reemplaza el corte diario del Excel: tarjeta, efectivo y total real.

### Flujo planeado

Ruta: `/finanzas/flujo-planeado`

Controla pagos del mes. Permite copiar meses, editar pagos, vincularlos con movimientos reales, marcarlos como pagados, registrados o no pagados. Los vencidos pendientes siguen visibles y se suman como obligación.

### Ingresos esperados

Ruta: `/finanzas/ingresos-esperados`

Controla dinero que se espera recibir: rentas, trabajo, SCIOS, FESI, Andrea comida u otros ingresos. Permite copiar meses, editar, vincular con movimientos reales, desligar, marcar recibido, registrado o no recibido.

### Créditos

Ruta: `/finanzas/creditos`

Administra compras a meses. Puede capturar por total o pago mensual, genera mensualidades, permite editar crédito completo, editar mensualidades, marcar pagado o registrado, borrar con deshacer y restaurar mensualidades.

### San Juan

Ruta: `/finanzas/san-juan`

Separa rentas, gastos, utilidad y movimientos relacionados con la casa de San Juan. Maneja contratos/rentas, próximos cobros y movimientos marcados como San Juan.

### Categorías

Ruta: `/finanzas/categorias`

Administra categorías con color, tipo, grupo y palabras clave. Evita borrar historial: si una categoría está en uso, se desactiva y puede deshacerse.

### Reportes

Ruta: `/finanzas/reportes`

Muestra análisis por día, semana, quincena, mes y año, además de gasto por categoría, conceptos principales, gasolina carro/moto e ingresos/egresos acumulados.

### Seguridad

Ruta: `/finanzas/seguridad`

Concentra respaldos de base de datos, respaldos completos, exportaciones existentes, snapshots de deshacer y fallas financieras registradas.

### Recordatorios

Ruta: `/finanzas/recordatorios`

Controla recordatorios de carro, moto y otros pagos recurrentes como refrendo, verificación o servicios.

### Operación

Ruta: `/finanzas/operacion`

Resume recomendaciones para operar el sistema en Ubuntu, Tailscale, hosting y GitHub. No ejecuta deploy ni sube código por sí solo.

## Comandos importantes

Desde `D:\Github\Finanzas\sistema-finanzas`:

```powershell
& 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe' artisan migrate
& 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe' artisan test
```

Para compilar assets cuando cambie CSS o JS:

```powershell
$env:PATH='C:\laragon\bin\nodejs\node-v22;' + $env:PATH
& 'C:\laragon\bin\nodejs\node-v22\npm.cmd' run build
```

Para importar Excel, usar el comando de importación del proyecto apuntando al archivo real y revisar el resultado antes de confiar en los datos.

## Respaldos

Desde `/finanzas/seguridad`:

- `Backup solo BD`: genera un `.sql` privado si `mysqldump` está disponible.
- `Backup completo`: genera un `.zip` con código y dump de base de datos.
- `.env` está desactivado por defecto porque contiene credenciales.

Los respaldos se guardan en:

```text
storage/app/private/finance-backups/database
storage/app/private/finance-backups/full
```

## Cómo probar manualmente

1. Capturar un gasto real y revisar que aparezca en Resumen y Movimientos.
2. Capturar un rendimiento NU y confirmar que suba ingresos/rendimientos.
3. Crear un corte con saldos reales y revisar `Resta corte`.
4. Crear un pago planeado vencido y confirmar que siga visible como pendiente.
5. Vincular un pago planeado con un movimiento y confirmar que ya no se cuente como pendiente.
6. Crear un ingreso esperado, ligarlo con un movimiento y desligarlo.
7. Crear un crédito a meses y revisar mensualidades del mes actual y siguiente.
8. Borrar un movimiento o pago y usar `Deshacer` antes de 2 minutos.
9. Crear un backup desde Seguridad y descargarlo.
10. Revisar Reportes por categoría y San Juan.

## Mejoras futuras

### Urgentes

- Probar restauración real de un backup en una base limpia.
- Revisar vulnerabilidades de dependencias `npm` antes de publicar en internet.
- Definir si las categorías viejas sin acento se renombrarán manualmente o mediante herramienta segura.

### Importantes

- Agregar exportación Excel final y documentar su formato.
- Configurar rotación/borrado de backups antiguos.
- Preparar repositorio privado en GitHub con `.env` excluido.
- Hacer checklist de instalación para Ubuntu + Tailscale.
- Agregar permisos por usuario si más personas usarán el sistema.

### Opcionales

- Notificaciones web/PWA con HTTPS.
- Más gráficas comparativas por año.
- Unificador asistido de categorías repetidas.
- Mejoras finas para móvil y tablet después de uso real.
