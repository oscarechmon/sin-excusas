<?php

/*
|--------------------------------------------------------------------------
| Ajustes editables del sitio web
|--------------------------------------------------------------------------
| Contacto, redes y scripts de terceros (Google Analytics, Tag Manager,
| Meta Pixel...). La definición vive aquí y los valores en `store_settings`,
| igual que el contenido de las páginas: así el panel se arma solo y agregar
| un campo es tocar un único archivo.
|
| type:  text | tel | email | url | textarea | code
| rules: validación del backend, además del tipo.
*/

return [
    'contact' => [
        'label' => 'Contacto',
        'hint' => 'Aparece en el pie de página y en los botones de WhatsApp.',
        'fields' => [
            'contact_whatsapp' => [
                'label' => 'WhatsApp',
                'type' => 'tel',
                'hint' => 'Solo números, con código de país y sin espacios ni "+". Ejemplo: 51987654321. Vacío oculta los botones de WhatsApp.',
                'placeholder' => '51987654321',
                'default' => env('SITE_WHATSAPP', ''),
                'rules' => ['nullable', 'regex:/^[0-9]{6,15}$/'],
            ],
            'contact_phone' => [
                'label' => 'Teléfono visible',
                'type' => 'text',
                'hint' => 'Como quieres que se lea en la web. Ejemplo: (01) 555-1234.',
                'rules' => ['nullable', 'string', 'max:40'],
            ],
            'contact_email' => [
                'label' => 'Correo de contacto',
                'type' => 'email',
                'rules' => ['nullable', 'email', 'max:190'],
            ],
            'contact_address' => [
                'label' => 'Dirección',
                'type' => 'text',
                'rules' => ['nullable', 'string', 'max:190'],
            ],
            'contact_schedule' => [
                'label' => 'Nota antes del horario',
                'type' => 'text',
                'hint' => 'Texto corto que acompaña al horario en la barra superior.',
                'placeholder' => 'Atención con cita previa',
                'default' => 'Atención con cita previa',
                'rules' => ['nullable', 'string', 'max:120'],
            ],
            'contact_hours' => [
                'label' => 'Horario de atención',
                'type' => 'hours',
                'hint' => 'Apaga el día que no atiendes. Los días seguidos con el mismo horario se resumen solos: "Lun a Vie 9:00 a 20:00".',
                'default' => [
                    'mon' => ['open' => true, 'from' => '09:00', 'to' => '20:00'],
                    'tue' => ['open' => true, 'from' => '09:00', 'to' => '20:00'],
                    'wed' => ['open' => true, 'from' => '09:00', 'to' => '20:00'],
                    'thu' => ['open' => true, 'from' => '09:00', 'to' => '20:00'],
                    'fri' => ['open' => true, 'from' => '09:00', 'to' => '20:00'],
                    'sat' => ['open' => true, 'from' => '09:00', 'to' => '14:00'],
                    'sun' => ['open' => false, 'from' => '09:00', 'to' => '14:00'],
                ],
            ],
        ],
    ],

    'social' => [
        'label' => 'Redes sociales',
        'hint' => 'Deja vacía la que no uses y su ícono no aparece.',
        'fields' => [
            'social_instagram' => ['label' => 'Instagram', 'type' => 'url', 'placeholder' => 'https://instagram.com/tucuenta', 'rules' => ['nullable', 'url', 'max:190']],
            'social_facebook' => ['label' => 'Facebook', 'type' => 'url', 'placeholder' => 'https://facebook.com/tupagina', 'rules' => ['nullable', 'url', 'max:190']],
            'social_tiktok' => ['label' => 'TikTok', 'type' => 'url', 'placeholder' => 'https://tiktok.com/@tucuenta', 'rules' => ['nullable', 'url', 'max:190']],
            'social_youtube' => ['label' => 'YouTube', 'type' => 'url', 'placeholder' => 'https://youtube.com/@tucanal', 'rules' => ['nullable', 'url', 'max:190']],
        ],
    ],

    'seo' => [
        'label' => 'Buscadores',
        'hint' => 'Palabras clave del sitio. Google ya no las usa para posicionar, pero otros buscadores y herramientas sí las leen.',
        'fields' => [
            'seo_keywords' => [
                'label' => 'Palabras clave',
                'type' => 'textarea',
                'hint' => 'Separadas por comas. Ejemplo: centro estético Lima, tratamientos faciales, podología, suplementos. Vacío quita la etiqueta.',
                'placeholder' => 'centro estético, tratamientos faciales, podología',
                'rules' => ['nullable', 'string', 'max:500'],
            ],
        ],
    ],

    'scripts' => [
        'label' => 'Scripts y etiquetas',
        'hint' => 'Se pegan tal cual en la web pública (Google Analytics, Tag Manager, Search Console, Meta Pixel). Nunca se cargan en el panel del ERP.',
        'fields' => [
            'scripts_head' => [
                'label' => 'Dentro de <head>',
                'type' => 'code',
                'hint' => 'Para Google Analytics (gtag), la verificación de Search Console y el <script> de Tag Manager.',
                'rules' => ['nullable', 'string', 'max:20000'],
            ],
            'scripts_body_start' => [
                'label' => 'Al comienzo del <body>',
                'type' => 'code',
                'hint' => 'Para el <noscript> de Google Tag Manager.',
                'rules' => ['nullable', 'string', 'max:20000'],
            ],
            'scripts_body_end' => [
                'label' => 'Al final del <body>',
                'type' => 'code',
                'hint' => 'Para chats, mapas de calor y todo lo que no deba retrasar la carga.',
                'rules' => ['nullable', 'string', 'max:20000'],
            ],
        ],
    ],
];
