@extends('site.layout')

@section('title', 'Finalizar compra')
@section('hide_cta', true)

@php
  $money = fn ($value) => 'S/ '.number_format((float) $value, 2);

  // Formas de recibir el pedido. Los envíos son solo para productos: los
  // servicios se atienden en el centro.
  $options = collect([
      'delivery' => [
          'title' => 'Delivery en Lima',
          'text' => 'Llevamos tu pedido a tu dirección en Lima Metropolitana.',
          'fee' => $rates['delivery']['fee'],
          'available' => $hasProducts && $rates['delivery']['enabled'],
      ],
      'province' => [
          'title' => 'Envío a provincia',
          'text' => 'Lo recoges en la agencia Shalom de tu ciudad con tu DNI o CE.',
          'fee' => $rates['province']['fee'],
          'available' => $hasProducts && $rates['province']['enabled'],
      ],
      'pickup' => [
          'title' => 'Recojo en el centro',
          'text' => $hasProducts
              ? 'Recoge tu pedido en el centro, sin costo.'
              : 'Los servicios se atienden en el centro. Te contactaremos para agendar tu cita.',
          'fee' => 0,
          'available' => true,
      ],
  ])->filter(fn ($option) => $option['available']);

  // Envíos que el centro apagó por ahora (solo importa si hay productos).
  $unavailable = $hasProducts
      ? collect(['delivery' => 'delivery en Lima', 'province' => 'envíos a provincia'])->reject(fn ($label, $key) => $options->has($key))
      : collect();

  $selected = old('fulfillment');
  if (! $options->has($selected)) {
      $selected = $options->keys()->first();
  }
  $fee = (float) $options[$selected]['fee'];
@endphp

