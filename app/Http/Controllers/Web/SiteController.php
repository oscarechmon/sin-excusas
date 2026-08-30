<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\Response;

class SiteController extends Controller
{
    public function home(): Response
    {
        $content = File::get(base_path('voroz-web/index.html'));

        return response($content, 200)
            ->header('Content-Type', 'text/html; charset=UTF-8');
    }
}
