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

  const dropdownItems = Array.from(document.querySelectorAll('.nav-item--has-dropdown'));

  if (dropdownItems.length > 0) {
    const closeDropdowns = () => {
      dropdownItems.forEach(item => {
        item.classList.remove('is-open');
        const toggle = item.querySelector('.nav-dropdown-toggle');
        if (toggle) {
          toggle.setAttribute('aria-expanded', 'false');
        }
      });
    };

    dropdownItems.forEach(item => {
      const toggle = item.querySelector('.nav-dropdown-toggle');
      if (!toggle) return;

      toggle.addEventListener('click', (event) => {
        event.preventDefault();
        const shouldOpen = !item.classList.contains('is-open');
        closeDropdowns();
        if (shouldOpen) {
          item.classList.add('is-open');
          toggle.setAttribute('aria-expanded', 'true');
        }
      });
    });

    document.addEventListener('click', (event) => {
      if (!(event.target instanceof Element)) return;
      if (!event.target.closest('.main-nav')) {
        closeDropdowns();
      }
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        closeDropdowns();
      }
    });
  }

  /* -----------------------------------------------------------------------
     Gallery lightbox
     ----------------------------------------------------------------------- */
  const lightbox    = document.getElementById('lightbox');
  const lightboxImg = document.getElementById('lightbox-img');

  if (lightbox && lightboxImg) {
    // Open
    document.querySelectorAll('.gallery-item[data-full]').forEach(item => {
      item.addEventListener('click', () => {
        const src = item.getAttribute('data-full');
        if (src) {
          lightboxImg.src = src;
          lightbox.hidden = false;
          document.body.style.overflow = 'hidden';
        }
      });
    });

    // Close on button
    lightbox.querySelector('.lightbox__close').addEventListener('click', () => {
      lightbox.hidden = true;
      lightboxImg.src = '';
      document.body.style.overflow = '';
    });

    // Close on background click
    lightbox.addEventListener('click', (e) => {
      if (e.target === lightbox) {
        lightbox.hidden = true;
        lightboxImg.src = '';
        document.body.style.overflow = '';
      }
    });

    // Close on Escape
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && !lightbox.hidden) {
        lightbox.hidden = true;
        lightboxImg.src = '';
        document.body.style.overflow = '';
      }
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
