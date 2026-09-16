@extends('site.layout')

@section('title', 'Finalizar compra')
@section('hide_cta', true)

@php
  $money = fn ($value) => 'S/ '.number_format((float) $value, 2);
  $canDeliver = $hasProducts && $deliveryEnabled;
  $selected = old('fulfillment', $canDeliver ? 'delivery' : 'pickup');
  $isDelivery = $selected === 'delivery' && $canDeliver;
@endphp

@section('content')

  <section class="se-pagehead">
    <div class="se-container">
      <p class="se-eyebrow se-eyebrow--row">Checkout</p>
      <h1 class="se-pagehead__title">Finalizar compra</h1>
    </div>
  </section>

  <section class="se-section pt-5">
    <div class="se-container">
      <form method="post" action="{{ route('shop.checkout.store') }}"
            data-checkout data-subtotal="{{ $subtotal }}" data-fee="{{ $deliveryFee }}">
        @csrf

        <div class="row g-4">
          <div class="col-12 col-lg-7">
            <div class="se-panel">
              @error('cart')<div class="se-alert se-alert--error mb-4">{{ $message }}</div>@enderror

              <h2 class="se-serif mb-3" style="font-size: 1.75rem;">¿Cómo recibes tu pedido?</h2>
              @error('fulfillment')<div class="se-alert se-alert--error mb-3">{{ $message }}</div>@enderror

              <div class="d-grid gap-3">
                @if ($canDeliver)
                  <label class="se-option">
                    <input class="form-check-input mt-1" type="radio" name="fulfillment" value="delivery" @checked($isDelivery)>
                    <span>
                      <strong class="d-block">Delivery</strong>
                      <span class="se-muted-note">
                        Llevamos tus productos a tu dirección · {{ $deliveryFee > 0 ? '+ '.$money($deliveryFee) : 'Sin costo' }}
                      </span>
                    </span>
                  </label>
                @endif
                <label class="se-option">
                  <input class="form-check-input mt-1" type="radio" name="fulfillment" value="pickup" @checked(! $isDelivery)>
                  <span>
                    <strong class="d-block">Recojo en el centro</strong>
                    <span class="se-muted-note">
                      {{ $hasProducts ? 'Recoge tus productos en el centro, sin costo.' : 'Los servicios se atienden en el centro. Te contactaremos para agendar tu cita.' }}
                    </span>
                  </span>
                </label>
              </div>

              @if ($hasProducts && ! $deliveryEnabled)
                <p class="se-muted-note mt-2">El delivery no está disponible por el momento.</p>
              @endif

              @if ($canDeliver)
                <div class="row g-3 mt-2" data-delivery-fields @unless ($isDelivery) hidden @endunless>
                  @include('site.shop.partials.field', ['name' => 'address', 'label' => 'Dirección', 'class' => 'col-12', 'deliveryOnly' => true, 'autocomplete' => 'street-address'])
                  @include('site.shop.partials.field', ['name' => 'district', 'label' => 'Distrito', 'class' => 'col-12 col-md-6', 'deliveryOnly' => true])
                  @include('site.shop.partials.field', ['name' => 'reference', 'label' => 'Referencia', 'class' => 'col-12 col-md-6'])
                </div>
              @endif

              <h2 class="se-serif mt-5 mb-3" style="font-size: 1.75rem;">Datos de contacto</h2>
              <div class="row g-3">
                @include('site.shop.partials.field', ['name' => 'recipient_name', 'label' => 'Nombre de quien recibe', 'value' => $customer->name, 'required' => true, 'class' => 'col-12 col-md-6', 'autocomplete' => 'name'])
                @include('site.shop.partials.field', ['name' => 'phone', 'label' => 'Teléfono / WhatsApp', 'value' => $customer->phone, 'required' => true, 'type' => 'tel', 'class' => 'col-12 col-md-6', 'autocomplete' => 'tel'])
                @include('site.shop.partials.field', ['name' => 'notes', 'label' => 'Notas para el pedido', 'type' => 'textarea', 'class' => 'col-12'])
              </div>
            </div>
          </div>

          <div class="col-12 col-lg-5">
            <aside class="se-panel se-sticky">
              <h2 class="se-serif mb-3" style="font-size: 1.75rem;">Tu pedido</h2>

              @foreach ($lines as $line)
                <div class="se-summary-row">
                  <span>{{ $line->name() }} <span class="se-muted-note">× {{ $line->quantity }}</span></span>
                  <span>{{ $money($line->subtotal()) }}</span>
                </div>
              @endforeach

              <div class="se-summary-row se-summary-row--divider"><span>Subtotal</span><span>{{ $money($subtotal) }}</span></div>
              @if ($canDeliver)
                <div class="se-summary-row" data-fee-row @unless ($isDelivery) hidden @endunless>
                  <span>Delivery</span><span>{{ $money($deliveryFee) }}</span>
                </div>
              @endif
              <div class="se-summary-row se-summary-row--total">
                <span>Total</span>
                <span data-total>{{ $money($subtotal + ($isDelivery ? $deliveryFee : 0)) }}</span>
              </div>

              <button type="submit" class="se-btn se-btn--gold w-100 mt-4">Continuar al pago</button>
              <p class="se-muted-note text-center mt-3 mb-0">Pago seguro con tarjeta a través de Izipay.</p>
            </aside>
          </div>
        </div>
      </form>
    </div>
  </section>

@endsection
