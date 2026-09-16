@extends('site.layout')

@section('title', 'Carrito')
@section('hide_cta', true)

@php($money = fn ($value) => 'S/ '.number_format((float) $value, 2))

@section('content')

  <section class="se-pagehead">
    <div class="se-container">
      <p class="se-eyebrow se-eyebrow--row">Tienda</p>
      <h1 class="se-pagehead__title">Tu carrito</h1>
    </div>
  </section>

  <section class="se-section pt-5">
    <div class="se-container">

      @if ($lines->isEmpty())
        <div class="se-panel text-center">
          <p class="se-lead mb-4">Tu carrito está vacío.</p>
          <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
            <a class="se-btn se-btn--gold" href="{{ route('site.products') }}">Ver productos</a>
            <a class="se-btn se-btn--outline" href="{{ route('site.services') }}">Ver servicios</a>
          </div>
        </div>
      @else
        <div class="row g-4">
          <div class="col-12 col-lg-8">
            <div class="se-panel se-table-wrap">
              <table class="se-table">
                <thead>
                  <tr>
                    <th scope="col">Artículo</th>
                    <th scope="col">Precio</th>
                    <th scope="col">Cantidad</th>
                    <th scope="col" class="text-end">Subtotal</th>
                    <th scope="col"><span class="visually-hidden">Quitar</span></th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($lines as $line)
                    <tr>
                      <td>
                        <div class="d-flex align-items-center gap-3">
                          @if ($line->model->imageUrl())
                            <img class="se-thumb" src="{{ $line->model->imageUrl() }}" alt="">
                          @else
                            <span class="se-thumb" aria-hidden="true"></span>
                          @endif
                          <span>
                            <strong class="d-block">{{ $line->name() }}</strong>
                            <span class="se-muted-note">{{ $line->isProduct() ? 'Producto' : 'Servicio' }}</span>
                          </span>
                        </div>
                      </td>
                      <td>{{ $money($line->unitPrice()) }}</td>
                      <td>
                        <form method="post" action="{{ route('shop.cart.update', $line->key) }}" class="d-flex gap-2 align-items-center">
                          @csrf
                          @method('PATCH')
                          <label class="visually-hidden" for="qty-{{ $loop->index }}">Cantidad de {{ $line->name() }}</label>
                          <input id="qty-{{ $loop->index }}" class="form-control se-qty" type="number" name="quantity"
                                 value="{{ $line->quantity }}" min="1" max="{{ $line->maxQuantity }}">
                          <button type="submit" class="se-link-mini border-0 bg-transparent p-0">Actualizar</button>
                        </form>
                        @if ($line->exceedsAvailable())
                          <span class="se-muted-note text-danger">Solo quedan {{ $line->maxQuantity }}</span>
                        @endif
                      </td>
                      <td class="text-end">{{ $money($line->subtotal()) }}</td>
                      <td class="text-end">
                        <form method="post" action="{{ route('shop.cart.remove', $line->key) }}">
                          @csrf
                          @method('DELETE')
                          <button type="submit" class="se-icon-btn" aria-label="Quitar {{ $line->name() }}">×</button>
                        </form>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>

          <div class="col-12 col-lg-4">
            <aside class="se-panel">
              <h2 class="se-serif mb-3" style="font-size: 1.75rem;">Resumen</h2>
              <div class="se-summary-row"><span>Subtotal</span><strong>{{ $money($subtotal) }}</strong></div>
              <p class="se-muted-note mt-2">El costo de delivery se calcula en el siguiente paso.</p>
              <a class="se-btn se-btn--gold w-100 mt-3" href="{{ route('shop.checkout') }}">Finalizar compra</a>
              <a class="se-link-mini d-block text-center mt-3" href="{{ route('site.products') }}">← Seguir comprando</a>
            </aside>
          </div>
        </div>
      @endif

    </div>
  </section>

@endsection
