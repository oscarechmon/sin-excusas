@extends('site.layout')

@section('title', 'Ingresar')
@section('hide_cta', true)

@section('content')

  <section class="se-section">
    <div class="se-container">
      <div class="se-panel se-auth">
        <p class="se-eyebrow se-eyebrow--row">Mi cuenta</p>
        <h1 class="se-serif mt-3 mb-2" style="font-size: 2.5rem;">Ingresar</h1>
        <p class="se-muted-note mb-4">Accede para comprar y seguir tus pedidos.</p>

        @include('site.shop.partials.google-button')

        <form method="post" action="{{ route('shop.login') }}" class="d-grid gap-3">
          @csrf
          @include('site.shop.partials.field', ['name' => 'email', 'label' => 'Correo', 'type' => 'email', 'required' => true, 'autocomplete' => 'email'])
          @include('site.shop.partials.field', ['name' => 'password', 'label' => 'Contraseña', 'type' => 'password', 'required' => true, 'autocomplete' => 'current-password'])

          <label class="d-flex gap-2 align-items-center se-muted-note">
            <input class="form-check-input m-0" type="checkbox" name="remember" value="1"> Mantener la sesión iniciada
          </label>

          <button type="submit" class="se-btn se-btn--gold w-100">Ingresar</button>
        </form>

        <p class="se-muted-note text-center mt-4 mb-0">
          ¿Aún no tienes cuenta? <a href="{{ route('shop.register') }}">Regístrate</a>
        </p>
      </div>
    </div>
  </section>

@endsection
