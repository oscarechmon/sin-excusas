@extends('site.layout')

@php
  $price = (float) $service->price;
  $ask = \App\Support\SiteSettings::whatsappUrl("Hola, quiero reservar: {$service->name}");
  // La descripción viene del sistema como texto: una línea en blanco separa párrafos.
  $paragraphs = $service->description ? preg_split('/\R\s*\R/', trim($service->description)) : [];
  // Va armado aquí: dentro de la plantilla, Blade leería "@context" como directiva.
  $schema = json_encode(array_filter([
    '@context' => 'https://schema.org',
    '@type' => 'Service',
    'name' => $service->name,
    'image' => $service->photoUrls() ?: null,
    'description' => $service->description ? Str::squish($service->description) : null,
    'category' => $category->name,
    'offers' => $price > 0 ? [
      '@type' => 'Offer',
      'url' => $service->webUrl(),
      'priceCurrency' => 'PEN',
      'price' => number_format($price, 2, '.', ''),
    ] : null,
  ]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG);
@endphp

@section('title', $service->name)
@section('description', Str::limit(Str::squish($service->description ?: "{$service->name}: reserva o cómpralo en línea en Sin Excusas Centro Estético."), 155))
@if ($service->imageUrl())
  @section('og_image', $service->imageUrl())
@endif

@section('content')

  <section class="se-product" aria-labelledby="titulo-servicio">
    <div class="se-container">

      <nav aria-label="Ruta de navegación">
        <ol class="se-breadcrumb">
          <li><a href="{{ route('site.home') }}">Inicio</a></li>
          <li><a href="{{ route('site.services') }}">Servicios</a></li>
          <li><a href="{{ route('site.services') }}#{{ Str::slug($category->name) }}">{{ $category->name }}</a></li>
          <li aria-current="page">{{ $service->name }}</li>
        </ol>
      </nav>

      <div class="row g-4 g-lg-5 mt-0">
        <div class="col-12 col-lg-6">
          @include('site.partials.gallery', ['item' => $service, 'mediaClass' => 'se-product__media--photo'])
        </div>

        <div class="col-12 col-lg-6">
          <p class="se-tile__kicker mb-0">{{ $category->name }}</p>
          <h1 class="se-product__title" id="titulo-servicio">{{ $service->name }}</h1>

          <div class="d-flex flex-wrap align-items-center gap-3">
            @if ($price > 0)
              @include('site.partials.price', ['item' => $service, 'price' => $price, 'class' => 'se-product__price mb-0'])
            @endif
            @include('site.partials.promo-badge', ['item' => $service, 'inline' => true])
            @if ($service->duration_minutes)
              <span class="se-tile__duration">{{ $service->duration_minutes }} min</span>
            @endif
          </div>

          @if ($paragraphs)
            <div class="se-product__description">
              @foreach ($paragraphs as $paragraph)
                <p>{!! nl2br(e(trim($paragraph))) !!}</p>
              @endforeach
            </div>
          @endif

          @if ($price > 0)
            <form method="post" action="{{ route('shop.cart.add') }}" class="se-buybox" data-cart-add data-turbo="false">
              @csrf
              <input type="hidden" name="type" value="service">
              <input type="hidden" name="id" value="{{ $service->id }}">

              <button type="submit" class="se-btn se-btn--gold w-100">Agregar al carrito</button>
              <button type="submit" name="buy_now" value="1" class="se-btn se-btn--outline w-100 mt-3">Comprar ahora</button>
            </form>

            <ul class="se-perks">
              <li>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="1"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
                Pago seguro en línea
              </li>
            </ul>

            @if ($ask)
              <a class="se-link-mini d-inline-block mt-4" href="{{ $ask }}" target="_blank" rel="noopener">¿Prefieres reservar tu cita? Escríbenos por WhatsApp →</a>
            @endif
          @elseif ($ask)
            <div class="se-buybox">
              <a class="se-btn se-btn--gold" href="{{ $ask }}" target="_blank" rel="noopener">Consultar por WhatsApp</a>
            </div>
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
        <div class="row g-4 mt-2">
          @foreach ($related as $item)
            <div class="col-12 col-md-6 col-lg-4">@include('site.partials.service-card', ['service' => $item])</div>
          @endforeach
        </div>
      </div>
    </section>
  @endif

  {{-- Datos del servicio para buscadores. --}}
  <script type="application/ld+json">{!! $schema !!}</script>

@endsection
