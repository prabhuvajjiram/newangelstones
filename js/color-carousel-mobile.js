/**
 * Ultra-Lightweight Mobile Color Carousel
 * Performance-first approach with minimal code
 */
(function() {
    'use strict';
    
    // Only execute for mobile devices
    if (window.innerWidth >= 768) return;
    
    // Wait for DOM to be interactive but don't block rendering
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initObserver);
    } else {
        // Small delay to ensure other scripts have initialized
        setTimeout(initObserver, 100);
    }
    
    // Use intersection observer to defer loading until visible
    function initObserver() {
        const colorSection = document.querySelector('.colors-section');
        if (!colorSection) {
            console.log("Mobile carousel: .colors-section not found, trying alternate selector");
            // Try alternate selector
            const alternateSection = document.querySelector('#variety-of-granites');
            if (alternateSection) {
                alternateSection.classList.add('colors-section');
                setupTouchCarousel();
            } else {
                console.error("Mobile carousel: Could not find color section");
            }
            return;
        }
        
        const observer = new IntersectionObserver(entries => {
            if (entries[0].isIntersecting) {
                // Add simple touch carousel
                setupTouchCarousel();
                observer.disconnect();
            }
        }, { 
            rootMargin: '200px 0px', 
            threshold: 0.01 
        });
        
        observer.observe(colorSection);
    }
    
    // Simple, lightweight touch carousel without dependencies
    function setupTouchCarousel() {
        // Try both possible selectors to find the color row
        let colorRow = document.querySelector('.color-row');
        if (!colorRow) {
            console.log("Mobile carousel: .color-row not found, trying alternate selector");
            // Try with owl-carousel
            colorRow = document.querySelector('#variety-of-granites .owl-carousel');
            if (colorRow) {
                colorRow.classList.add('color-row');
            } else {
                console.error("Mobile carousel: Could not find color row container");
                return;
            }
        }
        
        // Add CSS only if needed
        const style = document.createElement('style');
        style.textContent = `
            .color-row {
                display: flex;
                overflow-x: auto;
                scroll-snap-type: x mandatory;
                -webkit-overflow-scrolling: touch;
                scrollbar-width: none;
                padding: 10px 0;
                scroll-behavior: smooth;
            }
            .color-row::-webkit-scrollbar {
                display: none;
            }
            .color-item {
                flex: 0 0 auto;
                width: 160px;
                margin: 0 5px;
                scroll-snap-align: center;
            }
            .touch-dots {
                display: flex;
                justify-content: center;
                margin-top: 10px;
            }
            .touch-dot {
                width: 8px;
                height: 8px;
                border-radius: 50%;
                background: rgba(0,0,0,0.2);
                margin: 0 4px;
                transition: background 0.3s ease;
            }
            .touch-dot.active {
                background: rgba(0,0,0,0.6);
            }
        `;
        document.head.appendChild(style);
        
        // Get or create color items
        let colorItems = colorRow.querySelectorAll('.color-item');
        
        // If there are no color items, check if we need to create them
        if (colorItems.length === 0) {
            // Look for variety-of-granites items
            const varietyItems = colorRow.querySelectorAll('.variety-of-granites');
            if (varietyItems.length > 0) {
                // Convert existing items to color items
                varietyItems.forEach(item => {
                    item.classList.add('color-item');
                });
                colorItems = colorRow.querySelectorAll('.color-item');
            } else {
                // Look for owl-carousel items
                const owlItems = colorRow.querySelectorAll('.owl-item');
                if (owlItems.length > 0) {
                    owlItems.forEach(item => {
                        item.classList.add('color-item');
                    });
                    colorItems = colorRow.querySelectorAll('.color-item');
                }
            }
        }
        
        // Add indicator dots
        const touchDots = document.createElement('div');
        touchDots.className = 'touch-dots';
        
        // Add navigation dots based on visible items
        const visibleItems = Math.min(colorItems.length, 5);
        if (visibleItems === 0) {
            console.error("Mobile carousel: No color items found");
            return;
        }
        
        for (let i = 0; i < visibleItems; i++) {
            const dot = document.createElement('span');
            dot.className = i === 0 ? 'touch-dot active' : 'touch-dot';
            touchDots.appendChild(dot);
        }
        
        // Add dots after color row
        colorRow.parentNode.insertBefore(touchDots, colorRow.nextSibling);
        
        // Update dots on scroll
        let scrollTimeout;
        colorRow.addEventListener('scroll', function() {
            clearTimeout(scrollTimeout);
            scrollTimeout = setTimeout(() => updateActiveDot(colorRow, touchDots, colorItems), 50);
        }, { passive: true });
        
        // Auto-scroll functionality
        let currentIndex = 0;
        let autoScrollInterval;
        let isPaused = false;
        
        // Start auto-scrolling
        startAutoScroll(colorRow, colorItems);
        
        // Pause on user interaction
        colorRow.addEventListener('touchstart', () => {
            isPaused = true;
        }, { passive: true });
        
        // Resume after user interaction ends
        colorRow.addEventListener('touchend', () => {
            // Resume after a short delay
            setTimeout(() => {
                isPaused = false;
            }, 5000); // 5 second pause after user interaction
        }, { passive: true });
        
        // Handle visibility change
        document.addEventListener('visibilitychange', () => {
            isPaused = document.visibilityState !== 'visible';
        });
        
        // Add swipe support
        let startX, endX;
        colorRow.addEventListener('touchstart', function(e) {
            startX = e.touches[0].clientX;
        }, { passive: true });
        
        colorRow.addEventListener('touchend', function(e) {
            endX = e.changedTouches[0].clientX;
            const diffX = startX - endX;
            const threshold = 50;
            
            if (Math.abs(diffX) > threshold) {
                colorRow.scrollBy({
                    left: diffX * 1.5,
                    behavior: 'smooth'
                });
            }
        }, { passive: true });
        
        // Enhance modal
        enhanceColorModal();
        
        // Auto-scroll function
        function startAutoScroll(container, items) {
            if (autoScrollInterval) clearInterval(autoScrollInterval);
            
            autoScrollInterval = setInterval(() => {
                if (!isPaused && document.visibilityState === 'visible') {
                    currentIndex = (currentIndex + 1) % items.length;
                    const scrollTo = items[currentIndex].offsetLeft - container.offsetLeft;
                    container.scrollTo({
                        left: scrollTo,
                        behavior: 'smooth'
                    });
                }
            }, 3000); // Change slide every 3 seconds
        }
        
        // Update active dot
        function updateActiveDot(container, dotsContainer, items) {
            const scrollPosition = container.scrollLeft;
            const itemWidth = items[0].offsetWidth + 10; // including margin
            const activeIndex = Math.min(
                Math.floor((scrollPosition + itemWidth / 2) / itemWidth),
                visibleItems - 1
            );
            
            // Update dots
            const dots = dotsContainer.querySelectorAll('.touch-dot');
            dots.forEach((dot, i) => {
                dot.className = i === activeIndex ? 'touch-dot active' : 'touch-dot';
            });
        }
    }
    
    // Simple modal enhancement for mobile
    function enhanceColorModal() {
        // Look for the modal in different places
        let colorModal = document.getElementById('colorModal');
        if (!colorModal) {
            colorModal = document.getElementById('allColorsModal');
        }
        
        if (!colorModal) {
            // Create the modal if it doesn't exist
            const modalHTML = `
                <div class="modal fade" id="colorModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">All Colors</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="color-grid">
                                    <!-- Will be populated dynamically by color-carousel.js -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', modalHTML);
            colorModal = document.getElementById('colorModal');
        }
        
        // Add minimal mobile styles
        const style = document.createElement('style');
        style.textContent = `
            #colorModal .modal-body,
            #allColorsModal .modal-body {
                max-height: 70vh;
                overflow-y: auto;
                -webkit-overflow-scrolling: touch;
            }
            #colorModal .modal-dialog,
            #allColorsModal .modal-dialog {
                margin: 10px auto;
                max-width: calc(100% - 20px);
            }
            #colorModal .color-grid,
            #allColorsModal .color-grid {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }
            /* Using thumbnails-first approach for better mobile performance */
            .color-grid-item {
                cursor: pointer;
                text-align: center;
                transition: transform 0.2s ease;
            }
            .color-grid-item:active {
                transform: scale(0.95);
            }
            .color-image img {
                max-width: 100%;
                height: auto;
                border-radius: 4px;
            }
        `;
        document.head.appendChild(style);
        
        // Fix scroll lock for the modal
        document.querySelectorAll('[data-target="#colorModal"], [data-bs-target="#colorModal"], .view-all-colors-btn').forEach(button => {
            if (button) {
                button.addEventListener('click', () => {
                    document.body.style.overflow = 'hidden';
                });
            }
        });
        
        const closeSelectors = '#colorModal [data-dismiss="modal"], #colorModal .close, #colorModal .btn-close, #allColorsModal [data-dismiss="modal"], #allColorsModal .close, #allColorsModal .btn-close';
        document.querySelectorAll(closeSelectors).forEach(button => {
            if (button) {
                button.addEventListener('click', () => {
                    document.body.style.overflow = '';
                });
            }
        });
    }
})();
