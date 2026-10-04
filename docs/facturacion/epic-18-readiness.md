# EPIC 18 — Informe de preparación y puerta fiscal para CFDI 4.0

**Fecha de actualización y consulta documental:** 2026-10-04<br>
**Rama de proyecto:** `fracturacion`<br>
**Resultado de la puerta:** **BLOQUEADA**

> Este informe separa lo dicho en la transcripción, las verificaciones documentales y las decisiones que aún corresponden a Côté France y a su contador. La revisión de fuentes es informativa y **no constituye asesoría fiscal**.

## 1. Conclusión ejecutiva

La nueva transcripción aporta propuestas para capturar datos del receptor, configurar conceptos y evaluar Facturapi, pero no contiene aprobaciones contables ni decisiones fiscales definitivas. No confirma los datos fiscales o sucursales del emisor Côté France, RVOE/IEDU, catálogo SAT por concepto, IVA, Uso CFDI, forma de pago, política PUE/PPD, complemento de pagos ni cancelaciones.

Por tanto, EPIC 18 sigue bloqueada. Este cambio es exclusivamente documental: no implementa CFDI, timbrado, Complemento para Recepción de Pagos, integración con PAC ni credenciales. Los ejemplos del Loom/transcripción no deben convertirse en reglas de Côté France.

## 2. Clasificación de la evidencia nueva

### 2.1 Confirmado por evidencia de la transcripción

- El cliente puede entregar su **Constancia de Situación Fiscal (CSF)** para solicitar factura.
- El hablante no sabe explicar el punto relativo a complementos. La transcripción, por sí sola, no define si aplica IEDU ni el Complemento para Recepción de Pagos.
- La transcripción **no confirma** RFC, razón social, régimen fiscal, código postal de expedición ni sucursales del emisor Côté France.
- Tampoco confirma RVOE, claves SAT por concepto, política de cancelaciones, Uso CFDI, formas de pago ni el alcance operativo del complemento de pagos.

### 2.2 Propuestas del hablante (no aprobaciones)

- Capturar manualmente los datos fiscales del cliente o cargar su CSF en PDF para extraerlos.
- Registrar, por servicio/concepto, clave SAT de producto o servicio, unidad, objeto de impuesto e IVA.
- Mostrar PUE o PPD durante la facturación.
- Evaluar Facturapi y su ambiente de pruebas como proveedor/PAC candidato.

La mención verbal de IVA al 16 %, las definiciones verbales de PUE/PPD y cualquier ejemplo de Uso CFDI, forma de pago, clave, unidad u objeto de impuesto son solamente ejemplos o propuestas. **No son decisiones fiscales de Côté France y no deben codificarse como tales.**

### 2.3 Verificado en fuente oficial

Fuentes consultadas el **2026-10-04**:

