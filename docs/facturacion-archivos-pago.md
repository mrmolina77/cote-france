# Comprobantes bancarios privados (EPIC 9)

Los comprobantes definitivos se almacenan exclusivamente en el disco `local`, bajo
`storage/app/archivos_pago`. Sus nombres físicos son aleatorios y sólo la ruta de
descarga autenticada entrega los bytes. Los temporales de Livewire también se fijan
al disco `local`, bajo `storage/app/livewire-tmp`.

La validación efectiva es compartida por la preparación, la confirmación y la
persistencia: se inspecciona el MIME del contenido, se exige una extensión coherente,
un tamaño entre 1 byte y 10240 KB y un temporal todavía válido. La revisión de la
interfaz incluye un SHA-256 del contenido, no sólo nombre y tamaño.

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
árbol de páginas, tabla `xref`, `trailer` y `startxref`; el caso del límite exacto añade
únicamente espacio en blanco permitido después de `%%EOF`. Las imágenes de prueba son
PNG/JPEG decodificables. Su estructura se comprueba en la suite sin incorporar una
dependencia de producción para generarlos.

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
