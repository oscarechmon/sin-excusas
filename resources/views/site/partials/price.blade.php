{{-- Precio que se cobra. En promoción con precio propio (lo decide el sistema),
     el normal va tachado delante. --}}
<p class="se-price {{ $class ?? '' }}">
  @if ($regular = $item->regularPrice())
    <del class="se-price__old"><span class="visually-hidden">Antes </span>S/ {{ number_format($regular, 2) }}</del>
    <span class="visually-hidden">Ahora </span>
  @endif
  S/ {{ number_format($price, 2) }}
</p>
