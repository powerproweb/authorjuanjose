document.addEventListener('DOMContentLoaded', () => {

  /* -----------------------------------------------------------------------
     Mobile nav toggle
     ----------------------------------------------------------------------- */
  const navToggle = document.querySelector('.nav-toggle');
  const mainNav   = document.querySelector('.main-nav');

  if (navToggle && mainNav) {
    navToggle.addEventListener('click', () => {
      const isOpen = mainNav.classList.toggle('is-open');
      navToggle.setAttribute('aria-expanded', String(isOpen));
    });
  }

  /* -----------------------------------------------------------------------
     FAQ accordion
     ----------------------------------------------------------------------- */
  document.querySelectorAll('.faq-question').forEach(btn => {
    btn.addEventListener('click', () => {
      const item = btn.closest('.faq-item');
      if (!item) return;

      // Close other open items in the same list
      const list = item.parentElement;
      if (list) {
        list.querySelectorAll('.faq-item.is-open').forEach(open => {
          if (open !== item) open.classList.remove('is-open');
        });
      }

      item.classList.toggle('is-open');
    });
  });

});
