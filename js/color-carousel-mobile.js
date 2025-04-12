/**
 * Ultra-Lightweight Mobile Color Carousel
 * Performance-first approach with minimal code and accessibility improvements
 * MOBILE ONLY - Does not affect desktop experience
 */
(function() {
    'use strict';
    
    // Only execute for mobile devices - strict check to preserve desktop experience
    const isMobile = window.innerWidth < 768 || /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    if (!isMobile) {
        console.log("Mobile carousel: Desktop detected, not initializing mobile carousel");
        return; // Exit immediately for desktop devices
    }
    
    // Check for existing desktop carousel before initializing
    function checkForDesktopCarousel() {
        // Look for common desktop carousel indicators
        const hasOwlCarousel = typeof jQuery !== 'undefined' && typeof jQuery.fn.owlCarousel !== 'undefined';
        const hasInitializedOwl = document.querySelector('.owl-carousel.owl-loaded');
        
        if (hasOwlCarousel && hasInitializedOwl) {
            console.log("Mobile carousel: Desktop carousel already active, not initializing mobile version");
            return true;
        }
        return false;
    }
    
    // Wait for DOM to be interactive but don't block rendering
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            if (!checkForDesktopCarousel()) {
                initMobileCarousel();
            }
        });
    } else {
        // Small delay to ensure other scripts have initialized
        setTimeout(() => {
            if (!checkForDesktopCarousel()) {
                initMobileCarousel();
            }
        }, 100);
    }
    
    function initMobileCarousel() {
        // Find carousel container
        const colorSection = document.querySelector('#variety-of-granites');
        if (!colorSection) return;
        
        const colorRow = colorSection.querySelector('.owl-carousel') || 
                         colorSection.querySelector('.color-row') ||
                         colorSection.querySelector('.color-scroll-container');
        
        if (!colorRow) return;
        
        // Check if Owl Carousel is already initialized and working
        if (colorRow.classList.contains('owl-loaded') && 
            colorRow.querySelectorAll('.owl-item').length > 0 &&
            !colorRow.classList.contains('owl-broken')) {
            // Owl Carousel is working, no need for our implementation
            return;
        }
        
        // Create simple CSS for horizontal scrolling
        const style = document.createElement('style');
        style.textContent = `
            @media (max-width: 767px) {
                /* Fix video display */
                .hero-video {
                    height: 100vh;
                    height: -webkit-fill-available;
                }
                
                #hero-video {
                    opacity: 1 !important;
                    object-fit: cover !important;
                    width: 100% !important;
                    height: 100% !important;
                    display: block !important;
                }
                
                /* Fix color carousel */
                #variety-of-granites .owl-carousel,
                .owl-carousel.color-row,
                .color-scroll-container {
                    display: flex !important;
                    overflow-x: auto !important;
                    scroll-snap-type: x mandatory !important;
                    -webkit-overflow-scrolling: touch !important;
                    scroll-behavior: smooth !important;
                    padding-bottom: 10px !important;
                }
                
                /* Fix navigation controls on mobile */
                .owl-nav, .owl-dots {
                    display: none !important;
                }
                
                /* Fix carousel item display */
                .owl-item, .color-scroll-item, .variety-of-granites {
                    flex: 0 0 auto !important;
                    width: 160px !important;
                    margin: 0 5px !important;
                    scroll-snap-align: center !important;
                }
                
                /* Fix color name visibility */
                .caption p, 
                #variety-of-granites .caption p,
                .variety-of-granites .caption p,
                .color-item .caption p {
                    color: #ffffff !important;
                    text-shadow: 0 0 3px rgba(0,0,0,0.9), 0 0 5px rgba(0,0,0,0.7) !important;
                    font-weight: 500 !important;
                    margin-top: 8px !important;
                    font-size: 14px !important;
                    visibility: visible !important;
                    opacity: 1 !important;
                }
                
                /* Ensure images are visible */
                .variety-of-granites img,
                .color-item img {
                    opacity: 1 !important;
                    visibility: visible !important;
                }
                
                /* Fix webkit issues */
                .owl-stage {
                    display: flex !important;
                }
            }
        `;
        document.head.appendChild(style);
        
        // Fix video playback on mobile
        const video = document.getElementById('hero-video');
        if (video) {
            // Ensure video plays by resetting it
            video.pause();
            video.currentTime = 0;
            
            // Force inline playback (crucial for iOS)
            video.setAttribute('playsinline', '');
            video.setAttribute('webkit-playsinline', '');
            video.muted = true;
            
            // Set poster in JS as backup
            if (!video.poster || video.poster === '') {
                video.poster = 'images/video-poster-mobile.jpg';
            }
            
            // Make opacity 1 immediately to ensure it's visible
            video.style.opacity = '1';
            
            // Force play attempt
            setTimeout(function() {
                const playPromise = video.play();
                if (playPromise !== undefined) {
                    playPromise.catch(function(error) {
                        // If autoplay failed, try again with user interaction
                        console.log("Video autoplay prevented:", error);
                    });
                }
            }, 300);
        }
        
        // Fix color carousel scrolling
        setTimeout(function() {
            // Find carousel containers using multiple selectors to ensure we find it
            const colorRow = document.querySelector('#variety-of-granites .owl-carousel') || 
                           document.querySelector('.owl-carousel.color-row') ||
                           document.querySelector('#variety-of-granites .color-row') ||
                           document.querySelector('.color-scroll-container');
            
            if (!colorRow) return;
            
            // Force fixes on all carousel items for visibility
            const items = colorRow.querySelectorAll('.variety-of-granites, .owl-item, .color-item');
            items.forEach(function(item) {
                // Ensure captions are visible
                const caption = item.querySelector('.caption p');
                if (caption) {
                    caption.style.color = '#ffffff';
                    caption.style.textShadow = '0 0 3px rgba(0,0,0,0.9)';
                    caption.style.fontWeight = '500';
                }
                
                // Ensure images are loaded
                const img = item.querySelector('img');
                if (img && img.getAttribute('data-src')) {
                    img.src = img.getAttribute('data-src');
                    img.removeAttribute('data-src');
                }
            });
            
            // Add auto-scrolling functionality
            let scrollPosition = 0;
            let scrollInterval;
            let isScrolling = false;
            
            function startAutoScroll() {
                if (scrollInterval) clearInterval(scrollInterval);
                
                scrollInterval = setInterval(function() {
                    if (document.hidden || isScrolling) return;
                    
                    const itemWidth = 170; // Width + margin
                    const maxScroll = colorRow.scrollWidth - colorRow.clientWidth;
                    
                    // Increment position
                    scrollPosition += itemWidth;
                    
                    // Reset if we reach the end
                    if (scrollPosition > maxScroll) scrollPosition = 0;
                    
                    // Scroll
                    isScrolling = true;
                    colorRow.scrollTo({
                        left: scrollPosition,
                        behavior: 'smooth'
                    });
                    
                    // Reset scrolling flag after animation
                    setTimeout(function() {
                        isScrolling = false;
                    }, 500);
                }, 3000);
            }
            
            // Initialize auto-scrolling
            startAutoScroll();
            
            // Handle user interaction
            colorRow.addEventListener('touchstart', function() {
                clearInterval(scrollInterval);
                isScrolling = false;
            }, { passive: true });
            
            colorRow.addEventListener('touchend', function() {
                // Update current position
                scrollPosition = colorRow.scrollLeft;
                // Resume auto-scrolling after delay
                setTimeout(startAutoScroll, 5000);
            }, { passive: true });
            
            // Add improved swipe detection
            let startX;
            colorRow.addEventListener('touchstart', function(e) {
                startX = e.touches[0].clientX;
            }, { passive: true });
            
            colorRow.addEventListener('touchend', function(e) {
                if (!startX) return;
                
                const endX = e.changedTouches[0].clientX;
                const diff = startX - endX;
                
                if (Math.abs(diff) > 50) {
                    isScrolling = true;
                    
                    // Scroll in swipe direction
                    colorRow.scrollBy({
                        left: diff > 0 ? 170 : -170,
                        behavior: 'smooth'
                    });
                    
                    // Update position after scroll
                    setTimeout(function() {
                        scrollPosition = colorRow.scrollLeft;
                        isScrolling = false;
                    }, 500);
                }
                
                startX = null;
            }, { passive: true });
        }, 800);
    }
})();
