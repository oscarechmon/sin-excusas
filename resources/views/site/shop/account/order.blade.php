@extends('site.layout')

@section('title', "Pedido {$order->code}")
@section('hide_cta', true)

@php
  $money = fn ($value) => 'S/ '.number_format((float) $value, 2);
  $steps = \App\Enums\OnlineOrderStatus::trackingSteps($order->fulfillment);
  $currentIndex = array_search($order->status, $steps, true);
@endphp

@section('content')

  <section class="se-pagehead">
    <div class="se-container">
      <a class="se-link-mini" href="{{ route('shop.account.orders') }}">← Mis pedidos</a>
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mt-3">
        <div>
          <p class="se-eyebrow se-eyebrow--row">{{ $order->created_at->format('d/m/Y H:i') }}</p>
          <h1 class="se-pagehead__title">Pedido {{ $order->code }}</h1>
        </div>
        <span class="se-status se-status--{{ $order->status->value }}">{{ $order->status->label() }}</span>
      </div>
    </div>
  </section>

  <section class="se-section pt-5">
    <div class="se-container">

      @if ($order->status->awaitsPayment())
        <div class="se-alert se-alert--warning d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
          <span>Tu pedido está reservado, pero aún no se registra el pago.</span>
          <a class="se-btn se-btn--gold se-btn--sm" href="{{ route('shop.checkout.pay', $order->code) }}">Pagar ahora</a>
        </div>
      @elseif ($order->status === \App\Enums\OnlineOrderStatus::CANCELLED)
        <div class="se-alert se-alert--error mb-4">Este pedido fue anulado. Si tienes dudas, escríbenos por WhatsApp.</div>
      @else
        <div class="se-panel mb-4">
          <h2 class="visually-hidden">Seguimiento</h2>
          <ol class="se-steps">
            @foreach ($steps as $index => $step)
              <li @class(['is-done' => $currentIndex !== false && $index <= $currentIndex, 'is-current' => $currentIndex === $index])>
                {{ $step->label() }}
              </li>
            @endforeach
          </ol>
        </div>
      @endif

      <div class="row g-4">
        <div class="col-12 col-lg-7">
          <div class="se-panel se-table-wrap">
            <h2 class="se-serif mb-3" style="--fs: 1.75rem;">Detalle</h2>
            <table class="se-table">
              <thead>
                <tr>
                  <th scope="col">Artículo</th>
                  <th scope="col">Cant.</th>
                  <th scope="col" class="text-end">Subtotal</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($order->items as $item)
                  <tr>
                    <td>{{ $item->name }}<span class="se-muted-note d-block">{{ $item->item_type === 'product' ? 'Producto' : 'Servicio' }} · {{ $money($item->unit_price) }} c/u</span></td>
                    <td>{{ $item->quantity }}</td>
                    <td class="text-end">{{ $money($item->subtotal) }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
            <div class="se-summary-row se-summary-row--divider"><span>Subtotal</span><span>{{ $money($order->subtotal) }}</span></div>
            @if ((float) $order->delivery_fee > 0)
              <div class="se-summary-row"><span>Delivery</span><span>{{ $money($order->delivery_fee) }}</span></div>
            @endif
            <div class="se-summary-row se-summary-row--total"><span>Total</span><span>{{ $money($order->total) }}</span></div>
          </div>
        </div>

        <div class="col-12 col-lg-5">
          <div class="se-panel mb-4">
            <h2 class="se-serif mb-3" style="--fs: 1.75rem;">Entrega</h2>
            <p class="mb-1"><strong>{{ $order->fulfillment->label() }}</strong></p>
            <p class="se-muted-note mb-0">
              {{ $order->recipient_name }} · {{ $order->phone }}
              @if ($order->address)<br>{{ $order->address }}, {{ $order->district }}@endif
              @if ($order->reference)<br>Ref.: {{ $order->reference }}@endif
            </p>
          </div>

          <div class="se-panel">
            <h2 class="se-serif mb-3" style="--fs: 1.75rem;">Historial</h2>
            <ul class="se-history">
              @foreach ($order->histories->reverse() as $history)
                <li>
                  <strong class="d-block">{{ $history->status->label() }}</strong>
                  <span class="se-muted-note">{{ $history->created_at->format('d/m/Y H:i') }}</span>
                  @if ($history->note)<p class="mb-0 mt-1">{{ $history->note }}</p>@endif
                </li>
              @endforeach
            </ul>
          </div>
        </div>
      </div>

    </div>
  </section>

@endsection
