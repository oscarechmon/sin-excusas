<?php

namespace App\Enums;

/**
 * Fuente única de los permisos del sistema.
 *
 * Cada módulo expone el mismo verbo base (view/create/update/delete) más los
 * permisos propios de su negocio. Las rutas se protegen con el middleware
 * `permission:` y las Policies consultan estos mismos valores, de modo que un
 * permiso se declara una sola vez.
 */
enum PermissionName: string
{
    // Clientes
    case CLIENTS_VIEW = 'clients.view';
    case CLIENTS_CREATE = 'clients.create';
    case CLIENTS_UPDATE = 'clients.update';
    case CLIENTS_DELETE = 'clients.delete';

    // Agenda
    case APPOINTMENTS_VIEW = 'appointments.view';
    case APPOINTMENTS_CREATE = 'appointments.create';
    case APPOINTMENTS_UPDATE = 'appointments.update';
    case APPOINTMENTS_DELETE = 'appointments.delete';
    /** Ver la agenda de todo el centro y no solo la propia. */
    case APPOINTMENTS_VIEW_ALL = 'appointments.view_all';

    // Servicios y categorías
    case SERVICES_VIEW = 'services.view';
    case SERVICES_MANAGE = 'services.manage';

    // Personal
    case EMPLOYEES_VIEW = 'employees.view';
    case EMPLOYEES_MANAGE = 'employees.manage';

    // Inventario
    case INVENTORY_VIEW = 'inventory.view';
    case INVENTORY_MANAGE = 'inventory.manage';
    /** Ajustes manuales de stock, separados de la gestión normal. */
    case INVENTORY_ADJUST = 'inventory.adjust';

    // Paquetes
    case PACKAGES_VIEW = 'packages.view';
    case PACKAGES_MANAGE = 'packages.manage';
    case PACKAGES_SELL = 'packages.sell';

    // Atenciones
    case ATTENDANCES_VIEW = 'attendances.view';
    case ATTENDANCES_CREATE = 'attendances.create';

    // Ventas
    case SALES_VIEW = 'sales.view';
    case SALES_CREATE = 'sales.create';
    case SALES_CANCEL = 'sales.cancel';

    // Ventas online (pedidos de la web)
    case ONLINE_SALES_VIEW = 'online_sales.view';
    /** Cambiar el estado de entrega o anular pedidos. */
    case ONLINE_SALES_MANAGE = 'online_sales.manage';

    // Caja
    case CASH_VIEW = 'cash.view';
    case CASH_OPEN = 'cash.open';
    case CASH_CLOSE = 'cash.close';
    case CASH_EXPENSE = 'cash.expense';

    // Comisiones
    case COMMISSIONS_VIEW = 'commissions.view';
    case COMMISSIONS_MANAGE = 'commissions.manage';
    case COMMISSIONS_PAY = 'commissions.pay';

    // Reportes
    case REPORTS_VIEW = 'reports.view';

    // Usuarios, roles y configuración
    case USERS_VIEW = 'users.view';
    case USERS_MANAGE = 'users.manage';
    case SETTINGS_MANAGE = 'settings.manage';

    public static function values(): array
    {
        return array_map(fn (self $p) => $p->value, self::cases());
    }

    /**
     * Permisos de cada rol. El Administrador recibe todos, por lo que no se
     * enumera aquí: mantenerlo explícito obligaría a actualizar dos sitios
     * cada vez que se agrega un permiso.
     */
    public static function forRole(RoleName $role): array
    {
        return match ($role) {
            RoleName::ADMINISTRADOR => self::values(),

            RoleName::RECEPCION => array_map(fn (self $p) => $p->value, [
                self::CLIENTS_VIEW, self::CLIENTS_CREATE, self::CLIENTS_UPDATE,
                self::APPOINTMENTS_VIEW, self::APPOINTMENTS_CREATE,
                self::APPOINTMENTS_UPDATE, self::APPOINTMENTS_DELETE,
                self::APPOINTMENTS_VIEW_ALL,
                self::SERVICES_VIEW,
                self::EMPLOYEES_VIEW,
                self::PACKAGES_VIEW, self::PACKAGES_SELL,
                self::ATTENDANCES_VIEW,
                self::SALES_VIEW, self::SALES_CREATE,
                self::ONLINE_SALES_VIEW, self::ONLINE_SALES_MANAGE,
                self::CASH_VIEW, self::CASH_OPEN, self::CASH_CLOSE, self::CASH_EXPENSE,
                self::INVENTORY_VIEW,
            ]),

            RoleName::ESPECIALISTA => array_map(fn (self $p) => $p->value, [
                self::CLIENTS_VIEW,
                self::APPOINTMENTS_VIEW, self::APPOINTMENTS_UPDATE,
                self::SERVICES_VIEW,
                self::PACKAGES_VIEW,
                self::ATTENDANCES_VIEW, self::ATTENDANCES_CREATE,
                self::INVENTORY_VIEW,
            ]),
        };
    }
}
