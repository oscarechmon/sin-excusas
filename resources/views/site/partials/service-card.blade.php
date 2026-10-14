@php
  $price = (float) $service->price;
  $ask = \App\Support\SiteSettings::whatsappUrl("Hola, quiero reservar: {$service->name}");
@endphp

{{-- Toda la tarjeta lleva a la ficha (stretched-link en el título); el botón
     de compra y el enlace de WhatsApp quedan por encima y funcionan aparte. --}}
<article class="se-tile se-tile--link">
  <div class="se-media se-media--card">
    @if ($service->imageUrl())
      <img src="{{ $service->imageUrl() }}" alt="{{ $service->name }}" loading="lazy">
    @else
      <div class="se-placeholder">{{ $service->name }}</div>
    @endif
    @include('site.partials.promo-badge', ['item' => $service])
  </div>
  <div class="se-tile__body p-4">
    <h3 class="se-tile__title" style="--fs: 1.625rem;">
      <a class="stretched-link" href="{{ $service->webUrl() }}">{{ $service->name }}</a>
    </h3>
    @if ($service->description)
      <p class="se-tile__text se-clamp">{{ $service->description }}</p>
    @endif
    @if ($price > 0)
      @include('site.partials.price', ['item' => $service, 'price' => $price, 'class' => 'mb-0'])
    @endif
    <div class="se-tile__meta">
      <span class="se-tile__duration">{{ $service->duration_minutes }} min</span>
      @if ($ask)
        <a class="se-link-mini se-tile__actions" href="{{ $ask }}" target="_blank" rel="noopener">Consultar →</a>
      @endif
    </div>
    @if ($price > 0)
      <form method="post" action="{{ route('shop.cart.add') }}" class="se-tile__actions mt-3" data-cart-add data-turbo="false">
        @csrf
        <input type="hidden" name="type" value="service">
        <input type="hidden" name="id" value="{{ $service->id }}">
        <button type="submit" class="se-btn se-btn--gold se-btn--sm w-100">Comprar en línea</button>
      </form>
    @endif
  </div>
</article>
