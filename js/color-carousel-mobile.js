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
        initObserver();
    }
    
    // Use intersection observer to defer loading until visible
    function initObserver() {
        const colorSection = document.querySelector('.colors-section');
        if (!colorSection) return;
        
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
        const colorRow = document.querySelector('.color-row');
        if (!colorRow) return;
        
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
                background: rgba(255,255,255,0.4);
                margin: 0 4px;
            }
            .touch-dot.active {
                background: white;
            }
        `;
        document.head.appendChild(style);
        
        // Add indicator dots
        const touchDots = document.createElement('div');
        touchDots.className = 'touch-dots';
        
        // Get color items
        const colorItems = colorRow.querySelectorAll('.color-item');
        
        // Add navigation dots based on visible items
        const visibleItems = Math.min(colorItems.length, 5);
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
            scrollTimeout = setTimeout(updateActiveDot, 50);
        }, { passive: true });
        
        // Update active dot
        function updateActiveDot() {
            const scrollPosition = colorRow.scrollLeft;
            const itemWidth = colorItems[0].offsetWidth + 10; // including margin
            const activeIndex = Math.min(
                Math.floor((scrollPosition + itemWidth / 2) / itemWidth),
                visibleItems - 1
            );
            
            // Update dots
            const dots = touchDots.querySelectorAll('.touch-dot');
            dots.forEach((dot, i) => {
                dot.className = i === activeIndex ? 'touch-dot active' : 'touch-dot';
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
                if (!isPaused) {
                    currentIndex = (currentIndex + 1) % colorItems.length;
                    const scrollTo = colorItems[currentIndex].offsetLeft - colorRow.offsetLeft;
                    colorRow.scrollTo({
                        left: scrollTo,
                        behavior: 'smooth'
                    });
                }
            }, 3000); // Change slide every 3 seconds
        }
        
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
        
        // Start auto-scrolling
        startAutoScroll();
        
        // Enhance modal
        enhanceColorModal();
    }
    
    // Simple modal enhancement for mobile
    function enhanceColorModal() {
        const colorModal = document.getElementById('colorModal');
        if (!colorModal) return;
        
        // Add minimal mobile styles
        const style = document.createElement('style');
        style.textContent = `
            #colorModal .modal-body {
                max-height: 70vh;
                overflow-y: auto;
                -webkit-overflow-scrolling: touch;
            }
            #colorModal .modal-dialog {
                margin: 10px auto;
                max-width: calc(100% - 20px);
            }
            #colorModal .color-grid {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }
        `;
        document.head.appendChild(style);
        
        // Fix scroll lock
        const modalTrigger = document.querySelector('[data-target="#colorModal"]');
        if (modalTrigger) {
            modalTrigger.addEventListener('click', () => {
                document.body.style.overflow = 'hidden';
            });
            
            const closeButtons = colorModal.querySelectorAll('[data-dismiss="modal"], .close');
            closeButtons.forEach(button => {
                button.addEventListener('click', () => {
                    document.body.style.overflow = '';
                });
            });
        }
    }
})();
