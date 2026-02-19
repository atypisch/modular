$(document).ready(function() {
  const \$hamburger = $('.hamburger');
  const \$overlay = $('.mobile-overlay');
  const \$header = $('.site-header');
  const \$footer = $('footer');

  \$hamburger.on('click', function() {
    $(this).toggleClass('active');
    \$overlay.toggleClass('active');
  });

  $('.mobile-menu .has-submenu > a').on('click', function(e) {
    e.preventDefault();
    $(this).parent().toggleClass('open');
  });

  $(window).on('scroll', function() {
    const scrollY = $(window).scrollTop();
    const footerTop = \$footer.offset().top;
    const headerHeight = \$header.outerHeight();

    if (scrollY + headerHeight < footerTop) {
      \$header.addClass('sticky');
    } else {
      \$header.removeClass('sticky');
    }
  });
});
