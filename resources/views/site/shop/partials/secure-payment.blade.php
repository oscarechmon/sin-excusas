{{-- Tarjetas aceptadas y quién procesa el cobro. Se muestra en el checkout y al pagar
     (ahí los logos de tarjetas ya van junto al formulario: hideCards). --}}
<div class="se-secure {{ $class ?? '' }}">
  <div class="se-secure__cards">
    <span class="se-secure__label">
      <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M12 2a5 5 0 0 0-5 5v3H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2h-1V7a5 5 0 0 0-5-5Zm-3 8V7a3 3 0 1 1 6 0v3H9Zm3 4a2 2 0 0 1 1 3.73V19h-2v-1.27A2 2 0 0 1 12 14Z"/></svg>
      Pago 100% seguro
    </span>
    @unless ($hideCards ?? false)
      <span class="se-secure__logos">
        <span class="se-card-logo" title="Visa"><img src="{{ asset('assets/img/pagos/visa.svg') }}" alt="Visa" width="46" height="15"></span>
        <span class="se-card-logo" title="Mastercard"><img src="{{ asset('assets/img/pagos/mastercard.svg') }}" alt="Mastercard" width="34" height="21"></span>
      </span>
    @endunless
  </div>
  <div class="se-secure__gateway">
    <span>Procesado por</span>
    <img src="{{ asset('assets/img/pagos/izipay.png') }}" alt="Izipay" width="72" height="24">
  </div>
  <p class="se-secure__note">Aceptamos tarjetas Visa y Mastercard de crédito y débito. Izipay procesa el cobro: nosotros no vemos ni guardamos los datos de tu tarjeta.</p>
</div>
