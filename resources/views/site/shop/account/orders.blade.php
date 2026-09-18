@extends('site.layout')

@section('title', 'Mis pedidos')
@section('hide_cta', true)

@section('content')

  <section class="se-pagehead">
    <div class="se-container d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3">
      <div>
        <p class="se-eyebrow se-eyebrow--row">Hola, {{ auth('customer')->user()->name }}</p>
        <h1 class="se-pagehead__title">Mis pedidos</h1>
      </div>
      <form method="post" action="{{ route('shop.logout') }}">
        @csrf
        <button type="submit" class="se-link-mini border-0 bg-transparent p-0">Cerrar sesión</button>
      </form>
    </div>
  </section>

  <section class="se-section pt-5">
    <div class="se-container">
      @if ($orders->isEmpty())
        <div class="se-panel text-center">
          <p class="se-lead mb-4">Todavía no tienes pedidos.</p>
          <a class="se-btn se-btn--gold" href="{{ route('site.products') }}">Ver productos</a>
        </div>
      @else
        <div class="se-panel se-table-wrap">
          <table class="se-table se-table--stack">
            <thead>
              <tr>
                <th scope="col">Pedido</th>
                <th scope="col">Fecha</th>
                <th scope="col">Entrega</th>
                <th scope="col">Estado</th>
                <th scope="col" class="text-end">Total</th>
                <th scope="col"><span class="visually-hidden">Ver</span></th>
              </tr>
            </thead>
            <tbody>
              @foreach ($orders as $order)
                <tr>
                  <td><strong>{{ $order->code }}</strong><span class="se-muted-note d-block">{{ $order->items_count }} artículo(s)</span></td>
                  <td data-label="Fecha">{{ $order->created_at->format('d/m/Y H:i') }}</td>
                  <td data-label="Entrega">{{ $order->fulfillment->label() }}</td>
                  <td data-label="Estado"><span class="se-status se-status--{{ $order->status->value }}">{{ $order->status->label() }}</span></td>
                  <td class="text-end" data-label="Total">S/ {{ number_format((float) $order->total, 2) }}</td>
                  <td class="text-end se-table__action"><a class="se-link-mini" href="{{ route('shop.account.order', $order->code) }}">Seguimiento →</a></td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        <div class="mt-4">{{ $orders->links() }}</div>
      @endif
    </div>
  </section>

@endsection
