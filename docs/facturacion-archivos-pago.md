# Comprobantes bancarios privados (EPIC 9)

Los comprobantes definitivos se almacenan exclusivamente en el disco `local`, bajo
`storage/app/archivos_pago`. Sus nombres físicos son aleatorios y sólo la ruta de
descarga autenticada entrega los bytes. Los temporales de Livewire también se fijan
al disco `local`, bajo `storage/app/livewire-tmp`.

La validación efectiva es compartida por la preparación, la confirmación y la
persistencia: se inspecciona el MIME del contenido, se exige una extensión coherente,
un tamaño entre 1 byte y 10240 KB y un temporal todavía válido. Además, los PDF deben
tener cabecera, cierre, `startxref` y tabla `xref` coherentes, y las imágenes deben
poder decodificarse con dimensiones positivas. La revisión de la
interfaz incluye un SHA-256 del contenido, no sólo nombre y tamaño.

El nombre presentado al descargar se neutraliza sin intervenir en la ruta física. Se
preserva la extensión validada y el límite se aplica en bytes UTF-8 (no solamente en
caracteres), para respetar el tamaño de la columna también con nombres Unicode.

La escritura física ocurre dentro del flujo coordinado de confirmación. La ruta
aleatoria se comunica al coordinador antes de abrir el stream. Así, un `put()` falso,
una escritura parcial, un error de `exists()`, del registro de metadatos o uno posterior
en la transacción intentan eliminar exclusivamente esa ruta. Los streams se cierran en
todos los caminos y un error de limpieza se reporta sin sustituir la excepción original.
La base de datos y el filesystem no ofrecen una transacción distribuida: una
terminación abrupta del proceso entre ambas operaciones todavía podría dejar un archivo
huérfano sin metadatos; nunca produce un pago confirmado sin su registro de archivo en
una ejecución que alcance el manejo de errores.

Los PDF positivos de la suite son documentos mínimos reproducibles con catálogo,
árbol de páginas, tabla `xref`, `trailer` y `startxref`. El documento de exactamente
10240 KB coloca el relleno dentro de un `stream`, declara su longitud, recalcula todos
los offsets y termina físicamente en `%%EOF`; existe otro documento estructuralmente
válido de un byte adicional para aislar el rechazo por tamaño. Los tamaños se consultan
en los archivos temporales reales, no en metadatos simulados de `UploadedFile::fake()`.

Los fixtures pequeños están en `tests/Fixtures/archivos_pago`, no contienen datos
personales y se comparten entre las pruebas del servicio, Livewire y métodos de pago.
El JPEG defectuoso anterior fue sustituido por una imagen RGB 2 × 2 generada con GD.
PNG y JPEG se abren mediante sus decodificadores GD (`imagecreatefrompng` y
`imagecreatefromjpeg`), de modo que la evidencia no se limita a `finfo` o
`getimagesize()`. Ambos se versionan como texto base64 y se decodifican estrictamente
a temporales físicos, evitando parches binarios incompatibles con la herramienta de
revisión. El PDF también se versiona como `comprobante.pdf.base64`; se decodifica antes de usarlo y una prueba verifica que `startxref` apunta exactamente a `xref`, que cada entrada apunta al objeto correspondiente y que `%%EOF` cierra los bytes definitivos.
Estos controles no agregan Python ni dependencias de producción a PHPUnit y los
temporales creados por los helpers se eliminan en `tearDown()`.

## Estado de verificación de este correctivo (20/09/2026)

Se ejecutaron las comprobaciones autónomas de sintaxis PHP, `composer validate --strict` y la validación reproducible de los bytes PDF (base64 estricto, offsets, `startxref` y cierre). PHP está disponible, pero `vendor/` no está instalado; por ello Artisan/PHPUnit quedan pendientes y no se presentan resultados históricos como evidencia nueva. La instalación del lock fue impedida primero por PHP 8.5 y, al ignorar sólo ese requisito de plataforma, por respuestas 403 de la red. Tampoco hay un lector PDF estricto (`qpdf`, `pdfinfo` o `mutool`) instalado, así que esa apertura adicional queda documentada como pendiente; PHPUnit no depende de ella.

## Comprobación manual en desarrollo

1. Ejecutar `php artisan migrate` sobre la base de desarrollo.
2. Registrar un pago con un PDF, JPEG o PNG real y abrir su detalle.
3. Descargar el comprobante y verificar que otro usuario autenticado sin el permiso
   `manage-pagos` obtiene 403.
4. Cancelar el pago y comprobar que el detalle cancelado conserva la descarga.
5. Confirmar que el archivo existe en `storage/app/archivos_pago`, que no existe bajo
   `public/` ni `storage/app/public`, y que `public/storage` (si existe) apunta solamente
   a `storage/app/public`. La respuesta 404 de Laravel no sustituye esta inspección de
   la configuración real de Nginx/Apache y sus alias estáticos.
