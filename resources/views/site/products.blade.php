@extends('site.layout')

@section('title', $current?->name ?? 'Productos')
@section('description', $current?->description ?? 'Suplementos, vitaminas y productos que complementan tus tratamientos. Compra en línea con delivery.')

@section('content')

  <section class="se-pagehead" aria-labelledby="titulo-productos">
    <div class="se-container">
      <p class="se-eyebrow se-eyebrow--row">Productos</p>
      <h1 class="se-pagehead__title" id="titulo-productos">{{ $current?->name ?? 'Nuestros productos' }}</h1>
      <p class="se-lead mt-4" style="max-width: 600px;">
        {{ $current?->description ?? 'Suplementos, vitaminas y productos que complementan tus tratamientos. Cómpralos en línea y recíbelos en casa.' }}
      </p>

      @if ($categories->isNotEmpty())
        <ul class="nav se-tabs" aria-label="Categorías de productos">
          <li class="nav-item">
            <a @class(['nav-link', 'active' => ! $current]) href="{{ route('site.products') }}">Todos</a>
          </li>
          @foreach ($categories as $category)
            <li class="nav-item">
              <a @class(['nav-link', 'active' => $current?->is($category)])
                 href="{{ route('site.products', Str::slug($category->name)) }}">{{ $category->name }}</a>
            </li>
          @endforeach
        </ul>
      @endif
    </div>
  </section>

  <section class="se-section pt-5">
    <div class="se-container">

      @forelse ($groups as $group)
        <div class="se-catalog-group">
          @unless ($current)
            <h2 class="se-serif mb-0" style="--fs: 2.125rem;">{{ $group->name }}</h2>
            @if ($group->description)
              <p class="se-lead mt-2 mb-0" style="max-width: 560px;">{{ $group->description }}</p>
            @endif
            <hr class="se-rule">
          @endunless

          <div class="row g-4 mt-2 row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-4">
            @foreach ($group->items as $product)
              <div class="col">@include('site.partials.product-card', ['product' => $product, 'kicker' => $group->name])</div>
            @endforeach
          </div>
        </div>
      @empty
        @if ($uncategorized->isEmpty())
          <p class="se-empty">Estamos actualizando nuestro catálogo de productos. Escríbenos por WhatsApp para conocer la disponibilidad.</p>
        @endif
      @endforelse

      @if ($uncategorized->isNotEmpty())
        <div class="se-catalog-group">
          <h2 class="se-serif mb-0" style="--fs: 2.125rem;">Otros productos</h2>
          <hr class="se-rule">
          <div class="row g-4 mt-2 row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-4">
            @foreach ($uncategorized as $product)
              <div class="col">@include('site.partials.product-card', ['product' => $product, 'kicker' => 'Producto'])</div>
            @endforeach
          </div>
        </div>
      @endif

    </div>
  </section>

@endsection
