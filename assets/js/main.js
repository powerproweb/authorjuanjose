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
  /* -----------------------------------------------------------------------
     Contact form human slider
     ----------------------------------------------------------------------- */
  const contactForm = document.getElementById('contact-form');
  if (contactForm) {
    const humanSlider = document.getElementById('human_slider_value');
    const humanPercent = document.getElementById('human_slider_percent');
    const humanError = document.getElementById('human-slider-error');
    const humanTargetInput = document.getElementById('human_target_value');
    const humanElapsedInput = document.getElementById('human_elapsed_ms');
    const startedAt = Date.now();
    const sliderTarget = humanSlider
      ? parseInt(humanSlider.getAttribute('data-target') || humanSlider.max || '100', 10)
      : 100;
    if (humanTargetInput) {
      humanTargetInput.value = String(sliderTarget);
    }

    const setHumanError = (message) => {
      if (!humanError) return;
      if (message) {
        humanError.textContent = message;
        humanError.hidden = false;
        return;
      }
      humanError.textContent = '';
      humanError.hidden = true;
    };

    const sliderPercent = (sliderValue, targetValue) => {
      const safeTarget = Number.isFinite(targetValue) && targetValue > 0 ? targetValue : 100;
      const percent = Math.round((sliderValue / safeTarget) * 100);
      return Math.max(0, Math.min(100, percent));
    };

    const updateHumanPercent = () => {
      if (!humanSlider || !humanPercent) return;
      const sliderValue = parseInt(humanSlider.value || '0', 10);
      humanPercent.textContent = `${sliderPercent(sliderValue, sliderTarget)}%`;
    };

    if (humanSlider) {
      humanSlider.addEventListener('input', () => {
        updateHumanPercent();
        setHumanError('');
      });
      updateHumanPercent();
    }

    contactForm.addEventListener('submit', (event) => {
      if (!humanSlider) return;
      const sliderValue = parseInt(humanSlider.value || '0', 10);
      const currentPercent = sliderPercent(sliderValue, sliderTarget);
      const elapsedMs = Date.now() - startedAt;
      if (humanElapsedInput) {
        humanElapsedInput.value = String(elapsedMs);
      }

      if (currentPercent < 100) {
        event.preventDefault();
        setHumanError('Slide to 100% before submitting. The anti-bot bouncer is strict.');
        humanSlider.focus();
        return;
      }

      if (elapsedMs < 1200) {
        event.preventDefault();
        setHumanError('Please wait one second and submit again. We do not accept speedrun bots.');
        humanSlider.focus();
      }
    });
  }

  /* -----------------------------------------------------------------------
     Back-to-top control
     ----------------------------------------------------------------------- */
  const backToTop = document.querySelector('.backtotop');
  if (backToTop) {
    const backToTopLink = backToTop.querySelector('a');
    const scrollThreshold = 420;
    const scrollToTopSlow = () => {
      const startY = window.scrollY || window.pageYOffset || 0;
      if (startY <= 0) {
        return;
      }

      const duration = 900;
      const start = performance.now();
      const easeInOutCubic = (value) => (
        value < 0.5
          ? 4 * value * value * value
          : 1 - Math.pow(-2 * value + 2, 3) / 2
      );

      const step = (now) => {
        const progress = Math.min((now - start) / duration, 1);
        const eased = easeInOutCubic(progress);
        const nextY = Math.round(startY * (1 - eased));
        window.scrollTo(0, nextY);
        if (progress < 1) {
          requestAnimationFrame(step);
        }
      };

      requestAnimationFrame(step);
    };
    const toggleBackToTop = () => {
      if (window.scrollY > scrollThreshold) {
        backToTop.classList.add('show');
        backToTop.setAttribute('aria-hidden', 'false');
      } else {
        backToTop.classList.remove('show');
        backToTop.setAttribute('aria-hidden', 'true');
      }
    };

    window.addEventListener('scroll', toggleBackToTop, { passive: true });
    toggleBackToTop();

    if (backToTopLink) {
      backToTopLink.addEventListener('click', (event) => {
        event.preventDefault();
        scrollToTopSlow();
      });
    }
  }

});
