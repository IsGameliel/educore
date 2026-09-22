(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.getElementById('sidebar');
    const mobileToggle = document.querySelector('[data-toggle="offcanvas"]');

    if (!sidebar) {
      return;
    }

    const syncMobileState = function () {
      document.body.classList.toggle('sidebar-mobile-open', sidebar.classList.contains('active'));
    };

    mobileToggle?.addEventListener('click', function () {
      window.setTimeout(syncMobileState, 0);
    });

    document.addEventListener('click', function (event) {
      if (!document.body.classList.contains('sidebar-mobile-open')) {
        return;
      }

      const clickedInsideSidebar = sidebar.contains(event.target);
      const clickedToggle = mobileToggle?.contains(event.target);

      if (!clickedInsideSidebar && !clickedToggle) {
        sidebar.classList.remove('active');
        syncMobileState();
      }
    });

    sidebar.querySelectorAll('.nav-link').forEach(function (link) {
      link.addEventListener('pointerdown', function () {
        link.classList.add('is-tapping');
      });

      ['pointerup', 'pointercancel', 'pointerleave', 'blur'].forEach(function (eventName) {
        link.addEventListener(eventName, function () {
          link.classList.remove('is-tapping');
        });
      });

      if (link.classList.contains('active')) {
        link.setAttribute('aria-current', 'page');
        link.closest('.nav-item')?.classList.add('menu-open');
      }

      link.addEventListener('click', function () {
        const isCollapseToggle = link.matches('[data-bs-toggle="collapse"]');
        const isHashOnly = link.getAttribute('href')?.startsWith('#');

        if (!isCollapseToggle && !isHashOnly && window.matchMedia('(max-width: 991px)').matches) {
          sidebar.classList.remove('active');
          syncMobileState();
        }
      });
    });

    sidebar.querySelectorAll('.collapse').forEach(function (collapse) {
      const parentItem = collapse.closest('.nav-item');
      const trigger = parentItem?.querySelector('[data-bs-toggle="collapse"]');

      if (collapse.classList.contains('show')) {
        parentItem?.classList.add('menu-open');
      }

      collapse.addEventListener('show.bs.collapse', function () {
        parentItem?.classList.add('menu-open');
        trigger?.setAttribute('aria-expanded', 'true');
      });

      collapse.addEventListener('hide.bs.collapse', function () {
        parentItem?.classList.remove('menu-open');
        trigger?.setAttribute('aria-expanded', 'false');
      });
    });
  });
})();
