<?php

namespace App\Http\Controllers\Web\Shop;

use App\Http\Controllers\Controller;
use App\Services\Shop\Cart;
use App\Services\Shop\CartLine;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Carrito de la web. La página del carrito funciona con formularios normales;
 * el carrito lateral usa las mismas rutas por fetch y recibe su contenido ya
 * armado (`drawer`), así no hay que mantener dos versiones del carrito.
 */
class CartController extends Controller
{
    public function show(Cart $cart): View
    {
        return view('site.shop.cart', $this->contents($cart));
    }

    /** Contenido del carrito lateral, al abrirlo desde el ícono. */
    public function summary(Cart $cart): View
    {
        return view('site.shop.partials.cart-drawer', $this->contents($cart) + ['notice' => null]);
    }

    /**
     * Agrega al carrito. La web lo llama por fetch (responde JSON y la página
     * no se recarga); sin JavaScript sigue funcionando como formulario normal.
     * Con "Comprar ahora" (`buy_now`) sigue directo a finalizar la compra.
     */
    public function add(Request $request, Cart $cart): RedirectResponse|JsonResponse
    {
        $buyNow = $request->boolean('buy_now');

        $reply = function (bool $ok, string $message) use ($request, $cart, $buyNow) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => $ok,
                    'message' => $message,
                    'count' => $cart->count(),
                    'drawer' => $ok ? $this->drawer($cart, ['type' => 'success', 'text' => $message]) : null,
                    'redirect' => $ok && $buyNow ? route('shop.checkout') : null,
                ], $ok ? 200 : 422);
            }

            return $ok && $buyNow
                ? redirect()->route('shop.checkout')
                : back()->with($ok ? 'success' : 'error', $message);
        };

        $data = $request->validate([
            'type' => ['required', 'in:'.Cart::SERVICE.','.Cart::PRODUCT],
            'id' => ['required', 'integer'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);

        $model = $cart->find($data['type'], (int) $data['id']);

        if (! $model) {
            return $reply(false, 'Este artículo no está disponible para compra en línea.');
        }

        $key = Cart::key($data['type'], $model->id);
        $quantity = $cart->quantityOf($key) + (int) ($data['quantity'] ?? 1);
        $max = $cart->maxQuantity($model);

        if ($quantity > $max) {
            return $reply(false, $max > 0
                ? "Solo hay {$max} unidad(es) disponibles de {$model->name}."
                : "{$model->name} está agotado por el momento.");
        }

        $cart->put($key, $quantity);

        return $reply(true, "Agregaste {$model->name} al carrito.");
    }

    public function update(Request $request, Cart $cart, string $key): RedirectResponse|JsonResponse
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:99']]);

        $line = $cart->lines()->firstWhere('key', $key);

        if (! $line) {
            return $this->changed($request, $cart);
        }

        // Si se agotó mientras estaba en el carrito, se quita: una línea en 0 no se puede pagar.
        if ($line->maxQuantity < 1) {
            $cart->remove($key);

            return $this->changed($request, $cart, ['type' => 'error', 'text' => "{$line->name()} se agotó y lo quitamos de tu carrito."]);
        }

        $quantity = min((int) $data['quantity'], $line->maxQuantity);
        $cart->put($key, $quantity);

        return $this->changed($request, $cart, $quantity < (int) $data['quantity']
            ? ['type' => 'error', 'text' => "Solo hay {$line->maxQuantity} unidad(es) disponibles de {$line->name()}."]
            : ['type' => 'success', 'text' => 'Carrito actualizado.']);
    }

    public function remove(Request $request, Cart $cart, string $key): RedirectResponse|JsonResponse
    {
        $cart->remove($key);

        return $this->changed($request, $cart, ['type' => 'success', 'text' => 'Artículo eliminado del carrito.']);
    }

    /**
     * Respuesta tras cambiar una línea. Por fetch (carrito lateral) devuelve
     * su contenido nuevo; sin JavaScript vuelve a la página del carrito.
     *
     * @param  array{type: string, text: string}|null  $notice
     */
    private function changed(Request $request, Cart $cart, ?array $notice = null): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => ($notice['type'] ?? 'success') === 'success',
                'message' => $notice['text'] ?? '',
                'count' => $cart->count(),
                // En el carrito lateral, un cambio que salió bien se ve solo; el aviso es para lo que no.
                'drawer' => $this->drawer($cart, ($notice['type'] ?? null) === 'error' ? $notice : null),
            ]);
        }

        $redirect = redirect()->route('shop.cart.show');

        return $notice ? $redirect->with($notice['type'], $notice['text']) : $redirect;
    }

    /** @param  array{type: string, text: string}|null  $notice */
    private function drawer(Cart $cart, ?array $notice): string
    {
        return view('site.shop.partials.cart-drawer', $this->contents($cart) + ['notice' => $notice])->render();
    }

    /** @return array{lines: \Illuminate\Support\Collection<int, CartLine>, subtotal: float} */
    private function contents(Cart $cart): array
    {
        $lines = $cart->lines();

        return [
            'lines' => $lines,
            'subtotal' => round($lines->sum(fn (CartLine $line) => $line->subtotal()), 2),
        ];
    }
}
