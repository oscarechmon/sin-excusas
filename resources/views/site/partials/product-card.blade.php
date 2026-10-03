@php
  $price = $product->sale_price !== null ? (float) $product->sale_price : 0;
  $inStock = (float) $product->stock >= 1;
  $ask = \App\Support\SiteSettings::whatsappUrl("Hola, quiero información sobre: {$product->name}");
@endphp

{{-- Toda la tarjeta lleva a la ficha (stretched-link en el título); el botón
     y el enlace de WhatsApp quedan por encima y siguen funcionando aparte. --}}
<article class="se-tile se-tile--product">
  <div class="se-media se-media--card">
    @if ($product->imageUrl())
      <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" loading="lazy">
    @else
      <div class="se-placeholder">{{ $product->name }}</div>
    @endif
  </div>
  <div class="se-tile__body">
    <p class="se-tile__kicker mb-0">{{ $kicker }}</p>
    <h3 class="se-tile__title mt-2" style="--fs: 1.438rem;">
      <a class="stretched-link" href="{{ $product->webUrl() }}">{{ $product->name }}</a>
    </h3>
    @if ($product->description)
      <p class="se-tile__text se-clamp-sm" style="font-size: .844rem;">{{ Str::limit($product->description, 110) }}</p>
    @endif

    @if ($price > 0)
      <p class="se-price mb-3">S/ {{ number_format($price, 2) }}</p>
      @if ($inStock)
        <form method="post" action="{{ route('shop.cart.add') }}" class="se-tile__actions mt-auto" data-cart-add data-turbo="false">
          @csrf
          <input type="hidden" name="type" value="product">
          <input type="hidden" name="id" value="{{ $product->id }}">
          <button type="submit" class="se-btn se-btn--gold se-btn--sm w-100">Agregar al carrito</button>
        </form>
      @else
        <p class="se-tile__actions se-muted-note mt-auto mb-0">Agotado @if ($ask)· <a href="{{ $ask }}" target="_blank" rel="noopener">Avísame</a>@endif</p>
      @endif
    @else
      @if ($ask)
        <a class="se-tile__actions se-link-mini mt-auto" href="{{ $ask }}" target="_blank" rel="noopener">Consultar precio →</a>
      @endif
    @endif
  </div>
</article>
