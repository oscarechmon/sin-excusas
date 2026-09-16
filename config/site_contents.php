<?php

/*
|--------------------------------------------------------------------------
| Espacios editables de la web pública
|--------------------------------------------------------------------------
| Cada entrada es un bloque que el administrador puede cambiar desde
| "Contenido web" en el panel. Definirlos aquí (y no en la base de datos)
| mantiene fijos el diseño y los enlaces: el administrador cambia la foto y
| el texto, nunca la estructura de la página.
|
| fields:  qué puede editar (image, title, text).
| anchor:  ancla de /servicios a la que enlaza la tarjeta, si aplica.
| default: valor que se usa mientras no se edite nada.
*/

return [
    'home.hero' => [
        'aspect' => 16 / 9,
        'label' => 'Portada · foto principal',
        'group' => 'Inicio',
        'hint' => 'Horizontal y de buena calidad; se ve a pantalla completa. Ideal 1920×1080.',
        'fields' => ['image'],
        'default' => [],
    ],

    'home.line.faciales' => [
        'aspect' => 4 / 5,
        'label' => 'Línea 1 · Faciales',
        'group' => 'Inicio · Líneas de tratamiento',
        'hint' => 'Foto vertical (4:5).',
        'fields' => ['image', 'title', 'text'],
        'anchor' => 'faciales',
        'default' => ['title' => 'Faciales', 'text' => 'Limpieza, hidratación, peeling y firmeza.'],
    ],

    'home.line.corporales' => [
        'aspect' => 4 / 5,
        'label' => 'Línea 2 · Corporales',
        'group' => 'Inicio · Líneas de tratamiento',
        'hint' => 'Foto vertical (4:5).',
        'fields' => ['image', 'title', 'text'],
        'anchor' => 'corporales',
        'default' => ['title' => 'Corporales', 'text' => 'Reducción, drenaje y moldeo corporal.'],
    ],

    'home.line.post-operatorio' => [
        'aspect' => 4 / 5,
        'label' => 'Línea 3 · Post operatorio',
        'group' => 'Inicio · Líneas de tratamiento',
        'hint' => 'Foto vertical (4:5).',
        'fields' => ['image', 'title', 'text'],
        'anchor' => 'corporales',
        'default' => ['title' => 'Post operatorio', 'text' => 'Recuperación tras cirugía y post parto.'],
    ],

    'home.line.podologia' => [
        'aspect' => 4 / 5,
        'label' => 'Línea 4 · Podología',
        'group' => 'Inicio · Líneas de tratamiento',
        'hint' => 'Foto vertical (4:5).',
        'fields' => ['image', 'title', 'text'],
        'anchor' => 'podologia',
        'default' => ['title' => 'Podología', 'text' => 'Cuidado clínico del pie para todos.'],
    ],

    'home.products' => [
        'aspect' => 3 / 4,
        'label' => 'Inicio · Foto del bloque de productos',
        'group' => 'Inicio',
        'hint' => 'Foto vertical (3:4). Acompaña al texto de suplementos.',
        'fields' => ['image'],
        'default' => [],
    ],

    'about.photo' => [
        'aspect' => 21 / 9,
        'label' => 'Nosotros · Foto amplia',
        'group' => 'Nosotros',
        'hint' => 'Foto panorámica (21:9): equipo o recepción del centro.',
        'fields' => ['image'],
        'default' => [],
    ],
];
