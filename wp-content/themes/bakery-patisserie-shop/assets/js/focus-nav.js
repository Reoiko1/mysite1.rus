( function( window, document ) {
  function bakery_patisserie_shop_keepFocusInMenu() {
    document.addEventListener( 'keydown', function( e ) {
      const bakery_patisserie_shop_nav = document.querySelector( '.sidenav' );
      if ( ! bakery_patisserie_shop_nav || ! bakery_patisserie_shop_nav.classList.contains( 'open' ) ) {
        return;
      }
      const elements = [...bakery_patisserie_shop_nav.querySelectorAll( 'input, a, button' )],
        bakery_patisserie_shop_lastEl = elements[ elements.length - 1 ],
        bakery_patisserie_shop_firstEl = elements[0],
        bakery_patisserie_shop_activeEl = document.activeElement,
        tabKey = e.keyCode === 9,
        shiftKey = e.shiftKey;
      if ( ! shiftKey && tabKey && bakery_patisserie_shop_lastEl === bakery_patisserie_shop_activeEl ) {
        e.preventDefault();
        bakery_patisserie_shop_firstEl.focus();
      }
      if ( shiftKey && tabKey && bakery_patisserie_shop_firstEl === bakery_patisserie_shop_activeEl ) {
        e.preventDefault();
        bakery_patisserie_shop_lastEl.focus();
      }
    } );
  }
  bakery_patisserie_shop_keepFocusInMenu();
} )( window, document );