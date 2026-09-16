<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@hasSection('title')@yield('title') · @endif Sin Excusas Centro Estético</title>
  <meta name="description" content="@yield('description', 'Centro estético: tratamientos faciales, corporales, post operatorio y podología con evaluación previa.')">
  <meta name="theme-color" content="#14110C">
  <link rel="canonical" href="{{ url()->current() }}">

  <meta property="og:type" content="website">
  <meta property="og:title" content="@yield('title', 'Sin Excusas Centro Estético')">
  <meta property="og:description" content="@yield('description', 'Tu belleza, sin excusas.')">
  <meta property="og:image" content="{{ asset('assets/img/logo-sin-excusas.png') }}">
  <meta property="og:locale" content="es_PE">

  <link rel="icon" href="{{ asset('assets/img/logo-sin-excusas.png') }}" type="image/png">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;1,400&amp;family=Jost:wght@300;400;500;600&amp;display=swap" rel="stylesheet">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <link href="{{ asset('assets/css/main.css') }}?v={{ filemtime(public_path('assets/css/main.css')) }}" rel="stylesheet" data-turbo-track="reload">

  {{--
    Los scripts van en <head> con defer: Turbo reemplaza solo el <body> al
    navegar, así que se cargan una única vez y la navegación no recarga CSS,
    fuentes ni Bootstrap. Turbo además precarga los enlaces al pasar el mouse.
  --}}
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer
          integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/@hotwired/turbo@8.0.12/dist/turbo.es2017-umd.js" defer></script>
  <script src="{{ asset('assets/js/main.js') }}?v={{ filemtime(public_path('assets/js/main.js')) }}" defer data-turbo-track="reload"></script>

  @stack('head')
</head>
<body>

  <a class="se-skip-link" href="#contenido">Ir al contenido</a>

  @include('site.partials.header')

  <main id="contenido">
    @include('site.partials.flash')

    @yield('content')

    @unless (View::hasSection('hide_cta'))
      @include('site.partials.cta')
    @endunless
  </main>

  @include('site.partials.footer')

  <a class="se-whatsapp" href="{{ config('site.whatsapp_url') }}" target="_blank" rel="noopener"
     aria-label="Escribir por WhatsApp">
    <svg width="26" height="26" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
      <path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2 22l5.25-1.38a9.9 9.9 0 0 0 4.79 1.22h.01c5.46 0 9.91-4.45 9.91-9.91C21.96 6.45 17.5 2 12.04 2Zm5.8 14.13c-.24.68-1.42 1.32-1.95 1.36-.5.05-.98.24-3.3-.69-2.78-1.1-4.53-3.95-4.67-4.13-.14-.19-1.11-1.48-1.11-2.82s.7-2 .95-2.28c.24-.28.53-.35.71-.35.18 0 .36 0 .51.01.16.01.39-.06.6.47.24.57.8 1.96.87 2.1.07.14.12.31.02.5-.09.19-.14.31-.28.47-.14.16-.3.36-.42.48-.14.14-.29.29-.12.57.16.28.73 1.2 1.56 1.94 1.07.95 1.98 1.25 2.26 1.39.28.14.44.12.6-.07.16-.19.7-.81.88-1.09.19-.28.37-.23.63-.14.26.09 1.65.78 1.93.92.28.14.47.21.54.33.07.11.07.66-.17 1.34Z"/>
    </svg>
  </a>

</body>
</html>
