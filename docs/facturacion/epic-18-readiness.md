# EPIC 18 — Informe de preparación y puerta fiscal para CFDI 4.0

**Fecha de auditoría:** 2026-09-29<br>
**Rama auditada:** `work`<br>
**Resultado de la puerta:** **BLOQUEADA**

## 1. Conclusión ejecutiva

No hay en el repositorio una aprobación trazable de las siete decisiones fiscales y operativas necesarias para emitir CFDI 4.0. Tampoco hay una selección autorizada de PAC, datos aprobados del emisor o sucursales, certificados, política PUE/PPD, reglas de impuestos/redondeo, catálogo fiscal aprobado por concepto ni definición sobre IEDU/RVOE.

Por ello, esta auditoría se detiene antes de crear migraciones, entidades CFDI, generadores XML/PDF, integración PAC, trabajos de timbrado o cambios funcionales. **EPIC 18 no está implementada ni lista para producción.** No se efectuó ni simuló ningún timbrado y no se añadieron secretos ni datos fiscales de ejemplo.

Los campos fiscales que ya existen son capacidad técnica y datos capturados, no evidencia de que el contador haya aprobado su valor o su uso para emitir. En particular, `nivel_educativo` y `rvoe` hoy son obligatorios en el perfil fiscal, pero eso no demuestra que el complemento IEDU aplique; las claves SAT y la tasa de IVA de los conceptos son opcionales y tampoco constituyen un catálogo aprobado.

## 2. Alcance y evidencia revisados

Se inspeccionaron el estado y el historial de Git, `composer.json`, `.env.example`, configuración, rutas, permisos, migraciones, modelos, servicios, controladores, componentes Livewire, notificaciones, almacenamiento, documentación y pruebas relacionadas. No se encontró una propuesta o backlog de EPIC 17/18 en archivos Markdown; el estado vigente se reconstruyó desde el código y los commits de la rama, sin asumir que un ZIP anterior representa el `HEAD`.

### Componentes existentes y reutilizables

| Componente | Estado comprobado en el código | Utilidad futura / límite |
|---|---|---|
| Perfiles fiscales | `perfiles_fiscales` conserva RFC, razón social, CP, régimen, uso CFDI, correo, relación, CURP, nivel y RVOE; admite activo/predeterminado. El servicio valida, normaliza y serializa un snapshot versión 1. | Base reutilizable para receptor. Sus datos y reglas todavía requieren validación fiscal; no son un CFDI. |
| Auditoría del perfil | Registro append-only de creación, actualización, activación, desactivación y cambio de predeterminado, sin copiar valores sensibles en `campos_modificados`. | Reutilizable para trazabilidad; habrá que aprobar retención, visibilidad y enmascaramiento antes de auditar CFDI. |
| Solicitud y snapshot | Un pago puede marcar `solicita_factura`; al confirmarlo exige un perfil activo del mismo alumno y guarda su FK y snapshot. El flujo vuelve a validar el perfil dentro de la transacción. | Buen punto de entrada para una solicitud futura. No equivale a emisión, timbrado ni promesa de fecha de factura. |
| Pagos | Estado separado (`borrador`, `confirmado`, `cancelado`, `reembolsado`), monto y tipo de cambio decimales, forma de pago, referencias, actor y fechas. Los pagos con efecto financiero no se eliminan. | Deben permanecer separados del CFDI. La relación fiscal exacta depende de PUE/PPD, complementos, cancelaciones y reembolsos aprobados. |
| Aplicaciones | Tabla separada pago–cargo con importes y saldos `DECIMAL(12,2)` y unicidad por pago/cargo. La confirmación usa transacción y bloqueos. | Base de conciliación, sin inferir impuestos ni el documento fiscal que corresponde. |
| Conceptos y cargos | Los conceptos ya ofrecen campos opcionales de claves SAT, objeto de impuesto y tasa; los cargos guardan subtotal, descuento, recargo, impuestos, total y saldo en `DECIMAL`. | Es un esquema preliminar, no un catálogo fiscal aprobado. No debe alimentar XML hasta confirmar semántica, vigencia y redondeo. |
| Cancelación/reembolso | Existen estados, motivo, actor, fechas, reversión controlada y auditoría de pagos. | No representa una cancelación SAT ni define sustitución/relación de CFDI o nota de crédito. |
| Recibo interno | `comprobantes_pago` tiene relación única con pago, folio propio, hash, almacenamiento local privado, descarga autorizada y generación idempotente. PDF y correos dicen: “Comprobante interno de pago. Este documento no constituye un CFDI.” | Debe conservarse sin renombrarlo ni mezclarlo con CFDI. Patrones de privacidad e idempotencia son reutilizables. |
| Notificaciones | Entrega de recibo y aviso de cancelación mediante cola, con registro y pruebas de deduplicación. | Podría inspirar el correo de CFDI, pero no debe reutilizar adjuntos o destinatarios sin una política aprobada de datos fiscales. |
| Autorización financiera | Matriz central de roles y gates en rutas y acciones: administración/caja/contabilidad tienen permisos diferenciados para registrar, consultar documentos, auditar y gestionar perfiles. | Punto de partida; faltan permisos expresos para solicitar, emitir, reintentar, cancelar y descargar CFDI/XML. |

