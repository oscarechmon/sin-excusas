@extends('site.layout')

@section('description', 'Tratamientos faciales, corporales, post operatorios y podología. Protocolos personalizados en un espacio pensado para ti.')

@section('content')

  {{-- ============================================================ Hero --}}
  <section class="se-hero">
    <div class="se-hero__media">
      <img src="{{ $content['home.hero']['image_url'] ?? asset('assets/img/imagen-1.png') }}"
           alt="Tratamiento facial en cabina" fetchpriority="high">
    </div>
    <div class="se-hero__veil"></div>
    <div class="se-edge-bottom"></div>

    <div class="se-container se-hero__inner">
      <div class="se-hero__copy">
        <p class="se-eyebrow se-eyebrow--row">Centro estético</p>

        <h1 class="se-hero__title">
          Tu belleza,<br>
          <em class="se-gold-text">sin excusas</em>
        </h1>

        <p class="se-hero__text">
          Tratamientos faciales, corporales, post operatorios y podología.
          Protocolos personalizados en un espacio pensado para ti.
        </p>

        <div class="d-flex flex-column flex-sm-row gap-3 mt-5">
          <a class="se-btn se-btn--gold" href="{{ route('site.services') }}">Ver servicios</a>
          <a class="se-btn se-btn--ghost" href="{{ config('site.whatsapp_url') }}" target="_blank" rel="noopener">Reservar cita</a>
        </div>

        <ul class="se-stats">
          <li>
            <span class="se-stat__value d-block">4</span>
            <span class="se-stat__label d-block">Líneas de servicio</span>
          </li>
          <li class="se-stats__divider" aria-hidden="true"></li>
          <li>
            <span class="se-stat__value d-block">100%</span>
            <span class="se-stat__label d-block">Con evaluación previa</span>
          </li>
          <li class="se-stats__divider" aria-hidden="true"></li>
          <li>
            <span class="se-stat__value d-block">Para todos</span>
            <span class="se-stat__label d-block">Sin distinción de género</span>
          </li>
        </ul>
      </div>
    </div>
  </section>

  {{-- ============================================ La experiencia · pilares --}}
  <section class="se-section" aria-labelledby="titulo-experiencia">
    <div class="se-container">

      <header class="text-center mx-auto" style="max-width: 640px;">
        <p class="se-eyebrow">La experiencia</p>
        <h2 class="se-serif mt-3 mb-0" id="titulo-experiencia" style="--fs: 2.875rem;">Cuidado con método</h2>
        <hr class="se-rule se-rule--center">
      </header>

      @php
        $pillars = [
            ['Evaluación previa', 'Cada protocolo empieza con un diagnóstico de tu piel o tu caso.'],
            ['Para todos', 'Servicios sin distinción de género, incluida podología.'],
            ['Post operatorio / Post parto', 'Acompañamiento en recuperación quirúrgica y post parto.'],
            ['Complementos', 'Productos y suplementos para continuar el tratamiento en casa.'],
        ];
      @endphp

      <div class="row g-4 mt-4">
        @foreach ($pillars as [$title, $text])
          <div class="col-12 col-sm-6 col-lg-3">
            <article class="se-card se-pillar">
              <p class="se-pillar__num mb-0">No. {{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</p>
              <h3 class="se-card__title mt-3 mb-2">{{ $title }}</h3>
              <p class="se-card__text mb-0">{{ $text }}</p>
            </article>
          </div>
        @endforeach
      </div>

    </div>
  </section>

  {{-- ================================================ Líneas de servicio --}}
  <section class="se-section se-bg-cream" aria-labelledby="titulo-lineas">
    <div class="se-container">

      <header class="d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-4">
        <div>
          <p class="se-eyebrow">Nuestros servicios</p>
          <h2 class="se-serif mt-3 mb-0" id="titulo-lineas" style="--fs: 3rem;">Cuatro líneas de tratamiento</h2>
        </div>
        <a class="se-link-arrow flex-shrink-0" href="{{ route('site.services') }}">Ver todo →</a>
      </header>

      {{-- Fotos y textos editables desde el panel: Catálogo > Contenido web. --}}
      <div class="row g-4 mt-4">
        @foreach ($content->filter(fn ($slot, $key) => str_starts_with($key, 'home.line.')) as $line)
          <div class="col-12 col-sm-6 col-lg-3">
            <a class="se-tile" href="{{ route('site.services') }}#{{ $line['anchor'] }}">
              <div class="se-media se-media--tile">
                @if ($line['image_url'])
                  <img src="{{ $line['image_url'] }}" alt="{{ $line['title'] }}" loading="lazy">
                @else
                  <div class="se-placeholder">Foto: {{ mb_strtolower($line['title']) }}</div>
                @endif
              </div>
              <div class="se-tile__body se-tile__body--ruled">
                <h3 class="se-tile__title mb-0">{{ $line['title'] }}</h3>
                <p class="se-tile__text mb-0">{{ $line['text'] }}</p>
                <p class="se-tile__cue mb-0 mt-auto pt-3">Descubrir →</p>
              </div>
            </a>
          </div>
        @endforeach
      </div>
    </div>
  </section>

  {{-- ============================================ Suplementos Nutricost --}}
  <section class="se-section" aria-labelledby="titulo-nutricost">
    <div class="se-container">
      <div class="row g-5 align-items-center">

        <div class="col-12 col-lg-6">
          <div class="se-framed">
            <img src="{{ $content['home.products']['image_url'] ?? asset('assets/img/imagen-1.png') }}"
                 alt="Espacio y cabina del centro estético"
                 class="se-framed__media se-media se-media--portrait" loading="lazy">
          </div>
        </div>

        <div class="col-12 col-lg-6">
          <p class="se-eyebrow">Suplementos Nutricost</p>
          <h2 class="se-serif mt-3 mb-0" id="titulo-nutricost" style="--fs: 2.875rem;">
            El complemento <em>de tu tratamiento</em>
          </h2>
          <p class="se-lead mt-4" style="max-width: 460px;">
            Suplementos y productos de uso domiciliario disponibles en el centro,
            con la orientación de la especialista.
          </p>

          <ul class="se-checks">
            <li>Orientación de la especialista en cada compra</li>
            <li>Indicados según tu protocolo de tratamiento</li>
          </ul>

          <a class="se-btn se-btn--outline mt-4" href="{{ route('site.products') }}">Ver suplementos y productos</a>
        </div>

      </div>
    </div>
  </section>

  {{-- ======================================================= Testimonios --}}
  <section class="se-section se-bg-cream" aria-labelledby="titulo-testimonios">
    <div class="se-container">

      <header class="text-center mx-auto" style="max-width: 640px;">
        <p class="se-eyebrow">Lo que dicen de nosotros</p>
        <h2 class="se-serif mt-3 mb-0" id="titulo-testimonios" style="--fs: 2.875rem;">Testimonios</h2>
        <hr class="se-rule se-rule--center">
      </header>

      @php
        $testimonials = [
            ['María González - Tratamiento facial', 'El tratamiento facial cambió mi piel completamente. La evaluación previa fue muy completa y el protocolo personalizado funcionó perfecto. Volveré seguro.'],
            ['Carlos Mendez - Post operatorio', 'Después de mi cirugía, Sin Excusas me acompañó en todo el proceso de recuperación. Profesionales y muy atentos. Recomendado 100%.'],
            ['Sofía López - Tratamiento corporal', 'Los resultados del drenaje linfático fueron increíbles. El equipo realmente se preocupa por tu bienestar. Un lugar pensado para ti.'],
            ['Andrea Torres - Podología', 'No es típico encontrar una clínica de podología con este nivel de atención. Sin Excusas lo logró. Profesionales y cálidos.'],
        ];
      @endphp

      <div class="accordion se-testimonials mt-5" id="acordeonTestimonios">
        @foreach ($testimonials as [$author, $quote])
          <div class="accordion-item">
            <h3 class="accordion-header">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                      data-bs-target="#testimonio-{{ $loop->iteration }}" aria-expanded="false"
                      aria-controls="testimonio-{{ $loop->iteration }}">
                {{ $author }}
              </button>
            </h3>
            <div id="testimonio-{{ $loop->iteration }}" class="accordion-collapse collapse" data-bs-parent="#acordeonTestimonios">
              <div class="accordion-body">"{{ $quote }}"</div>
            </div>
          </div>
        @endforeach
      </div>

    </div>
  </section>

@endsection
