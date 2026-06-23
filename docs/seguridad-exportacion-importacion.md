# Seguridad, exportación e importación

## Backup externo

Para copiar backups fuera del proyecto configura una carpeta existente y escribible en `.env`:

```env
FINANCE_EXTERNAL_BACKUP_PATH="C:/Users/axelg/OneDrive/BackupsFinanzas"
```

Después limpia la configuración:

```powershell
php artisan config:clear
```

La ruta puede ser OneDrive, Google Drive local, disco externo o una carpeta fuera del proyecto. El sistema no crea la ruta principal: debe existir antes de usar el botón. Si no existe, no tiene permisos o falla la copia, se muestra un error claro y se registra una falla en Finanzas > Seguridad.

No subas `.env` ni archivos con credenciales a GitHub.

## Exportación Excel

Movimientos y Reportes permiten exportar:

- CSV compatible con Excel.
- XLSX real cuando PhpSpreadsheet está disponible.

Ambos respetan los filtros actuales de mes, tipo, búsqueda y categoría.

## Importación histórica 2025/2026

El importador histórico recibe CSV y nunca guarda automáticamente. Primero muestra una vista previa con errores y advertencias; después se guardan solo los movimientos válidos confirmados.

Columnas obligatorias:

- `fecha`
- `tipo`
- `descripcion`
- `monto`

Columnas opcionales:

- `cuenta`
- `categoria`
- `persona`
- `notas`
- `san_juan`
- `renta`
- `desconocido`
- `diferencia_conciliacion`

Si `diferencia_conciliacion` viene distinta de `0`, el registro queda con advertencia para revisión porque tus reportes históricos deben venir conciliados.
