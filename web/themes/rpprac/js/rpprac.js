(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    document.documentElement.classList.add('agecomevent-ready');

    var backToTopLink = document.querySelector('.agecomevent-back-to-top');

    if (!backToTopLink) {
      return;
    }

    var toggleBackToTop = function () {
      backToTopLink.classList.toggle('is-visible', window.scrollY > 280);
    };

    toggleBackToTop();
    document.addEventListener('scroll', toggleBackToTop, { passive: true });
  });
})();
