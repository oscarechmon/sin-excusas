<?php

/*
|--------------------------------------------------------------------------
| Pasarela de pagos Izipay
|--------------------------------------------------------------------------
| Credenciales del Back Office de Izipay (Configuración > Tienda > Claves
| de API REST). Usar las claves de TEST hasta aprobar la integración.
|
| driver:
|   - izipay: cobro real contra la API de Izipay.
|   - fake:   pago simulado para desarrollo. Bloqueado en producción.
*/

return [
    'driver' => env('IZIPAY_DRIVER', 'fake'),

    // Usuario (identificador de tienda) y contraseña de la API REST.
    'username' => env('IZIPAY_USERNAME'),
    'password' => env('IZIPAY_PASSWORD'),

    // Clave pública JavaScript y clave HMAC-SHA-256.
    'public_key' => env('IZIPAY_PUBLIC_KEY'),
    'hmac_key' => env('IZIPAY_HMAC_KEY'),

    'api_url' => env('IZIPAY_API_URL', 'https://api.micuentaweb.pe'),
    'static_url' => env('IZIPAY_STATIC_URL', 'https://static.micuentaweb.pe'),

    'currency' => 'PEN',
];
