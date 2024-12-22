class PromotionBanner {
    constructor() {
        this.banner = document.querySelector('.promotion-banner');
        if (!this.banner) return;
        
        this.currentIndex = 0;
        this.promotions = [];
        this.carouselInterval = null;
        this.minimized = false;
        this.initializeControls();
        this.loadPromotions();
    }

    initializeControls() {
        const minimizeBtn = this.banner.querySelector('.minimize-btn');
        const closeBtn = this.banner.querySelector('.close-btn');
        
        // Check if banner was previously closed
        if (sessionStorage.getItem('promotionClosed') === 'true') {
            this.banner.style.display = 'none';
            return;
        }

        minimizeBtn.addEventListener('click', () => {
            this.minimized = !this.minimized;
            this.banner.classList.toggle('minimized', this.minimized);
            
            const icon = minimizeBtn.querySelector('i');
            if (this.minimized) {
                icon.classList.remove('bi-chevron-up');
                icon.classList.add('bi-chevron-down');
                minimizeBtn.title = 'Expand';
            } else {
                icon.classList.remove('bi-chevron-down');
                icon.classList.add('bi-chevron-up');
                minimizeBtn.title = 'Minimize';
            }
        });

        closeBtn.addEventListener('click', () => {
            this.banner.style.display = 'none';
            sessionStorage.setItem('promotionClosed', 'true');
            if (this.carouselInterval) {
                clearInterval(this.carouselInterval);
            }
        });
    }

    async loadPromotions() {
        try {
            const response = await fetch('/crm/ajax/get_active_promotions.php');
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();
            
            // Check for success flag and promotions array in response
            if (data.success && Array.isArray(data.promotions) && data.promotions.length > 0) {
                this.promotions = data.promotions;
                this.showPromotion(0);
                if (this.promotions.length > 1) {
                    this.startCarousel();
                }
            } else {
                console.log('No active promotions found');
                this.banner.style.display = 'none';
            }
        } catch (error) {
            console.error('Error loading promotions:', error);
            this.banner.style.display = 'none';
        }
    }

    showPromotion(index) {
        const promotion = this.promotions[index];
        const content = this.banner.querySelector('.promotion-content');
        
        content.innerHTML = `
            <a href="${promotion.link_url}" class="promotion-text-link">
                <div class="promotion-image-link">
                    <img src="${promotion.image_url}" alt="${promotion.title}" class="promotion-image">
                </div>
                <div class="promotion-text">
                    <h3>${promotion.title}</h3>
                    <p>${promotion.description}</p>
                </div>
            </a>
            <div class="promotion-expanded">
                <a href="${promotion.link_url}" class="promotion-link desktop-only">Shop Now</a>
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
        
        this.initializeControls();
    }

    startCarousel() {
        this.carouselInterval = setInterval(() => {
            this.currentIndex = (this.currentIndex + 1) % this.promotions.length;
            this.showPromotion(this.currentIndex);
        }, 5000); // Change every 5 seconds
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new PromotionBanner();
});
