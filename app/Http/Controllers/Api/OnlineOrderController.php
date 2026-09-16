<?php

namespace App\Http\Controllers\Api;

use App\Actions\Shop\ChangeOnlineOrderStatusAction;
use App\Enums\OnlineOrderStatus;
use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateOnlineOrderStatusRequest;
use App\Http\Resources\OnlineOrderResource;
use App\Models\OnlineOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Ventas online: pedidos de la web y su seguimiento. */
class OnlineOrderController extends Controller
{
    use ApiResponses;

    public function index(Request $request): JsonResponse
    {
        $orders = OnlineOrder::query()
            ->with('clientUser')
            ->withCount('items')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('fulfillment'), fn ($q) => $q->where('fulfillment', $request->string('fulfillment')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(fn ($w) => $w->where('code', 'like', $term)
                    ->orWhere('recipient_name', 'like', $term)
                    ->orWhereHas('clientUser', fn ($u) => $u->where('email', 'like', $term)->orWhere('name', 'like', $term)));
            })
            ->latest('id')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($orders, OnlineOrderResource::class);
    }

    public function show(OnlineOrder $onlineOrder): JsonResponse
    {
        return $this->ok(new OnlineOrderResource(
            $onlineOrder->load(['clientUser', 'client', 'items', 'histories.user', 'payments'])
        ));
    }

    public function updateStatus(
        UpdateOnlineOrderStatusRequest $request,
        OnlineOrder $onlineOrder,
        ChangeOnlineOrderStatusAction $action,
    ): JsonResponse {
        $status = OnlineOrderStatus::from($request->string('status')->value());

        $order = $action->execute($onlineOrder, $status, $request->input('note'), $request->user()->id);

        return $this->ok(
            new OnlineOrderResource($order->load(['clientUser', 'client', 'items', 'histories.user', 'payments'])),
            "Pedido actualizado a \"{$status->label()}\"."
        );
    }
}