1. El trámite del SAT sobre la [Constancia de Situación Fiscal](https://www.sat.gob.mx/portal/public/tramites/constancia-de-situacion-fiscal) identifica la constancia como el documento para consultar información del Registro Federal de Contribuyentes. En este flujo, la CSF que entrega el cliente describe al **receptor**; no acredita los datos fiscales, establecimientos o sucursales del **emisor** Côté France.
2. La guía de llenado de CFDI del SAT, accesible desde el [portal del SAT](https://www.sat.gob.mx/), distingue el **MétodoPago**: `PUE`, pago en una sola exhibición, y `PPD`, pago en parcialidades o diferido. **Forma de pago es un dato distinto de método de pago.** Estas definiciones oficiales sustituyen las explicaciones imprecisas de la transcripción.
3. La documentación del SAT del [Complemento para Recepción de Pagos](https://www.sat.gob.mx/portal/public/tramites/complemento-recepcion-de-pagos) contempla el comprobante con complemento al recibir pagos vinculados a una factura emitida con PPD, según corresponda. La operación concreta, parcialidades, saldos, plazos y conciliación con los pagos de la aplicación aún deben diseñarse y aprobarse.
4. El artículo 15 de la [Ley del Impuesto al Valor Agregado](https://www.diputados.gob.mx/LeyesBiblio/pdf/LIVA.pdf), entre otros supuestos y condiciones legales, contempla servicios de enseñanza con autorización o reconocimiento de validez oficial de estudios. Esto **no permite concluir** que todos los cursos de inglés de Côté France estén exentos: se requiere dictamen del contador sobre la institución y cada servicio relevante.
5. La [documentación oficial de Facturapi](https://docs.facturapi.io/api-es/) describe su API y contempla ambiente y llaves de prueba. Esto respalda evaluarlo en sandbox, pero no demuestra contratación, aprobación definitiva, creación de cuenta ni ejecución de una prueba. Este documento no contiene credenciales.

### 2.4 Pendiente de aprobación de Côté France y su contador

- La identidad fiscal completa del emisor y la configuración por sucursal/plantel.
- La aplicabilidad de RVOE/IEDU y los datos exigibles por servicio educativo.
- El tratamiento de IVA de cada concepto, incluidos los cursos de inglés y cualquier condición para un tratamiento exento.
- Las claves SAT, unidades, objetos de impuesto, descripciones y vigencias por concepto.
- Los criterios operativos para PUE/PPD, forma de pago y pagos posteriores.
- El alcance del Complemento para Recepción de Pagos 2.0.
- Uso CFDI, facturación individual/global y política de cancelación/sustitución.
- La aprobación comercial y técnica del PAC.

## 3. CSF y datos de receptor/emisor

La carga de PDF y extracción de la CSF queda registrada como **propuesta funcional**, sujeta a confirmar:

- viabilidad y exactitud técnica de la extracción;
- revisión/corrección por una persona antes de guardar;
- base y finalidad del tratamiento de datos;
- controles de acceso, almacenamiento, cifrado y auditoría;
- plazo de retención y eliminación tanto del PDF como de los datos extraídos.

La **captura manual** es la alternativa planteada y tampoco elimina la necesidad de validar los datos. No se ha decidido si se conservará el PDF original.

La CSF del cliente alimentaría datos del **receptor**. Los datos del **emisor** —RFC, razón social, régimen fiscal, código postal de expedición, establecimientos y sucursales— requieren documentación propia y aprobación expresa de Côté France; no se deben inferir de la CSF de un alumno o responsable.

## 4. Conceptos, IVA e IEDU/RVOE

Los campos existentes o propuestos para clave SAT, unidad, objeto de impuesto e IVA representan capacidad técnica, no un catálogo fiscal aprobado. Deben permanecer como valores por confirmar y versionarse antes de alimentar un CFDI.

No se adopta 16 % como regla general. Côté France y su contador deben determinar por cada servicio:

- si está gravado, exento o tiene otro tratamiento;
- tasa, base, descuentos, recargos, traslados/retenciones y redondeo;
- si la autorización o RVOE de Côté France y la naturaleza del servicio cumplen las condiciones legales aplicables;
- si corresponde IEDU, con qué datos y para qué concepto, nivel, plantel y receptor.

La sola existencia de campos `nivel_educativo` o `rvoe` no confirma que IEDU aplique. Del mismo modo, la referencia legal a enseñanza no prueba que los cursos de inglés de Côté France cumplan un supuesto de exención.

## 5. PUE, PPD, forma de pago y complemento

- **PUE:** pago en una sola exhibición.
- **PPD:** pago en parcialidades o diferido.
- **Forma de pago:** atributo distinto (por ejemplo, el medio utilizado); sus valores permitidos y el momento en que se conocen deben ser confirmados para el proceso real.
- **Complemento de recepción de pagos:** cuando se reciben pagos vinculados a un CFDI emitido con PPD, se genera el comprobante con Complemento para Recepción de Pagos conforme corresponda a la operación y a las reglas vigentes.

Estas definiciones documentales no resuelven cuándo debe elegir Côté France PUE o PPD. Tampoco autorizan inferir el método a partir de un estado interno ni equiparar los pagos/aplicaciones actuales con un complemento fiscal. Falta definir parcialidad, saldo anterior, importe pagado, saldo insoluto, moneda, tipo de cambio, fechas, agrupación, idempotencia y correcciones.

## 6. Facturapi como candidato

Facturapi queda identificado como **PAC/proveedor candidato recomendado en la transcripción**, no como proveedor contratado, aprobado definitivamente o integrado. Antes de seleccionarlo se requiere evaluar, como mínimo:

- situación y alcance como PAC/proveedor, contrato, costos, límites y SLA;
- API de emisión, consulta, cancelación y recuperación ante respuestas ambiguas;
- separación entre pruebas y producción;
- creación y titularidad de la cuenta;
- custodia, rotación y responsable de llaves/credenciales fuera de Git;
- tratamiento, ubicación, retención y eliminación de datos y documentos.

La documentación contempla ambiente y llaves de prueba; **no se ejecutó ni se afirma haber ejecutado una prueba de Facturapi**.

## 7. Decisiones concretas requeridas

Côté France y su contador deben responder y aprobar, con fecha, responsable y vigencia:

1. **Emisor y sucursales:** RFC, razón social, régimen fiscal, código postal de expedición y sucursales/planteles del emisor.
2. **Autorización educativa:** autorización, RVOE e IEDU aplicables a Côté France y a cada servicio educativo relevante.
3. **IVA:** tratamiento de IVA por cada concepto, incluidas condiciones de gravamen o exención, bases, tasas y redondeo.
4. **Catálogo por concepto:** clave SAT de producto/servicio, unidad y objeto de impuesto, con descripción, vigencia y aprobador.
5. **Cobro y método:** cuándo usar PUE o PPD en el proceso real, forma de pago y manejo de pagos recibidos después de emitir.
6. **Complemento de Pagos 2.0:** alcance y relación exacta con los pagos y aplicaciones que ya registra el sistema, incluidos saldos, parcialidades y conciliación.
7. **Facturapi:** aprobación comercial/técnica, cuenta, sandbox, responsable de credenciales y autorización independiente para producción.
8. **Política de facturación:** Uso CFDI (sin adoptar `G03`, `D10` u otro ejemplo), facturación individual/global y política de cancelaciones, sustituciones, devoluciones y notas de crédito.

Hasta obtener estas respuestas, ningún ejemplo de la transcripción debe convertirse en valor predeterminado ni regla de negocio.

## 8. Estado técnico y alcance bloqueado

El sistema tiene piezas potencialmente reutilizables —perfiles fiscales del receptor, snapshots, pagos, aplicaciones, conceptos, recibos internos, auditoría y autorización—, pero ninguna equivale a un CFDI o a una decisión fiscal. En particular:

- un recibo interno no debe renombrarse ni tratarse como CFDI;
- el estado o la forma de pago guardados actualmente no determinan por sí solos PUE/PPD;
- cancelar o reembolsar un pago interno no cancela un CFDI ante el SAT;
- los importes y campos opcionales existentes no constituyen una matriz fiscal aprobada;
- todavía no existen entidad CFDI, XML fiscal, timbrado, integración PAC, consulta fiscal, cancelación SAT ni Complemento para Recepción de Pagos 2.0.

Quedan fuera del alcance de esta actualización y bloqueados: migraciones, modelos fiscales, generación XML/PDF CFDI, timbrado, PAC, credenciales, correos fiscales, cancelación SAT, complementos, conciliación y activación de producción.

## 9. Evidencia de pruebas

### 9.1 Resultados proporcionados por el usuario

El usuario informó la siguiente ejecución en **Windows, PHP 8.3.8 y Composer 2.8.6**. Se registra como evidencia externa proporcionada por el usuario; **no son pruebas ejecutadas por quien actualiza este informe, no fueron reproducidas en este entorno y no representan cobertura completa de EPIC 18**.

| Ejecución informada | Resultado informado |
|---|---|
| `composer install --no-interaction --prefer-dist` | Terminó correctamente. |
| `git status --short` | Sin cambios. |
| `git diff -- composer.json composer.lock` | Sin cambios en dependencias o lock. |
| 16 archivos de pruebas focalizadas existentes | 251 pruebas aprobadas. |
| `PerfilFiscalConcurrencyTest.php` con MySQL | 1 prueba adicional aprobada. |
| **Total informado** | **252 aprobadas, 0 fallidas.** |

Estas pruebas aportan confianza sobre comportamientos existentes —incluida concurrencia del perfil fiscal—, pero no prueban emisión, XML CFDI 4.0, sellado, timbrado, sandbox de PAC, PUE/PPD, Complemento de Pagos 2.0, IEDU, cancelación SAT ni conciliación fiscal.

### 9.2 Pruebas de integración CFDI pendientes

No existen todavía pruebas de integración CFDI que puedan considerarse ejecutadas. Cuando las decisiones fiscales sean aprobadas y exista implementación, deberán cubrir al menos:

- validación de catálogos, identidad emisor/receptor y esquemas CFDI 4.0;
- aritmética, impuestos y redondeo con casos aprobados por el contador;
- PUE, PPD, parcialidades, saldos y Complemento de Pagos 2.0;
- sandbox del PAC aprobado, idempotencia, timeout, consulta antes de reintento y recuperación;
- XML/PDF, almacenamiento privado, autorizaciones y retención;
- cancelación, sustitución, facturación global, reembolsos y conciliación;
- concurrencia real en MySQL y separación estricta entre pruebas y producción.

## 10. Criterio para reabrir la implementación

La implementación solo debe reabrirse cuando las ocho decisiones de la sección 7 estén documentadas y aprobadas, exista una matriz fiscal versionada por concepto/sucursal, se haya autorizado el PAC y su manejo seguro de secretos, y el contador haya aportado casos de aceptación verificables.

**Siguiente paso:** convocar a Côté France, su contador y responsables operativos/técnicos para cerrar esas decisiones. Hasta entonces, detenerse en este informe: no iniciar CFDI, timbrado, complemento de pagos ni integración con PAC.
