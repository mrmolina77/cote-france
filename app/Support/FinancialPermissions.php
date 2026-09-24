<?php

namespace App\Support;

use App\Models\User;

final class FinancialPermissions
{
    public const VIEW = 'view-financial';
    public const REGISTER_PAYMENTS = 'register-payments';
    public const VIEW_PAYMENT_DOCUMENTS = 'view-payment-documents';
    public const AUDIT = 'audit-payments';
    public const VIEW_ENROLLMENTS = 'view-financial-enrollments';
    public const VIEW_REPORTS = 'view-financial-reports';
    public const EXPORT_REPORTS = 'export-financial-reports';
    public const CLOSE_CASH = 'close-cash';

    private const ROLES = [
        self::VIEW => ['admin', 'caja', 'contabilidad', 'venta'],
        self::REGISTER_PAYMENTS => ['admin', 'caja'],
        self::VIEW_PAYMENT_DOCUMENTS => ['admin', 'caja', 'contabilidad'],
        self::AUDIT => ['admin', 'contabilidad'],
        self::VIEW_ENROLLMENTS => ['admin', 'contabilidad'],
        self::VIEW_REPORTS => ['admin', 'caja', 'contabilidad'],
        self::EXPORT_REPORTS => ['admin', 'contabilidad'],
        self::CLOSE_CASH => ['admin', 'caja'],
        'manage-inscripciones' => ['admin'],
        'manage-conceptos-cobro' => ['admin'],
        'manage-metodos-pago' => ['admin'],
        'manage-cargos' => ['admin'],
        'manage-pagos' => ['admin', 'caja'],
        'cancel-pagos' => ['admin', 'contabilidad'],
    ];

    public static function allows(User $user, string $permission): bool
    {
        $code = optional($user->role)->roles_codigo;

        return is_string($code) && in_array($code, self::ROLES[$permission] ?? [], true);
    }

    public static function names(): array
    {
        return array_keys(self::ROLES);
    }
}
