@php
  $price = $product->sale_price !== null ? (float) $product->sale_price : 0;
  $inStock = (float) $product->stock >= 1;
  $ask = config('site.whatsapp_url').'?text='.rawurlencode("Hola, quiero información sobre: {$product->name}");
@endphp

<article class="se-tile">
  <div class="se-media se-media--card">
    @if ($product->imageUrl())
      <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" loading="lazy">
    @else
      <div class="se-placeholder">{{ $product->name }}</div>
    @endif
  </div>
  <div class="se-tile__body">
    <p class="se-tile__kicker mb-0">{{ $kicker }}</p>
    <h3 class="se-tile__title mt-2" style="--fs: 1.438rem;">{{ $product->name }}</h3>
    @if ($product->description)
      <p class="se-tile__text se-clamp-sm" style="font-size: .844rem;">{{ $product->description }}</p>
    @endif

    @if ($price > 0)
      <p class="se-price mb-3">S/ {{ number_format($price, 2) }}</p>
      @if ($inStock)
        <form method="post" action="{{ route('shop.cart.add') }}" class="mt-auto">
          @csrf
          <input type="hidden" name="type" value="product">
          <input type="hidden" name="id" value="{{ $product->id }}">
          <button type="submit" class="se-btn se-btn--gold se-btn--sm w-100">Agregar al carrito</button>
        </form>
      @else
        <p class="se-muted-note mt-auto mb-0">Agotado · <a href="{{ $ask }}" target="_blank" rel="noopener">Avísame</a></p>
      @endif
    @else
      <a class="se-link-mini mt-auto" href="{{ $ask }}" target="_blank" rel="noopener">Consultar precio →</a>
    @endif
  </div>
</article>
