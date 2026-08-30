/**
 * Sin Excusas · Centro Estético
 * Comportamientos de interfaz. Se ejecuta con `defer`, así que el DOM ya existe.
 *
 * Todo lo esencial del sitio funciona sin JavaScript: este archivo sólo añade
 * conveniencias (enlaces profundos a una categoría, año del pie, cierre del menú).
 */
(function () {
  'use strict';

  /* ------------------------------------------------------------------
   * Año actual en el aviso de copyright
   * ------------------------------------------------------------------ */
  function setCurrentYear() {
    document.querySelectorAll('[data-current-year]').forEach(function (el) {
      el.textContent = String(new Date().getFullYear());
    });
  }

  /* ------------------------------------------------------------------
   * Pestañas de categoría en Servicios
   *
   * Permite enlazar directamente a una categoría desde otra página
   * (p. ej. servicios.html#corporales) y mantiene el hash sincronizado
   * al cambiar de pestaña, para que el enlace sea compartible.
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
      history.replaceState(null, '', '#' + category);
    });
  }

  /* ------------------------------------------------------------------
   * Menú móvil: se cierra al elegir un enlace
   * ------------------------------------------------------------------ */
  function initMobileNav() {
    var menu = document.getElementById('seMainNav');
    if (!menu || !window.bootstrap) return;

    menu.querySelectorAll('a').forEach(function (link) {
      link.addEventListener('click', function () {
        if (!menu.classList.contains('show')) return;
        bootstrap.Collapse.getOrCreateInstance(menu).hide();
      });
    });
  }

  setCurrentYear();
  initCategoryTabs();
  initMobileNav();
})();
