# EPIC 15 — Roles y seguridad financiera

## Política implementada

Los Gates se resuelven en una sola matriz (`FinancialPermissions`) a partir de `users.roles_id → roles.roles_codigo`; un rol ausente o desconocido se deniega.

| Gate | admin | caja | contabilidad | venta |
| --- | --- | --- | --- | --- |
| `view-financial` (cobranza, pagos y estado de cuenta) | Sí | Sí | Sí | Sí |
| `register-payments` / `manage-pagos` | Sí | Sí | No | No |
| `view-payment-documents` | Sí | Sí | Sí | No |
| `audit-payments` | Sí | No | Sí | No |
| `cancel-pagos` | Sí | No | Sí | No |
| `view-financial-enrollments` | Sí | No | Sí | No |
| `manage-inscripciones`, `manage-cargos`, `manage-conceptos-cobro`, `manage-metodos-pago` | Sí | No | No | No |

`profe`, `alum`, rol nulo y códigos no reconocidos no reciben permisos financieros. Cancelar requiere Admin/Contabilidad, el motivo obligatorio existente y una segunda autorización dentro del servicio transaccional. Contabilidad consulta inscripciones, pero las acciones de edición continúan protegidas por `manage-inscripciones`.

## Alta segura en una base existente

Ejecutar primero las migraciones (la ampliación no destructiva de `roles_codigo` permite almacenar `contabilidad`) y después el seeder existente:

```bash
php artisan migrate --force
php artisan db:seed --class=RoleSeeder --force
```

El seeder usa el código como clave natural: crea únicamente códigos ausentes, no cambia IDs, nombres existentes ni `users.roles_id`, y puede repetirse.

## Verificación

Línea base del 23-09-2026: la suite financiera no pudo iniciar porque el checkout no contenía `vendor/autoload.php`. Tras el cambio se ejecutaron las comprobaciones que se enumeran en la entrega; no se documenta como aprobada ninguna suite que no haya llegado a ejecutarse.

- `composer validate --strict`: aprobado (`composer.json is valid`).
- `git diff --check`: aprobado, sin errores.
- `php -l` sobre todos los PHP modificados: aprobado.
- Suite Feature financiera solicitada: omitida por limitación del entorno; `vendor/autoload.php` no existe. `composer install --ignore-platform-req=php` tampoco pudo completarse porque el proxy devolvió HTTP 403 al descargar dependencias desde GitHub.
