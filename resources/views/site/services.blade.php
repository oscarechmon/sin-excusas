@extends('site.layout')

@section('title', 'Servicios')
@section('description', 'Tratamientos faciales, corporales, post operatorio, post parto y podología, con evaluación previa.')

@php
  $money = fn ($value) => 'S/ '.number_format((float) $value, 2);
  $ask = fn (string $name) => config('site.whatsapp_url').'?text='.rawurlencode("Hola, quiero reservar: {$name}");
@endphp

@section('content')

  <section class="se-pagehead se-bg-cream" style="border-bottom: 1px solid var(--se-line);">
    <div class="se-container">
      <p class="se-eyebrow se-eyebrow--row">Servicios</p>
      <h1 class="se-pagehead__title">Nuestros tratamientos</h1>

      @if ($categories->isNotEmpty())
        <ul class="nav se-tabs" role="tablist" data-category-tabs>
          @foreach ($categories as $category)
            @php($slug = Str::slug($category->name))
            <li class="nav-item" role="presentation">
              <button @class(['nav-link', 'active' => $loop->first]) id="tab-{{ $slug }}" type="button" role="tab"
                      data-bs-toggle="tab" data-bs-target="#panel-{{ $slug }}" data-category="{{ $slug }}"
                      aria-controls="panel-{{ $slug }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                {{ $category->name }}
              </button>
            </li>
          @endforeach
        </ul>
      @endif
    </div>
  </section>

  <section class="se-section">
    <div class="se-container">

      @forelse ($categories as $category)
        @if ($loop->first)
          <div class="tab-content">
        @endif

        @php($slug = Str::slug($category->name))
        <div @class(['tab-pane fade', 'show active' => $loop->first]) id="panel-{{ $slug }}" role="tabpanel"
             aria-labelledby="tab-{{ $slug }}" tabindex="0">

          <h2 class="se-serif mb-0" style="--fs: 2.375rem;">{{ $category->name }}</h2>
          @if ($category->description)
            <p class="se-lead mt-3" style="max-width: 560px;">{{ $category->description }}</p>
          @endif

          <div class="row g-4 mt-3">
            @foreach ($category->services as $service)
              <div class="col-12 col-md-6 col-lg-4">
                <article class="se-tile">
                  <div class="se-media se-media--card">
                    @if ($service->imageUrl())
                      <img src="{{ $service->imageUrl() }}" alt="{{ $service->name }}" loading="lazy">
                    @else
                      <div class="se-placeholder">{{ $service->name }}</div>
                    @endif
                  </div>
                  <div class="se-tile__body p-4">
                    <h3 class="se-tile__title" style="--fs: 1.625rem;">{{ $service->name }}</h3>
                    @if ($service->description)
                      <p class="se-tile__text se-clamp">{{ $service->description }}</p>
                    @endif
                    @if ((float) $service->price > 0)
                      <p class="se-price mb-0">{{ $money($service->price) }}</p>
                    @endif
                    <div class="se-tile__meta">
                      <span class="se-tile__duration">{{ $service->duration_minutes }} min</span>
                      <a class="se-link-mini" href="{{ $ask($service->name) }}" target="_blank" rel="noopener">Consultar →</a>
                    </div>
                    @if ((float) $service->price > 0)
                      <form method="post" action="{{ route('shop.cart.add') }}" class="mt-3">
                        @csrf
                        <input type="hidden" name="type" value="service">
                        <input type="hidden" name="id" value="{{ $service->id }}">
                        <button type="submit" class="se-btn se-btn--gold se-btn--sm w-100">Comprar en línea</button>
                      </form>
                    @endif
                  </div>
                </article>
              </div>
            @endforeach
          </div>
        </div>

        @if ($loop->last)
          </div>
        @endif
      @empty
        <p class="se-empty">Estamos actualizando nuestros servicios. Escríbenos por WhatsApp y te contamos todo.</p>
      @endforelse

    </div>
  </section>

  @if ($packages->isNotEmpty())
    <section class="se-section se-bg-cream" id="paquetes" aria-labelledby="titulo-paquetes">
      <div class="se-container">
        <p class="se-eyebrow">Paquetes</p>
        <h2 class="se-serif mt-3 mb-0" id="titulo-paquetes" style="--fs: 2.375rem;">Tratamientos por sesiones</h2>
        <hr class="se-rule">

        <div class="row g-4 mt-3">
          @foreach ($packages as $package)
            <div class="col-12 col-md-6 col-lg-4">
              <article class="se-tile">
                <div class="se-tile__body p-4">
                  <p class="se-tile__kicker mb-0">{{ $package->total_sessions }} sesiones</p>
                  <h3 class="se-tile__title mt-2" style="--fs: 1.625rem;">{{ $package->name }}</h3>
                  @if ($package->description)
                    <p class="se-tile__text">{{ $package->description }}</p>
                  @endif
                  <ul class="se-includes">
                    @foreach ($package->services as $included)
                      <li>{{ $included->name }}</li>
                    @endforeach
                  </ul>
                  <p class="se-price mt-4 mb-0">{{ $money($package->price) }}</p>
                  <div class="se-tile__meta">
                    <span class="se-tile__duration">
                      {{ $package->validity_days ? "Vigencia {$package->validity_days} días" : 'Sin vencimiento' }}
                    </span>
                    <a class="se-link-mini" href="{{ $ask($package->name) }}" target="_blank" rel="noopener">Consultar →</a>
                  </div>
                </div>
              </article>
            </div>
          @endforeach
        </div>
      </div>
    </section>
  @endif

@endsection
