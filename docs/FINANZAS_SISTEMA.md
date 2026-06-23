# Sistema de Finanzas

## Objetivo

Sistema privado para reemplazar el flujo manual de `Gastos 2026.xlsx` y `Nuevos Gastos 2026 v4 CORREGIDO.xlsx`.

La captura diaria debe ser simple:

- Ingresos reales: dinero que entra a cuenta o efectivo.
- Gastos reales: dinero que ya se gasto.
- Rendimientos: NU, MPW u otra cuenta que genere pesos diarios.
- Corte diario: saldo real de efectivo y tarjetas/cuentas.
- Pagos planeados: pagos del mes, marcados como pendiente, pagado o no pagado.
- Creditos manuales: compras a meses que generan mensualidades automaticas.

## Regla central de conciliacion

El Excel original cuadraba porque el usuario capturaba el dinero real que tenia en cartera y tarjetas.

En el sistema se mantiene la misma idea:

```text
Sobrante esperado = ingresos reales + rendimientos - gastos reales
Total real corte = suma de saldos reales capturados en el corte
Resta corte = sobrante esperado - total real corte
Cuanto me falta = total real corte - pagos pendientes del mes
```

Si `Resta corte` es `0.00`, el mes cuadra contra el dinero fisico/digital real. Si no es cero, el sistema marca el corte como `Revisar`.

## Traduccion desde Gastos 2026

- Filas `3:63` de cada mes eran la fuente real.
- `?` era un gasto real desconocido. En el sistema se guarda como gasto con marca `?`.
- `Tarjeta` era la suma real de NU, Mercado Pago, BBVA, DIDI, MPW u otras cuentas.
- `Efectivo` era el efectivo real en cartera.
- Los rendimientos NU/MPW cuentan como ingreso tipo `yield`.
- Las tablas fuera del rango principal del Excel eran apoyo visual o calculos auxiliares, no movimientos reales por si solas.

## San Juan

Se considera San Juan cuando el movimiento:

- Tiene categoria San Juan, JAPAM o Limpieza Jorge.
- Tiene texto como `SNJ`, `San Juan`, `JAPAM`, `Jorge`, `limpieza`, `cloro`, `jabon` o `escoba`.
- Se marca manualmente como San Juan.

Las rentas se identifican si:

- La categoria es rentas.
- La persona esta marcada como inquilino.
- La descripcion contiene `renta` o `rentas`.

## Primer MVP implementado

- Dashboard financiero.
- Captura de movimientos.
- Captura de cortes diarios por cuenta.
- Flujo planeado mensual.
- Creditos a meses con mensualidades.
- Resumen San Juan.
- Catalogos base automaticos por usuario.

## Hosting HostGator

El proyecto usa Laravel sobre PHP 8.2+ y MySQL/Percona 5.7 compatible.

Como el hosting no tiene SSH:

- El proyecto debe subirse ya con `vendor`.
- Los assets de Velok pueden usarse ya compilados en `public/build`.
- Las migraciones se pueden ejecutar desde un instalador web protegido o preparar la base importando SQL desde phpMyAdmin.
- Cron puede ejecutar el scheduler si se configura una ruta o comando compatible con cPanel.

