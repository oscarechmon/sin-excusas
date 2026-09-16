@extends('site.layout')

@section('title', 'Nosotros')
@section('description', 'Sin Excusas es un centro estético donde cada protocolo empieza con una evaluación. Tratamientos faciales, corporales, post operatorio y podología.')

@section('content')

  <section class="se-pagehead" aria-labelledby="titulo-nosotros">
    <div class="se-container">

      <div style="max-width: 740px;">
        <p class="se-eyebrow se-eyebrow--row">Nosotros</p>
        <h1 class="se-pagehead__title se-pagehead__title--lg" id="titulo-nosotros">
          Un centro estético para todos, <em>sin distinción</em>
        </h1>
        <p class="se-lead mt-4">
          Somos Sin Excusas, un centro estético donde cada protocolo empieza con una evaluación.
          Trabajamos tratamientos faciales, corporales, acompañamiento post operatorio y post parto,
          y podología clínica.
        </p>
      </div>

      <div class="se-media se-media--wide mt-5" style="border-radius: var(--se-radius);">
        <div class="se-placeholder">Foto amplia: equipo o recepción del centro</div>
      </div>

    </div>
  </section>

  @php
    $values = [
        ['Diagnóstico primero', 'No vendemos sesiones sueltas: definimos un plan según lo que tu piel o tu caso necesita.'],
        ['Espacio para todos', 'Atendemos a cualquier persona que quiera cuidarse, sin distinción de género.'],
        ['Seguimiento real', 'Acompañamos el proceso completo, incluido lo que haces en casa entre sesiones.'],
    ];
  @endphp

  <section class="se-section" aria-label="Nuestros valores">
    <div class="se-container">
      <div class="row g-4">
        @foreach ($values as [$title, $text])
          <div class="col-12 col-lg-4">
            <article class="se-card p-4 p-lg-5">
              <h2 class="se-card__title" style="font-size: 1.688rem;">{{ $title }}</h2>
              <hr class="se-rule se-rule--left">
              <p class="se-card__text mb-0" style="font-size: .938rem; line-height: 1.85;">{{ $text }}</p>
            </article>
          </div>
        @endforeach
      </div>
    </div>
  </section>

@endsection
