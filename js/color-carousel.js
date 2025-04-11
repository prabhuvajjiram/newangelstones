/**
 * Color Carousel - Dynamic color image loader
 * For Angel Stones website
 */
(function($) {
    'use strict';

    // Colors container
    let colors = [];
    let currentColorIndex = 0;

    /**
     * Initialize the color carousel and modal
     */
    function initColorCarousel() {
        // Fetch all color images from the directory
        fetchColorImages()
            .then(() => {
                populateColorDisplay();
                setupEventListeners();
            })
            .catch(error => {
                console.error('Error initializing color carousel:', error);
                // Fallback to static HTML if dynamic loading fails
            });
    }

    /**
     * Fetch all color images from the directory
     */
    function fetchColorImages() {
        return new Promise((resolve, reject) => {
            // Use AJAX to fetch the list of color images
            $.ajax({
                url: 'get_color_images.php',
                dataType: 'json',
                success: function(data) {
                    if (data && data.colors && data.colors.length > 0) {
                        colors = data.colors;
                        resolve();
                    } else {
                        reject('No color images found');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching color images:', error);
                    reject(error);
                }
            });
        });
    }

    /**
     * Populate the color display with scrollable row
     */
    function populateColorDisplay() {
        const $container = $('#variety-of-granites .owl-carousel');
        
        // Replace owl carousel with scrollable row
        $container.removeClass('owl-carousel owl-theme');
        $container.empty();
        $container.addClass('color-scroll-container');

        // Add colors to scrollable row
        colors.forEach((color, index) => {
            const item = `
                <div class="color-scroll-item" data-index="${index}">
                    <div class="color-scroll-image">
                        <img src="${color.path}" loading="lazy" class="img-fluid" 
                             alt="${color.name}" onerror="this.onerror=null; this.src='images/placeholder.jpg';">
                    </div>
                    <div class="caption"><p>${color.name}</p></div>
                </div>
            `;
            $container.append(item);
        });

        // Destroy owl carousel if it was initialized
        if ($container.data('owl.carousel')) {
            $container.trigger('destroy.owl.carousel');
        }

        // Add View All Colors button after color display
        const $carouselContainer = $container.closest('.col-md-12');
        
        if ($carouselContainer.find('.view-all-colors-btn').length === 0) {
            $carouselContainer.append(`
                <div class="text-center mt-4">
                    <button class="btn view-all-colors-btn">View All Colors</button>
                </div>
            `);
        }
    }

    /**
     * Setup event listeners for interactions
     */
    function setupEventListeners() {
        // View All Colors button click
        $(document).on('click', '.view-all-colors-btn', function(e) {
            e.preventDefault();
            showAllColorsModal();
        });

        // Color image click in scrollable row
        $(document).on('click', '.color-scroll-item', function(e) {
            const index = $(this).data('index');
            showColorFullscreen(index);
        });

        // Color image click in modal
        $(document).on('click', '.color-grid-item', function(e) {
            const index = $(this).data('index');
            showColorFullscreen(index);
        });

        // Next/Previous buttons in fullscreen view
        $(document).on('click', '.color-fullscreen-nav', function(e) {
            e.stopPropagation();
            if ($(this).hasClass('color-fullscreen-prev')) {
                navigateColors('prev');
            } else {
                navigateColors('next');
            }
        });

        // Close fullscreen view on background click
        $(document).on('click', '.color-fullscreen-container', function(e) {
            if (e.target === this) {
                closeColorFullscreen();
            }
        });

        // Close fullscreen view on X button click
        $(document).on('click', '.color-fullscreen-close', function(e) {
            e.stopPropagation();
            closeColorFullscreen();
        });

        // Keyboard navigation
        $(document).on('keydown', function(e) {
            if ($('.color-fullscreen-container').is(':visible')) {
                if (e.key === 'Escape') {
                    closeColorFullscreen();
                } else if (e.key === 'ArrowLeft') {
                    navigateColors('prev');
                } else if (e.key === 'ArrowRight') {
                    navigateColors('next');
                }
            }
        });

        // Touch swipe for mobile
        let touchStartX = 0;
        let touchEndX = 0;

        $(document).on('touchstart', '.color-fullscreen-container', function(e) {
            touchStartX = e.originalEvent.touches[0].clientX;
        });

        $(document).on('touchend', '.color-fullscreen-container', function(e) {
            touchEndX = e.originalEvent.changedTouches[0].clientX;
            handleSwipe();
        });

        function handleSwipe() {
            const threshold = 50; // Minimum swipe distance
            if (touchEndX < touchStartX - threshold) {
                // Swipe left -> next
                navigateColors('next');
            } else if (touchEndX > touchStartX + threshold) {
                // Swipe right -> previous
                navigateColors('prev');
            }
        }
        
        // Add horizontal scroll indicator for desktop
        $('.color-scroll-container').on('mouseenter', function() {
            if (this.scrollWidth > this.clientWidth) {
                $(this).addClass('scrollable-hint');
                setTimeout(() => {
                    $(this).removeClass('scrollable-hint');
                }, 1500);
            }
        });
    }

    /**
     * Show the All Colors modal with grid view
     */
    function showAllColorsModal() {
        // Remove existing modal if any
        $('#allColorsModal').remove();

        // Create modal HTML
        let modalHtml = `
            <div class="modal fade" id="allColorsModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">All Colors</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="color-grid">
        `;

        // Add colors to the grid
        colors.forEach((color, index) => {
            modalHtml += `
                <div class="color-grid-item" data-index="${index}">
                    <div class="color-image">
                        <img src="${color.path}" alt="${color.name}" 
                             onerror="this.onerror=null; this.src='images/placeholder.jpg';">
                    </div>
                    <div class="color-name">${color.name}</div>
                </div>
            `;
        });

        modalHtml += `
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Append modal to body and show it
        $('body').append(modalHtml);
        const modal = new bootstrap.Modal(document.getElementById('allColorsModal'));
        modal.show();
    }

    /**
     * Show fullscreen view of a color
     */
    function showColorFullscreen(index) {
        currentColorIndex = index;
        const color = colors[index];

        // Remove existing fullscreen container if any
        $('.color-fullscreen-container').remove();

        // Create fullscreen view
        const fullscreenHtml = `
            <div class="color-fullscreen-container">
                <div class="color-fullscreen-content">
                    <img src="${color.path}" alt="${color.name}" class="color-fullscreen-image"
                         onerror="this.onerror=null; this.src='images/placeholder.jpg';">
                    <div class="color-fullscreen-caption">${color.name}</div>
                    <button class="color-fullscreen-nav color-fullscreen-prev">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    <button class="color-fullscreen-nav color-fullscreen-next">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                    <button class="color-fullscreen-close">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
            </div>
        `;

        $('body').append(fullscreenHtml);
        $('body').addClass('overflow-hidden');

        // Close the modal if it's open
        const modal = bootstrap.Modal.getInstance(document.getElementById('allColorsModal'));
        if (modal) {
            modal.hide();
        }
    }

    /**
     * Close fullscreen view
     */
    function closeColorFullscreen() {
        $('.color-fullscreen-container').remove();
        $('body').removeClass('overflow-hidden');

        // Re-open the modal if it was open
        if ($('#allColorsModal').length) {
            const modal = new bootstrap.Modal(document.getElementById('allColorsModal'));
            modal.show();
        }
    }

    /**
     * Navigate between colors in fullscreen view
     */
    function navigateColors(direction) {
        if (direction === 'prev') {
            currentColorIndex = (currentColorIndex - 1 + colors.length) % colors.length;
        } else {
            currentColorIndex = (currentColorIndex + 1) % colors.length;
        }

        const color = colors[currentColorIndex];
        $('.color-fullscreen-image').attr('src', color.path)
            .attr('alt', color.name)
            .on('error', function() {
                $(this).attr('src', 'images/placeholder.jpg');
            });
        $('.color-fullscreen-caption').text(color.name);
    }

    // Initialize on document ready
    $(document).ready(function() {
        initColorCarousel();
    });

})(jQuery);
