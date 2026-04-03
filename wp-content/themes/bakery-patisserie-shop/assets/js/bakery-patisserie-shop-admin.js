( function( jQuery ){
 jQuery( document ).on( 'click', '.notice-get-started-class .notice-dismiss', function () {
        var type = jQuery( this ).closest( '.notice-get-started-class' ).data( 'notice' );
        jQuery.ajax( ajaxurl,
          {
            type: 'POST',
            data: {
              action: 'bakery_patisserie_shop_dismissed_notice_handler',
              type: type,
              wpnonce: bakery_patisserie_shop.wpnonce
            }
          } );
      } );
}( jQuery ) )

// notice js
// Get Started Detail Notice - Dismiss permanently
document.addEventListener("DOMContentLoaded", function() {
    let closeBtn = document.getElementById("close-detail-theme");
    let detailBox = document.getElementById("detail-theme-box");

    if (closeBtn && detailBox) {
        closeBtn.addEventListener("click", function(e) {
            e.preventDefault();
            
            // AJAX call to save dismissal permanently
            fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=bakery_patisserie_shop_dismissed_get_started_detail_notice'
            })
            .then(response => response.json())
            .then(data => {
                // Remove notice from DOM
                detailBox.remove();
            });
        });
    }
});

function bakery_patisserie_shop_open_tab(evt, tabName) {
    let i, tabcontent, tablinks;

    // Hide all tabs
    tabcontent = document.getElementsByClassName("bakery-patisserie-shop-tabcontent");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }

    // Remove active from all buttons
    tablinks = document.getElementsByClassName("bakery-patisserie-shop-tablinks");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].classList.remove("nav-tab-active", "active");
    }

    // Show selected tab
    let targetTab = document.getElementById(tabName);
    if (targetTab) {
        targetTab.style.display = "block";
    }

    // **FIX**: Check if evt.currentTarget exists before using classList
    if (evt && evt.currentTarget && evt.currentTarget.classList) {
        evt.currentTarget.classList.add("nav-tab-active", "active");
    }
}

// **SAFE** Default activation - NO trigger("click")
jQuery(document).ready(function ($) {
    // Hide all tabs first
    $(".bakery-patisserie-shop-tabcontent").hide();
    
    // **DIRECTLY** activate first tab (no trigger)
    let firstTab = $(".bakery-patisserie-shop-tablinks").first();
    let firstTabId = firstTab.attr('onclick').match(/'([^']+)'/)[1]; // Extract tab ID
    
    firstTab.addClass("nav-tab-active active");
    $('#' + firstTabId).show();
});

