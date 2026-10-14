/* SCAFFOLD script: FAQ accordion. Replace or extend with the real site's interactions. */
(function () {
  document.querySelectorAll('[data-accordion] .faq__q button').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var panel = document.getElementById(btn.getAttribute('aria-controls'));
      var open = btn.getAttribute('aria-expanded') === 'true';
      btn.setAttribute('aria-expanded', String(!open));
      if (panel) panel.hidden = open;
    });
  });
})();
