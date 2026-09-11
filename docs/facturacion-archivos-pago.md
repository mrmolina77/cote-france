# Comprobantes bancarios privados (EPIC 9)

Los comprobantes definitivos se almacenan exclusivamente en el disco `local`, bajo
`storage/app/archivos_pago`. Sus nombres físicos son aleatorios y sólo la ruta de
descarga autenticada entrega los bytes. Los temporales de Livewire también se fijan
al disco `local`, bajo `storage/app/livewire-tmp`.

La escritura física ocurre dentro del flujo coordinado de confirmación. Si falla la
transacción después de escribir, la aplicación intenta eliminar únicamente el archivo
nuevo. La base de datos y el filesystem no ofrecen una transacción distribuida: una
terminación abrupta del proceso entre ambas operaciones todavía podría dejar un archivo
huérfano sin metadatos; nunca produce un pago confirmado sin su registro de archivo en
una ejecución que alcance el manejo de errores.

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
