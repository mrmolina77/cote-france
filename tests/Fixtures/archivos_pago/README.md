# Fixtures de comprobantes

Los tres archivos son mínimos, no contienen datos personales y se conservan como
bytes reales (no como tamaños simulados de `UploadedFile::fake()`).

* `comprobante.pdf.base64` contiene en base64 un PDF que se generó de forma determinista con catálogo, árbol de páginas,
  tabla `xref`, `trailer` y un `startxref` que apunta al byte inicial de la tabla.
* `comprobante.png.base64` y `comprobante.jpeg.base64` representan imágenes RGB de
  2 × 2 píxeles generadas con GD. Se guardan como texto base64 para que la revisión y
  el transporte del cambio no dependan de soporte para parches binarios. El helper decodifica los tres fixtures `.base64`
  de forma estricta a archivos temporales reales antes de cada prueba; la
  prueba abre y decodifica el archivo completo con GD, además de pasar la inspección de
  contenido del servicio.

No se necesita Python ni una dependencia de producción para ejecutar PHPUnit. Las
imágenes se verificaron con el decodificador completo de GD y PHPUnit conserva para el
PDF comprobaciones estructurales reproducibles. La apertura adicional con Pillow y un
lector PDF se mantiene como comprobación manual cuando esas herramientas estén
disponibles.
