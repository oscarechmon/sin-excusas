<?php

namespace App\Actions\Clients;

use App\Models\Client;

class CreateClientAction
{
    public function execute(array $data): Client
    {
        $data['code'] = Client::generateCode();

        return Client::create($data);
    }
}
