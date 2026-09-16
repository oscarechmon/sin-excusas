/**
 * Sin Excusas · Centro Estético
 * Comportamientos de interfaz de la web pública.
 *
 * La navegación la hace Turbo (Hotwire): al cambiar de página solo se
 * reemplaza el <body>, por eso la inicialización corre en `turbo:load`
 * (que también se dispara en la primera carga) y no en DOMContentLoaded.
 * Si Turbo no cargara, el sitio sigue funcionando como páginas normales.
 */
(function () {
  'use strict';

  /* ------------------------------------------------------------------
   * Pestañas de categoría en Servicios
   *
   * Permite enlazar a una categoría (/servicios#corporales) y mantiene el
   * hash sincronizado al cambiar de pestaña para que el enlace se comparta.
   * ------------------------------------------------------------------ */
  function initCategoryTabs() {
    var tabList = document.querySelector('[data-category-tabs]');
    if (!tabList || !window.bootstrap) return;

    var hash = window.location.hash.replace('#', '');
    if (hash) {
      var trigger = tabList.querySelector('[data-category="' + CSS.escape(hash) + '"]');
      if (trigger) bootstrap.Tab.getOrCreateInstance(trigger).show();
    }

    tabList.addEventListener('shown.bs.tab', function (event) {
      var category = event.target.getAttribute('data-category');
      if (!category) return;
      // replaceState evita ensuciar el historial con cada cambio de pestaña.
      history.replaceState(history.state, '', '#' + category);
    });
  }

  /* ------------------------------------------------------------------
   * Checkout: muestra los campos de dirección y recalcula el total al
   * elegir delivery o recojo. El servidor vuelve a calcular el total: esto
   * es solo para que el cliente vea el monto antes de continuar.
   * ------------------------------------------------------------------ */
  function initCheckout() {
    var form = document.querySelector('[data-checkout]');
    if (!form) return;

    var fields = form.querySelector('[data-delivery-fields]');
    var feeRow = form.querySelector('[data-fee-row]');
    var total = form.querySelector('[data-total]');
    var subtotal = parseFloat(form.getAttribute('data-subtotal')) || 0;
    var fee = parseFloat(form.getAttribute('data-fee')) || 0;

    function sync() {
      var checked = form.querySelector('input[name="fulfillment"]:checked');
      var isDelivery = !!checked && checked.value === 'delivery';

      if (fields) {
        fields.hidden = !isDelivery;
        fields.querySelectorAll('[data-delivery-required]').forEach(function (input) {
          input.required = isDelivery;
        });
      }
      if (feeRow) feeRow.hidden = !isDelivery;
      if (total) total.textContent = 'S/ ' + (subtotal + (isDelivery ? fee : 0)).toFixed(2);
    }

    form.addEventListener('change', sync);
    sync();
  }

  /* ------------------------------------------------------------------
   * Verificación: el botón "Reenviar código" muestra la cuenta regresiva
   * hasta que el servidor permita pedir otro. El servidor también lo valida.
   * ------------------------------------------------------------------ */
  function initResendCountdown() {
    var button = document.querySelector('[data-resend-in]');
    if (!button) return;

    var seconds = parseInt(button.getAttribute('data-resend-in'), 10) || 0;
    var label = button.textContent.trim();

    function tick() {
      if (seconds <= 0) {
        button.disabled = false;
        button.textContent = label;
        return;
      }
      button.disabled = true;
      button.textContent = label + ' (' + seconds + ' s)';
      seconds -= 1;
      setTimeout(tick, 1000);
    }

    tick();
  }

  function init() {
    initCategoryTabs();
    initCheckout();
    initResendCountdown();
  }

  if (window.Turbo) {
    document.addEventListener('turbo:load', init);

    // Turbo guarda una copia de la página para mostrarla al volver atrás:
    // se cierra el menú móvil antes para que no reaparezca abierto.
    document.addEventListener('turbo:before-cache', function () {
      var menu = document.getElementById('seMainNav');
      if (menu) menu.classList.remove('show');
    });
  } else {
    init();
  }
})();
