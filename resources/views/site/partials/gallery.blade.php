{{-- Fotos de la ficha (producto o servicio): la principal en grande y, si el
     sistema tiene más, sus miniaturas debajo; al tocar una, esa pasa a verse
     en grande (main.js). Con una sola foto no hay miniaturas. --}}
@php($photos = $item->photoUrls())

<div class="se-gallery" data-gallery>
  <div @class(['se-product__media', $mediaClass ?? null])>
    @if ($photos)
      <img src="{{ $photos[0] }}" alt="{{ $item->name }}" data-gallery-main>
    @else
      <div class="se-placeholder">{{ $item->name }}</div>
    @endif
  </div>

  @if (count($photos) > 1)
    <ul class="se-gallery__thumbs" aria-label="Fotos de {{ $item->name }}">
      @foreach ($photos as $photo)
        <li>
          <button type="button" class="se-gallery__thumb" data-gallery-thumb data-src="{{ $photo }}"
                  aria-label="Ver foto {{ $loop->iteration }} de {{ count($photos) }}"
                  @if ($loop->first) aria-current="true" @endif>
            <img src="{{ $photo }}" alt="" loading="lazy">
          </button>
        </li>
      @endforeach
    </ul>
  @endif
</div>