## 3. Evaluación de la puerta fiscal

“Confirmado” exige una decisión explícita y aprobada para Côté France. La mera presencia de una columna, un valor de prueba o una clave SAT en código no alcanza ese estándar.

| # | Requisito | Estado | Evidencia concreta | Riesgo de decidir por suposición |
|---:|---|---|---|---|
| 1 | RVOE e IEDU: aplicabilidad, nivel, clave/RVOE y datos | **Pendiente** | `PerfilFiscal::snapshot()` incluye `nivel_educativo` y `rvoe`; `PerfilFiscalService::reglas()` los exige. No hay documento que autorice IEDU, defina niveles válidos o relacione RVOE con plantel/servicio. | Emitir un complemento improcedente u omitir uno requerido; asociar una autorización o nivel incorrectos. |
| 2 | IVA por concepto, tasas, exenciones y redondeos | **Pendiente** | `conceptos_cobro` tiene `objeto_impuesto_sat` y `tasa_iva` opcionales; `cargos` tiene `impuestos DECIMAL(12,2)`. No existe una matriz aprobada ni una regla documentada de base, exención, traslado o redondeo. | Impuestos y totales fiscales erróneos; diferencias entre cargo, CFDI, pago y contabilidad. |
| 3 | PUE/PPD, momento de CFDI y Complemento de Pagos 2.0 | **Pendiente** | Los pagos guardan `forma_pago_sat`, solicitud y snapshot, pero no hay entidad CFDI, método de pago fiscal, parcialidades, saldo fiscal ni complemento. Ningún documento fija cuándo emitir. | Duplicar comprobantes, usar método incorrecto o dejar parcialidades sin complemento/conciliación. |
| 4 | PAC, ambientes, autenticación, costos, límites, retención y cancelación | **Pendiente** | `composer.json`, `.env.example` y `config/services.php` no declaran proveedor, credenciales ni endpoints PAC; no existe interfaz PAC. | Dependencia no autorizada, exposición de secretos, costos inesperados o reintentos que dupliquen timbrados. |
| 5 | Catálogo fiscal por concepto | **Pendiente** | La migración de `conceptos_cobro` proporciona campos opcionales y el CRUD valida principalmente forma/longitud; no se halló catálogo firmado/aprobado ni vigencias. | Claves, unidad u objeto de impuesto incorrectos; emitir conceptos incompletos. |
| 6 | Emisor y sucursales: identidad, régimen, CP, certificados y custodia | **Pendiente** | No hay entidad/configuración aprobada de emisor o sucursal ni mecanismo de certificados. `.env.example` no contiene variables PAC/fiscales y no se encontraron certificados versionados. | Facturar con identidad/lugar equivocados o comprometer llave privada y responsabilidades de custodia. |
| 7 | Política: individual/global, posterior al cobro, notas de crédito, cancelación y relación con pagos/reembolsos | **Pendiente** | El sistema distingue pago, aplicaciones, cancelación/reembolso y recibo interno, pero no hay política CFDI documentada ni modelos fiscales. | Estados incompatibles, cancelaciones incompletas, dobles efectos o conciliación imposible. |

