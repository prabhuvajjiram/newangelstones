// Wait for jQuery and DOM to be ready
$(document).ready(function() {
    'use strict';
    
    // Toggle menu
    $('.as-nav-toggle').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $('body').toggleClass('offcanvas');
    });

    // Close menu when clicking overlay
    $('.menu-overlay').on('click', function() {
        $('body').removeClass('offcanvas');
    });

    // Handle menu item clicks with smooth scroll
    $('.nav-menu a').on('click', function(e) {
        e.preventDefault();
        var target = $(this).attr('href');
        
        // Close mobile menu
        $('body').removeClass('offcanvas');
        
        // Smooth scroll to target
        if (target.charAt(0) === '#') {
            var $target = $(target);
            if ($target.length) {
                var offset = $target.offset().top - 60; // Adjust for header height
                $('html, body').animate({
                    scrollTop: offset
                }, 800, 'swing', function() {
                    // Update URL without triggering scroll
                    if (history.pushState) {
                        history.pushState(null, null, target);
                    }
                });
            }
        } else {
            // If it's not an anchor link, just navigate normally
            window.location.href = target;
        }
    });

    // Handle escape key
    $(document).on('keyup', function(e) {
        if (e.key === "Escape") {
            $('body').removeClass('offcanvas');
        }
    });

    // Prevent menu from closing when clicking inside it
    $('#as-nav').on('click', function(e) {
        e.stopPropagation();
    });

    // Back to top button
    var backtotop = $('.back-to-top');
    
    $(window).scroll(function() {
        if ($(this).scrollTop() > 100) {
            backtotop.addClass('active');
        } else {
            backtotop.removeClass('active');
        }
    });

    backtotop.on('click', function(e) {
        e.preventDefault();
        $('html, body').animate({
            scrollTop: 0
        }, 800, 'swing');
        return false;
    });
});
