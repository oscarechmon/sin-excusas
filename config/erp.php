<?php

/*
|--------------------------------------------------------------------------
| Sistema (ERP) dueño del catálogo y del stock
|--------------------------------------------------------------------------
| Con ERP_URL y ERP_TOKEN configurados, productos, servicios, paquetes,
| precios y stock se administran en el sistema (sistema.sinexcusas.org.pe) y
| la web los lee en vivo por su API, sin guardar copia. Imágenes,
| descripciones y qué se publica siguen siendo de esta aplicación. Sin ellos,
| todo funciona como antes: el inventario se administra aquí.
*/

return [
    'url' => env('ERP_URL'),

    // El mismo valor que INTEGRATION_TOKEN en el .env del sistema.
    'token' => env('ERP_TOKEN'),

    // Segundos que se espera al sistema antes de darlo por caído.
    'timeout' => (int) env('ERP_TIMEOUT', 15),

    // Al leer el catálogo para mostrar una página se espera menos: si el
    // sistema no contesta, la web sigue con lo último que respondió.
    'read_timeout' => (int) env('ERP_READ_TIMEOUT', 5),

    // Segundos que la web reutiliza el catálogo leído. Cada cambio en el
    // sistema lo avisa y lo invalida al instante; esto es solo por si un aviso
    // se pierde. El carrito y el pago siempre consultan el stock en el momento.
    'catalog_ttl' => (int) env('ERP_CATALOG_TTL', 60),
];
