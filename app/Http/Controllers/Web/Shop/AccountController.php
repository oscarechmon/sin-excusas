<?php

namespace App\Http\Controllers\Web\Shop;

use App\Http\Controllers\Controller;
use App\Models\OnlineOrder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/** "Mis pedidos": historial y seguimiento del cliente. */
class AccountController extends Controller
{
    public function orders(Request $request): View
    {
        $orders = $request->user('customer')->orders()
            ->withCount('items')
            ->latest('id')
            ->paginate(10);

        return view('site.shop.account.orders', compact('orders'));
    }

    public function order(Request $request, string $code): View
    {
        // Un pedido ajeno responde 404 y no 403, para no revelar que existe.
        $order = OnlineOrder::query()
            ->where('code', $code)
            ->where('client_user_id', $request->user('customer')->id)
            ->with(['items', 'histories' => fn ($q) => $q->where('internal', false)])
            ->firstOrFail();

        return view('site.shop.account.order', compact('order'));
    }
}
