// Initialize plugins after DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Defer non-critical initializations
    requestIdleCallback(() => {
        // Initialize carousel with optimized settings
        if (typeof $.fn.owlCarousel !== 'undefined') {
            $('.owl-carousel').owlCarousel({
                lazyLoad: true,
                smartSpeed: 500,
                // Your other carousel settings
            });
        }

        // Initialize Magnific Popup with lazy loading
        if (typeof $.fn.magnificPopup !== 'undefined') {
            $('.image-popup-vertical-fit').magnificPopup({
                type: 'image',
                closeOnContentClick: true,
                mainClass: 'mfp-img-mobile',
                image: {
                    verticalFit: true
                }
            });
        }

        // Initialize isotope with optimized settings
        if (typeof $.fn.isotope !== 'undefined') {
            let $grid = $('.projects-filter').isotope({
                itemSelector: '.projects-item',
                layoutMode: 'fitRows'
            });
            
            // Update layout after images load
            $grid.imagesLoaded().progress(function() {
                $grid.isotope('layout');
            });
        }
    });
});

// Optimize scroll events
let ticking = false;
window.addEventListener('scroll', function() {
    if (!ticking) {
        window.requestAnimationFrame(function() {
            // Handle scroll-based animations here
            ticking = false;
        });
        ticking = true;
    }
});
