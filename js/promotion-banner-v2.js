(function() {
    'use strict';
    
    let banner = null;
    let currentIndex = 0;
    let promotions = [];
    let carouselInterval = null;
    let minimized = false;

    function initializeBanner() {
        banner = document.querySelector('.promotion-banner');
        if (!banner) {
            console.log('Promotion banner element not found');
            return;
        }
        
        // Check if banner was previously closed
        if (sessionStorage.getItem('promotionClosed') === 'true') {
            banner.style.display = 'none';
            return;
        }

        loadPromotions();
    }

    function initializeControls() {
        if (!banner) return;
        
        const minimizeBtn = banner.querySelector('.minimize-btn');
        const closeBtn = banner.querySelector('.close-btn');
        
        if (minimizeBtn) {
            minimizeBtn.addEventListener('click', function() {
                minimized = !minimized;
                banner.classList.toggle('minimized', minimized);
                
                const icon = minimizeBtn.querySelector('i');
                if (minimized) {
                    icon.classList.remove('bi-chevron-up');
                    icon.classList.add('bi-chevron-down');
                    minimizeBtn.title = 'Expand';
                } else {
                    icon.classList.remove('bi-chevron-down');
                    icon.classList.add('bi-chevron-up');
                    minimizeBtn.title = 'Minimize';
                }
            });
        }

        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                banner.style.display = 'none';
                sessionStorage.setItem('promotionClosed', 'true');
                if (carouselInterval) {
                    clearInterval(carouselInterval);
                }
            });
        }
    }

    async function loadPromotions() {
        try {
            // Add cache-busting parameter
            const response = await fetch('/crm/ajax/get_active_promotions.php?v=' + Date.now());
            
            if (!response.ok) {
                throw new Error('HTTP error! status: ' + response.status);
            }
            
            const data = await response.json();
            
            console.log('API Response:', data);
            
            // Check for success flag and promotions array in response
            if (data.success && Array.isArray(data.promotions) && data.promotions.length > 0) {
                promotions = data.promotions;
                showPromotion(0);
                if (promotions.length > 1) {
                    startCarousel();
                }
            } else {
                console.log('No active promotions found');
                banner.style.display = 'none';
            }
        } catch (error) {
            console.error('Error loading promotions:', error);
            banner.style.display = 'none';
        }
    }

    function showPromotion(index) {
        const promotion = promotions[index];
        if (!promotion) {
            console.error('No promotion at index:', index);
            return;
        }
        
        const content = banner.querySelector('.promotion-content');
        if (!content) {
            console.error('Promotion content element not found');
            return;
        }
        
        // Use linkUrl or default to promotions page
        const promoLink = promotion.linkUrl || '/promotions.html';
        const imageUrl = promotion.imageUrl || '';
        const isExternal = promotion.type === 'event' && promotion.linkUrl && promotion.linkUrl.startsWith('http');
        const target = isExternal ? '_blank' : '_self';
        
        // Debug logging
        console.log('Displaying promotion:', {
            title: promotion.title,
            imageUrl: imageUrl,
            linkUrl: promoLink,
            type: promotion.type
        });
        
        content.innerHTML = `
            <a href="${promoLink}" target="${target}" class="promotion-text-link">
                <div class="promotion-image-link">
                    <img src="${imageUrl}" alt="${promotion.title}" class="promotion-image" onerror="console.error('Image failed to load:', this.src)">
                </div>
                <div class="promotion-text">
                    <h3>${promotion.title}</h3>
                    <p>${promotion.subtitle || promotion.description || ''}</p>
                </div>
            </a>
            <div class="promotion-expanded">
                <a href="/promotions.html" class="promotion-link desktop-only">View All Promotions</a>
            </div>
            <div class="promotion-controls">
                <button type="button" class="promotion-btn minimize-btn" title="Minimize">
                    <i class="bi bi-chevron-up"></i>
                </button>
                <button type="button" class="promotion-btn close-btn" title="Close">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        `;
        
        initializeControls();
    }

    function startCarousel() {
        carouselInterval = setInterval(function() {
            currentIndex = (currentIndex + 1) % promotions.length;
            showPromotion(currentIndex);
        }, 5000);
    }

    // Initialize when DOM is loaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeBanner);
    } else {
        initializeBanner();
    }
})();
