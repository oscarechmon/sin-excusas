<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Concerns\VerifiesDeployToken;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;

/**
 * Tareas que el despliegue por FTP no puede hacer: migrar y cachear.
 *
 * Con config, rutas y vistas en caché, Laravel deja de leer el .env y todos
 * los archivos de config/ y routes/ en cada petición, que es buena parte del
 * tiempo de respuesta en un hosting compartido.
 */
class DeployController extends Controller
{
    use VerifiesDeployToken;

    public function __invoke(Request $request): Response
    {
        $this->verifyDeployToken($request);

        $output = [];

        // El orden importa: primero se descartan las cachés del código anterior,
        // luego se migra y al final se cachea ya con el código nuevo.
        foreach (['optimize:clear' => [], 'migrate' => ['--force' => true], 'optimize' => []] as $command => $options) {
            Artisan::call($command, $options);
            $output[] = "$ php artisan {$command}\n".trim(Artisan::output());
        }

        return response(implode("\n\n", $output)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
