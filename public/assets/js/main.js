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
   * Carrito sin recargar la página
   *
   * Agregar es una sola petición: se actualiza el contador y se abre el
   * carrito lateral con lo elegido. "Comprar ahora" agrega y sigue directo a
   * finalizar la compra. El ícono del carrito abre el carrito lateral en vez
   * de ir a /carrito: ahí se cambian cantidades o se quita algo, y un botón
   * lleva a la página del carrito.
   *
   * El contenido del carrito lateral lo arma el servidor: se pide al abrirlo
   * y cada acción del carrito lo devuelve ya actualizado (`drawer`). Si algo
   * falla, el formulario se envía de la forma normal, como sin JavaScript.
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

  function visit(url) {
    if (window.Turbo) window.Turbo.visit(url);
    else window.location.href = url;
  }

  /** Envía el formulario por fetch y devuelve su JSON (null si hay que recargar). */
  function sendForm(form, submitter) {
    var body = new FormData(form);
    // El botón pulsado ("Comprar ahora", + o −) no viene en FormData(form).
    if (submitter && submitter.name) body.set(submitter.name, submitter.value);

    return fetch(form.action, {
      method: 'POST',
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      body: body,
      credentials: 'same-origin'
    }).then(function (response) {
      // 419: la página llevaba mucho abierta y el token venció.
      if (response.status === 419) { window.location.reload(); return null; }
      return response.json();
    });
  }

  /** Plan B: el envío normal del formulario, con el botón que se pulsó. */
  function submitNatively(form, submitter) {
    if (submitter && submitter.name) {
      var input = document.createElement('input');
      input.type = 'hidden';
      input.name = submitter.name;
      input.value = submitter.value;
      form.appendChild(input);
    }
    form.submit();
  }

  function cartDrawer() {
    return window.bootstrap ? document.querySelector('[data-cart-drawer]') : null;
  }

  function renderDrawer(html) {
    var drawer = cartDrawer();
    if (!drawer) return;
    var body = drawer.querySelector('[data-cart-drawer-body]');
    body.innerHTML = html;
    body.removeAttribute('aria-busy');
  }

  function openDrawer() {
    var drawer = cartDrawer();
    if (drawer) bootstrap.Offcanvas.getOrCreateInstance(drawer).show();
  }

  /** Desde el ícono: abre el carrito lateral y pide su contenido al día. */
  function showDrawer(fallbackUrl) {
    var drawer = cartDrawer();
    drawer.querySelector('[data-cart-drawer-body]').setAttribute('aria-busy', 'true');
    openDrawer();

    fetch(drawer.getAttribute('data-src'), {
      headers: { Accept: 'text/html', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin'
    })
      .then(function (response) {
        if (!response.ok) throw new Error('HTTP ' + response.status);
        return response.text();
      })
      .then(renderDrawer, function () { visit(fallbackUrl); });
  }

  function addToCart(event, form) {
    event.preventDefault();

    var submitter = event.submitter || form.querySelector('button[type="submit"]');
    var buyNow = !!submitter && submitter.name === 'buy_now';
    var label = submitter ? submitter.textContent : '';
    if (submitter) {
      submitter.classList.add('is-busy');
      submitter.textContent = buyNow ? 'Un momento…' : 'Agregando…';
    }
    function done() {
      if (submitter) { submitter.classList.remove('is-busy'); submitter.textContent = label; }
    }

    sendForm(form, submitter).then(function (data) {
      if (!data) return;
      if (typeof data.count === 'number') updateCartCount(data.count);
      if (data.success && data.redirect) { visit(data.redirect); return; }
      done();
      if (data.success && data.drawer && cartDrawer()) {
        renderDrawer(data.drawer);
        openDrawer();
        return;
      }
      showToast(data.message || 'Listo', !data.success);
    }, function () { done(); submitNatively(form, submitter); });
  }

  /** + / − y Quitar dentro del carrito lateral. */
  function changeDrawerLine(event, form) {
    event.preventDefault();

    var submitter = event.submitter;
    var focusKey = submitter ? submitter.getAttribute('data-focus') : null;
    var body = form.closest('[data-cart-drawer-body]');
    if (body) body.setAttribute('aria-busy', 'true');

    sendForm(form, submitter).then(function (data) {
      if (!data) return;
      if (typeof data.count === 'number') updateCartCount(data.count);
      if (typeof data.drawer !== 'string') {
        if (body) body.removeAttribute('aria-busy');
        showToast(data.message || 'No se pudo actualizar el carrito.', true);
        return;
      }
      renderDrawer(data.drawer);

      // El contenido se reemplazó: el foco vuelve al mismo botón (o al panel).
      var drawer = cartDrawer();
      var target = focusKey ? drawer.querySelector('[data-focus="' + focusKey + '"]') : null;
      if (target && !target.disabled) target.focus();
      else drawer.focus();
    }, function () { submitNatively(form, submitter); });
  }

  function initCartForms() {
    // Listeners delegados: sirven también para lo que Turbo o el carrito
    // lateral cambien después, sin volver a registrarlos en cada navegación.
    document.addEventListener('submit', function (event) {
      var form = event.target;
      if (!form.matches || !window.fetch) return;
      if (form.matches('form[data-cart-add]')) addToCart(event, form);
      else if (form.matches('form[data-cart-drawer-form]') && cartDrawer()) changeDrawerLine(event, form);
    });

    document.addEventListener('click', function (event) {
      var link = event.target.closest ? event.target.closest('a[data-cart-link]') : null;
      if (!link || !cartDrawer() || !window.fetch) return;
      // Ctrl/⌘ + clic o clic central: que abra la página del carrito en otra pestaña.
      if (event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
      event.preventDefault();
      showDrawer(link.href);
    });
  }

  /* ------------------------------------------------------------------
   * Cantidad en la ficha de producto: botones − y + junto al número, sin
   * pasarse de lo disponible (el servidor igual lo vuelve a revisar).
   * ------------------------------------------------------------------ */
  function syncStepper(stepper) {
    var input = stepper.querySelector('input');
    var value = parseInt(input.value, 10) || 0;
    stepper.querySelector('[data-step="-1"]').disabled = value <= (parseInt(input.min, 10) || 1);
    stepper.querySelector('[data-step="1"]').disabled = value >= (parseInt(input.max, 10) || 99);
  }

  function initQtySteppers() {
    document.addEventListener('click', function (event) {
      var button = event.target.closest ? event.target.closest('[data-qty-stepper] [data-step]') : null;
      if (!button) return;
      var stepper = button.closest('[data-qty-stepper]');
      var input = stepper.querySelector('input');
      var min = parseInt(input.min, 10) || 1;
      var max = parseInt(input.max, 10) || 99;
      var value = (parseInt(input.value, 10) || min) + parseInt(button.getAttribute('data-step'), 10);
      input.value = String(Math.min(max, Math.max(min, value)));
      syncStepper(stepper);
    });

    document.addEventListener('input', function (event) {
      var stepper = event.target.closest ? event.target.closest('[data-qty-stepper]') : null;
      if (stepper) syncStepper(stepper);
    });
  }

  function init() {
    initCategoryTabs();
    initCheckout();
    initResendCountdown();
    document.querySelectorAll('[data-qty-stepper]').forEach(syncStepper);
  }

  initCartForms();
  initQtySteppers();

  if (window.Turbo) {
    document.addEventListener('turbo:load', init);

    // Turbo guarda una copia de la página para mostrarla al volver atrás:
    // se cierran antes el menú móvil y el carrito lateral para que no
    // reaparezcan abiertos (ni con la página bloqueada para desplazarse).
    document.addEventListener('turbo:before-cache', function () {
      var menu = document.getElementById('seMainNav');
      if (menu) menu.classList.remove('show');

      var drawer = cartDrawer();
      if (!drawer) return;
      var instance = bootstrap.Offcanvas.getInstance(drawer);
      if (instance) instance.dispose();
      drawer.classList.remove('show', 'showing', 'hiding');
      drawer.removeAttribute('aria-modal');
      drawer.removeAttribute('role');
      document.querySelectorAll('.offcanvas-backdrop').forEach(function (backdrop) { backdrop.remove(); });
      ['overflow', 'padding-right'].forEach(function (property) {
        document.body.style.removeProperty(property);
        document.body.removeAttribute('data-bs-' + property);
      });
    });
  } else {
    init();
  }
})();
