# Prompt maestro — Sistema de Finanzas (contexto para agentes/IA)

> Pégalo al iniciar una conversación nueva. Da el contexto técnico y operativo
> para seguir manteniendo esta app sin repetir la curva de aprendizaje.
> El contexto de **negocio/reglas contables** está en
> `docs/prompt-maestro-sistema-finanzas.md` (léelo también).
> Para hacer revisiones o recomendaciones con datos de producción, leer
> **completo** `docs/manual-asesor-financiero-agentes.md`.

## 1. Qué es

App **Laravel de finanzas personales** (dueño: Axel). Idea central: **conciliación**
— tarjetas + efectivo deben cuadrar contra ingresos, egresos, rendimientos,
créditos, rentas (San Juan) y cortes diarios. Módulos: Resumen/Dashboard,
Movimientos, Flujo planeado, Créditos/Tarjetas, Ingresos esperados, San Juan,
Reportes, Recordatorios, Cortes diarios, Seguridad/Backups, Importación histórica,
Corrector mensual.

## 2. Cómo trabajar (rol del agente)

- Responder en **español**.
- Cambios **pequeños, verificados con tests**. Antes de afirmar "quedó", correr
  los tests relevantes.
- **SIEMPRE subir la versión** en cada cambio desplegable (ver §5).
- No adivinar en bugs sutiles: reproducir/verificar (esta app enseñó a punta de
  ir a producción). Preguntar solo decisiones reales del usuario.
- Las revisiones financieras usan el cliente local de solo lectura
  `tools/finance-advisor.ps1`; nunca pedir ni mostrar el token, guardar el JSON o
  confundir ingresos esperados con dinero disponible.

## 3. Stack

- **Laravel**, PHP 8.2+ (local 8.3), Blade + **tema admin Bootstrap** (layout
  `layouts.vertical`), iconos **lucide** (`<i data-lucide="...">`).
- **Vite** compila los assets del tema a `public/build/`. **PERO** el JS/CSS de
  cada pantalla suele ir **inline dentro del `.blade.php`** (`<script>` /
  `<style>`), así que **no pasa por `npm run build`**: cambiarlo es solo editar
  el blade.
- BD **MySQL** en producción; **SQLite `:memory:`** en tests.
- Auth simple. Dueño = `config('finance.owner_email')`; rutas sensibles
  (Seguridad, Diagnóstico, Usuarios) bajo middleware `finance.owner`.

## 4. Despliegue (CRÍTICO — leer completo)

- **Producción:** HostGator, **hosting compartido SIN SSH/terminal**. Dominio
  `https://finanzas.xaanal.com`.
- **Pipeline:** `git push` a `main` → en cPanel **Git™ Version Control → Update
  from Remote** (hace `git pull` al servidor; el repo está clonado directo en la
  carpeta del sitio, por eso el pull actualiza los archivos en vivo) → en la app
  **Seguridad → Mantenimiento → "Limpiar caché"** (`optimize:clear`, obligatorio
  para refrescar vistas compiladas Y config).
- **Migraciones:** NO corren solas. En la app **Seguridad → Mantenimiento →
  "Ejecutar migraciones"** (`migrate --force`, crea backup automático antes).
- **GitHub ≠ el servidor.** No hay auto-deploy (sin `.github/workflows`, sin
  `.cpanel.yml`). Un cambio puede estar en GitHub y **no** en el sitio hasta hacer
  el "Update from Remote". Verificar siempre con el **marcador de versión**.
- Cambios en `.php` / `.blade.php` / `config`: basta pull + limpiar caché.
  Solo cambios de **assets del tema** (`public/build/`, vía `npm run build`) o
  **dependencias** (`vendor/`, vía `composer install`) requieren subir artefactos;
  `public/build` está en `.gitignore`, así que esos van por el botón **"Subir y
  montar build"** (solo `public/build`) o por el **release completo**
  (`php artisan finance:build-release`, ZIP que se sube por el Administrador de
  archivos de cPanel).

## 5. Versionado (regla firme) — ver `CLAUDE.md`

- **Fuente única:** `config/finance.php` → `'version'` (semver `MAJOR.MINOR.PATCH`).
- Se muestra en **menú lateral** (`resources/views/layouts/partials/main-nav.blade.php`)
  y **footer** (`resources/views/layouts/partials/footer.blade.php`) vía
  `config('finance.version')` (el footer se ve también en móvil).
- **Sube la versión en CUALQUIER cambio desplegable** antes del commit:
  PATCH = fix / menor · MINOR = feature nueva · MAJOR = rompe compatibilidad.
  Nunca bajar. Sirve para confirmar el deploy a simple vista.
- **Versión actual: 2.14.1.**

## 6. Entorno local (Windows + Laragon)

- **PHP** (no está en PATH): `C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe`
- **Node**: `C:\laragon\bin\nodejs\node-v22\node.exe`
- **MySQL**: arrancarlo **desde Laragon** (no `mysqld` a mano: hay un datadir viejo
  a medio migrar que da errores como `Unknown column 'credit_limit'`).
