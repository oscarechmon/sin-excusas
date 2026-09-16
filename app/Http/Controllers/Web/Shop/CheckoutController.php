<?php

namespace App\Http\Controllers\Web\Shop;

use App\Actions\Shop\ConfirmOnlinePaymentAction;
use App\Actions\Shop\PlaceOnlineOrderAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\PlaceOnlineOrderRequest;
use App\Models\OnlineOrder;
use App\Models\StoreSetting;
use App\Services\Payments\PaymentGatewayException;
use App\Services\Payments\PaymentGatewayManager;
use App\Services\Shop\Cart;
use App\Services\Shop\CartLine;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function show(Request $request, Cart $cart): View|RedirectResponse
    {
        $lines = $cart->lines();

        if ($lines->isEmpty()) {
            return redirect()->route('shop.cart.show')->with('error', 'Tu carrito está vacío.');
        }

        return view('site.shop.checkout', [
            'lines' => $lines,
            'subtotal' => round($lines->sum(fn (CartLine $line) => $line->subtotal()), 2),
            'hasProducts' => $lines->contains(fn (CartLine $line) => $line->isProduct()),
            'deliveryEnabled' => StoreSetting::deliveryEnabled(),
            'deliveryFee' => StoreSetting::deliveryFee(),
            'customer' => $request->user('customer'),
        ]);
    }

    public function store(PlaceOnlineOrderRequest $request, Cart $cart, PlaceOnlineOrderAction $placeOrder): RedirectResponse
    {
        $order = $placeOrder->execute($request->user('customer'), $cart->lines(), $request->validated());

        $cart->clear();

        return redirect()->route('shop.checkout.pay', $order->code);
    }

    public function pay(Request $request, string $code, PaymentGatewayManager $gateways): View|RedirectResponse
    {
        $order = OnlineOrder::query()
            ->where('code', $code)
            ->where('client_user_id', $request->user('customer')->id)
            ->with(['items', 'clientUser'])
            ->firstOrFail();

        if (! $order->status->awaitsPayment()) {
            return redirect()->route('shop.account.order', $order->code);
        }

        try {
            $checkout = $gateways->driver()->checkout($order);
        } catch (PaymentGatewayException $e) {
            report($e);

            return view('site.shop.pay', [
                'order' => $order,
                'checkout' => null,
                'gatewayError' => 'No pudimos conectar con la pasarela de pago. Intenta nuevamente en unos minutos.',
            ]);
        }

        return view('site.shop.pay', ['order' => $order, 'checkout' => $checkout, 'gatewayError' => null]);
    }

    /**
     * Retorno del navegador tras pagar. Es pública porque Izipay la envía
     * desde su dominio, donde la cookie de sesión puede no viajar: el pedido
     * se identifica por la respuesta firmada, no por la sesión.
     */
    public function return(Request $request, PaymentGatewayManager $gateways, ConfirmOnlinePaymentAction $confirm): RedirectResponse
    {
        $gateway = $gateways->driver();
        $result = $gateway->parseReturn($request);
        $order = $result ? $confirm->execute($result, $gateway->name()) : null;

        if (! $order) {
            return redirect()->route('site.home')
                ->with('error', 'No pudimos validar la respuesta del pago. Si se realizó un cargo, escríbenos por WhatsApp.');
        }

        return $order->status->isPaid()
            ? redirect()->route('shop.account.order', $order->code)
                ->with('success', '¡Pago confirmado! Aquí puedes seguir el avance de tu pedido.')
            : redirect()->route('shop.account.order', $order->code)
                ->with('error', 'El pago no fue aprobado. Puedes intentarlo nuevamente.');
    }
}
