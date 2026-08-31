<?php

namespace App\Http\Controllers\Api;

use App\Actions\Users\CreateUserAction;
use App\Actions\Users\UpdateUserAction;
use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserAccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use ApiResponses;

    public function __construct(private readonly UserAccountService $accounts) {}

    public function index(Request $request): JsonResponse
    {
        $users = User::query()
            ->with('roles', 'employee')
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.$request->string('search').'%';
                $query->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('email', 'like', $term));
            })
            ->when($request->filled('role'), fn ($q) => $q->whereHas(
                'roles',
                fn ($r) => $r->where('name', $request->string('role'))
            ))
            ->when($request->filled('active'), fn ($q) => $q->where('active', $request->boolean('active')))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($users, UserResource::class);
    }

    public function show(User $user): JsonResponse
    {
        return $this->ok(new UserResource($user->load('roles', 'employee')));
    }

    public function store(StoreUserRequest $request, CreateUserAction $action): JsonResponse
    {
        $user = $action->execute($request->validated());

        return $this->created(new UserResource($user), 'Usuario creado correctamente.');
    }

    public function update(UpdateUserRequest $request, User $user, UpdateUserAction $action): JsonResponse
    {
        $updated = $action->execute($user, $request->validated(), $request->user());

        return $this->ok(new UserResource($updated), 'Usuario actualizado correctamente.');
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        $this->accounts->assertCanDelete($user, $request->user());

        // Un usuario referenciado por caja, ventas o movimientos no puede
        // borrarse sin perder trazabilidad: se desactiva en su lugar (§34).
        if ($this->hasActivity($user)) {
            $user->update(['active' => false]);
            $user->tokens()->delete();

            return $this->ok(null, 'El usuario tiene historial, por lo que se desactivó en lugar de eliminarse.');
        }

        $user->tokens()->delete();
        $user->delete();

        return $this->ok(null, 'Usuario eliminado correctamente.');
    }

    /**
     * Cierra todas las sesiones activas del usuario.
     *
     * Necesario porque desactivar la cuenta no invalida por sí solo los tokens
     * de Sanctum ya emitidos.
     */
    public function revokeSessions(Request $request, User $user): JsonResponse
    {
        $deleted = $user->tokens()->count();
        $user->tokens()->delete();

        return $this->ok(
            ['revoked' => $deleted],
            $deleted === 0
                ? 'El usuario no tenía sesiones abiertas.'
                : "Se cerraron {$deleted} sesión(es) del usuario."
        );
    }

    private function hasActivity(User $user): bool
    {
        return $user->employee()->exists()
            || $user->cashSessions()->exists()
            || $user->sales()->exists();
    }
}