- **Correr tests sin MySQL** (recomendado):
  ```powershell
  $env:Path = "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64;" + $env:Path
  $env:DB_CONNECTION = "sqlite"; $env:DB_DATABASE = ":memory:"
  php artisan config:clear
  php artisan test --filter=Finance
  ```

## 7. Convenciones del código

- **Filtros de listas = client-side.** Cada fila/tarjeta lleva atributos `data-*`
  (p. ej. `data-balance`, `data-paid`, `data-creditor-key`, `data-current-due`) y
  hay botones `data-*` con JS que muestra/oculta. Ejemplos: **Créditos** (filtros
  **combinables** Estado `data-credit-status` × Acreedor `data-credit-creditor`) y
  **Flujo planeado** (Pendiente/Pagado `data-planned-filter` sobre
  `[data-planned-row]`, botones en ambas tablas, se sincronizan).
- **Layout del Resumen** por usuario en `users.dashboard_layout` (JSON: order,
  sizes, hidden, autoLayout). Se guarda con POST a `finanzas/resumen/diseno`.
  `sizes` DEBE ser objeto `{}` (no array) — ver lección #1.
- **Conciliación:** la fuente de verdad es el **corte guardado** (`DailyCut`). El
  Resumen **lee** `latest_cut->difference`, no lo recalcula. El "saldo proyectado"
  del corte = saldo de arranque (corte anterior o `opening_balance`) + movimientos.

## 8. Estructura

- **Controllers** (`app/Http/Controllers/Finance/`): FinanceDashboard, Movement,
  CreditPurchase, DailyCut, PlannedPayment, ExpectedIncome, SanJuan, Reminder,
  FinanceReport, FinanceSecurity/Maintenance/BuildDeploy/Health/Triage/Restore,
  HistoricalImport, MonthlyReview, Account, Category, FinanceUser, FinanceOperation,
  FinancePending.
- **Services** (`app/Services/Finance/`): FinanceSummaryService (`monthSummary`),
  FinanceCutSuggestionService (`suggest`/`expectedBalances`/`reconciliationFor`/
  `expectedTotalThrough`), FinanceCatalogService, FinanceBackupService,
  FinanceMaintenanceService, FinanceHealthCheckService, AutomaticYieldService,
  CreditFreePaymentService, FinancePendingResolutionService, etc.
- **Models** (`app/Models/Finance/`): Account, Movement, CreditPurchase,
  CreditInstallment, CreditFreePayment, DailyCut, DailyCutBalance, PlannedPayment,
  ExpectedIncome, ExpectedIncomePayment, RentalContract, Reminder, Person, Category,
  DeleteSnapshot, SystemFailure. (`User` tiene `dashboard_layout` casteado a array.)
- **Rutas:** `routes/web.php` (prefijo `finanzas`, nombres `finance.*`, middleware
  `auth`; owner-only con `finance.owner`).
- **Asesor financiero:** `GET /api/finance/advisor/snapshot`, autenticado con
  token exclusivo y propietario fijo. Contrato y procedimiento:
  `docs/manual-asesor-financiero-agentes.md`.
- **Docs:** `docs/prompt-maestro-sistema-finanzas.md` (negocio/reglas),
  `docs/deploy-hostgator.md`, `docs/FINANZAS_SISTEMA.md`,
  `docs/GUIA_FINAL_SISTEMA_FINANZAS.md`,
  `docs/manual-asesor-financiero-agentes.md`, `CLAUDE.md`.

## 9. Lecciones / gotchas (bugs reales ya resueltos)

1. **`dashboard_layout.sizes` como array:** PHP serializa un objeto vacío como
   `[]`; el JS lo trataba como array y `JSON.stringify` descartaba las claves de
   texto → los **tamaños del Resumen nunca se guardaban**. Fix: normalizar
   `layout.sizes` a objeto `{}` al parsear/leer.
2. **Guardado del layout perdido en F5:** era `debounce` de 400 ms sin flush al
   salir. Fix: guardado inmediato en acciones discretas + flush en `pagehide` con
   `fetch(..., {keepalive:true})` + errores **no** silenciosos (`console.warn`).
3. **"Tamaño manual gana":** el auto-ajuste estiraba y pisaba el tamaño elegido.
   Fix: los cuadros con tamaño fijado (en `sizes`) no se estiran.
4. **Conciliación:** "Saldo proyectado" ignoraba el saldo de arranque → el primer
   corte / sin movimientos daba diferencia = todo tu saldo en negativo, aunque
   "cuadrara" por cuenta. Fix: `expectedTotalThrough` (baseline + movimientos) en
   `DailyCutController::store`, y el Resumen lee `latest_cut->difference`.
5. **"El deploy no llega":** casi siempre el commit no se había jalado en cPanel
   (el código SÍ estaba en GitHub). Confirmar con el marcador de versión y el
   "Update from Remote".

## 10. Estado actual

- **Versión 2.14.1**, rama `main` sincronizada con `origin`
  (`github.com/Sciosdev/sistema-finanzas`).
- Trabajos recientes: asesor financiero de solo lectura para agentes, despliegue
  remoto protegido desde la app, captura manual de créditos y mejoras de
  conciliación/planeación.
