/**
 * Color Carousel - Dynamic color image loader
 * For Angel Stones website
 * @module colorCarousel
 */
(function($) {
    'use strict';

    // Colors container
    let colors = [];
    let currentColorIndex = 0;
    let isLoading = false;
    let lastUpdateTime = 0;
    const CACHE_DURATION = 5 * 60 * 1000; // 5 minutes cache
    let touchStartX = 0;
    let touchEndX = 0;

    /**
     * Initialize the color carousel and modal
     */
    function initColorCarousel() {
        // Show loading state
        const $container = $('#variety-of-granites .owl-carousel');
        $container.html(`
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading colors...</span>
                </div>
                <p class="mt-2">Loading color options...</p>
            </div>
        `);

        // Fetch all color images from the directory
        fetchColorImages()
            .then(() => {
                if (colors.length > 0) {
                    populateColorDisplay();
                    setupEventListeners();
                } else {
                    showError('No color images found');
                }
            })
            .catch(error => {
                console.error('Error initializing color carousel:', error);
                showError('Failed to load colors. Please try again later.');
            });
    }

    /**
     * Show error message
     */
    function showError(message) {
        const $container = $('#variety-of-granites .owl-carousel');
        $container.html(`
            <div class="alert alert-warning m-3">
                <i class="bi bi-exclamation-triangle me-2"></i>
                ${message}
                <button class="btn btn-sm btn-outline-primary ms-3" onclick="window.location.reload()">
                    <i class="bi bi-arrow-clockwise"></i> Retry
                </button>
            </div>
        `);
    }

    /**
     * Fetch all color images from the server
     */
    function fetchColorImages() {
        return new Promise((resolve, reject) => {
            if (isLoading) return;
            
            const now = Date.now();
            // Use cached data if it's still fresh
            if (colors.length > 0 && (now - lastUpdateTime) < CACHE_DURATION) {
                return resolve();
            }

            isLoading = true;
            const timestamp = now; // Add cache busting parameter
            
            $.ajax({
                url: `get_color_images.php?_=${timestamp}`,
                dataType: 'json',
                cache: false,
                success: (data) => {
                    isLoading = false;
                    if (data && data.success && data.colors && data.colors.length > 0) {
                        colors = data.colors;
                        lastUpdateTime = now;
                        console.log(`Loaded ${colors.length} color images from ${data.directory}`);
                        resolve();
                    } else {
                        reject(data?.error || 'No color images found');
                    }
                },
                error: (xhr, status, error) => {
                    isLoading = false;
                    console.error('Error fetching color images:', status, error);
                    reject(`Failed to load colors: ${error || 'Unknown error'}`);
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
            const safeName = $('<div>').text(color.name).html(); // Escape HTML in color name
            
            // Format color name for URL
            const formattedName = color.name.toLowerCase().replace(/\s+/g, '-');
            
            // Create color item with schema.org attributes
            const item = `
                <div class="color-item" 
                     itemprop="itemListElement" 
                     itemscope 
                     itemtype="https://schema.org/ListItem"
                     data-color-name="${color.name}">
                    <meta itemprop="position" content="${index + 1}" />
                    <div class="color-item-inner" 
                         itemscope 
                         itemtype="https://schema.org/Product" 
                         itemid="#${formattedName}">
                        <meta itemprop="name" content="${color.name} Granite">
                        <meta itemprop="description" content="${color.description || 'Premium quality ' + color.name + ' granite'}">
                        <meta itemprop="image" content="${color.image}">
                        <div itemprop="offers" itemscope itemtype="https://schema.org/Offer">
                            <meta itemprop="priceCurrency" content="USD">
                            <meta itemprop="price" content="0">
                            <link itemprop="availability" href="https://schema.org/InStock" />
                        </div>
                        <img src="${color.thumbnail || color.image}" 
                             alt="${color.name} Granite" 
                             loading="lazy"
                             class="img-fluid"
                             itemprop="image">
                        <div class="color-name" itemprop="name">${color.name}</div>
                    </div>
                </div>`;
            $container.append(item);
        });

        // Add loading animation to images
        $container.find('img').on('load', function() {
            $(this).closest('.color-scroll-item').addClass('loaded');
        });

        // Add or update the View All Colors button
        const $carouselContainer = $container.closest('.col-md-12');
        $carouselContainer.find('.view-all-colors-container').remove();
        
        $carouselContainer.append(`
            <div class="view-all-colors-container text-center mt-4">
                <button class="btn btn-primary view-all-colors-btn">
                    <i class="bi bi-grid-3x3-gap me-2"></i>
                    View All ${colors.length} Colors
                </button>
            </div>
        `);
        
        // Update the carousel header with count
        const $header = $container.closest('.section-padding').find('.section-header .title');
        if ($header.length && !$header.find('.badge').length) {
            $header.append(` <span class="badge bg-primary">${colors.length} colors</span>`);
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
                    e.preventDefault();
                } else if (e.key === 'ArrowRight') {
                    navigateColors('next');
                    e.preventDefault();
                }
            }
        });

        // Touch events for mobile swipe
        $(document).on('touchstart', '.color-fullscreen-container', function(e) {
            touchStartX = e.originalEvent.touches[0].clientX;
        });

        $(document).on('touchmove', '.color-fullscreen-container', function(e) {
            e.preventDefault();
            touchEndX = e.originalEvent.touches[0].clientX;
        });

        $(document).on('touchend', '.color-fullscreen-container', function() {
            handleSwipe();
        });

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
     * Handle swipe events for touch devices
     */
    function handleSwipe() {
        if (Math.abs(touchEndX - touchStartX) > 50) {
            if (touchEndX < touchStartX) {
                navigateColors('next');
            } else if (touchEndX > touchStartX) {
                navigateColors('prev');
            }
        }
    }

    /**
     * Show the All Colors modal with grid view
     */
    function showAllColorsModal() {
        const modalId = 'all-colors-modal';
        let modalHtml = `
            <div class="modal fade" id="${modalId}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">All Colors (${colors.length})</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3">
        `;

        // Add color grid items
        colors.forEach((color, index) => {
            const safeName = $('<div>').text(color.name).html();
            modalHtml += `
                <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                    <div class="color-grid-item text-center" data-index="${index}">
                        <div class="color-grid-image mb-2">
                            <img src="${color.path}" 
                                 class="img-fluid rounded shadow-sm" 
                                 alt="${safeName}"
                                 onerror="this.onerror=null; this.src='images/placeholder.jpg';">
                        </div>
                        <div class="color-grid-name small">${safeName}</div>
                    </div>
                </div>
            `;
        });

        modalHtml += `
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Remove existing modal if any
        $(`#${modalId}`).remove();
        
        // Add new modal to body and show it
        $('body').append(modalHtml);
        const modal = new bootstrap.Modal(document.getElementById(modalId));
        modal.show();

        // Clean up modal on hide
        $(`#${modalId}`).on('hidden.bs.modal', function() {
            $(this).remove();
        });
    }

    /**
     * Show fullscreen view of a color
     */
    function showColorFullscreen(index) {
        if (index < 0 || index >= colors.length) return;
        
        currentColorIndex = index;
        const color = colors[index];
        const safeName = $('<div>').text(color.name).html();
        
        // Create fullscreen container if it doesn't exist
        if ($('.color-fullscreen-container').length === 0) {
            $('body').append(`
                <div class="color-fullscreen-container">
                    <div class="color-fullscreen-content">
                        <img src="${color.path}" alt="${safeName}" class="img-fluid">
                        <div class="color-fullscreen-caption">${safeName}</div>
                    </div>
                    <button class="color-fullscreen-nav color-fullscreen-prev">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    <button class="color-fullscreen-nav color-fullscreen-next">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                    <button class="color-fullscreen-close">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            `);
        } else {
            // Update existing fullscreen view
            const $fullscreen = $('.color-fullscreen-container');
            $fullscreen.find('img').attr('src', color.path).attr('alt', safeName);
            $fullscreen.find('.color-fullscreen-caption').text(safeName);
        }
        
        // Show fullscreen view
        $('body').addClass('color-fullscreen-active');
    }

    /**
     * Close fullscreen view
     */
    function closeColorFullscreen() {
        $('body').removeClass('color-fullscreen-active');
        
        // Keep the element in DOM but hide it for better performance
        $('.color-fullscreen-container')
            .one('transitionend', function() {
                if (!$('body').hasClass('color-fullscreen-active')) {
                    $(this).remove();
                }
            });
    }

    /**
     * Navigate between colors in fullscreen view
     */
    function navigateColors(direction) {
        if (direction === 'prev') {
            currentColorIndex = (currentColorIndex - 1 + colors.length) % colors.length;
        } else if (direction === 'next') {
            currentColorIndex = (currentColorIndex + 1) % colors.length;
        } else if (typeof direction === 'number') {
            // Direct index navigation
            currentColorIndex = Math.max(0, Math.min(direction, colors.length - 1));
        }
        
        // Update the fullscreen view
        showColorFullscreen(currentColorIndex);
    }

    /**
     * Add CSS styles for the color carousel
     * @private
     */
    function addColorCarouselStyles() {
        if ($('#color-carousel-styles').length) {
            return;
        }

        const styles = `
            /* Color Carousel Styles */
            .color-scroll-container {
                display: flex;
                overflow-x: auto;
                padding: 1rem 0.5rem;
                scrollbar-width: thin;
                scroll-behavior: smooth;
                scroll-snap-type: x mandatory;
                gap: 1rem;
            }
            
            .color-scroll-container::-webkit-scrollbar {
                height: 6px;
            }
            
            .color-scroll-container::-webkit-scrollbar-thumb {
                background-color: rgba(0, 0, 0, 0.2);
                border-radius: 3px;
            }
            
            .color-scroll-container.scrollable-hint {
                background: linear-gradient(to right, #fff, #f8f9fa, #fff);
            }
            
            .color-scroll-item {
                flex: 0 0 auto;
                width: 150px;
                cursor: pointer;
                transition: transform 0.2s ease;
                scroll-snap-align: start;
            }
            
            .color-scroll-item:hover {
                transform: translateY(-5px);
            }
            
            .color-scroll-image {
                position: relative;
                overflow: hidden;
                border-radius: 8px;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
                aspect-ratio: 1;
            }
            
            .color-scroll-image img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                transition: transform 0.3s ease;
            }
            
            .color-scroll-item:hover .color-scroll-image img {
                transform: scale(1.05);
            }
            
            .color-overlay {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: linear-gradient(to bottom, rgba(0,0,0,0.1) 0%, rgba(0,0,0,0.4) 100%);
                opacity: 0;
                transition: opacity 0.3s ease;
            }
            
            .color-scroll-item:hover .color-overlay {
                opacity: 1;
            }
            
            .color-scroll-item .caption {
                margin-top: 0.5rem;
                font-size: 0.85rem;
                color: #333;
            }
            
            /* Fullscreen View */
            .color-fullscreen-container {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: rgba(0, 0, 0, 0.9);
                z-index: 2000;
                display: flex;
                align-items: center;
                justify-content: center;
                opacity: 0;
                visibility: hidden;
                transition: opacity 0.3s ease, visibility 0.3s ease;
            }
            
            .color-fullscreen-active .color-fullscreen-container {
                opacity: 1;
                visibility: visible;
            }
            
            .color-fullscreen-content {
                max-width: 90%;
                max-height: 90vh;
                position: relative;
                text-align: center;
            }
            
            .color-fullscreen-content img {
                max-height: 80vh;
                max-width: 100%;
                border-radius: 8px;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            }
            
            .color-fullscreen-caption {
                color: #fff;
                margin-top: 1rem;
                font-size: 1.1rem;
                text-shadow: 0 1px 3px rgba(0, 0, 0, 0.5);
            }
            
            .color-fullscreen-nav {
                position: fixed;
                top: 50%;
                transform: translateY(-50%);
                width: 50px;
                height: 50px;
                background-color: rgba(255, 255, 255, 0.2);
                border: none;
                border-radius: 50%;
                color: white;
                font-size: 1.5rem;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                transition: background-color 0.2s ease;
                z-index: 2100;
            }
            
            .color-fullscreen-nav:hover {
                background-color: rgba(255, 255, 255, 0.3);
            }
            
            .color-fullscreen-prev {
                left: 20px;
            }
            
            .color-fullscreen-next {
                right: 20px;
            }
            
            .color-fullscreen-close {
                position: fixed;
                top: 20px;
                right: 20px;
                width: 40px;
                height: 40px;
                background-color: rgba(255, 255, 255, 0.2);
                border: none;
                border-radius: 50%;
                color: white;
                font-size: 1.2rem;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                transition: background-color 0.2s ease;
                z-index: 2100;
            }
            
            .color-fullscreen-close:hover {
                background-color: rgba(255, 255, 255, 0.3);
            }
            
            /* Grid View in Modal */
            .color-grid-item {
                cursor: pointer;
                transition: transform 0.2s ease;
            }
            
            .color-grid-item:hover {
                transform: translateY(-3px);
            }
            
            .color-grid-image {
                position: relative;
                overflow: hidden;
                border-radius: 6px;
                box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
                aspect-ratio: 1;
                margin-bottom: 0.5rem;
            }
            
            .color-grid-image img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                transition: transform 0.3s ease;
            }
            
            .color-grid-item:hover .color-grid-image img {
                transform: scale(1.05);
            }
            
            .color-grid-name {
                color: #333;
                font-weight: 500;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            
            /* Responsive Adjustments */
            @media (max-width: 767.98px) {
                .color-scroll-item {
                    width: 120px;
                }
                
                .color-fullscreen-nav {
                    width: 40px;
                    height: 40px;
                    font-size: 1.2rem;
                }
                
                .color-fullscreen-close {
                    width: 36px;
                    height: 36px;
                    font-size: 1.1rem;
                }
            }
            
            @media (max-width: 575.98px) {
                .color-scroll-item {
                    width: 100px;
                }
                
                .color-scroll-item .caption {
                    font-size: 0.75rem;
                }
                
                .color-fullscreen-caption {
                    font-size: 0.95rem;
                }
            }
        `;
        
        $('<style id="color-carousel-styles">' + styles + '</style>').appendTo('head');
    }

    // Initialize on document ready
    $(document).ready(function() {
        // Add styles first
        addColorCarouselStyles();
        
        // Then initialize the carousel if the container exists
        if ($('#variety-of-granites').length) {
            initColorCarousel();
        }
    });

})(jQuery);
