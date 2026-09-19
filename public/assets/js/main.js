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

  /* ------------------------------------------------------------------
   * Agregar al carrito sin recargar la página
   *
   * Antes cada clic costaba dos viajes al servidor (enviar el formulario y
   * volver a pedir la página) y devolvía al inicio del listado. Ahora es una
   * sola petición: se actualiza el contador del carrito y se muestra un
   * aviso. Si algo falla, el formulario se envía de la forma normal.
   * ------------------------------------------------------------------ */
  var toastTimer = null;

  function showToast(message, isError) {
    var toast = document.querySelector('.se-toast');
    if (!toast) {
      toast = document.createElement('div');
      toast.className = 'se-toast';
      toast.setAttribute('role', 'status');
      toast.innerHTML = '<span></span><a href="/carrito">Ver carrito</a>';
      document.body.appendChild(toast);
    }
    toast.querySelector('span').textContent = message;
    toast.classList.toggle('se-toast--error', !!isError);
    toast.querySelector('a').hidden = !!isError;
    toast.classList.add('is-visible');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () { toast.classList.remove('is-visible'); }, 3500);
  }

  function updateCartCount(count) {
    document.querySelectorAll('[data-cart-link]').forEach(function (link) {
      var badge = link.querySelector('.se-cart-badge');
      if (count > 0 && !badge) {
        badge = document.createElement('span');
        badge.className = 'se-cart-badge';
        link.appendChild(badge);
      }
      if (badge) {
        badge.textContent = String(count);
        badge.hidden = count <= 0;
      }
      link.setAttribute('aria-label', 'Carrito, ' + count + ' artículos');
    });
  }

  function initCartForms() {
    // Un solo listener delegado: sirve también para el contenido que Turbo
    // cambie después, sin volver a registrarlo en cada navegación.
    document.addEventListener('submit', function (event) {
      var form = event.target;
      if (!form.matches || !form.matches('form[data-cart-add]') || !window.fetch) return;
      event.preventDefault();

      var button = form.querySelector('button[type="submit"]');
      var label = button ? button.textContent : '';
      if (button) { button.classList.add('is-busy'); button.textContent = 'Agregando…'; }

      fetch(form.action, {
        method: 'POST',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: new FormData(form),
        credentials: 'same-origin'
      })
        .then(function (response) {
          // 419: la página llevaba mucho abierta y el token venció.
          if (response.status === 419) { window.location.reload(); return null; }
          return response.json();
        })
        .then(function (data) {
          if (!data) return;
          if (typeof data.count === 'number') updateCartCount(data.count);
          showToast(data.message || 'Listo', !data.success);
        })
        .catch(function () { form.submit(); })
        .finally(function () {
          if (button) { button.classList.remove('is-busy'); button.textContent = label; }
        });
    });
  }
  function init() {
    initCategoryTabs();
    initCheckout();
    initResendCountdown();
  }

  initCartForms();

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