**Contradicciones encontradas:** ninguna decisión fiscal aprobada entra en contradicción con otra porque no se encontró ninguna aprobación trazable. Sí existe una **ambigüedad que debe resolverse**: el perfil exige RVOE/nivel para toda solicitud, mientras la aplicabilidad de IEDU continúa sin definir. Esto no autoriza ni impide por sí solo el complemento.

## 4. Preguntas bloqueantes para Côté France y su contador

Las respuestas deben quedar fechadas, versionadas y aprobadas por responsables identificables; no deben incluir contraseñas ni llaves privadas.

### RVOE / IEDU

1. ¿El complemento IEDU aplica a todos, algunos o ningún concepto/plantel/nivel impartido?
2. Para cada caso aplicable, ¿cuál es el nivel educativo, RVOE/claves autorizadas, CURP y restantes datos obligatorios, y de qué fuente oficial interna se obtienen?
3. ¿Los campos pertenecen al concepto, curso/grupo, plantel o receptor? ¿Qué debe ocurrir si faltan o están vencidos?

### Impuestos y conceptos

4. Por cada concepto cobrable, ¿cuáles son clave de producto/servicio, clave de unidad, objeto de impuesto, descripción fiscal, IVA trasladado/exento/no objeto y cualquier retención?
5. ¿Cómo se determinan base, descuentos, recargos e impuestos, y en qué nivel se redondea (concepto, impuesto, documento y moneda)?
6. ¿Quién aprueba cambios, desde qué fecha rigen y cómo se versiona el catálogo sin modificar comprobantes históricos?

### Emisión, pagos y correcciones

7. ¿Qué operaciones son PUE y cuáles PPD? ¿Se permiten ambas y cuál es la regla verificable para decidir?
8. ¿En qué evento y plazo se genera el CFDI: cargo, solicitud, confirmación del pago, cierre u otro evento autorizado?
9. Para PPD, ¿cómo se controlan parcialidad, saldo anterior, importe pagado, saldo insoluto, moneda/tipo de cambio y Complemento de Pagos 2.0?
10. ¿Cuándo procede CFDI individual o global, y cómo se excluyen o sustituyen operaciones ya facturadas?
11. ¿Cómo se tratan cancelación, sustitución, nota de crédito, devolución/reembolso, pago aplicado a varios cargos y cargo pagado con varios pagos?

### Emisor, sucursales y PAC

12. ¿Cuál es la razón social, RFC, régimen y código postal/lugar de expedición aprobados por emisor y sucursal, y quién mantiene esos datos?
13. ¿Qué PAC está autorizado contractualmente, qué ambientes y API ofrece, cómo autentica, y cuáles son costos, límites, SLA, retención, consulta y cancelación?
14. ¿Quién custodia certificados y contraseñas, dónde se almacenan cifrados fuera del repositorio, cómo se autoriza su uso y cuál es el procedimiento de rotación/revocación?
15. ¿Qué plazo de retención y quiénes pueden consultar/descargar XML, PDF, acuses y trazas técnicas minimizadas?
16. Ante timeout o respuesta ambigua, ¿qué mecanismo de consulta por idempotencia/UUID confirma el resultado antes de reintentar?

## 5. Nivel de decisión requerido

