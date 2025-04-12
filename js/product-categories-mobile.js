/**
 * Mobile-Optimized Product Categories
 * Lightweight approach for better mobile performance
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
        const projectsSection = document.getElementById('projects');
        if (!projectsSection) return;
        
        const observer = new IntersectionObserver(entries => {
            if (entries[0].isIntersecting) {
                optimizeProductCategories();
                observer.disconnect();
            }
        }, { 
            rootMargin: '200px 0px', 
            threshold: 0.01 
        });
        
        observer.observe(projectsSection);
    }
    
    function optimizeProductCategories() {
        // Add touch-friendly styling and horizontal scrolling
        const style = document.createElement('style');
        style.textContent = `
            /* Mobile optimized category display */
            @media (max-width: 767px) {
                .project-item-container {
                    display: flex;
                    overflow-x: auto;
                    scroll-snap-type: x mandatory;
                    -webkit-overflow-scrolling: touch;
                    scrollbar-width: none;
                    padding: 10px 0;
                    gap: 15px;
                }
                
                .project-item-container::-webkit-scrollbar {
                    display: none;
                }
                
                .project-item {
                    flex: 0 0 80%;
                    max-width: 280px;
                    scroll-snap-align: center;
                    margin: 0;
                }
                
                .project-img img {
                    aspect-ratio: 3/2;
                    object-fit: cover;
                    width: 100%;
                    height: auto;
                }
                
                /* Add indicators dots */
                .scroll-indicator {
                    display: flex;
                    justify-content: center;
                    margin-top: 15px;
                    gap: 6px;
                }
                
                .scroll-dot {
                    width: 8px;
                    height: 8px;
                    border-radius: 50%;
                    background: rgba(255,255,255,0.3);
                }
                
                .scroll-dot.active {
                    background: white;
                }
                
                /* Add some visual feedback for touch */
                .project-img {
                    position: relative;
                    overflow: hidden;
                    border-radius: 8px;
                }
                
                .project-img::after {
                    content: '';
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: rgba(255,255,255,0.1);
                    opacity: 0;
                    transition: opacity 0.3s ease;
                }
                
                .project-item:active .project-img::after {
                    opacity: 1;
                }
                
                /* Optimize project modals for mobile */
                .project-modal .modal-dialog {
                    margin: 10px;
                    max-width: calc(100% - 20px);
                }
                
                .project-modal .modal-content {
                    height: calc(100vh - 20px);
                    display: flex;
                    flex-direction: column;
                }
                
                .project-modal .modal-body {
                    flex: 1;
                    overflow-y: auto;
                    padding: 15px;
                    -webkit-overflow-scrolling: touch;
                }
                
                /* Better thumbnails layout for mobile */
                .thumbnails-first .thumbnails-container {
                    display: grid;
                    grid-template-columns: repeat(3, 1fr);
                    gap: 8px;
                }
                
                .thumbnails-first .thumbnail-item {
                    display: block;
                    padding-bottom: 100%;
                    position: relative;
                    overflow: hidden;
                    border-radius: 4px;
                }
                
                .thumbnails-first .thumbnail-item img {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    object-fit: cover;
                }
            }
        `;
        document.head.appendChild(style);
        
        // Find all project item containers
        const projectContainers = document.querySelectorAll('.project-row, .row.projects');
        
        projectContainers.forEach(container => {
            // Get items
            const items = container.querySelectorAll('.project-item, .col-md-4');
            if (items.length === 0) return;
            
            // Create indicator dots based on number of items
            const indicatorContainer = document.createElement('div');
            indicatorContainer.className = 'scroll-indicator';
            
            const maxDots = Math.min(items.length, 5);
            for (let i = 0; i < maxDots; i++) {
                const dot = document.createElement('span');
                dot.className = i === 0 ? 'scroll-dot active' : 'scroll-dot';
                indicatorContainer.appendChild(dot);
            }
            
            // Add indicator dots after container
            container.parentNode.insertBefore(indicatorContainer, container.nextSibling);
            
            // Convert container to horizontal scroll
            container.classList.add('project-item-container');
            
            // Add scroll listener to update indicator dots
            let scrollTimeout;
            container.addEventListener('scroll', function() {
                clearTimeout(scrollTimeout);
                scrollTimeout = setTimeout(updateDots, 50);
            }, { passive: true });
            
            // Update indicator dots based on scroll position
            function updateDots() {
                const scrollLeft = container.scrollLeft;
                const itemWidth = items[0].offsetWidth;
                const containerWidth = container.offsetWidth;
                
                // Calculate which dot should be active
                const totalScrollWidth = container.scrollWidth - containerWidth;
                const scrollRatio = scrollLeft / totalScrollWidth;
                const activeDotIndex = Math.min(
                    Math.floor(scrollRatio * maxDots),
                    maxDots - 1
                );
                
                // Update dots
                const dots = indicatorContainer.querySelectorAll('.scroll-dot');
                dots.forEach((dot, i) => {
                    dot.className = i === activeDotIndex ? 'scroll-dot active' : 'scroll-dot';
                });
            }
            
            // Auto-scroll functionality
            let currentIndex = 0;
            let autoScrollInterval;
            let isPaused = false;
            
            // Add scroll-behavior for smooth scrolling
            container.style.scrollBehavior = 'smooth';
            
            // Start auto-scrolling
            function startAutoScroll() {
                if (autoScrollInterval) clearInterval(autoScrollInterval);
                
                autoScrollInterval = setInterval(() => {
                    if (!isPaused) {
                        currentIndex = (currentIndex + 1) % items.length;
                        const scrollTo = items[currentIndex].offsetLeft - container.offsetLeft;
                        container.scrollTo({
                            left: scrollTo,
                            behavior: 'smooth'
                        });
                    }
                }, 4000); // Change slide every 4 seconds
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
        });
        
        // Fix modal scroll locks for all project modals
        document.addEventListener('click', function(e) {
            // Find all modal triggers
            if (e.target.closest('[data-toggle="modal"]')) {
                document.body.style.overflow = 'hidden';
            }
            
            // Find all modal close buttons
            if (e.target.closest('[data-dismiss="modal"]') || 
                (e.target.classList && e.target.classList.contains('modal'))) {
                document.body.style.overflow = '';
            }
        });
        
        // Enhance all modals to follow thumbnails-first approach
        const projectModals = document.querySelectorAll('.project-modal, .modal');
        projectModals.forEach(modal => {
            modal.classList.add('thumbnails-first');
        });
    }
})();
