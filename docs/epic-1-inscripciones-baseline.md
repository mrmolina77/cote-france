# EPIC 1: baseline técnico de inscripciones

## Decisiones de estabilización

- `CreateInscripciones` conserva una única operación persistente, por lo que no se añadió una transacción. En EPIC 2, la creación de la inscripción y cualquier operación económica relacionada deberán trasladarse juntas a un servicio transaccional.
- La prevención defensiva de duplicados reproduce la regla actual de la interfaz: un prospecto que ya aparece en `inscripciones` no puede volver a inscribirse desde `CreateInscripciones`. No se añadió un índice `UNIQUE` porque la política de reinscripciones aún no está definida.
- `CreateProspect` conserva su transacción y su creación de inscripción existente. No se incorporaron reglas globales al modelo que pudieran romper ese segundo punto de entrada.

## Hallazgos deliberadamente no modificados

- Aunque `inscripciones`, `prospectos`, `cursos` y `grupos` tienen `deleted_at`, sus modelos no usan `SoftDeletes`; la eliminación física actual se conserva. Antes de introducir datos financieros debe definirse una política de conservación y cancelación y migrarse este comportamiento de forma explícita.
- La configuración actual usa colas síncronas y no existe una tabla `jobs`. Este baseline no cambia la infraestructura de colas.
- La aplicación usa UTC cuando `APP_TIMEZONE` no está definido. Este baseline no cambia la zona horaria; debe definirse antes de registrar operaciones financieras.
- No se añadieron, eliminaron ni modificaron índices o restricciones únicas.