| Ámbito | Decisiones que deben aprobarse |
|---|---|
| **Por concepto (versionado y con vigencia)** | Claves SAT de producto/servicio y unidad; descripción fiscal; objeto y tratamiento de impuestos; tasa/exención/retención; reglas de base y redondeo; datos IEDU cuando su naturaleza sea propia del servicio. |
| **Por sucursal/plantel** | Lugar de expedición/CP; datos y RVOE aplicables; series/folios internos si se usan; emisor habilitado; responsables operativos; ambiente y certificados autorizados. |
| **General/institucional** | Identidad y régimen del emisor; política PUE/PPD; momento de emisión; individual/global; complementos; cancelación, sustitución, notas de crédito y reembolsos; PAC y SLA; custodia/rotación; retención, privacidad, auditoría y matriz de permisos. |
| **Por operación (derivada de reglas aprobadas, no libre elección)** | Receptor/snapshot, uso CFDI permitido, forma y método de pago, moneda/tipo de cambio, conceptos y aplicaciones, relación con CFDI previos, parcialidad y condición de factura global. |

## 6. Mapa de alcance de EPIC 18

Todo el mapa permanece **bloqueado para implementación** hasta contar con las aprobaciones correspondientes.

| Área de backlog | Base disponible | Decisión/entregable pendiente antes de programar |
|---|---|---|
| PAC | Ninguna integración | Selección y contrato autorizados; sandbox; autenticación; SLA; límites; consulta/cancelación. |
| Catálogos SAT | Campos opcionales en conceptos y perfiles | Fuente, sincronización/versión, catálogo por concepto aprobado y validación por vigencia. |
| Entidad CFDI | Ninguna; deliberadamente separada de recibos | Modelo de estados, identificador lógico único, UUID, emisor/receptor, importes/impuestos, ambiente/PAC, fechas y relaciones. |
| Generación | Snapshot de receptor y datos de pago/cargo | Reglas fiscales aprobadas, cadena/original/sello y validación de esquema en sandbox. |
| Timbrado | Ninguno | Interfaz propia PAC, máquina de estados, timeout y resultado indeterminado, idempotencia y consulta antes de retry. |
| XML/PDF | Patrón privado del recibo interno | Disco/ruta privada propios, cifrado si procede, hashes, retención, render autorizado, descarga con política y auditoría. |
| Consulta de estado | Ninguna | Semántica PAC/SAT, frecuencia, autoridad del estado y conciliación. |
| Correo | Cola de recibos internos | Destinatarios, contenido, adjuntos/enlaces, caducidad y minimización aprobados. |
| Cancelación | Cancelación interna de pago | Motivos SAT, aceptación, sustitución, acuses, permisos y relación sin alterar el pago histórico. |
| PUE/PPD | Forma de pago en pago; sin método CFDI | Matriz de decisión y momento de emisión. |
| Complemento de Pagos 2.0 | Aplicaciones pago–cargo | Modelo fiscal de documentos relacionados, parcialidad/saldos, moneda y deduplicación del evento. |
| IEDU condicional | Nivel/RVOE/CURP capturables | Dictamen de aplicabilidad y mapeo aprobado por servicio/plantel. |
| Errores y reintentos | Patrones transaccionales en pagos/recibos | Estados explícitos, outbox/job, backoff, consulta por idempotencia, alertas y recuperación manual autorizada. |
| Conciliación | Pagos, aplicaciones y reportes existentes | Reglas CFDI↔pago↔cargo↔reembolso, excepciones, responsables y reportes. |

## 7. Orden propuesto tras levantar la puerta

