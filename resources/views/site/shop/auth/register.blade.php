@extends('site.layout')

@section('title', 'Crear cuenta')
@section('hide_cta', true)

@section('content')

  <section class="se-section">
    <div class="se-container">
      <div class="se-panel se-auth">
        <p class="se-eyebrow se-eyebrow--row">Mi cuenta</p>
        <h1 class="se-serif mt-3 mb-2" style="--fs: 2.5rem;">Crear cuenta</h1>
        <p class="se-muted-note mb-4">Con tu cuenta compras en línea y ves el seguimiento de cada pedido.</p>

        @include('site.shop.partials.google-button', ['label' => 'Registrarme con Google'])

        <p class="se-muted-note mb-3">Si te registras con tu correo, te enviaremos un código de 6 dígitos para confirmarlo.</p>

        <form method="post" action="{{ route('shop.register') }}" class="row g-3">
          @csrf
          @include('site.shop.partials.field', ['name' => 'name', 'label' => 'Nombre completo', 'required' => true, 'class' => 'col-12', 'autocomplete' => 'name'])
          @include('site.shop.partials.field', ['name' => 'email', 'label' => 'Correo', 'type' => 'email', 'required' => true, 'class' => 'col-12', 'autocomplete' => 'email'])
          @include('site.shop.partials.field', ['name' => 'phone', 'label' => 'Celular / WhatsApp', 'type' => 'tel', 'required' => true, 'class' => 'col-12 col-md-6', 'autocomplete' => 'tel'])
          @include('site.shop.partials.field', ['name' => 'document_number', 'label' => 'DNI (opcional)', 'class' => 'col-12 col-md-6'])
          @include('site.shop.partials.field', ['name' => 'password', 'label' => 'Contraseña', 'type' => 'password', 'required' => true, 'class' => 'col-12 col-md-6', 'autocomplete' => 'new-password'])
          @include('site.shop.partials.field', ['name' => 'password_confirmation', 'label' => 'Repite la contraseña', 'type' => 'password', 'required' => true, 'class' => 'col-12 col-md-6', 'autocomplete' => 'new-password'])

          <div class="col-12">
            <button type="submit" class="se-btn se-btn--gold w-100">Crear cuenta</button>
          </div>
        </form>

        <p class="se-muted-note text-center mt-4 mb-0">
          ¿Ya tienes cuenta? <a href="{{ route('shop.login') }}">Ingresa</a>
        </p>
      </div>
    </div>
  </section>

@endsection
