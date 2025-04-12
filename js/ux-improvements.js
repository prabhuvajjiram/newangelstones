/**
 * UX Improvements for Angel Stones
 * - Adds swipe support for mobile galleries
 * - Fixes scroll lock on modals
 * - Enhances "View All Colors" modal experience
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize Swiper for galleries on mobile devices
    initMobileGalleries();
    
    // Fix modal scroll locking
    fixModalScrollLock();
    
    // Enhance the color gallery modal
    enhanceColorModal();
});

/**
 * Initialize Swiper for mobile galleries
 */
function initMobileGalleries() {
    // Check if we're on mobile
    const isMobile = window.innerWidth < 768;
    
    if (isMobile) {
        // Featured products carousel
        const featuredSwiperElements = document.querySelectorAll('.featured-carousel');
        if (featuredSwiperElements.length > 0) {
            featuredSwiperElements.forEach((element, index) => {
                // Convert each carousel to Swiper while maintaining thumbnails-first approach
                new Swiper(element, {
                    slidesPerView: 'auto',
                    spaceBetween: 10,
                    grabCursor: true,
                    resistanceRatio: 0.65,
                    touchEventsTarget: 'container',
                    passiveListeners: true,
                    threshold: 5,
                    navigation: {
                        nextEl: element.querySelector('.swiper-button-next') || null,
                        prevEl: element.querySelector('.swiper-button-prev') || null,
                    },
                    pagination: {
                        el: element.querySelector('.swiper-pagination') || null,
                        type: 'bullets',
                        clickable: true
                    }
                });
            });
        }
        
        // Category thumbnails with swipe support
        // Respects the thumbnails-first approach implementation
        const categorySwiper = document.querySelector('.category-thumbnails');
        if (categorySwiper) {
            new Swiper(categorySwiper, {
                slidesPerView: 'auto',
                spaceBetween: 10,
                freeMode: true,
                grabCursor: true,
                resistanceRatio: 0.65,
                watchSlidesProgress: true,
                touchEventsTarget: 'container',
                passiveListeners: true
            });
        }
    }
}

/**
 * Fix modal scroll locking (prevents background scrolling while modal is open)
 */
function fixModalScrollLock() {
    // Get all modal triggers
    const modalTriggers = document.querySelectorAll('[data-toggle="modal"]');
    
    // For each modal trigger
    modalTriggers.forEach(trigger => {
        const targetId = trigger.getAttribute('data-target');
        const modal = document.querySelector(targetId);
        
        if (!modal) return;
        
        // When modal is shown
        trigger.addEventListener('click', function() {
            document.body.style.overflow = 'hidden';
            document.body.style.paddingRight = getScrollbarWidth() + 'px';
        });
        
        // Find close buttons in this modal
        const closeButtons = modal.querySelectorAll('[data-dismiss="modal"], .close');
        closeButtons.forEach(button => {
            button.addEventListener('click', function() {
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
            });
        });
        
        // Also handle clicking outside modal
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
            }
        });
    });
    
    // Get scrollbar width to prevent layout shift
    function getScrollbarWidth() {
        const outer = document.createElement('div');
        outer.style.visibility = 'hidden';
        outer.style.overflow = 'scroll';
        document.body.appendChild(outer);
        
        const inner = document.createElement('div');
        outer.appendChild(inner);
        
        const scrollbarWidth = outer.offsetWidth - inner.offsetWidth;
        outer.parentNode.removeChild(outer);
        
        return scrollbarWidth;
    }
}

/**
 * Enhance the color gallery modal with better UI and lazy loading
 */
function enhanceColorModal() {
    // Find the color gallery modal
    const colorModal = document.getElementById('colorModal');
    if (!colorModal) return;
    
    // Add a header to the color modal content
    const modalBody = colorModal.querySelector('.modal-body');
    if (modalBody) {
        // Check if header already exists to avoid duplicates
        if (!modalBody.querySelector('.color-modal-header')) {
            const header = document.createElement('div');
            header.className = 'color-modal-header';
            header.innerHTML = '<h5>All Available Colors</h5><p>Scroll to explore all our color options</p>';
            modalBody.insertBefore(header, modalBody.firstChild);
            
            // Add scrollbar styles
            const style = document.createElement('style');
            style.textContent = `
                .color-modal-header {
                    position: sticky;
                    top: 0;
                    background: #fff;
                    padding: 10px 0;
                    margin-bottom: 15px;
                    border-bottom: 1px solid #eee;
                    text-align: center;
                    z-index: 1;
                }
                .color-modal-header h5 {
                    margin: 0 0 5px;
                    font-size: 1.2rem;
                }
                .color-modal-header p {
                    margin: 0;
                    font-size: 0.9rem;
                    color: #777;
                }
                #colorModal .modal-body {
                    max-height: 70vh;
                    overflow-y: auto;
                    scrollbar-width: thin;
                    scrollbar-color: #ccc #f5f5f5;
                }
                #colorModal .modal-body::-webkit-scrollbar {
                    width: 6px;
                }
                #colorModal .modal-body::-webkit-scrollbar-track {
                    background: #f5f5f5;
                }
                #colorModal .modal-body::-webkit-scrollbar-thumb {
                    background-color: #ccc;
                    border-radius: 6px;
                }
                /* Images in color grid */
                #colorModal .color-grid img {
                    opacity: 0;
                    transition: opacity 0.3s ease;
                }
                #colorModal .color-grid img.loaded {
                    opacity: 1;
                }
            `;
            document.head.appendChild(style);
        }
        
        // Implement lazy loading for colors
        const colorImages = modalBody.querySelectorAll('.color-item img');
        if (colorImages.length > 0) {
            // Use intersection observer for lazy loading
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        const dataSrc = img.getAttribute('data-src');
                        
                        if (dataSrc) {
                            img.src = dataSrc;
                            img.addEventListener('load', () => {
                                img.classList.add('loaded');
                            });
                            img.removeAttribute('data-src');
                            observer.unobserve(img);
                        }
                    }
                });
            }, {
                rootMargin: '200px 0px',
                threshold: 0.01
            });
            
            // Observe all color images
            colorImages.forEach(img => {
                // Only setup lazy loading if not already loaded
                if (!img.complete || img.naturalWidth === 0) {
                    if (!img.getAttribute('data-src') && img.src) {
                        img.setAttribute('data-src', img.src);
                        img.src = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1 1"%3E%3C/svg%3E';
                        observer.observe(img);
                    }
                }
            });
        }
    }
}
