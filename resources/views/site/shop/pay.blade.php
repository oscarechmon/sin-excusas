@extends('site.layout')

@section('title', "Pagar pedido {$order->code}")
@section('hide_cta', true)

@push('head')
  {{-- El formulario de Izipay se inicializa al cargar la página: se fuerza
       una carga completa en lugar de la navegación de Turbo. --}}
  <meta name="turbo-visit-control" content="reload">
  <meta name="robots" content="noindex">

  {{-- 3D Secure envía formularios ocultos hacia iframes del banco (fingerprinting
       y challenge). Turbo intercepta envíos de formularios, así que en esta
       página se apaga del todo para no interrumpir la autenticación. --}}
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      if (window.Turbo) window.Turbo.session.drive = false;
    });
  </script>

  @if (($checkout['driver'] ?? null) === 'izipay')
    <link rel="stylesheet" href="{{ $checkout['staticUrl'] }}/static/js/krypton-client/V4.0/ext/neon-reset.min.css">
    <script src="{{ $checkout['staticUrl'] }}/static/js/krypton-client/V4.0/stable/kr-payment-form.min.js"
            kr-public-key="{{ $checkout['publicKey'] }}"
            kr-post-url-success="{{ route('shop.checkout.return') }}"
            kr-post-url-refused="{{ route('shop.checkout.return') }}"
            kr-language="es-PE"></script>
    <script src="{{ $checkout['staticUrl'] }}/static/js/krypton-client/V4.0/ext/neon.js"></script>
  @endif
@endpush

@php($money = fn ($value) => 'S/ '.number_format((float) $value, 2))

@section('content')

  <section class="se-pagehead">
    <div class="se-container">
      <p class="se-eyebrow se-eyebrow--row">Pedido {{ $order->code }}</p>
      <h1 class="se-pagehead__title">Pago seguro</h1>
      <ol class="se-checkout-steps" aria-label="Pasos de la compra">
        <li class="is-done"><span>✓</span> Datos y entrega</li>
        <li class="is-current" aria-current="step"><span>2</span> Pago seguro</li>
        <li><span>3</span> Seguimiento</li>
      </ol>
    </div>
  </section>

  <section class="se-section pt-5">
    <div class="se-container">
      <div class="row g-4">
        <div class="col-12 col-lg-7">
          <div class="se-panel">
            @if ($order->status === \App\Enums\OnlineOrderStatus::PAYMENT_FAILED)
              <div class="se-alert se-alert--error mb-4">El intento anterior no fue aprobado. Puedes volver a intentarlo.</div>
            @endif

            <div class="se-pay-head">
              <h2 class="se-checkout-title mb-0">Paga con tu tarjeta</h2>
              <span class="se-secure__logos">
                <span class="se-card-logo" title="Visa"><img src="{{ asset('assets/img/pagos/visa.svg') }}" alt="Visa" width="46" height="15"></span>
                <span class="se-card-logo" title="Mastercard"><img src="{{ asset('assets/img/pagos/mastercard.svg') }}" alt="Mastercard" width="34" height="21"></span>
              </span>
            </div>

            @if ($gatewayError)
              <div class="se-alert se-alert--error">{{ $gatewayError }}</div>
            @elseif ($checkout['driver'] === 'izipay')
              <p class="se-muted-note mb-4">Ingresa los datos de tu tarjeta Visa o Mastercard. Total a pagar: <strong>{{ $money($order->total) }}</strong>.</p>
              <div class="se-pay-form">
                <div class="kr-embedded" kr-form-token="{{ $checkout['formToken'] }}"></div>
              </div>
            @else
              <div class="se-alert se-alert--warning mb-4">
                <strong>Modo de prueba.</strong> Izipay aún no está configurado: elige el resultado del pago para simularlo.
              </div>
              <div class="d-flex flex-column flex-sm-row gap-3">
                @foreach (['approved' => ['Simular pago aprobado', 'se-btn--gold', $checkout['approve']], 'refused' => ['Simular pago rechazado', 'se-btn--outline', $checkout['refuse']]] as $result => [$label, $style, $signature])
                  <form method="post" action="{{ route('shop.checkout.return') }}" data-turbo="false">
                    <input type="hidden" name="order" value="{{ $order->code }}">
                    <input type="hidden" name="result" value="{{ $result }}">
                    <input type="hidden" name="signature" value="{{ $signature }}">
                    <button type="submit" class="se-btn {{ $style }}">{{ $label }}</button>
                  </form>
                @endforeach
              </div>
            @endif

            @include('site.shop.partials.secure-payment', ['class' => 'mt-4', 'hideCards' => true])
          </div>
        </div>

        <div class="col-12 col-lg-5">
          <aside class="se-panel">
            <h2 class="se-serif mb-3" style="--fs: 1.75rem;">Resumen</h2>
            @foreach ($order->items as $item)
              <div class="se-summary-row">
                <span>{{ $item->name }} <span class="se-muted-note">× {{ $item->quantity }}</span></span>
                <span>{{ $money($item->subtotal) }}</span>
              </div>
            @endforeach
            <div class="se-summary-row se-summary-row--divider"><span>Subtotal</span><span>{{ $money($order->subtotal) }}</span></div>
            @if ((float) $order->delivery_fee > 0)
              <div class="se-summary-row"><span>{{ $order->fulfillment->label() }}</span><span>{{ $money($order->delivery_fee) }}</span></div>
            @endif
            <div class="se-summary-row se-summary-row--total"><span>Total a pagar</span><span>{{ $money($order->total) }}</span></div>

            @include('site.shop.partials.delivery-card', ['order' => $order, 'class' => 'mt-4'])
          </aside>
        </div>
      </div>
    </div>
  </section>

@endsection
