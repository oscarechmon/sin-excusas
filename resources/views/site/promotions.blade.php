@extends('site.layout')

@section('title', 'Promociones')
@section('description', 'Tratamientos y productos con precio especial en Sin Excusas Centro Estético. Reserva o compra en línea.')

@php
  // Una pestaña por tipo, y solo las que tienen algo en promoción.
  $groups = collect([
    'servicios' => ['label' => 'Servicios', 'items' => $services],
    'productos' => ['label' => 'Productos', 'items' => $products],
  ])->filter(fn (array $group) => $group['items']->isNotEmpty());
@endphp

@section('content')

  <section class="se-pagehead se-bg-cream" style="border-bottom: 1px solid var(--se-line);">
    <div class="se-container">
      <p class="se-eyebrow se-eyebrow--row">Promociones</p>
      <h1 class="se-pagehead__title">Precios especiales</h1>
      <p class="se-lead mt-4" style="max-width: 600px;">
        Tratamientos y productos en promoción. Resérvalos o cómpralos en línea mientras dure la promoción.
      </p>

      @if ($groups->isNotEmpty())
        <ul class="nav se-tabs" role="tablist" data-category-tabs>
          @foreach ($groups as $slug => $group)
            <li class="nav-item" role="presentation">
              <button @class(['nav-link', 'active' => $loop->first]) id="tab-{{ $slug }}" type="button" role="tab"
                      data-bs-toggle="tab" data-bs-target="#panel-{{ $slug }}" data-category="{{ $slug }}"
                      aria-controls="panel-{{ $slug }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                {{ $group['label'] }}
              </button>
            </li>
          @endforeach
        </ul>
      @endif
    </div>
  </section>

  <section class="se-section">
    <div class="se-container">

      @forelse ($groups as $slug => $group)
        @if ($loop->first)
          <div class="tab-content">
        @endif

        <div @class(['tab-pane fade', 'show active' => $loop->first]) id="panel-{{ $slug }}" role="tabpanel"
             aria-labelledby="tab-{{ $slug }}" tabindex="0">

          @if ($slug === 'servicios')
            <div class="row g-4">
              @foreach ($group['items'] as $service)
                <div class="col-12 col-md-6 col-lg-4">
                  @include('site.partials.service-card')
                </div>
              @endforeach
            </div>
          @else
            <div class="row g-4 row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-4">
              @foreach ($group['items'] as $product)
                <div class="col">@include('site.partials.product-card', ['product' => $product, 'kicker' => $product->category?->name ?? 'Producto'])</div>
              @endforeach
            </div>
          @endif
        </div>

        @if ($loop->last)
          </div>
        @endif
      @empty
        <p class="se-empty">Por ahora no tenemos promociones activas. Escríbenos por WhatsApp y te contamos las novedades.</p>
      @endforelse

    </div>
  </section>

@endsection
