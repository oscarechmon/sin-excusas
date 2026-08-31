<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ApiResponses;

    public function __invoke(Request $request, DashboardService $dashboard): JsonResponse
    {
        return $this->ok($dashboard->metrics($request->date('date')));
    }
}
