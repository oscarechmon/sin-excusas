<?php

/*
|--------------------------------------------------------------------------
| Sistema (ERP) dueño del catálogo y del stock
|--------------------------------------------------------------------------
| Con ERP_URL y ERP_TOKEN configurados, productos, servicios, precios y stock
| se administran en el sistema (sistema.sinexcusas.org.pe) y aquí se guarda
| una copia para la web. Imágenes, descripciones y qué se publica siguen
| siendo de esta aplicación. Sin ellos, todo funciona como antes: el
| inventario se administra aquí.
*/

return [
    'url' => env('ERP_URL'),

    // El mismo valor que INTEGRATION_TOKEN en el .env del sistema.
    'token' => env('ERP_TOKEN'),

    // Segundos que se espera al sistema antes de darlo por caído.
    'timeout' => (int) env('ERP_TIMEOUT', 15),
];
