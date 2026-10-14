{{-- Etiqueta de promoción: el descuento si tiene precio propio; si no, «Promoción». --}}
@if ($item->onPromotion())
  <span @class(['se-promo-badge', 'se-promo-badge--inline' => $inline ?? false])>
    @if ($discount = $item->discountPercent())
      −{{ $discount }}&nbsp;%<span class="visually-hidden"> de descuento</span>
    @else
      Promoción
    @endif
  </span>
@endif
