class CategoryCarousel {
    constructor() {
        this.carousel = document.querySelector('.category-carousel');
        this.items = document.querySelectorAll('.category-item');
        this.prevBtn = document.querySelector('.carousel-prev');
        this.nextBtn = document.querySelector('.carousel-next');
        this.currentPage = 0;
        this.itemsPerPage = 3; // Show 3 items at a time
        this.totalPages = Math.ceil(this.items.length / this.itemsPerPage);

        this.init();
    }

    init() {
        if (!this.carousel || !this.items.length) return;

        this.updateCarousel();
        this.bindEvents();
    }

    updateCarousel() {
        this.items.forEach((item, index) => {
            const startIndex = this.currentPage * this.itemsPerPage;
            const endIndex = startIndex + this.itemsPerPage;
            
            if (index >= startIndex && index < endIndex) {
                item.classList.remove('hidden');
            } else {
                item.classList.add('hidden');
            }
        });
        
        // Update button states
        if (this.prevBtn) {
            this.prevBtn.style.opacity = this.currentPage === 0 ? '0.5' : '1';
        }
        if (this.nextBtn) {
            this.nextBtn.style.opacity = this.currentPage === this.totalPages - 1 ? '0.5' : '1';
        }
    }

    bindEvents() {
        if (this.prevBtn) {
            this.prevBtn.addEventListener('click', () => {
                if (this.currentPage > 0) {
                    this.currentPage--;
                    this.updateCarousel();
                }
            });
        }

        if (this.nextBtn) {
            this.nextBtn.addEventListener('click', () => {
                if (this.currentPage < this.totalPages - 1) {
                    this.currentPage++;
                    this.updateCarousel();
                }
            });
        }
    }
}

// Initialize carousel when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new CategoryCarousel();
}); 