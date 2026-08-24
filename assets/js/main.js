document.addEventListener('DOMContentLoaded', () => {
  // 1. Mobile Menu Drawer Toggle
  const menuBtn = document.getElementById('mobileMenuBtn');
  const siteNav = document.getElementById('siteNav');

  if (menuBtn && siteNav) {
    menuBtn.addEventListener('click', () => {
      menuBtn.classList.toggle('is-active');
      siteNav.classList.toggle('is-open');
    });
  }

  // 2. Form Reservasi: Proteksi Minimal Tanggal (Hari ini)
  const pickupDateInput = document.querySelector('input[name="pickup_date"]');
  if (pickupDateInput) {
    const today = new Date().toISOString().split('T')[0];
    pickupDateInput.setAttribute('min', today);
  }

  // 3. Smooth Scroll untuk Anchor Link Internal
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
      const targetId = this.getAttribute('href');
      if (targetId.length > 1) {
        const targetElement = document.querySelector(targetId);
        if (targetElement) {
          e.preventDefault();
          targetElement.scrollIntoView({ behavior: 'smooth' });
        }
      }
    });
  });
});

document.addEventListener('DOMContentLoaded', () => {
  // 1. Minimum Pickup Date Enforcement
  const pickupDateInput = document.querySelector('input[name="pickup_date"]');
  if (pickupDateInput) {
    const today = new Date().toISOString().split('T')[0];
    pickupDateInput.setAttribute('min', today);
  }

  // 2. Pause Continuous Carousel on Touch Drag
  const track = document.querySelector('.carousel-track');
  if (track) {
    track.addEventListener('touchstart', () => {
      track.style.animationPlayState = 'paused';
    }, { passive: true });

    track.addEventListener('touchend', () => {
      track.style.animationPlayState = 'running';
    });
  }
});