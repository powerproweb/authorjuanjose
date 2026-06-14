document.addEventListener('DOMContentLoaded', () => {
  const body = document.body;
  const main = document.querySelector('main');
  if (main && !main.id) {
    main.id = 'main-content';
  }

  /* -----------------------------------------------------------------------
     Hero revisit state
     ----------------------------------------------------------------------- */
  try {
    const revisitKey = 'ajj_epic_revisit';
    if (window.localStorage.getItem(revisitKey)) {
      body.classList.add('revisit');
    } else {
      window.localStorage.setItem(revisitKey, '1');
    }
  } catch (error) {
    // Ignore localStorage failures
  }

  /* -----------------------------------------------------------------------
     Sticky header condense
     ----------------------------------------------------------------------- */
  const header = document.querySelector('.site-header');
  if (header) {
    const syncHeaderCondense = () => {
      if (window.scrollY > 40) {
        header.classList.add('site-header--condensed');
      } else {
        header.classList.remove('site-header--condensed');
      }
    };

    window.addEventListener('scroll', syncHeaderCondense, { passive: true });
    syncHeaderCondense();
  }

  /* -----------------------------------------------------------------------
     Navigation and dropdown accessibility
     ----------------------------------------------------------------------- */
  const navToggle = document.querySelector('.nav-toggle');
  const mainNav = document.querySelector('.main-nav');
  const dropdownItems = Array.from(document.querySelectorAll('.nav-item--has-dropdown'));

  const closeDropdowns = () => {
    dropdownItems.forEach((item) => {
      item.classList.remove('is-open');
      const toggle = item.querySelector('.nav-dropdown-toggle');
      if (toggle) {
        toggle.setAttribute('aria-expanded', 'false');
      }
    });
  };

  if (navToggle && mainNav) {
    navToggle.addEventListener('click', () => {
      const isOpen = mainNav.classList.toggle('is-open');
      navToggle.setAttribute('aria-expanded', String(isOpen));
      if (!isOpen) {
        closeDropdowns();
      }
    });
  }

  dropdownItems.forEach((item) => {
    const toggle = item.querySelector('.nav-dropdown-toggle');
    const menu = item.querySelector('.nav-dropdown');
    if (!toggle || !menu) {
      return;
    }

    const getMenuLinks = () => Array.from(menu.querySelectorAll('a'));
    const setOpen = (open) => {
      if (open) {
        closeDropdowns();
      }
      item.classList.toggle('is-open', open);
      toggle.setAttribute('aria-expanded', String(open));
    };

    toggle.addEventListener('click', (event) => {
      event.preventDefault();
      const willOpen = !item.classList.contains('is-open');
      setOpen(willOpen);
      if (willOpen) {
        const firstLink = getMenuLinks()[0];
        if (firstLink && window.matchMedia('(max-width: 900px)').matches) {
          firstLink.focus();
        }
      }
    });

    toggle.addEventListener('keydown', (event) => {
      const links = getMenuLinks();
      if (event.key === 'ArrowDown' && links.length > 0) {
        event.preventDefault();
        setOpen(true);
        links[0].focus();
      } else if (event.key === 'Escape') {
        setOpen(false);
        toggle.focus();
      }
    });

    menu.addEventListener('keydown', (event) => {
      const links = getMenuLinks();
      if (links.length === 0) {
        return;
      }

      const currentIndex = links.indexOf(document.activeElement);
      if (event.key === 'ArrowDown') {
        event.preventDefault();
        const next = links[(currentIndex + 1) % links.length];
        next.focus();
      } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        const previous = links[(currentIndex - 1 + links.length) % links.length];
        previous.focus();
      } else if (event.key === 'Home') {
        event.preventDefault();
        links[0].focus();
      } else if (event.key === 'End') {
        event.preventDefault();
        links[links.length - 1].focus();
      } else if (event.key === 'Escape') {
        event.preventDefault();
        setOpen(false);
        toggle.focus();
      }
    });
  });

  document.addEventListener('click', (event) => {
    if (!(event.target instanceof Element)) return;
    if (!event.target.closest('.site-header')) {
      closeDropdowns();
      if (mainNav && navToggle && mainNav.classList.contains('is-open') && window.matchMedia('(max-width: 900px)').matches) {
        mainNav.classList.remove('is-open');
        navToggle.setAttribute('aria-expanded', 'false');
      }
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      closeDropdowns();
      if (mainNav && navToggle && mainNav.classList.contains('is-open') && window.matchMedia('(max-width: 900px)').matches) {
        mainNav.classList.remove('is-open');
        navToggle.setAttribute('aria-expanded', 'false');
      }
    }
  });

  /* -----------------------------------------------------------------------
     Scroll reveal
     ----------------------------------------------------------------------- */
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (!reducedMotion && 'IntersectionObserver' in window) {
    const revealTargets = Array.from(document.querySelectorAll('.section, .panel, .card-grid > .card, .book-card'));
    revealTargets.forEach((target) => {
      if (!target.closest('.hero')) {
        target.classList.add('reveal-on-scroll');
      }
    });

    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -8% 0px' });

    revealTargets.forEach((target) => observer.observe(target));
  }

  /* -----------------------------------------------------------------------
     Gallery lightbox
     ----------------------------------------------------------------------- */
  const lightbox = document.getElementById('lightbox');
  const lightboxImg = document.getElementById('lightbox-img');

  if (lightbox && lightboxImg) {
    const closeLightbox = () => {
      lightbox.hidden = true;
      lightboxImg.src = '';
      document.body.style.overflow = '';
    };

    document.querySelectorAll('.gallery-item[data-full]').forEach((item) => {
      item.addEventListener('click', () => {
        const src = item.getAttribute('data-full');
        if (!src) return;
        lightboxImg.src = src;
        lightbox.hidden = false;
        document.body.style.overflow = 'hidden';
      });
    });

    const closeButton = lightbox.querySelector('.lightbox__close');
    if (closeButton) {
      closeButton.addEventListener('click', closeLightbox);
    }

    lightbox.addEventListener('click', (event) => {
      if (event.target === lightbox) {
        closeLightbox();
      }
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && !lightbox.hidden) {
        closeLightbox();
      }
    });
  }

  /* -----------------------------------------------------------------------
     FAQ accordion
     ----------------------------------------------------------------------- */
  document.querySelectorAll('.faq-question').forEach((button) => {
    button.addEventListener('click', () => {
      const item = button.closest('.faq-item');
      if (!item) return;

      const list = item.parentElement;
      if (list) {
        list.querySelectorAll('.faq-item.is-open').forEach((openItem) => {
          if (openItem !== item) {
            openItem.classList.remove('is-open');
          }
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
      } else {
        humanError.textContent = '';
        humanError.hidden = true;
      }
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
     Back to top
     ----------------------------------------------------------------------- */
  const backToTop = document.querySelector('.backtotop');
  if (backToTop) {
    const backToTopLink = backToTop.querySelector('a');
    const scrollThreshold = 420;

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
        window.scrollTo({ top: 0, behavior: reducedMotion ? 'auto' : 'smooth' });
      });
    }
  }
});