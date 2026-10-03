@extends('site.layout')

@php
  $price = $product->sale_price !== null ? (float) $product->sale_price : 0;
  $stock = max(0, (int) floor((float) $product->stock));
  $kicker = $category?->name ?? 'Producto';
  $ask = \App\Support\SiteSettings::whatsappUrl("Hola, quiero información sobre: {$product->name}");
  // La descripción viene del sistema como texto: una línea en blanco separa párrafos.
  $paragraphs = $product->description ? preg_split('/\R\s*\R/', trim($product->description)) : [];
  // Va armado aquí: dentro de la plantilla, Blade leería "@context" como directiva.
  $schema = json_encode(array_filter([
    '@context' => 'https://schema.org',
    '@type' => 'Product',
    'name' => $product->name,
    'image' => $product->imageUrl(),
    'description' => $product->description ? Str::squish($product->description) : null,
    'category' => $category?->name,
    'offers' => $price > 0 ? [
      '@type' => 'Offer',
      'url' => $product->webUrl(),
      'priceCurrency' => 'PEN',
      'price' => number_format($price, 2, '.', ''),
      'availability' => $stock > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
    ] : null,
  ]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG);
@endphp

@section('title', $product->name)
@section('description', Str::limit(Str::squish($product->description ?: "{$product->name}: cómpralo en línea en Sin Excusas Centro Estético, con delivery o recojo en el centro."), 155))
@if ($product->imageUrl())
  @section('og_image', $product->imageUrl())
@endif

@section('content')

  <section class="se-product" aria-labelledby="titulo-producto">
    <div class="se-container">

      <nav aria-label="Ruta de navegación">
        <ol class="se-breadcrumb">
          <li><a href="{{ route('site.home') }}">Inicio</a></li>
          <li><a href="{{ route('site.products') }}">Productos</a></li>
          @if ($category)
            <li><a href="{{ route('site.products', Str::slug($category->name)) }}">{{ $category->name }}</a></li>
          @endif
          <li aria-current="page">{{ $product->name }}</li>
        </ol>
      </nav>

      <div class="row g-4 g-lg-5 mt-0">
        <div class="col-12 col-lg-6">
          <div class="se-product__media">
            @if ($product->imageUrl())
              <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}">
            @else
              <div class="se-placeholder">{{ $product->name }}</div>
            @endif
          </div>
        </div>

        <div class="col-12 col-lg-6">
          <p class="se-tile__kicker mb-0">{{ $kicker }}</p>
          <h1 class="se-product__title" id="titulo-producto">{{ $product->name }}</h1>

          @if ($price > 0)
            <div class="d-flex flex-wrap align-items-center gap-3">
              <p class="se-price se-product__price mb-0">S/ {{ number_format($price, 2) }}</p>
              @if ($stock < 1)
                <span class="se-stock se-stock--out">Agotado</span>
              @elseif ($stock <= 5)
                <span class="se-stock se-stock--low">{{ $stock === 1 ? 'Última unidad' : "Últimas {$stock} unidades" }}</span>
              @else
                <span class="se-stock">En stock</span>
              @endif
            </div>
          @endif

          @if ($paragraphs)
            <div class="se-product__description">
              @foreach ($paragraphs as $paragraph)
                <p>{!! nl2br(e(trim($paragraph))) !!}</p>
              @endforeach
            </div>
          @endif

          @if ($price > 0 && $stock > 0)
            <form method="post" action="{{ route('shop.cart.add') }}" class="se-buybox" data-cart-add data-turbo="false">
              @csrf
              <input type="hidden" name="type" value="product">
              <input type="hidden" name="id" value="{{ $product->id }}">

              <label class="se-buybox__label" for="cantidad">Cantidad</label>
              <div class="se-buybox__row">
                <div class="se-stepper" data-qty-stepper>
                  <button type="button" data-step="-1" aria-label="Quitar una unidad">−</button>
                  <input id="cantidad" type="number" name="quantity" value="1" min="1" max="{{ min($stock, 99) }}" inputmode="numeric">
                  <button type="button" data-step="1" aria-label="Agregar una unidad">+</button>
                </div>
                <button type="submit" class="se-btn se-btn--gold">Agregar al carrito</button>
              </div>
              <button type="submit" name="buy_now" value="1" class="se-btn se-btn--outline w-100 mt-3">Comprar ahora</button>
            </form>
          @elseif ($price > 0)
            <div class="se-buybox">
              <p class="se-muted-note mb-3">Este producto está agotado por el momento.</p>
              @if ($ask)
                <a class="se-btn se-btn--outline" href="{{ $ask }}" target="_blank" rel="noopener">Avísame por WhatsApp</a>
              @endif
            </div>
          @elseif ($ask)
            <div class="se-buybox">
              <a class="se-btn se-btn--gold" href="{{ $ask }}" target="_blank" rel="noopener">Consultar precio por WhatsApp</a>
            </div>
          @endif

          @if ($price > 0)
            <ul class="se-perks">
              @if (\App\Models\StoreSetting::deliveryEnabled())
                <li>
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 7h11v9H3zM14 10h4l3 3v3h-7"/><circle cx="7" cy="17.5" r="1.5"/><circle cx="17" cy="17.5" r="1.5"/></svg>
                  Delivery a domicilio
                </li>
              @endif
              <li>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 10h16v10H4zM3 10l2-5h14l2 5"/><path d="M10 20v-5h4v5"/></svg>
                Recojo en el centro
              </li>
              <li>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="1"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
                Pago seguro en línea
              </li>
            </ul>
          @endif

          @if ($ask && $price > 0 && $stock > 0)
            <a class="se-link-mini d-inline-block mt-4" href="{{ $ask }}" target="_blank" rel="noopener">¿Tienes dudas? Escríbenos por WhatsApp →</a>
          @endif
        </div>
      </div>
    </div>
  </section>

  @if ($related->isNotEmpty())
    <section class="se-section se-bg-cream" aria-labelledby="titulo-relacionados">
      <div class="se-container">
        <h2 class="se-serif mb-0" id="titulo-relacionados" style="--fs: 2.125rem;">También te puede interesar</h2>
        <hr class="se-rule">
        <div class="row g-4 mt-2 row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-4">
          @foreach ($related as $item)
            <div class="col">@include('site.partials.product-card', ['product' => $item, 'kicker' => $kicker])</div>
          @endforeach
        </div>
      </div>
    </section>
  @endif

  {{-- Datos del producto para buscadores (precio y disponibilidad en Google). --}}
  <script type="application/ld+json">{!! $schema !!}</script>

@endsection
