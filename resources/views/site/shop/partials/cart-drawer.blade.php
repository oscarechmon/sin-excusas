{{--
  Contenido del carrito lateral. Lo pide main.js al abrirlo y lo devuelven ya
  armado las acciones del carrito (agregar, +/−, quitar), así siempre muestra
  lo mismo que la página del carrito. Los formularios funcionan también sin
  JavaScript: llevan a la página del carrito.
--}}
@php($money = fn ($value) => 'S/ '.number_format((float) $value, 2))

<div class="se-drawer__content">
  @if ($notice)
    <p @class(['se-drawer__notice', 'se-drawer__notice--error' => $notice['type'] === 'error']) role="status">{{ $notice['text'] }}</p>
  @endif

  @if ($lines->isEmpty())
    <div class="se-drawer__empty">
      <span class="se-drawer__empty-icon">@include('site.partials.cart-icon')</span>
      <p class="mb-0">Tu carrito está vacío.</p>
      <a class="se-btn se-btn--gold se-btn--sm" href="{{ route('site.products') }}">Ver productos</a>
      <a class="se-link-mini" href="{{ route('site.services') }}">Ver servicios</a>
    </div>
  @else
    <ul class="se-drawer__list">
      @foreach ($lines as $line)
        @php($url = $line->isProduct() ? $line->model->webUrl() : route('site.services'))
        <li class="se-drawer__item">
          <a href="{{ $url }}" tabindex="-1" aria-hidden="true">
            @if ($line->model->imageUrl())
              <img class="se-drawer__thumb" src="{{ $line->model->imageUrl() }}" alt="" loading="lazy">
            @else
              <span class="se-drawer__thumb" aria-hidden="true"></span>
            @endif
          </a>

          <div class="se-drawer__info">
            <a class="se-drawer__name" href="{{ $url }}">{{ $line->name() }}</a>
            <span class="se-drawer__meta">{{ $money($line->unitPrice()) }} · {{ $line->isProduct() ? 'Producto' : 'Servicio' }}</span>
            @if ($line->exceedsAvailable())
              <span class="se-drawer__meta text-danger d-block">Solo quedan {{ $line->maxQuantity }}</span>
            @endif

            <div class="se-drawer__controls">
              <form method="post" action="{{ route('shop.cart.update', $line->key) }}" class="se-stepper se-stepper--sm" data-cart-drawer-form>
                @csrf
                @method('PATCH')
                <button type="submit" name="quantity" value="{{ $line->quantity - 1 }}" data-focus="menos-{{ $line->key }}"
                        aria-label="Quitar una unidad de {{ $line->name() }}" @disabled($line->quantity <= 1)>−</button>
                <span class="se-stepper__value" aria-label="Cantidad">{{ $line->quantity }}</span>
                <button type="submit" name="quantity" value="{{ $line->quantity + 1 }}" data-focus="mas-{{ $line->key }}"
                        aria-label="Agregar una unidad de {{ $line->name() }}" @disabled($line->quantity >= $line->maxQuantity)>+</button>
              </form>

              <form method="post" action="{{ route('shop.cart.remove', $line->key) }}" data-cart-drawer-form>
                @csrf
                @method('DELETE')
                <button type="submit" class="se-drawer__remove" aria-label="Quitar {{ $line->name() }} del carrito">Quitar</button>
              </form>
            </div>
          </div>

          <strong class="se-drawer__line-total">{{ $money($line->subtotal()) }}</strong>
        </li>
      @endforeach
    </ul>

    <div class="se-drawer__footer">
      <div class="se-drawer__total">
        <span>Subtotal</span>
        <strong>{{ $money($subtotal) }}</strong>
      </div>
      <p class="se-muted-note mt-1 mb-3">El delivery se calcula al finalizar la compra.</p>
      <a class="se-btn se-btn--gold w-100" href="{{ route('shop.cart.show') }}">Ir al carrito</a>
      <a class="se-btn se-btn--outline w-100 mt-2" href="{{ route('shop.checkout') }}">Finalizar compra</a>
      <button type="button" class="se-link-mini se-drawer__continue" data-bs-dismiss="offcanvas">Seguir comprando</button>
    </div>
  @endif
</div>
