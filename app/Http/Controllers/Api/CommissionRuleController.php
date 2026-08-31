<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCommissionRuleRequest;
use App\Http\Resources\CommissionRuleResource;
use App\Models\CommissionRule;
use Illuminate\Http\JsonResponse;

class CommissionRuleController extends Controller
{
    use ApiResponses;

    public function index(): JsonResponse
    {
        $rules = CommissionRule::with('employee', 'service')
            // Las más generales primero: es el orden en que se leen mejor.
            ->orderByRaw('employee_id IS NULL DESC, service_id IS NULL DESC')
            ->get();

        return $this->ok(CommissionRuleResource::collection($rules));
    }

    public function store(StoreCommissionRuleRequest $request): JsonResponse
    {
        $rule = CommissionRule::create($request->validated());

        return $this->created(
            new CommissionRuleResource($rule->load('employee', 'service')),
            'Regla de comisión creada correctamente.'
        );
    }

    public function update(StoreCommissionRuleRequest $request, CommissionRule $commissionRule): JsonResponse
    {
        $commissionRule->update($request->validated());

        return $this->ok(
            new CommissionRuleResource($commissionRule->load('employee', 'service')),
            'Regla actualizada correctamente.'
        );
    }

    public function destroy(CommissionRule $commissionRule): JsonResponse
    {
        $commissionRule->delete();

        return $this->ok(null, 'Regla eliminada correctamente.');
    }
}