1. **Registrar decisiones aprobadas y casos de aceptación:** versionar matriz fiscal, política de emisión/cancelación, emisor/sucursales, matriz de acceso y contrato técnico del PAC sin secretos.
2. **Cerrar diseño y amenazas:** diagramar estados y relaciones separadas; idempotencia; respuesta ambigua; minimización/cifrado; custodia y rotación; retención; concurrencia MySQL y recuperación.
3. **Catálogos y configuración segura:** importar/validar catálogos con vigencia; aprobar cada concepto; configurar emisor/sucursal y referencias de secretos en plataforma, nunca su material en Git.
4. **Primera etapa acotada:** crear solo el modelo persistente y flujo de solicitud/preparación que haya sido aprobado, con migraciones aditivas y reversibles, restricciones e idempotencia; conservar intactos pago y recibo interno.
5. **Adaptador PAC en sandbox:** interfaz propia, contrato falso para pruebas, cliente del PAC autorizado con timeouts y estados concluyentes/indeterminados; consulta antes de reintentar.
6. **Generación y validación:** XML CFDI 4.0 y, solo si corresponde, IEDU/PUE/PPD/Complemento 2.0; validar esquemas, aritmética decimal y casos suministrados por el contador.
7. **Documentos, autorización y entrega:** almacenamiento privado XML/PDF/acuses, políticas de descarga, auditoría minimizada, correo seguro y cancelación aprobada.
8. **Conciliación y liberación:** pruebas concurrentes reales en MySQL de pruebas, replay/timeout, recuperación, conciliación, revisión del contador y piloto integral en sandbox antes de considerar producción.

Cada etapa debe poder desplegarse sin activar timbrado. La habilitación de producción requiere una autorización independiente y verificable.

## 8. Pruebas y verificaciones de esta auditoría

Las pruebas específicas se intentaron **antes de editar**. El entorno no contenía `vendor/autoload.php`.

| Momento | Comando | Resultado exacto |
|---|---|---|
| Antes de cambios | `php artisan test --testsuite=Feature --filter='(PerfilFiscal|RegistrarPago|AplicarPago|CancelarPago|ComprobantePago|ReenvioRecibo|PagoRecibidoNotification|PagoCanceladoNotification|FinancialRoleSecurity|ArchivoPagoDetalleCancelacion|PagosMigration|PagoAplicacionesMigration)'` | **No ejecutada (0 pruebas):** código de salida 255; `vendor/autoload.php` no existe. |
| Antes de cambios | `composer install --no-interaction --prefer-dist` | **Falló:** código 2; PHP del entorno es 8.5.7-dev y el lock limita `nette/schema` a PHP 8.1–8.3 y `nette/utils` a `<8.4`. No se actualizó el lock. |
| Antes de cambios | `composer install --no-interaction --prefer-dist --ignore-platform-req=php` | **Falló/interrumpido:** las descargas desde GitHub recibieron `CONNECT tunnel failed, response 403`; no fue posible construir `vendor`. |

En consecuencia quedaron **omitidas por limitación del entorno**, no aprobadas: las pruebas funcionales de perfiles, pagos, aplicaciones, cancelaciones, recibos, notificaciones, autorización y migraciones, incluidas sus variantes de concurrencia. El único cambio de esta entrega es documental y no modifica el comportamiento ejecutable.

## 9. Criterio para reabrir implementación

La puerta puede reevaluarse cuando estén disponibles en el repositorio o en un registro de decisión enlazado:

- respuestas aprobadas a los siete bloques de la sección 3 y a las preguntas aplicables;
- catálogo fiscal versionado por concepto y sucursal;
- PAC autorizado y contrato técnico de sandbox, sin secretos en documentación;
- política de seguridad/custodia y responsables;
- casos esperados validados por el contador para PUE, PPD, impuestos, redondeo, cancelación y, si aplica, IEDU;
- un entorno de pruebas compatible que permita ejecutar las suites existentes y MySQL aislado para concurrencia.

**Siguiente paso concreto:** Côté France debe convocar al contador y a los responsables de facturación/seguridad para completar y aprobar por escrito la matriz de la sección 3, empezando por aplicabilidad IEDU/RVOE, matriz IVA por concepto y política PUE/PPD; después debe autorizar un PAC y proporcionar únicamente su documentación y acceso de sandbox mediante el gestor seguro de secretos de la plataforma.
