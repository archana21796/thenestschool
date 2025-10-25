// menu.js (deferred)
(function () {
  const toggle = document.getElementById('menuToggle');
  const siteMenu = document.getElementById('siteMenu');
  const overlay = document.getElementById('menuOverlay');
  const closeBtn = document.getElementById('menuClose');

  if (!toggle || !siteMenu || !overlay) return;

  function openMenu() {
    siteMenu.classList.add('open');
    toggle.classList.add('open');
    toggle.setAttribute('aria-expanded', 'true');
    siteMenu.setAttribute('aria-hidden', 'false');
    overlay.classList.add('visible');
    overlay.hidden = false;
    document.documentElement.style.overflow = 'hidden';
    const first = siteMenu.querySelector('a, button, input');
    if (first) first.focus();
  }

  function closeMenu() {
    siteMenu.classList.remove('open');
    toggle.classList.remove('open');
    toggle.setAttribute('aria-expanded', 'false');
    siteMenu.setAttribute('aria-hidden', 'true');
    overlay.classList.remove('visible');
    setTimeout(() => { overlay.hidden = true; }, 300);
    document.documentElement.style.overflow = '';
    toggle.focus();
  }

  toggle.addEventListener('click', () => {
    const expanded = toggle.getAttribute('aria-expanded') === 'true';
    expanded ? closeMenu() : openMenu();
  });

  if (closeBtn) closeBtn.addEventListener('click', closeMenu);
  overlay.addEventListener('click', closeMenu);

  // ESC to close
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeMenu();
  });

  // Dropdowns inside the menu (delegation)
  siteMenu.addEventListener('click', (e) => {
    const btn = e.target.closest('.dropdown-toggle');
    if (!btn) {
      const a = e.target.closest('a');
      if (a) closeMenu(); // close on link click (mobile)
      return;
    }
    const li = btn.closest('.has-dropdown');
    const isOpen = li.classList.toggle('open');
    btn.setAttribute('aria-expanded', String(isOpen));
    // close other dropdowns
    Array.from(siteMenu.querySelectorAll('.has-dropdown')).forEach(other => {
      if (other !== li) {
        other.classList.remove('open');
        const otherBtn = other.querySelector('.dropdown-toggle');
        if (otherBtn) otherBtn.setAttribute('aria-expanded', 'false');
      }
    });
  });
})();














document.addEventListener('DOMContentLoaded', function() {
    const processSteps = document.querySelectorAll('.process-step');
    const processContainer = document.querySelector('.process-container');

    processSteps.forEach(step => {
        // Use 'mouseenter' to trigger the animation
        step.addEventListener('mouseenter', () => {
            // First, remove 'active' from all other steps
            processSteps.forEach(s => {
                if (s !== step) {
                    s.classList.remove('active');
                }
            });
            // Then, add 'active' to the one you're hovering over
            step.classList.add('active');
        });
    });

    // Optional: Add a listener to the container to close all cards when the mouse leaves the area
    processContainer.addEventListener('mouseleave', () => {
        processSteps.forEach(step => {
            step.classList.remove('active');
        });
    });
});


document.addEventListener('DOMContentLoaded', () => {
  const tickerMove = document.querySelector('.ticker-move');
  const track = tickerMove.querySelector('.ticker-track');

  // Clone entire track to make a seamless loop
  const clone = track.cloneNode(true);
  clone.querySelectorAll('img').forEach(img => img.setAttribute('aria-hidden', 'true'));
  tickerMove.appendChild(clone);

  // Wait until all images are loaded to start animation (prevents cutoff)
  const images = [...tickerMove.querySelectorAll('img')];
  let loaded = 0;

  const startAnimation = () => tickerMove.classList.add('animated');

  images.forEach(img => {
    if (img.complete) {
      loaded++;
      if (loaded === images.length) startAnimation();
    } else {
      img.addEventListener('load', () => {
        loaded++;
        if (loaded === images.length) startAnimation();
      });
    }
  });
});












