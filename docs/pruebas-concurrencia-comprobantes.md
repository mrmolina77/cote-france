# Prueba de concurrencia de folios de comprobantes

La prueba de concurrencia usa conexiones y procesos independientes contra una
base MySQL/MariaDB dedicada. No se sustituye por SQLite ni por llamadas
secuenciales. Para ejecutarla:

```bash
EPIC12_MYSQL_HOST=127.0.0.1 EPIC12_MYSQL_PORT=3306 \
EPIC12_MYSQL_DATABASE=cote_france_epic12 EPIC12_MYSQL_USERNAME=root \
EPIC12_MYSQL_PASSWORD=secret \
php artisan test tests/Feature/GeneradorFolioComprobanteConcurrencyTest.php
```

La base indicada debe ser desechable y el usuario debe poder crear y eliminar
tablas. Si falta cualquiera de esas variables, PHPUnit marca la integración
como omitida de forma explícita.
