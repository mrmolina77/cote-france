# Concurrencia de perfiles fiscales (EPIC 17)

La prueba usa exclusivamente una base MySQL aislada cuyo nombre contenga `test`. Se ejecuta con:

```bash
EPIC17_MYSQL_HOST=127.0.0.1 EPIC17_MYSQL_PORT=3306 \
EPIC17_MYSQL_DATABASE=cote_france_epic17_test EPIC17_MYSQL_USERNAME=root \
EPIC17_MYSQL_PASSWORD=secret \
php artisan test --filter=test_mysql_two_processes_leave_exactly_one_default_profile_for_student
```

La prueba se omite si falta alguna variable. Nunca debe apuntarse a desarrollo o producción: prepara y elimina
tablas dentro de la base aislada indicada.
