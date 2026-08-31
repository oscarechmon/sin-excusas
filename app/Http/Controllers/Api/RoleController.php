<?php

namespace App\Http\Controllers\Api;

use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\SyncRolePermissionsRequest;
use App\Http\Resources\RoleResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Configuración de roles y permisos (§29, §45).
 *
 * El conjunto de roles es fijo: sus nombres viven en el enum RoleName y el
 * código los referencia por valor, así que crear roles arbitrarios desde la
 * interfaz dejaría reglas de negocio apuntando a roles inexistentes. Lo que sí
 * es configurable —y es lo que pide la especificación— son los permisos de
 * cada rol.
 */
class RoleController extends Controller
{
    use ApiResponses;

    public function index(): JsonResponse
    {
        // El conteo va como subconsulta y no con withCount('users'): esa
        // relación de spatie necesita el guard_name del rol para resolver el
        // modelo de usuario, y withCount la evalúa sobre una instancia vacía.
        $roles = Role::query()
            ->with('permissions')
            ->select('roles.*')
            ->selectSub(
                DB::table('model_has_roles')
                    ->selectRaw('count(*)')
                    ->whereColumn('model_has_roles.role_id', 'roles.id')
                    ->where('model_type', User::class),
                'users_count'
            )
            ->orderBy('id')
            ->get();

        return $this->ok([
            'roles' => RoleResource::collection($roles),
            // El catálogo de permisos se agrupa por módulo para que la
            // interfaz pueda mostrarlo como una matriz legible.
            'permissions' => $this->groupedPermissions(),
            'locked_role' => RoleName::ADMINISTRADOR->value,
        ]);
    }

    public function syncPermissions(SyncRolePermissionsRequest $request, Role $role): JsonResponse
    {
        // El Administrador conserva siempre todos los permisos: poder
        // recortárselos permitiría dejar el sistema sin nadie capaz de
        // gestionar usuarios, sin forma de recuperarlo desde la interfaz.
        if ($role->name === RoleName::ADMINISTRADOR->value) {
            return $this->failed(
                'El rol Administrador siempre conserva todos los permisos y no puede modificarse.',
                422
            );
        }

        $role->syncPermissions($request->input('permissions', []));

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $this->ok(
            new RoleResource($role->load('permissions')),
            "Permisos del rol {$role->name} actualizados correctamente."
        );
    }

    /**
     * Permisos agrupados por módulo, con etiqueta legible.
     *
     * @return array<int,array{module:string,label:string,permissions:array<int,array{name:string,label:string}>}>
     */
    private function groupedPermissions(): array
    {
        $moduleLabels = [
            'clients' => 'Clientes',
            'appointments' => 'Agenda',
            'services' => 'Servicios',
            'employees' => 'Personal',
            'inventory' => 'Inventario',
            'packages' => 'Paquetes',
            'attendances' => 'Atenciones',
            'sales' => 'Ventas',
            'cash' => 'Caja',
            'commissions' => 'Comisiones',
            'reports' => 'Reportes',
            'users' => 'Usuarios',
            'settings' => 'Configuración',
        ];

        $actionLabels = [
            'view' => 'Ver',
            'view_all' => 'Ver todo el centro',
            'create' => 'Crear',
            'update' => 'Editar',
            'delete' => 'Eliminar',
            'manage' => 'Administrar',
            'adjust' => 'Ajustar stock',
            'sell' => 'Vender',
            'cancel' => 'Anular',
            'open' => 'Abrir',
            'close' => 'Cerrar',
            'expense' => 'Registrar egresos',
            'pay' => 'Pagar',
        ];

        $grouped = [];

        foreach (PermissionName::cases() as $permission) {
            [$module, $action] = explode('.', $permission->value, 2);

            $grouped[$module]['module'] = $module;
            $grouped[$module]['label'] = $moduleLabels[$module] ?? ucfirst($module);
            $grouped[$module]['permissions'][] = [
                'name' => $permission->value,
                'label' => $actionLabels[$action] ?? ucfirst($action),
            ];
        }

        return array_values($grouped);
    }
}
