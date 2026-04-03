jQuery(function($) {
    "use strict";

    // Scroll to top functionality
    $(window).on('scroll', function() {
        if ($(this).scrollTop() >= 50) {
            $('#return-to-top').fadeIn(200);
        } else {
            $('#return-to-top').fadeOut(200);
        }
    });

    $('#return-to-top').on('click', function() {
        $('body,html').animate({ scrollTop: 0 }, 500);
    });

    // Side navigation toggle
    $('.gb_toggle').on('click', function() {
        bakery_patisserie_shop_Keyboard_loop($('.side_gb_nav'));
    });

    // Preloader fade out
    setTimeout(function() {
        $(".loader").fadeOut("slow");
    }, 1000);

});

// Mobile responsive menu
function bakery_patisserie_shop_menu_open_nav() {
    jQuery(".sidenav").addClass('open');
}

function bakery_patisserie_shop_menu_close_nav() {
    jQuery(".sidenav").removeClass('open');
}

(function( $ ) {

  $(window).scroll(function(){
    var sticky = $('.sticky-header'),
    scroll = $(window).scrollTop();

    if (scroll >= 100) sticky.addClass('fixed-header');
    else sticky.removeClass('fixed-header');
  });

})( jQuery );

// slider
jQuery(document).ready(function($) {
  $('#slider .owl-carousel').owlCarousel({
    loop: true,
    margin: 0,
    nav: true,
    dots: false,
    rtl: false,
    items: 1,
    autoplay: false,
    autoplayTimeout: 3000,
    autoplayHoverPause: true,
    navText: ['<i class="fas fa-arrow-left"></i>','<i class="fas fa-arrow-right"></i>']
  });
});

// product
jQuery(document).ready(function($) {
  $('.product-carousel').owlCarousel({
    loop: true,
    margin: 20,
    nav: false,
    dots: false,
    autoplay: false,
    autoplayTimeout: 3000,
    autoplayHoverPause: true,
    responsive: {
      0:   { items: 1 },
      768: { items: 2 },
      992: { items: 3 },
      1200:{ items: 3 }
    },
  });
});

