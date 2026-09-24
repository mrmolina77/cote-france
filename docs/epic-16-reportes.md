# EPIC 16 — Reportes y cierre de caja

## Definiciones operativas

- Cobranza incluye exclusivamente pagos cuyo estado actual es `confirmado`, usa `fecha_pago` y no convierte monedas. Cancelados, reembolsados y borradores se excluyen.
- Las agrupaciones por grupo/curso cuentan cada pago una vez. Por período se distribuye `pago_aplicaciones.importe_aplicado` al mes/año real del cargo; el remanente aparece como **Anticipo / sin asignación**.
- Vencido significa saldo positivo, estado distinto de cancelado y `fecha_vencimiento < fecha de corte`.
- El esquema no conserva monto ni actor específico de reembolso. Por ello el reporte identifica claramente el monto total del pago y usa el responsable de cancelación disponible; valores históricos ausentes no se inventan.
- La ventana diaria y de cierre es `[00:00 de la fecha, 00:00 del día siguiente)` en `config('app.timezone')`. La identidad única es cajero + fecha de operación.
- Un cierre guarda el resumen, los IDs/folios/importes incluidos y los valores contados. Es transaccional, tiene restricción única y es inmutable; no se ofrece reapertura porque no existe un permiso/regla de corrección aprobada.

## Permisos

`view-financial-reports`: admin, caja, contabilidad. `export-financial-reports`: admin y contabilidad. `close-cash`: admin y caja. Venta y profesores no tienen acceso, aunque venta conserve el acceso limitado de EPIC 15 a cobranza.
