<?php

namespace App\Actions\Shop;

use App\Actions\Clients\CreateClientAction;
use App\Models\Client;
use App\Models\ClientUser;
use Illuminate\Support\Facades\DB;

/**
 * Crea una cuenta web y la vincula a su ficha de cliente del ERP.
 * Lo usan tanto el registro con correo como el acceso con Google.
 */
class RegisterClientUserAction
{
    public function __construct(private readonly CreateClientAction $createClient) {}

    /**
     * @param  array{name:string,email:string,phone?:?string,document_number?:?string,password?:?string}  $data
     */
    public function execute(array $data, bool $emailVerified = false, ?string $googleId = null, ?string $avatarUrl = null): ClientUser
    {
        return DB::transaction(function () use ($data, $emailVerified, $googleId, $avatarUrl) {
            $user = new ClientUser([
                'client_id' => $this->resolveClient($data)->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'document_number' => $data['document_number'] ?? null,
                'password' => $data['password'] ?? null,
            ]);

            $user->forceFill([
                'google_id' => $googleId,
                'avatar_url' => $avatarUrl,
                'email_verified_at' => $emailVerified ? now() : null,
            ])->save();

            return $user;
        });
    }

    /**
     * Reutiliza la ficha si la persona ya se atendió en el centro, para no
     * duplicar clientes en el ERP.
     *
     * @param  array<string,mixed>  $data
     */
    private function resolveClient(array $data): Client
    {
        $existing = null;

        if (! empty($data['document_number'])) {
            $existing = Client::query()->where('document_number', $data['document_number'])->first();
        }

        $existing ??= Client::query()->where('email', $data['email'])->first();

        return $existing ?? $this->createClient->execute([
            'full_name' => $data['name'],
            'document_number' => $data['document_number'] ?? null,
            'phone' => $data['phone'] ?? null,
            'whatsapp' => $data['phone'] ?? null,
            'email' => $data['email'],
            'how_knew' => 'Tienda web',
            'active' => true,
        ]);
    }
}
