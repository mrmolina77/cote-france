# Concurrencia de notificaciones de pago (MySQL/MariaDB)

La prueba usa **dos procesos PHP independientes**, la cola `database` y una base desechable. El nombre de la base debe contener `test`; la prueba ejecuta `migrate:fresh` y nunca debe apuntarse a una base operativa.

```bash
EPIC13_MYSQL_HOST=127.0.0.1 EPIC13_MYSQL_PORT=3306 \
EPIC13_MYSQL_DATABASE=cote_france_epic13_test EPIC13_MYSQL_USERNAME=root \
EPIC13_MYSQL_PASSWORD=secret \
php artisan test tests/Feature/NotificacionesPagoConcurrencyTest.php \
  --filter=test_mysql_two_real_processes_compete_for_initial_and_same_resend_token
```

Las cinco variables son obligatorias. No se almacenan credenciales en el repositorio. La conexión de cola utilizada por los procesos es `database` y comparte esa misma base aislada.
