// Toggle full mobile menu
document.querySelector('.mobile-nav-toggle').addEventListener('click', () => {
  document.querySelector('.mobile-nav').classList.toggle('active');
});

// Toggle submenu dropdowns
document.querySelectorAll('.mobile-nav .has-submenu > a').forEach(link => {
  link.addEventListener('click', (e) => {
    e.preventDefault();
    link.parentElement.classList.toggle('open');
  });
});


function initNavigation() {
  const hamburger = document.querySelector('.hamburger');
  const overlay = document.querySelector('.mobile-overlay');
  const mobileSubmenus = document.querySelectorAll('.mobile-menu .has-submenu > a');
  const header = document.querySelector('.site-header');
  const footer = document.querySelector('footer');

  if (!hamburger || !overlay || !header || !footer) return;

  // Hamburger toggle
  hamburger.addEventListener('click', () => {
    hamburger.classList.toggle('active');
    overlay.classList.toggle('active');
  });

  // Mobile dropdown toggle
  mobileSubmenus.forEach(link => {
    link.addEventListener('click', (e) => {
      e.preventDefault();
      link.parentElement.classList.toggle('open');
    });
  });

  // Sticky nav with footer stop
  window.addEventListener('scroll', () => {
    const scrollY = window.scrollY;
    const footerTop = footer.offsetTop;
    const headerHeight = header.offsetHeight;

    if (scrollY + headerHeight < footerTop) {
      header.classList.add('sticky');
    } else {
      header.classList.remove('sticky');
    }
  });
}
