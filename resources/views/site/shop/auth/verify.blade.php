@extends('site.layout')

@section('title', 'Verifica tu correo')
@section('hide_cta', true)

@push('head')
  <meta name="robots" content="noindex">
@endpush

@section('content')

  <section class="se-section">
    <div class="se-container">
      <div class="se-panel se-auth text-center">
        <p class="se-eyebrow">Último paso</p>
        <h1 class="se-serif mt-3 mb-2" style="--fs: 2.5rem;">Verifica tu correo</h1>
        <p class="se-muted-note mb-4">
          Enviamos un código de 6 dígitos a <strong>{{ $email }}</strong>.
          Vence en {{ $minutes }} minutos. Revisa también la carpeta de spam.
        </p>

        <form method="post" action="{{ route('shop.verification.verify') }}" class="d-grid gap-3">
          @csrf
          <label class="visually-hidden" for="code">Código de verificación</label>
          <input id="code" name="code" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
                 autocomplete="one-time-code" autofocus required placeholder="000000"
                 @class(['form-control', 'se-code-input', 'is-invalid' => $errors->has('code')])>
          @error('code')
            <div class="invalid-feedback d-block">{{ $message }}</div>
          @enderror

          <button type="submit" class="se-btn se-btn--gold w-100">Verificar</button>
        </form>

        <form method="post" action="{{ route('shop.verification.resend') }}" class="mt-4">
          @csrf
          <span class="se-muted-note">¿No te llegó?</span>
          <button type="submit" class="se-link-mini border-0 bg-transparent p-0"
                  data-resend-in="{{ $resendIn }}" @disabled($resendIn > 0)>
            Reenviar código
          </button>
        </form>

        <form method="post" action="{{ route('shop.logout') }}" class="mt-3">
          @csrf
          <button type="submit" class="se-muted-note border-0 bg-transparent p-0 text-decoration-underline">Usar otro correo</button>
        </form>
      </div>
    </div>
  </section>

@endsection
