<?php

namespace App\Http\Controllers\Web\Shop;

use App\Http\Controllers\Controller;
use App\Services\Shop\Cart;
use App\Services\Shop\CartLine;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function show(Cart $cart): View
    {
        $lines = $cart->lines();

        return view('site.shop.cart', [
            'lines' => $lines,
            'subtotal' => round($lines->sum(fn (CartLine $line) => $line->subtotal()), 2),
        ]);
    }

    /**
     * Agrega al carrito. La web lo llama por fetch (responde JSON y la página
     * no se recarga); sin JavaScript sigue funcionando como formulario normal.
     */
    public function add(Request $request, Cart $cart): RedirectResponse|JsonResponse
    {
        $reply = fn (bool $ok, string $message) => $request->expectsJson()
            ? response()->json(['success' => $ok, 'message' => $message, 'count' => $cart->count()], $ok ? 200 : 422)
            : back()->with($ok ? 'success' : 'error', $message);

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

    public function update(Request $request, Cart $cart, string $key): RedirectResponse
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:99']]);

        $line = $cart->lines()->firstWhere('key', $key);

        if (! $line) {
            return redirect()->route('shop.cart.show');
        }

        $quantity = min((int) $data['quantity'], $line->maxQuantity);
        $cart->put($key, $quantity);

        return redirect()->route('shop.cart.show')->with(
            $quantity < (int) $data['quantity'] ? 'error' : 'success',
            $quantity < (int) $data['quantity']
                ? "Solo hay {$line->maxQuantity} unidad(es) disponibles de {$line->name()}."
                : 'Carrito actualizado.'
        );
    }

    public function remove(Cart $cart, string $key): RedirectResponse
    {
        $cart->remove($key);

        return redirect()->route('shop.cart.show')->with('success', 'Artículo eliminado del carrito.');
    }
}
