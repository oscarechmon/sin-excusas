<footer class="se-footer">
  <div class="se-container">
    <div class="row g-4">

      <div class="col-12 col-lg-3">
        <img class="se-footer__logo" src="{{ asset('assets/img/logo-300.png') }}"
             alt="Sin Excusas, centro estético" width="150" height="150" loading="lazy">
      </div>

      {{-- Solo las categorías con algo publicado en el sistema: una categoría
           sin nada que mostrar no deja un enlace roto en el pie. --}}
      <div class="col-6 col-lg-3">
        <h2 class="se-footer__heading">Servicios</h2>
        <ul class="se-footer__list">
          @forelse (\App\Support\SiteMenu::serviceCategories() as $category)
            <li><a href="{{ route('site.services') }}#{{ Str::slug($category->name) }}">{{ $category->name }}</a></li>
          @empty
            <li><a href="{{ route('site.services') }}">Nuestros tratamientos</a></li>
          @endforelse
        </ul>
      </div>

      <div class="col-6 col-lg-3">
        <h2 class="se-footer__heading">Tienda</h2>
        <ul class="se-footer__list">
          @foreach (\App\Support\SiteMenu::productCategories() as $category)
            <li><a href="{{ route('site.products', Str::slug($category->name)) }}">{{ $category->name }}</a></li>
          @endforeach
          <li><a href="{{ route('shop.cart.show') }}">Carrito</a></li>
          <li><a href="{{ route('shop.account.orders') }}">Mis pedidos</a></li>
          @if (\App\Support\SiteMenu::packages()->isNotEmpty())
            <li><a href="{{ route('site.services') }}#paquetes">Paquetes</a></li>
          @endif
        </ul>
      </div>

      <div class="col-12 col-lg-3">
        <h2 class="se-footer__heading">Contacto</h2>
        <ul class="se-footer__list">
          @if ($address = \App\Support\SiteSettings::get('contact_address'))
            <li>{{ $address }}</li>
          @endif
          @if ($phone = \App\Support\SiteSettings::get('contact_phone'))
            <li><a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}">{{ $phone }}</a></li>
          @endif
          @if ($email = \App\Support\SiteSettings::get('contact_email'))
            <li><a href="mailto:{{ $email }}">{{ $email }}</a></li>
          @endif
          @if ($whatsapp = \App\Support\SiteSettings::whatsappUrl())
            <li><a href="{{ $whatsapp }}" target="_blank" rel="noopener">Escríbenos por WhatsApp</a></li>
          @endif
        </ul>

        @if ($schedule = \App\Support\SiteSettings::scheduleGroups())
          <h2 class="se-footer__heading mt-4">Horario</h2>
          <ul class="se-footer__list">
            @foreach ($schedule as $group)
              <li>{{ $group['days'] }} · {{ $group['hours'] }}</li>
            @endforeach
          </ul>
        @endif

        @include('site.partials.social-links')
      </div>

    </div>
  </div>

  <div class="se-footer__legal">
    <div class="se-container">
      © {{ date('Y') }} Sin Excusas Centro Estético. Todos los derechos reservados.
    </div>
  </div>
</footer>
