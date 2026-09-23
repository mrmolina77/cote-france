# EPIC 14 — auditoría financiera de pagos

La bitácora financiera cubre las operaciones efectivas de creación y confirmación
atómica de pagos, cancelación, generación o regeneración de recibos y solicitud o
entrega de correos. Los eventos se conservan como registros *append-only* y sus
snapshots y metadatos se limitan a listas blancas que excluyen secretos y rutas
privadas.

## Límite de alcance: edición de pagos

No existe actualmente un flujo funcional de edición de pagos.
`AuditoriaPagoService::registrarModificacion()` conserva el contrato de auditoría
para una futura operación autorizada, pero EPIC 14 no crea dicha operación. La
prueba automatizada de ese método verifica exclusivamente el contrato preparado y
no debe presentarse como prueba de una edición real.

La protección *append-only* se aplica a las operaciones de instancia de Eloquent
(`save`, `update`, `delete` y `deleteOrFail`). Las escrituras directas mediante el
constructor de consultas quedan reservadas para tareas internas controladas (por
ejemplo, limpieza aislada de pruebas); no se añadieron triggers de base de datos
porque su comportamiento y despliegue no serían portables entre SQLite y MySQL.
