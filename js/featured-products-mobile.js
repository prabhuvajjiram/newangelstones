/**
 * Mobile-Optimized Featured Products
 * Lightweight, zero-dependency approach for mobile devices
 */
(function() {
    'use strict';
    
    // Only execute for mobile devices
    if (window.innerWidth >= 768) return;
    
    // Wait for DOM to be interactive
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    function init() {
        // Use intersection observer to lazy initialize
        const featuredSection = document.getElementById('featured-products');
        if (!featuredSection) return;
        
        const observer = new IntersectionObserver(entries => {
            if (entries[0].isIntersecting) {
                optimizeFeaturedProducts();
                observer.disconnect();
            }
        }, { 
            rootMargin: '200px 0px', 
            threshold: 0.01 
        });
        
        observer.observe(featuredSection);
    }
    
    function optimizeFeaturedProducts() {
        // Check for thumbnails container (from previous work) or create one
        let thumbnailsContainer = document.getElementById('category-thumbnails-container');
        
        // If no container exists yet, we're dealing with the initial page load
        if (!thumbnailsContainer) {
            // Create container for the thumbnails-first approach
            thumbnailsContainer = document.createElement('div');
            thumbnailsContainer.id = 'category-thumbnails-container';
            thumbnailsContainer.className = 'category-thumbnails-container';
            
            // Find where to insert it
            const featuredSection = document.getElementById('featured-products');
            const sectionHeader = featuredSection ? featuredSection.querySelector('.section-header') : null;
            
            if (sectionHeader) {
                sectionHeader.after(thumbnailsContainer);
            }
        }
        
        // Add touch-friendly styling
        const style = document.createElement('style');
        style.textContent = `
            .category-thumbnails-container {
                display: flex;
                overflow-x: auto;
                scroll-snap-type: x mandatory;
                -webkit-overflow-scrolling: touch;
                scrollbar-width: none;
                padding: 10px 0;
                scroll-behavior: smooth;
            }
            
            .category-thumbnails-container::-webkit-scrollbar {
                display: none;
            }
            
            .category-thumb {
                flex: 0 0 75%;
                min-width: 250px;
                max-width: 300px;
                margin-right: 15px;
                scroll-snap-align: center;
                aspect-ratio: 4/3;
                background-color: #222;
                border-radius: 8px;
                overflow: hidden;
                position: relative;
                box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            }
            
            .category-thumb img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                transition: transform 0.3s ease;
            }
            
            .category-thumb:active img {
                transform: scale(1.05);
            }
            
            .category-name {
                position: absolute;
                bottom: 0;
                left: 0;
                right: 0;
                background: rgba(0,0,0,0.7);
                color: white;
                padding: 8px 12px;
                font-size: 14px;
                text-align: center;
            }
            
            /* Navigation dots */
            .category-dots {
                display: flex;
                justify-content: center;
                margin-top: 10px;
            }
            .category-dot {
                width: 8px;
                height: 8px;
                border-radius: 50%;
                background: rgba(255,255,255,0.3);
                margin: 0 4px;
            }
            .category-dot.active {
                background: white;
            }
            
            /* Modal optimization */
            .products-modal-dialog {
                margin: 15px auto;
                max-width: calc(100% - 30px);
            }
            
            .products-modal-body {
                max-height: 75vh;
                overflow-y: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            /* Thumbnail grid in modal */
            .thumbnails-grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 8px;
                margin-top: 15px;
            }
            
            .thumbnail-item {
                aspect-ratio: 1/1;
                background: #f0f0f0;
                border-radius: 4px;
                overflow: hidden;
            }
            
            .thumbnail-item img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }
        `;
        document.head.appendChild(style);

        // Add auto-scrolling functionality once thumbnails are loaded
        const thumbnailObserver = new MutationObserver(function(mutations) {
            mutations.forEach(mutation => {
                if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                    // Check if we've added enough thumbnails to start auto-scrolling
                    const thumbnails = thumbnailsContainer.querySelectorAll('.category-thumb, .carousel-item, .category-item');
                    
                    if (thumbnails.length > 1 && !thumbnailsContainer.hasAttribute('data-auto-scroll-initialized')) {
                        // Add auto-scrolling
                        setupAutoScroll(thumbnailsContainer, thumbnails);
                        thumbnailsContainer.setAttribute('data-auto-scroll-initialized', 'true');
                    }
                    
                    // Make sure click events work properly on mobile with touch events
                    Array.from(mutation.addedNodes).forEach(node => {
                        if (node.nodeType === Node.ELEMENT_NODE && 
                            (node.classList.contains('carousel-item') || 
                             node.classList.contains('category-item') ||
                             node.classList.contains('category-thumb'))) {
                            
                            node.addEventListener('touchend', function(e) {
                                // Prevent double events
                                if (e.target.closest('a') || e.target.closest('button')) return;
                                
                                // This will trigger a click event which will be handled by the existing code
                                const clickEvent = new MouseEvent('click', {
                                    bubbles: true,
                                    cancelable: true,
                                    view: window
                                });
                                e.target.dispatchEvent(clickEvent);
                            });
                        }
                    });
                }
            });
        });
        
        // Start observing the thumbnail container
        thumbnailObserver.observe(thumbnailsContainer, { childList: true, subtree: true });
        
        // Setup auto-scrolling for any container
        function setupAutoScroll(container, items) {
            if (!container || items.length < 2) return;
            
            // Create indicator dots
            const dotsContainer = document.createElement('div');
            dotsContainer.className = 'category-dots';
            
            // Add navigation dots based on items
            const maxDots = Math.min(items.length, 5);
            for (let i = 0; i < maxDots; i++) {
                const dot = document.createElement('span');
                dot.className = i === 0 ? 'category-dot active' : 'category-dot';
                dotsContainer.appendChild(dot);
            }
            
            // Add dots after container
            container.parentNode.insertBefore(dotsContainer, container.nextSibling);
            
            // Add scroll listener to update dots
            let scrollTimeout;
            container.addEventListener('scroll', function() {
                clearTimeout(scrollTimeout);
                scrollTimeout = setTimeout(updateDots, 50);
            }, { passive: true });
            
            // Update dots based on scroll position
            function updateDots() {
                const scrollLeft = container.scrollLeft;
                const totalWidth = container.scrollWidth - container.clientWidth;
                const scrollRatio = scrollLeft / totalWidth;
                const activeDotIndex = Math.min(
                    Math.floor(scrollRatio * maxDots),
                    maxDots - 1
                );
                
                // Update dots
                const dots = dotsContainer.querySelectorAll('.category-dot');
                dots.forEach((dot, i) => {
                    dot.className = i === activeDotIndex ? 'category-dot active' : 'category-dot';
                });
            }
            
            // Auto-scroll functionality
            let currentIndex = 0;
            let autoScrollInterval;
            let isPaused = false;
            
            // Start auto-scrolling
            function startAutoScroll() {
                if (autoScrollInterval) clearInterval(autoScrollInterval);
                
                autoScrollInterval = setInterval(() => {
                    if (!isPaused && items.length > 1) {
                        currentIndex = (currentIndex + 1) % items.length;
                        const scrollTo = items[currentIndex].offsetLeft - container.offsetLeft;
                        container.scrollTo({
                            left: scrollTo,
                            behavior: 'smooth'
                        });
                    }
                }, 3500); // Change slide every 3.5 seconds
            }
            
            // Pause on user interaction
            container.addEventListener('touchstart', () => {
                isPaused = true;
            }, { passive: true });
            
            // Resume after user interaction ends
            container.addEventListener('touchend', () => {
                // Resume after a short delay
                setTimeout(() => {
                    isPaused = false;
                }, 5000); // 5 second pause after user interaction
            }, { passive: true });
            
            // Start auto-scrolling
            startAutoScroll();
        }
        
        // Fix modal scroll locks
        document.addEventListener('click', function(e) {
            // Find all modal triggers and handle them
            if (e.target.closest('[data-toggle="modal"]')) {
                document.body.style.overflow = 'hidden';
            }
            
            // Find all modal close buttons and handle them
            if (e.target.closest('[data-dismiss="modal"]') || 
                (e.target.classList && e.target.classList.contains('modal'))) {
                document.body.style.overflow = '';
            }
        });
    }
})();
