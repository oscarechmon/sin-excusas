{{-- Cómo se entrega un pedido: dirección en Lima, agencia Shalom en provincia o recojo. --}}
@php($fulfillment = $order->fulfillment)

<div class="se-delivery-card {{ $class ?? '' }}">
  <div class="d-flex align-items-center justify-content-between gap-3">
    <strong>{{ $fulfillment->label() }}</strong>
    @if ($fulfillment === \App\Enums\FulfillmentType::PROVINCE)
      <img src="{{ asset('assets/img/pagos/shalom.png') }}" alt="Shalom" width="90" height="18">
    @endif
  </div>

  <dl>
    <dt>{{ $fulfillment === \App\Enums\FulfillmentType::PICKUP ? 'Recoge' : 'Recibe' }}</dt>
    <dd>{{ $order->recipient_name }}</dd>

    @if ($order->document_number)
      <dt>{{ $order->document_type === 'ce' ? 'CE' : 'DNI' }}</dt>
      <dd>{{ $order->document_number }}</dd>
    @endif

    <dt>Teléfono</dt>
    <dd>{{ $order->phone }}</dd>

    @if ($fulfillment === \App\Enums\FulfillmentType::DELIVERY)
      <dt>Dirección</dt>
      <dd>{{ $order->address }}, {{ $order->district }}</dd>
      @if ($order->reference)
        <dt>Referencia</dt>
        <dd>{{ $order->reference }}</dd>
      @endif
    @elseif ($fulfillment === \App\Enums\FulfillmentType::PROVINCE)
      <dt>Destino</dt>
      <dd>{{ $order->province }}, {{ $order->department }}</dd>
      <dt>Agencia</dt>
      <dd>{{ $order->agency }}</dd>
    @endif
  </dl>
</div>