@section('content')

  <section class="se-pagehead">
    <div class="se-container">
      <p class="se-eyebrow se-eyebrow--row">Checkout</p>
      <h1 class="se-pagehead__title">Finalizar compra</h1>
      <ol class="se-checkout-steps" aria-label="Pasos de la compra">
        <li class="is-current" aria-current="step"><span>1</span> Datos y entrega</li>
        <li><span>2</span> Pago seguro</li>
        <li><span>3</span> Seguimiento</li>
      </ol>
    </div>
  </section>

  <section class="se-section pt-5">
    <div class="se-container">
      <form method="post" action="{{ route('shop.checkout.store') }}" data-checkout data-subtotal="{{ $subtotal }}">
        @csrf

        <div class="row g-4">
          <div class="col-12 col-lg-7">
            <div class="se-panel">
              @error('cart')<div class="se-alert se-alert--error mb-4">{{ $message }}</div>@enderror

              <h2 class="se-checkout-title"><span class="se-checkout-title__n">1</span> ¿Cómo recibes tu pedido?</h2>
              @error('fulfillment')<div class="se-alert se-alert--error mb-3">{{ $message }}</div>@enderror

              <div class="se-ship-options" role="radiogroup" aria-label="Forma de entrega">
                @foreach ($options as $mode => $option)
                  <label class="se-ship">
                    <input class="se-ship__input" type="radio" name="fulfillment" value="{{ $mode }}"
                           data-fee="{{ $option['fee'] }}" data-label="{{ $option['title'] }}" @checked($selected === $mode)>
                    <span class="se-ship__media" aria-hidden="true">
                      @if ($mode === 'province')
                        <img src="{{ asset('assets/img/pagos/shalom.png') }}" alt="" width="96" height="19">
                      @elseif ($mode === 'delivery')
                        <svg viewBox="0 0 24 24" width="28" height="28"><path fill="currentColor" d="M3 5h11v3h3.6l3.4 4.1V17h-2.1a2.5 2.5 0 0 1-4.8 0H9.9a2.5 2.5 0 0 1-4.8 0H3V5Zm2 2v8h.3a2.5 2.5 0 0 1 4.4 0H12V7H5Zm9 3v5h.3a2.5 2.5 0 0 1 4.4 0h.3v-2.2L16.6 10H14Zm-6.5 6a.9.9 0 1 0 0 1.8.9.9 0 0 0 0-1.8Zm9 0a.9.9 0 1 0 0 1.8.9.9 0 0 0 0-1.8Z"/></svg>
                      @else
                        <svg viewBox="0 0 24 24" width="28" height="28"><path fill="currentColor" d="M4 4h16l1 5a3 3 0 0 1-2 2.83V20H5v-8.17A3 3 0 0 1 3 9l1-5Zm1.6 2-.6 3a1 1 0 0 0 2 0V6H5.6ZM9 6v3a1 1 0 0 0 2 0V6H9Zm4 0v3a1 1 0 0 0 2 0V6h-2Zm4 0v3a1 1 0 0 0 2 0l-.6-3H17ZM7 12v6h4v-4h2v4h4v-6a3 3 0 0 1-2-.78 3 3 0 0 1-4 0 3 3 0 0 1-4 0Z"/></svg>
                      @endif
                    </span>
                    <span class="se-ship__body">
                      <strong class="se-ship__title">{{ $option['title'] }}</strong>
                      <span class="se-ship__text">{{ $option['text'] }}</span>
                    </span>
                    <span class="se-ship__price">{{ $option['fee'] > 0 ? $money($option['fee']) : 'Gratis' }}</span>
                  </label>
                @endforeach
              </div>

              @if ($unavailable->isNotEmpty())
                <p class="se-muted-note mt-2 mb-0">Por el momento no hacemos {{ $unavailable->join(' ni ') }}.</p>
              @endif

              <h2 class="se-checkout-title mt-5"><span class="se-checkout-title__n">2</span> Datos de quien recibe</h2>
              <div class="row g-3">
                @include('site.shop.partials.field', [
                    'name' => 'recipient_name', 'label' => 'Nombres y apellidos completos', 'value' => $customer->name,
                    'required' => true, 'class' => 'col-12 col-md-7', 'autocomplete' => 'name',
                    'placeholder' => 'Ej.: María Fernanda López Quispe',
                ])
                @include('site.shop.partials.field', [
                    'name' => 'phone', 'label' => 'Teléfono / WhatsApp', 'value' => $customer->phone, 'required' => true,
                    'type' => 'tel', 'class' => 'col-12 col-md-5', 'autocomplete' => 'tel', 'inputmode' => 'tel',
                ])
              </div>

              @if ($options->has('province'))
                <div class="row g-3 mt-0" data-fields="province" @unless ($selected === 'province') hidden @endunless>
                  @include('site.shop.partials.field', [
                      'name' => 'document_type', 'label' => 'Documento', 'type' => 'select', 'groupRequired' => true,
                      'class' => 'col-5 col-md-4', 'options' => ['dni' => 'DNI', 'ce' => 'Carné de extranjería'], 'value' => 'dni',
                  ])
                  @include('site.shop.partials.field', [
                      'name' => 'document_number', 'label' => 'Número de documento', 'groupRequired' => true,
                      'class' => 'col-7 col-md-8', 'maxlength' => 12, 'inputmode' => 'text', 'placeholder' => 'Ej.: 45678912',
                      'hint' => 'La agencia te pedirá este documento y que el nombre coincida al recoger.',
                  ])
                </div>
              @endif

              @if ($options->has('delivery'))
                <div data-fields="delivery" @unless ($selected === 'delivery') hidden @endunless>
                  <h2 class="se-checkout-title mt-5"><span class="se-checkout-title__n">3</span> Dirección de entrega</h2>
                  <div class="row g-3">
                    @include('site.shop.partials.field', ['name' => 'address', 'label' => 'Dirección', 'class' => 'col-12', 'groupRequired' => true, 'autocomplete' => 'street-address', 'placeholder' => 'Av., calle, número, dpto.'])
                    @include('site.shop.partials.field', ['name' => 'district', 'label' => 'Distrito', 'class' => 'col-12 col-md-6', 'groupRequired' => true])
                    @include('site.shop.partials.field', ['name' => 'reference', 'label' => 'Referencia', 'class' => 'col-12 col-md-6', 'placeholder' => 'Cerca de…'])
                  </div>
                </div>
              @endif

              @if ($options->has('province'))
                <div data-fields="province" @unless ($selected === 'province') hidden @endunless>
                  <h2 class="se-checkout-title mt-5"><span class="se-checkout-title__n">3</span> Agencia Shalom de destino</h2>
                  <div class="se-agency-note">
                    <img src="{{ asset('assets/img/pagos/shalom.png') }}" alt="Shalom" width="110" height="22">
                    <p class="mb-0">
                      Enviamos tu pedido por <strong>Agencia Shalom</strong>. Cuando llegue, lo recoges en la agencia
                      que elijas presentando tu documento. Te enviaremos por WhatsApp el número de orden.
                      <a href="https://shalom.com.pe/agencias" target="_blank" rel="noopener">Busca tu agencia&nbsp;↗</a>
                    </p>
                  </div>
                  <div class="row g-3">
                    @include('site.shop.partials.field', [
                        'name' => 'department', 'label' => 'Departamento', 'type' => 'select', 'groupRequired' => true,
                        'class' => 'col-12 col-md-6', 'placeholder' => 'Elige…',
                        'options' => array_combine(\App\Support\Peru::DEPARTMENTS, \App\Support\Peru::DEPARTMENTS),
                    ])
                    @include('site.shop.partials.field', ['name' => 'province', 'label' => 'Provincia / ciudad', 'class' => 'col-12 col-md-6', 'groupRequired' => true, 'placeholder' => 'Ej.: Arequipa'])
                    @include('site.shop.partials.field', [
                        'name' => 'agency', 'label' => 'Agencia Shalom donde recoges', 'class' => 'col-12', 'groupRequired' => true,
                        'placeholder' => 'Ej.: Shalom Av. Ejército 123, Cayma',
                    ])
                  </div>
                </div>
              @endif

              <div class="row g-3 mt-4">
                @include('site.shop.partials.field', ['name' => 'notes', 'label' => 'Notas para el pedido (opcional)', 'type' => 'textarea', 'class' => 'col-12'])
              </div>
            </div>
          </div>

          <div class="col-12 col-lg-5">
            <aside class="se-panel se-sticky">
              <h2 class="se-serif mb-3" style="--fs: 1.75rem;">Tu pedido</h2>

              @foreach ($lines as $line)
                <div class="se-summary-row">
                  <span>{{ $line->name() }} <span class="se-muted-note">× {{ $line->quantity }}</span></span>
                  <span>{{ $money($line->subtotal()) }}</span>
                </div>
              @endforeach

              <div class="se-summary-row se-summary-row--divider"><span>Subtotal</span><span>{{ $money($subtotal) }}</span></div>
              <div class="se-summary-row" data-fee-row @if ($fee <= 0) hidden @endif>
                <span data-fee-label>{{ $options[$selected]['title'] }}</span>
                <span data-fee-amount>{{ $money($fee) }}</span>
              </div>
              <div class="se-summary-row se-summary-row--total">
                <span>Total</span>
                <span data-total>{{ $money($subtotal + $fee) }}</span>
              </div>

              <button type="submit" class="se-btn se-btn--gold w-100 mt-4">Continuar al pago</button>

              @include('site.shop.partials.secure-payment', ['class' => 'mt-4'])
            </aside>
          </div>
        </div>
      </form>
    </div>
  </section>

@endsection
