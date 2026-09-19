<?php

/*
|--------------------------------------------------------------------------
| Tareas posteriores al despliegue
|--------------------------------------------------------------------------
| El despliegue es por FTP, que no puede ejecutar comandos. Con este token,
| GitHub Actions llama a POST /deploy/optimize al terminar de subir archivos
| y el servidor corre las migraciones y regenera las cachés de Laravel.
| Sin token configurado la ruta responde 404.
*/

return [
    'token' => env('DEPLOY_TOKEN'),
];
