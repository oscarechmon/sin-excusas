@php
    $productCategories = \App\Support\SiteMenu::productCategories();
    $cartCount = app(\App\Services\Shop\Cart::class)->count();
    $customer = auth('customer')->user();
    $links = [
        'site.home' => 'Inicio',
        'site.about' => 'Nosotros',
        'site.services' => 'Servicios',
    ];
@endphp

<div class="se-topbar">
  <div class="se-container">
    <span class="se-topbar__note">Atención con cita previa · Lun a Sáb</span>
    <a href="{{ config('site.whatsapp_url') }}" target="_blank" rel="noopener">Reservar por WhatsApp</a>
  </div>
</div>

<header class="se-header">
  <nav class="navbar navbar-expand-lg p-0" aria-label="Navegación principal">
    <div class="se-container d-flex align-items-center gap-3">

      <a class="se-brand" href="{{ route('site.home') }}" aria-label="Sin Excusas, inicio">
        <img class="se-brand__logo" src="{{ asset('assets/img/logo-sin-excusas.png') }}" alt="" width="64" height="64">
        <span>
          <span class="se-brand__name d-block">SIN EXCUSAS</span>
          <span class="se-brand__tag d-block">Centro estético</span>
        </span>
      </a>

      {{-- En móvil el carrito queda visible fuera del menú colapsado. --}}
      <a class="se-cart-link d-lg-none ms-auto" href="{{ route('shop.cart.show') }}" aria-label="Carrito, {{ $cartCount }} artículos">
        @include('site.partials.cart-icon')
        @if ($cartCount > 0)<span class="se-cart-badge">{{ $cartCount }}</span>@endif
      </a>

      <button class="navbar-toggler se-toggler" type="button"
              data-bs-toggle="collapse" data-bs-target="#seMainNav"
              aria-controls="seMainNav" aria-expanded="false" aria-label="Abrir menú">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="seMainNav">
        <ul class="navbar-nav se-nav d-flex flex-lg-row gap-lg-4 mb-0">
          @foreach ($links as $route => $label)
            <li class="nav-item">
              <a @class(['nav-link', 'active' => request()->routeIs($route)])
                 @if (request()->routeIs($route)) aria-current="page" @endif
                 href="{{ route($route) }}">{{ $label }}</a>
            </li>
          @endforeach

          <li class="nav-item dropdown">
            <a @class(['nav-link dropdown-toggle', 'active' => request()->routeIs('site.products')])
               href="{{ route('site.products') }}" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              Productos
            </a>
            <ul class="dropdown-menu se-dropdown">
              <li><a class="dropdown-item" href="{{ route('site.products') }}">Todos los productos</a></li>
              @foreach ($productCategories as $category)
                <li>
                  <a class="dropdown-item" href="{{ route('site.products', Str::slug($category->name)) }}">{{ $category->name }}</a>
                </li>
              @endforeach
            </ul>
          </li>
        </ul>

        <div class="se-header__actions ms-lg-auto mt-3 mt-lg-0">
          @if ($customer)
            <div class="dropdown">
              <button class="se-cart-link se-account-toggle dropdown-toggle border-0 bg-transparent p-0" type="button"
                      data-bs-toggle="dropdown" aria-expanded="false">
                @if ($customer->avatar_url)
                  <img class="se-avatar" src="{{ $customer->avatar_url }}" alt="" width="24" height="24" referrerpolicy="no-referrer">
                @endif
                {{ Str::words($customer->name, 1, '') }}
              </button>
              <ul class="dropdown-menu dropdown-menu-end se-dropdown">
                <li><span class="dropdown-item-text se-muted-note">{{ $customer->email }}</span></li>
                <li><a class="dropdown-item" href="{{ route('shop.account.orders') }}">Mis pedidos</a></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                  <form method="post" action="{{ route('shop.logout') }}">
                    @csrf
                    <button type="submit" class="dropdown-item">Cerrar sesión</button>
                  </form>
                </li>
              </ul>
            </div>
          @else
            <a class="se-cart-link" href="{{ route('shop.login') }}">Ingresar</a>
          @endif
          <a class="se-cart-link d-none d-lg-inline-flex" href="{{ route('shop.cart.show') }}" aria-label="Carrito, {{ $cartCount }} artículos">
            @include('site.partials.cart-icon')
            @if ($cartCount > 0)<span class="se-cart-badge">{{ $cartCount }}</span>@endif
          </a>
          <a class="se-btn se-btn--gold se-btn--sm"
             href="{{ config('site.whatsapp_url') }}" target="_blank" rel="noopener">Agendar cita</a>
        </div>
      </div>

    </div>
  </nav>
</header>
