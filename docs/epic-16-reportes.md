# EPIC 16 — Reportes y cierre de caja

## Definiciones operativas

- Cobranza incluye exclusivamente pagos cuyo estado actual es `confirmado`, usa `fecha_pago` y no convierte monedas. Cancelados, reembolsados y borradores se excluyen.
- Las agrupaciones por grupo/curso cuentan cada pago una vez. Por período se distribuye `pago_aplicaciones.importe_aplicado` al mes/año real del cargo; el remanente aparece como **Anticipo / sin asignación**.
- Vencido significa saldo positivo, estado distinto de cancelado y `fecha_vencimiento < fecha de corte`.
- La identidad del usuario que registró es `created_by`; la del cajero que recibió y confirmó el pago es `confirmed_by`. CF-FAC-174 presenta ambas agregaciones por separado. Los pagos históricos sin `confirmed_by` aparecen como **Sin cajero registrado** y no se atribuyen a un cierre nominal.
- Una cancelación se atribuye exclusivamente a `cancelled_by`. El esquema no conserva monto ni actor específico de reembolso: el reporte identifica el monto total, muestra el actor como **No registrado** y un reembolso sin actor no se atribuye a una caja nominal. Nunca se usa `created_by` como sustituto.
- La ventana diaria y de cierre es `[00:00 de la fecha, 00:00 del día siguiente)` en `config('app.timezone')`. La identidad única es cajero + fecha de operación.
- Un cierre guarda el resumen, los IDs/folios/importes incluidos y los valores contados. Es transaccional, tiene restricción única y es inmutable; no se ofrece reapertura porque no existe un permiso/regla de corrección aprobada.
- Los importes contados aceptan únicamente decimales no negativos (máximo 12 enteros y dos decimales), sin exponentes ni valores especiales. Cada combinación con movimientos es obligatoria, incluso si su neto esperado resulta cero; las combinaciones de métodos activos y monedas configuradas también pueden capturarse con esperado cero. La diferencia se calcula por método y moneda con aritmética decimal, sin conversión ni suma entre monedas.
- Las monedas permitidas para conteos sin movimientos se configuran en `FACTURACION_MONEDAS` (por defecto `MXN`). La clasificación depende únicamente del método del catálogo; en particular, un depósito no se infiere como efectivo.
- CSV/XLSX de diario incluyen resumen y detalle completo. La descarga de un cierre definitivo usa exclusivamente el snapshot persistido; si aún no existe, se rotula **PRE-CIERRE, AÚN NO CERRADO**. El generador XLSX actual crea el documento en un archivo temporal y, por ello, el límite operativo debe ajustarse a la memoria disponible para construir las filas.

## Permisos

`view-financial-reports`: admin, caja, contabilidad. `export-financial-reports`: admin y contabilidad. `close-cash`: admin y caja. Venta y profesores no tienen acceso, aunque venta conserve el acceso limitado de EPIC 15 a cobranza.

Un usuario `caja` solo consulta y cierra su propia caja aunque manipule query string o estado Livewire. `admin` puede seleccionar y cerrar la de otro cajero. `contabilidad` puede consultar y exportar cualquier caja, pero no cerrarla.
