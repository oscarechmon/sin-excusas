<?php

/*
|--------------------------------------------------------------------------
| Sitio web público
|--------------------------------------------------------------------------
| Datos de contacto que usan las vistas Blade. El número va sin "+" ni
| espacios, en el formato que espera https://wa.me/.
*/

$whatsapp = env('SITE_WHATSAPP', '51999999999');

return [
    'whatsapp' => $whatsapp,
    'whatsapp_url' => "https://wa.me/{$whatsapp}",
];
