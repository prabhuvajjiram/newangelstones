document.addEventListener('DOMContentLoaded', function() {
    // Add modal styles
    const modalStyles = document.createElement('style');
    modalStyles.textContent = `
        .featured-products {
            padding: 2rem 0;
            background: #101010;
            min-height: 400px;
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .product-card {
            background: #333;
            border-radius: 8px;
            overflow: hidden;
            height: 100%;
            position: relative;
        }
        
        .product-image {
            position: relative;
            width: 100%;
            height: 300px;
            background: #2a2a2a;
            overflow: hidden;
        }
        
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .product-card:hover .product-image img {
            transform: scale(1.05);
        }
        
        .product-details {
            padding: 1.5rem;
        }
        
        .product-title {
            margin: 0 0 0.5rem;
            color: #fff;
            font-size: 1.2rem;
        }
        
        .product-description {
            color: #ccc;
            margin: 0;
            font-size: 0.9rem;
        }
        
        .category-carousel {
            display: flex;
            overflow-x: auto;
            scroll-behavior: smooth;
            -ms-overflow-style: none;  /* IE and Edge */
            scrollbar-width: none;  /* Firefox */
            padding: 1.5rem 0;
            gap: 1.5rem;
        }
        
        .category-carousel::-webkit-scrollbar {
            display: none;
        }
        
        .category-item {
            flex: 0 0 300px;
            scroll-snap-align: center;
        }
        
        .carousel-container {
            position: relative;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 40px;
        }
        
        .carousel-wrapper {
            display: flex;
            gap: 20px;
            overflow-x: auto;
            -ms-overflow-style: none;  /* IE and Edge */
            scrollbar-width: none;  /* Firefox */
            scroll-behavior: smooth;
            padding: 20px 0;
        }
        
        .carousel-wrapper::-webkit-scrollbar {
            display: none;
        }
        
        .carousel-item {
            flex: 0 0 260px;
            transition: transform 0.3s ease;
        }
        
        .carousel-item:hover {
            transform: translateY(-10px);
        }
        
        .category-link {
            display: block;
            text-decoration: none;
            background: #222;
            border-radius: 8px;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .category-link:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }
        
        .category-image {
            height: 200px;
            overflow: hidden;
        }
        
        .category-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .category-link:hover .category-image img {
            transform: scale(1.1);
        }
        
        .carousel-item h4 {
            color: #fff;
            padding: 15px 15px 5px;
            margin: 0;
            font-size: 1.2rem;
        }
        
        .category-count {
            display: block;
            padding: 0 15px 15px;
            color: #d6b772;
            font-size: 0.9rem;
        }
        
        .nav-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 40px;
            height: 40px;
            background: rgba(0, 0, 0, 0.5);
            border: none;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #fff;
            font-size: 1.2rem;
            cursor: pointer;
            z-index: 5;
            transition: all 0.3s ease;
        }
        
        .nav-btn:hover {
            background: rgba(214, 183, 114, 0.7);
        }
        
        .prev-btn {
            left: 10px;
        }
        
        .next-btn {
            right: 10px;
        }
        
        @media (max-width: 768px) {
            .carousel-item {
                flex: 0 0 200px;
            }
            
            .category-image {
                height: 150px;
            }
            
            .carousel-item h4 {
                font-size: 1rem;
            }
        }
        
        /* Search results grid */
        .search-results-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            width: 100%;
            box-sizing: border-box;
        }
        
        @media (max-width: 768px) {
            .search-results-grid {
                grid-template-columns: repeat(1, 1fr);
                gap: 20px;
                display: flex;
                flex-direction: column;
            }
            
            .search-results-grid .thumbnail {
                max-width: 100%;
                margin: 0 auto 20px;
                width: 100%;
                border: 1px solid rgba(255, 255, 255, 0.1);
                flex: 0 0 auto;
                position: relative;
            }
            
            .search-results-grid .thumbnail img {
                width: 100%;
                max-height: 450px;
                height: auto;
                object-fit: contain;
                display: block;
            }
        }
        
        @media (max-width: 576px) {
            .search-results-grid {
                grid-template-columns: repeat(1, 1fr);
                gap: 10px;
            }
            
            .search-results-grid .thumbnail {
                max-width: 100%;
                margin: 0 auto;
                width: 100%;
            }
        }
        
        @media (max-width: 375px) {
            .search-results-grid {
                grid-template-columns: repeat(1, 1fr);
                gap: 8px;
            }
        }
        
        /* Responsive thumbnail adjustments */
        @media (max-width: 768px) {
            .thumbnails-container {
                grid-template-columns: repeat(1, 1fr);
                gap: 20px;
                padding: 12px;
                display: flex;
                flex-direction: column;
                height: auto;
            }
            
            .thumbnail {
                max-width: 100%;
                margin: 0 auto 20px;
                width: 100%;
                border: 1px solid rgba(255, 255, 255, 0.1);
                flex: 0 0 auto;
                position: relative;
            }
            
            .thumbnail img {
                width: 100%;
                max-height: 450px;
                height: auto;
                object-fit: contain;
                display: block;
            }
        }
        
        @media (max-width: 576px) {
            .thumbnails-container {
                grid-template-columns: repeat(1, 1fr);
                gap: 8px;
                padding: 10px;
            }
            
            .thumbnail {
                max-width: 100%;
                margin: 0 auto;
                width: 100%;
            }
        }
        
        @media (max-width: 375px) {
            .thumbnails-container {
                grid-template-columns: repeat(1, 1fr);
                gap: 6px;
                padding: 8px;
            }
        }
        
        /* Fullscreen styles */
        .fullscreen-view {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.95);
            z-index: 3000;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .fullscreen-image-container {
            position: relative;
            width: 90%;
            height: 90%;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .fullscreen-image {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        
        .fullscreen-close {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(0, 0, 0, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            font-size: 24px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            transition: all 0.2s ease;
            z-index: 10007;
        }
        
        .fullscreen-close:hover {
            background: #000;
            border-color: rgba(255, 255, 255, 0.4);
            transform: scale(1.1);
        }
        
        .fullscreen-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0, 0, 0, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #fff;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            font-size: 24px;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            z-index: 10007;
            transition: all 0.2s ease;
        }
        
        .fullscreen-nav:hover {
            background: #000;
            border-color: rgba(255, 255, 255, 0.4);
            transform: scale(1.1) translateY(-50%);
        }
        
        .fullscreen-prev {
            left: 20px;
        }
        
        .fullscreen-next {
            right: 20px;
        }
        
        /* Loading and error styles */
        .loading-thumbnail {
            position: relative;
            min-height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f5f5f5;
            border-radius: 8px;
        }
        
        .loading-spinner {
            border: 4px solid rgba(0, 0, 0, 0.1);
            border-left-color: #888;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            100% { transform: rotate(360deg); }
        }
        
        .image-error-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 15px;
            background: #f8f8f8;
            min-height: 200px;
            border-radius: 8px;
            border: 1px dashed #ccc;
        }
        
        .image-error-icon {
            font-size: 32px;
            color: #888;
            margin-bottom: 10px;
        }
        
        .image-error-text {
            text-align: center;
            color: #555;
            font-size: 14px;
        }
        
        /* Mobile-specific styles */
        @media (max-width: 768px) {
            .modal-content {
                width: 100%;
                height: 100%;
                margin: 0;
                border-radius: 0;
            }
            
            .main-carousel-container {
                height: 60vh;
            }
            
            .thumbnails-scroll-container {
                height: 30vh;
            }
            
            .carousel-image {
                width: 100%;
                height: 100%;
                object-fit: contain;
            }
            
            .thumbnail {
                width: 120px;
                height: 120px;
                flex: 0 0 120px;
            }
            
            .thumbnail img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }
            
            .nav-button {
                width: 40px;
                height: 40px;
                font-size: 20px;
            }
            
            .modal-header {
                padding: 10px;
            }
            
            .modal-header h2 {
                font-size: 1.2rem;
            }
        }
        
        /* Small mobile devices */
        @media (max-width: 480px) {
            .main-carousel-container {
                height: 50vh;
            }
            
            .thumbnails-scroll-container {
                height: 40vh;
            }
            
            .thumbnail {
                width: 100px;
                height: 100px;
                flex: 0 0 100px;
            }
        }
    `;
    document.head.appendChild(modalStyles);

    // Category Carousel
    const categoryCarousel = {
        container: null,
        wrapper: null,
        items: [],
        prevBtn: null,
        nextBtn: null,
        scrollAmount: 0,
        itemWidth: 0,
        visibleWidth: 0,

        init() {
            this.container = document.querySelector('.carousel-container');
            if (!this.container) return;

            this.wrapper = this.container.querySelector('.carousel-wrapper');
            this.items = Array.from(this.wrapper.querySelectorAll('.carousel-item'));
            this.prevBtn = this.container.querySelector('.prev-btn');
            this.nextBtn = this.container.querySelector('.next-btn');

            if (!this.container || !this.wrapper) {
                console.error('Carousel elements not found');
                return;
            }

            // Calculate dimensions
            this.updateDimensions();

            // Set up event listeners
            this.prevBtn.addEventListener('click', () => this.scroll('left'));
            this.nextBtn.addEventListener('click', () => this.scroll('right'));

            // Update on window resize
            window.addEventListener('resize', () => this.updateDimensions());
        },

        updateDimensions() {
            if (this.items.length === 0) return;
            
            this.itemWidth = this.items[0].offsetWidth + parseInt(window.getComputedStyle(this.items[0]).marginRight);
            this.visibleWidth = this.wrapper.offsetWidth;
            this.scrollAmount = this.itemWidth * 3; // Scroll by 3 items at a time
        },

        scroll(direction) {
            const currentScroll = this.wrapper.scrollLeft;
            const newScroll = direction === 'left' 
                ? Math.max(0, currentScroll - this.scrollAmount)
                : currentScroll + this.scrollAmount;
            
            this.wrapper.scrollTo({
                left: newScroll,
                behavior: 'smooth'
            });
        }
    };

    // Product Collection Modal
    const productModal = {
        modal: null,
        modalTitle: null,
        modalBody: null,
        carousel: null,
        directory: null,
        images: [],
        currentIndex: 0,
        fullscreenViewer: null,
        
        init(categoryName, images = [], directory = '') {
            this.categoryName = categoryName;
            this.images = images;
            
            // Handle special case for MBNA 2025 - use exact directory name from server
            let categoryForPath = categoryName;
            if (categoryName.toLowerCase() === 'mbna 2025') {
                // Use exact directory name that exists on server
                categoryForPath = 'MBNA_2025';
                console.log('FeaturedProducts: Using exact server directory "MBNA_2025" for MBNA 2025 category');
            }
            // For other acronym categories, preserve capitalization
            else if (/^mbna|^ibm|^hp|^ge/i.test(categoryName)) {
                categoryForPath = categoryName.toUpperCase();
                console.log('FeaturedProducts: Using uppercase for acronym category:', categoryForPath);
            } else {
                categoryForPath = categoryName.charAt(0).toUpperCase() + categoryName.slice(1);
            }
            
            // Use server-provided name if available
            if (window.serverCategoryNames && window.serverCategoryNames[categoryName]) {
                categoryForPath = window.serverCategoryNames[categoryName];
                console.log('FeaturedProducts: Using server-provided category name:', categoryForPath);
            }
            
            this.directory = directory || `images/products/${categoryForPath}`;
            console.log('FeaturedProducts: Using directory:', this.directory);
            
            // Only log error if actually trying to initialize with a category
            if (categoryName !== '' && !categoryName) {
                console.error('Invalid category name');
                return;
            }
            
            this.createModal();
            this.initEvents();
        },

        createModal() {
            // Completely replace the modal DOM structure
            this.modal = document.createElement('div');
            this.modal.className = 'collection-modal';
            this.modal.innerHTML = `
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 id="featured-products-modal-title">Product Collection</h2>
                        <button class="close-modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="main-carousel-container">
                            <button class="nav-button prev"><i class="bi bi-chevron-left"></i></button>
                            <button class="nav-button next"><i class="bi bi-chevron-right"></i></button>
                            <div class="main-carousel-slides"></div>
                        </div>
                        <div class="thumbnails-scroll-container">
                            <div class="thumbnails-container"></div>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(this.modal);

            // Add modal styles
            const modalStyles = document.createElement('style');
            modalStyles.textContent = `
                .collection-modal {
                    display: none;
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background-color: rgba(0, 0, 0, 0.9);
                    z-index: 1000;
                    overflow: auto;
                }
                
                .modal-content {
                    position: relative;
                    width: 90%;
                    max-width: 1200px;
                    margin: 30px auto;
                    background-color: #222;
                    border-radius: 8px;
                    box-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
                    overflow: hidden;
                    display: flex;
                    flex-direction: column;
                    height: 90vh;
                }
                
                .modal-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 15px 20px;
                    background-color: #333;
                    border-bottom: 1px solid #444;
                }
                
                .modal-header h2 {
                    color: #fff;
                    margin: 0;
                    font-size: 1.5rem;
                }
                
                .close-modal {
                    background: none;
                    border: none;
                    color: #fff;
                    font-size: 1.8rem;
                    cursor: pointer;
                    line-height: 1;
                }
                
                .modal-body {
                    padding: 20px;
                    flex-grow: 1;
                    display: flex;
                    flex-direction: column;
                    height: calc(100% - 60px);
                    overflow: hidden;
                }
                
                /* Main carousel styles */
                .main-carousel-container {
                    position: relative;
                    width: 100%;
                    height: 65%;
                    background-color: #000;
                    margin-bottom: 20px;
                    border-radius: 4px;
                    overflow: hidden;
                }
                
                .main-carousel-slide {
                    display: none;
                    width: 100%;
                    height: 100%;
                    justify-content: center;
                    align-items: center;
                }
                
                .main-carousel-slide.active {
                    display: flex;
                }
                
                .main-carousel-slide img {
                    max-width: 100%;
                    max-height: 100%;
                    object-fit: contain;
                    cursor: pointer;
                }
                
                .image-error-container {
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    display: flex;
                    flex-direction: column;
                    justify-content: center;
                    align-items: center;
                    background-color: rgba(0, 0, 0, 0.7);
                    color: #fff;
                    padding: 20px;
                    text-align: center;
                }
                
                .image-error-icon {
                    font-size: 48px;
                    margin-bottom: 10px;
                }
                
                .image-error-text {
                    font-size: 16px;
                }
                
                /* Navigation buttons */
                .nav-button {
                    position: absolute;
                    top: 50%;
                    transform: translateY(-50%);
                    width: 50px;
                    height: 50px;
                    background: rgba(0, 0, 0, 0.8);
                    border: 1px solid rgba(255, 255, 255, 0.2);
                    border-radius: 50%;
                    color: white;
                    font-size: 24px;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    cursor: pointer;
                    z-index: 10;
                    transition: all 0.3s;
                }
                
                .nav-button:hover {
                    background: #000;
                    border-color: rgba(255, 255, 255, 0.4);
                    transform: scale(1.1) translateY(-50%);
                }
                
                .nav-button.prev {
                    left: 10px;
                }
                
                .nav-button.next {
                    right: 10px;
                }
                
                /* Thumbnails styles */
                .thumbnails-scroll-container {
                    width: 100%;
                    height: 35%;
                    overflow-x: hidden;
                    overflow-y: hidden;
                    position: relative;
                    border-radius: 4px;
                    background-color: #1a1a1a;
                }
                
                .thumbnails-container {
                    display: flex;
                    flex-wrap: nowrap;
                    gap: 10px;
                    padding: 10px;
                    overflow-x: auto;
                    overflow-y: hidden;
                    height: 100%;
                    scrollbar-width: thin;
                    scrollbar-color: #555 #333;
                }
                
                .thumbnails-container::-webkit-scrollbar {
                    height: 8px;
                }
                
                .thumbnails-container::-webkit-scrollbar-track {
                    background: #333;
                    border-radius: 4px;
                }
                
                .thumbnails-container::-webkit-scrollbar-thumb {
                    background-color: #555;
                    border-radius: 4px;
                }
                
                .thumbnail {
                    flex: 0 0 auto;
                    position: relative;
                    border-radius: 4px;
                    overflow: hidden;
                    cursor: pointer;
                    width: 150px;
                    height: 150px;
                    background-color: #1a1a1a;
                    transition: transform 0.2s;
                    border: 2px solid transparent;
                }
                
                .thumbnail:hover {
                    transform: scale(1.05);
                }
                
                .thumbnail.active {
                    border-color: #ffeb3b;
                }
                
                .thumbnail img {
                    width: 100%;
                    height: 100%;
                    object-fit: cover;
                }
                
                .thumbnail-label {
                    position: absolute;
                    bottom: 0;
                    left: 0;
                    right: 0;
                    background-color: rgba(0, 0, 0, 0.7);
                    color: #fff;
                    padding: 5px;
                    font-size: 12px;
                    text-align: center;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    white-space: nowrap;
                }
                
                /* Mobile styles */
                @media (max-width: 768px) {
                    .modal-content {
                        width: 95%;
                        margin: 15px auto;
                    }
                    
                    .main-carousel-container {
                        height: 60%;
                    }
                    
                    .thumbnails-scroll-container {
                        height: 40%;
                    }
                    
                    .thumbnail {
                        width: 120px;
                        height: 120px;
                    }
                }
                
                @media (max-width: 576px) {
                    .modal-content {
                        margin: 10px auto;
                    }
                    
                    .main-carousel-container {
                        height: 55%;
                    }
                    
                    .thumbnails-scroll-container {
                        height: 45%;
                    }
                    
                    .thumbnail {
                        width: 100px;
                        height: 100px;
                    }
                }
                
                /* Fullscreen view styles */
                .fullscreen-view {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background-color: rgba(0, 0, 0, 0.95);
                    z-index: 3000;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                }
                
                .fullscreen-image-container {
                    position: relative;
                    width: 90%;
                    height: 90%;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                }
                
                .fullscreen-image {
                    max-width: 100%;
                    max-height: 100%;
                    object-fit: contain;
                }
                
                .fullscreen-close {
                    position: absolute;
                    top: 20px;
                    right: 20px;
                    background: rgba(0, 0, 0, 0.8);
                    border: 1px solid rgba(255, 255, 255, 0.2);
                    color: white;
                    font-size: 24px;
                    width: 40px;
                    height: 40px;
                    border-radius: 50%;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    cursor: pointer;
                    transition: all 0.2s ease;
                    z-index: 10007;
                }
                
                .fullscreen-close:hover {
                    background: #000;
                    border-color: rgba(255, 255, 255, 0.4);
                    transform: scale(1.1);
                }
                
                /* Search results container */
                .search-results-container {
                    display: none;
                    width: 100%;
                    height: 100%;
                    overflow-y: auto;
                    background-color: #1a1a1a;
                    border-radius: 4px;
                    padding: 15px;
                }
                
                .search-results-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 15px;
                    color: #fff;
                }
                
                .search-results-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                    gap: 15px;
                }
                
                @media (max-width: 768px) {
                    .search-results-grid {
                        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
                    }
                }
                
                @media (max-width: 576px) {
                    .search-results-grid {
                        grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
                    }
                }
            `;
            document.head.appendChild(modalStyles);

            // Cache elements
            this.closeBtn = this.modal.querySelector('.close-modal');
            this.carouselContainer = this.modal.querySelector('.main-carousel-slides');
            this.mainCarouselContainer = this.modal.querySelector('.main-carousel-container');
            this.thumbnailsContainer = this.modal.querySelector('.thumbnails-container');
            this.prevButton = this.modal.querySelector('.nav-button.prev');
            this.nextButton = this.modal.querySelector('.nav-button.next');
        },
        
        initEvents() {
            // Close modal button
            const closeBtn = this.modal.querySelector('.close-modal');
            closeBtn.addEventListener('click', () => {
                this.closeModal();
            });

            // Close modal when clicking outside of content
            this.modal.addEventListener('click', (e) => {
                if (e.target === this.modal) {
                    this.closeModal();
                }
            });

            // Next button
            const nextBtn = this.modal.querySelector('.nav-button.next');
            nextBtn.addEventListener('click', () => {
                this.showNextSlide();
            });

            // Previous button
            const prevBtn = this.modal.querySelector('.nav-button.prev');
            prevBtn.addEventListener('click', () => {
                this.showPrevSlide();
            });

            // Keyboard navigation
            document.addEventListener('keydown', (e) => {
                if (this.modal.style.display === 'block') {
                    if (e.key === 'Escape') {
                        this.closeModal();
                    } else if (e.key === 'ArrowLeft') {
                        this.showPrevSlide();
                    } else if (e.key === 'ArrowRight') {
                        this.showNextSlide();
                    }
                }
            });

            // Fullscreen view for carousel images
            this.carouselContainer.addEventListener('click', (e) => {
                // Make sure we're actually clicking an image, not the container or error message
                if (e.target.tagName === 'IMG' && e.target.classList.contains('carousel-image')) {
                    const imgSrc = e.target.src;
                    // Prevent event bubbling
                    e.stopPropagation();
                    // Open fullscreen view with explicit high z-index
                    this.openFullscreenView(imgSrc);
                }
            });
            
            // Enable horizontal scrolling with mouse wheel for thumbnails
            this.thumbnailsContainer.addEventListener('wheel', (e) => {
                if (e.deltaY !== 0) {
                    e.preventDefault();
                    this.thumbnailsContainer.scrollLeft += e.deltaY;
                }
            });
        },

        constructImagePath(imageName) {
            if (!imageName) {
                console.error('FeaturedProducts: Empty image name provided to constructImagePath');
                return null;
            }
            
            // If the path is already a full path, return it
            if (typeof imageName === 'object' && imageName.path) {
                console.log('FeaturedProducts: Using provided full path:', imageName.path);
                return imageName.path;
            }
            
            // If imageName is an object with name property
            const filename = typeof imageName === 'object' ? imageName.name : imageName;
            
            // Handle special case for MBNA 2025
            let categoryPath = this.categoryName;
            if (this.categoryName.toLowerCase() === 'mbna 2025') {
                categoryPath = 'MBNA_2025';
            } else if (/^mbna|^ibm|^hp|^ge/i.test(this.categoryName)) {
                categoryPath = this.categoryName.toUpperCase();
            }
            
            // Construct the path
            const path = `images/products/${categoryPath}/${filename}`;
            console.log('FeaturedProducts: Constructed path:', path);
            return path;
        },
        
        loadImages() {
            console.log('FeaturedProducts: Loading images:', this.images.length);
            
            if (!this.images || this.images.length === 0) {
                console.error('FeaturedProducts: No images to load');
                this.thumbnailsContainer.innerHTML = '<div class="no-images">No images found in this category.</div>';
                return;
            }

            // Create a collection of image objects for easier manipulation
            const imageObjects = this.images.map(item => {
                // If item is an object with path property, use it directly
                if (typeof item === 'object' && item.path) {
                    return {
                        name: item.name,
                        path: item.path,
                        fullname: item.fullname
                    };
                }
                
                // Otherwise construct the path
                const name = typeof item === 'string' ? item : item.name;
                return {
                    name: name,
                    path: this.constructImagePath(item),
                    fullname: name
                };
            });

            // Store all images for reference
            this.allImages = imageObjects;

            // Create carousel items
            this.carouselContainer.innerHTML = '';
            imageObjects.forEach((imageObj, index) => {
                const slide = document.createElement('div');
                slide.className = `main-carousel-slide ${index === 0 ? 'active' : ''}`;
                
                const img = document.createElement('img');
                img.alt = imageObj.name;
                img.className = 'carousel-image';
                
                // Set up load and error handlers
                img.onload = () => {
                    console.log(`Image loaded successfully: ${img.src}`);
                };
                
                img.onerror = () => {
                    console.error(`Failed to load image: ${img.src}`);
                    // Try without /products/ in path
                    const simplifiedPath = img.src.replace('/products/', '/');
                    if (img.src !== simplifiedPath) {
                        console.log(`Trying simplified path: ${simplifiedPath}`);
                        img.src = simplifiedPath;
                    } else {
                        img.src = 'images/default-thumbnail.jpg';
                    }
                };
                
                // Set image source
                img.src = imageObj.path;
                
                slide.appendChild(img);
                this.carouselContainer.appendChild(slide);
            });
            
            // Create thumbnails
            this.thumbnailsContainer.innerHTML = '';
            imageObjects.forEach((imageObj, index) => {
                const thumbContainer = document.createElement('div');
                thumbContainer.className = `thumbnail ${index === 0 ? 'active' : ''}`;
                thumbContainer.dataset.index = index;
                
                const thumb = document.createElement('img');
                thumb.alt = imageObj.name;
                
                // Set up load and error handlers for thumbnail
                thumb.onload = () => {
                    console.log(`Thumbnail loaded successfully: ${thumb.src}`);
                };
                
                thumb.onerror = () => {
                    console.error(`Failed to load thumbnail: ${thumb.src}`);
                    // Try without /products/ in path
                    const simplifiedPath = thumb.src.replace('/products/', '/');
                    if (thumb.src !== simplifiedPath) {
                        console.log(`Trying simplified path: ${simplifiedPath}`);
                        thumb.src = simplifiedPath;
                    } else {
                        thumb.src = 'images/default-thumbnail.jpg';
                    }
                };
                
                // Set thumbnail source
                thumb.src = imageObj.path;
                
                thumbContainer.appendChild(thumb);
                
                // Add name label
                const label = document.createElement('div');
                label.className = 'thumbnail-label';
                label.textContent = imageObj.name.replace(/\.[^/.]+$/, '');
                thumbContainer.appendChild(label);
                
                // Add click event handler
                thumbContainer.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    console.log(`FeaturedProducts: Showing slide ${index} from thumbnail click`);
                    
                    const allThumbs = this.thumbnailsContainer.querySelectorAll('.thumbnail');
                    allThumbs.forEach(t => t.classList.remove('active'));
                    thumbContainer.classList.add('active');
                    
                    thumbContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
                    this.showSlide(index);
                });
                
                this.thumbnailsContainer.appendChild(thumbContainer);
            });
            
            // Update modal title with image count
            const modalTitle = this.modal.querySelector('.modal-header h2');
            modalTitle.textContent = `${this.categoryName.replace(/_/g, ' ')} Collection (${this.images.length} Designs)`;
        },
        
        fetchImages(category) {
            const apiUrl = `get_directory_files.php?directory=${encodeURIComponent(this.directory)}`;
            console.log('Fetching:', apiUrl);
            
            return fetch(apiUrl)
                .then(response => response.text())
                .then(text => {
                    console.log('Raw API response:', text);
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Failed to parse API response:', e);
                        throw new Error('Invalid API response');
                    }
                })
                .then(data => {
                    console.log('Parsed data:', data);
                    if (data.success && data.files && data.files.length > 0) {
                        console.log('Featured Products API Response:', data);
                        this.images = data.files;
                        this.loadImages();
                    } else {
                        console.error('Error loading images:', data.error || 'No images found');
                        throw new Error('Error loading images');
                    }
                })
                .catch(error => {
                    console.error('Failed to fetch images:', error);
                    this.carouselContainer.innerHTML = '<div class="error-message">Failed to load images. Please try again later.</div>';
                    this.thumbnailsContainer.innerHTML = '';
                });
        },
        
        setupEventListeners() {
            // Find all category links
            const categoryLinks = document.querySelectorAll('.category-link');
            
            // Add event listeners to category links
            categoryLinks.forEach(link => {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    // Get category from data-category attribute OR from href attribute
                    let category = link.getAttribute('data-category');
                    
                    // If no data-category, try to extract from href
                    if (!category && link.href) {
                        const href = link.getAttribute('href');
                        if (href && href.startsWith('#')) {
                            // Extract category name from href (e.g., #mbna_2025-collection -> mbna_2025)
                            category = href.substring(1).replace('-collection', '');
                            console.log('Extracted category from href:', category);
                        }
                    }
                    
                    if (category) {
                        this.openCategory(category);
                    }
                });
            });
            
            // Dynamic binding for category links that might be added later
            document.addEventListener('click', (e) => {
                // Check if clicked element or its parent is a category link
                const categoryLink = e.target.closest('.category-link');
                if (categoryLink) {
                    e.preventDefault();
                    // Get category from data-category attribute OR from href attribute
                    let category = categoryLink.getAttribute('data-category');
                    
                    // If no data-category, try to extract from href
                    if (!category && categoryLink.href) {
                        const href = categoryLink.getAttribute('href');
                        if (href && href.startsWith('#')) {
                            // Extract category name from href (e.g., #mbna_2025-collection -> mbna_2025)
                            category = href.substring(1).replace('-collection', '');
                            console.log('Extracted category from href:', category);
                        }
                    }
                    
                    if (category) {
                        this.openCategory(category);
                    }
                }
            });
            
            // Initialize click handlers for navigation buttons
            document.addEventListener('click', (e) => {
                if (e.target.matches('.modal-prev')) {
                    this.showPrevSlide();
                } else if (e.target.matches('.modal-next')) {
                    this.showNextSlide();
                } else if (e.target.matches('.modal-fullscreen')) {
                    const index = parseInt(e.target.getAttribute('data-index'));
                    this.openFullscreen(index);
                } else if (e.target.matches('.modal-close') || e.target.matches('.modal-backdrop')) {
                    this.closeModal();
                }
            });
            
            // Handle keyboard navigation
            document.addEventListener('keydown', (e) => {
                if (this.modal && this.modal.classList.contains('show')) {
                    if (e.key === 'ArrowLeft') {
                        this.showPrevSlide();
                    } else if (e.key === 'ArrowRight') {
                        this.showNextSlide();
                    } else if (e.key === 'Escape') {
                        this.closeModal();
                    }
                }
            });
        },

        openCategory(category) {
            console.log(`FeaturedProducts: Opening category ${category}`);
            
            // Make sure category exists
            if (!category) {
                console.error('FeaturedProducts: No category provided to openCategory');
                return;
            }
            
            // Handle special case for MBNA 2025 directory
            let categoryForPath = category;
            if (category.toLowerCase() === 'mbna_2025' || category.toLowerCase() === 'mbna 2025') {
                // Use exact directory name that exists on server
                categoryForPath = 'MBNA_2025';
                console.log('FeaturedProducts: Using exact directory name "MBNA_2025" for MBNA 2025 category');
            }
            // For MBNA and similar acronym categories, preserve capitalization
            else if (/^mbna|^ibm|^hp|^ge/i.test(category)) {
                categoryForPath = category.toUpperCase();
                console.log('FeaturedProducts: Using uppercase for acronym category:', categoryForPath);
            } else {
                categoryForPath = category.charAt(0).toUpperCase() + category.slice(1);
            }
            
            // Use server-provided name if available
            if (window.serverCategoryNames && window.serverCategoryNames[category]) {
                categoryForPath = window.serverCategoryNames[category];
                console.log('FeaturedProducts: Using server-provided category name:', categoryForPath);
            }
            
            // Update properties
            this.categoryName = category;
            this.directory = `images/products/${categoryForPath}`;
            
            // Reset search state if active
            if (this.searchResultsContainer) {
                this.searchResultsContainer.style.display = 'none';
            }
            
            // Display the modal
            this.modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
            
            // Set modal title
            const modalTitle = document.getElementById('featured-products-modal-title');
            if (modalTitle) {
                modalTitle.textContent = `${this.categoryName.replace(/_/g, ' ')} Collection`;
            }
            
            // Clear current images
            this.images = [];
            this.allImages = [];
            this.currentImageIndex = 0;
            
            // Show loading indicators
            this.carouselContainer.innerHTML = '<div class="loading-spinner"><div class="spinner"></div><div>Loading images...</div></div>';
            this.thumbnailsContainer.innerHTML = '<div class="loading-spinner"><div class="spinner"></div></div>';
            
            // Fetch images from the server
            this.fetchImages(category)
                .then(data => {
                    this.images = data;
                    this.loadImages();
                })
                .catch(error => {
                    console.error('FeaturedProducts: Error fetching images:', error);
                    this.carouselContainer.innerHTML = '<div class="error-message">Error loading images. Please try again later.</div>';
                    this.thumbnailsContainer.innerHTML = '<div class="error-message">Error loading images. Please try again later.</div>';
                });
        },
        
        showSlide(index) {
            if (!this.carouselContainer) {
                console.error('FeaturedProducts: Carousel container not found');
                return;
            }
            
            const slides = this.carouselContainer.querySelectorAll('.main-carousel-slide');
            if (slides.length === 0) {
                console.error('FeaturedProducts: No slides found');
                return;
            }
            
            // Validate index
            if (index < 0) {
                index = slides.length - 1;
            } else if (index >= slides.length) {
                index = 0;
            }
            
            // Update current index
            this.currentImageIndex = index;
            
            // Hide all slides
            slides.forEach(slide => {
                slide.classList.remove('active');
            });
            
            // Show the current slide
            slides[index].classList.add('active');
            
            // Update active thumbnail
            const thumbnails = this.thumbnailsContainer.querySelectorAll('.thumbnail');
            thumbnails.forEach((thumb, idx) => {
                if (idx === index) {
                    thumb.classList.add('active');
                    // Scroll the thumbnail into view if it's not visible
                    thumb.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
                } else {
                    thumb.classList.remove('active');
                }
            });
            
            console.log(`FeaturedProducts: Showing slide ${index}`);
        },
        
        showPrevSlide() {
            const newIndex = (this.currentImageIndex - 1 + this.images.length) % this.images.length;
            this.showSlide(newIndex);
        },

        showNextSlide() {
            const newIndex = (this.currentImageIndex + 1) % this.images.length;
            this.showSlide(newIndex);
        },

        openFullscreen(index) {
            const fullscreenView = document.createElement('div');
            fullscreenView.className = 'fullscreen-view';
            
            const imageContainer = document.createElement('div');
            imageContainer.className = 'fullscreen-image-container';
            
            const fullscreenImg = document.createElement('img');
            fullscreenImg.className = 'fullscreen-image';
            fullscreenImg.src = `${this.directory}/${this.images[index]}`;
            fullscreenImg.alt = `Full size ${this.images[index].replace('.png', '')}`;
            
            const closeButton = document.createElement('button');
            closeButton.className = 'fullscreen-close';
            closeButton.innerHTML = '&times;';
            
            const prevButton = document.createElement('button');
            prevButton.className = 'nav-button fullscreen-nav fullscreen-prev';
            prevButton.innerHTML = '<i class="bi bi-chevron-left"></i>';
            
            const nextButton = document.createElement('button');
            nextButton.className = 'nav-button fullscreen-nav fullscreen-next';
            nextButton.innerHTML = '<i class="bi bi-chevron-right"></i>';
            
            imageContainer.appendChild(fullscreenImg);
            imageContainer.appendChild(closeButton);
            imageContainer.appendChild(prevButton);
            imageContainer.appendChild(nextButton);
            
            fullscreenView.appendChild(imageContainer);
            document.body.appendChild(fullscreenView);
            
            let fullscreenIndex = index;
            
            const updateFullscreenImage = () => {
                fullscreenImg.src = `${this.directory}/${this.images[fullscreenIndex]}`;
                fullscreenImg.alt = `Full size ${this.images[fullscreenIndex].replace('.png', '')}`;
                
                // Update button states
                prevButton.disabled = fullscreenIndex === 0;
                nextButton.disabled = fullscreenIndex === this.images.length - 1;
            };
            
            closeButton.addEventListener('click', () => {
                fullscreenView.remove();
            });
            
            prevButton.addEventListener('click', () => {
                if (fullscreenIndex > 0) {
                    fullscreenIndex--;
                    updateFullscreenImage();
                }
            });
            
            nextButton.addEventListener('click', () => {
                if (fullscreenIndex < this.images.length - 1) {
                    fullscreenIndex++;
                    updateFullscreenImage();
                }
            });
            
            fullscreenView.addEventListener('click', (e) => {
                if (e.target === fullscreenView) {
                    fullscreenView.remove();
                }
            });
            
            document.addEventListener('keydown', function handleKeyDown(e) {
                if (e.key === 'Escape') {
                    fullscreenView.remove();
                    document.removeEventListener('keydown', handleKeyDown);
                } else if (e.key === 'ArrowLeft' && fullscreenIndex > 0) {
                    fullscreenIndex--;
                    updateFullscreenImage();
                } else if (e.key === 'ArrowRight' && fullscreenIndex < this.images.length - 1) {
                    fullscreenIndex++;
                    updateFullscreenImage();
                }
            }.bind(this));
        },
        
        openFullscreenView(imgSrc) {
            // Create fullscreen container
            const fullscreenView = document.createElement('div');
            fullscreenView.className = 'fullscreen-view';
            
            // Create image container
            const imgContainer = document.createElement('div');
            imgContainer.className = 'fullscreen-image-container';
            
            // Create image element
            const img = document.createElement('img');
            img.className = 'fullscreen-image';
            img.src = imgSrc;
            
            // Create close button
            const closeBtn = document.createElement('button');
            closeBtn.className = 'fullscreen-close';
            closeBtn.innerHTML = '&times;';
            closeBtn.addEventListener('click', () => {
                document.body.removeChild(fullscreenView);
            });
            
            // Add everything to the DOM
            imgContainer.appendChild(img);
            fullscreenView.appendChild(imgContainer);
            fullscreenView.appendChild(closeBtn);
            
            // Ensure the fullscreen view is rendered at the very top level of the DOM
            // This prevents any z-index conflicts with nested containers
            document.body.appendChild(fullscreenView);
            
            // Explicitly set high z-index to ensure it's above everything
            fullscreenView.style.zIndex = '10000';
            closeBtn.style.zIndex = '10001';
            
            // Close on click outside the image
            fullscreenView.addEventListener('click', (e) => {
                if (e.target === fullscreenView) {
                    document.body.removeChild(fullscreenView);
                }
            });
            
            // Close on escape key
            const escKeyHandler = (e) => {
                if (e.key === 'Escape') {
                    document.body.removeChild(fullscreenView);
                    document.removeEventListener('keydown', escKeyHandler);
                }
            };
            document.addEventListener('keydown', escKeyHandler);
        },
        
        closeModal() {
            this.modal.style.display = 'none';
            document.body.style.overflow = '';
            
            // Clear data to free memory
            this.images = [];
            this.allImages = [];
            this.currentImageIndex = 0;
            
            // Clear containers
            this.carouselContainer.innerHTML = '';
            this.thumbnailsContainer.innerHTML = '';
        },
        
        handleSearch(query) {
            const searchResults = this.filterImagesByQuery(query);
            
            if (searchResults.length > 0) {
                // Display search results in thumbnail format first
                this.displaySearchResults(searchResults);
            } else {
                // Show no results message
                this.displayNoResults();
            }
        },
        
        filterImagesByQuery(query) {
            // Filter images based on search query
            return this.images.filter(imageName => 
                imageName.toLowerCase().includes(query.toLowerCase())
            );
        },
        
        displaySearchResults(results) {
            console.log('FeaturedProducts: Displaying search results:', results.length);
            
            // Clear the search results container
            this.searchResultsContainer.innerHTML = '';
            this.searchResultsCount.textContent = `${results.length} results found`;
            
            // If no results, show message
            if (results.length === 0) {
                return this.displayNoResults();
            }
            
            // Create search results container if it doesn't exist
            if (!this.searchResultsContainer) {
                this.searchResultsContainer = document.createElement('div');
                this.searchResultsContainer.className = 'search-results-container';
                this.modal.querySelector('.modal-body').appendChild(this.searchResultsContainer);
            }
            
            // Show the search results container, hide the carousel and thumbnails
            this.searchResultsContainer.style.display = 'block';
            this.mainCarouselContainer.style.display = 'none';
            this.thumbnailsContainer.style.display = 'none';
            
            // Create a grid for the search results
            const gridContainer = document.createElement('div');
            gridContainer.className = 'search-results-grid';
            this.searchResultsContainer.appendChild(gridContainer);
            
            // Populate with result thumbnails
            this.searchResults = results.map(item => {
                // Create the base path for the image
                const basePath = this.constructImagePath(item.name);
                
                return {
                    name: item.name,
                    basePath,
                    category: this.categoryName
                };
            });
            
            // Create thumbnails for search results
            this.searchResults.forEach((item, index) => {
                const thumbnail = document.createElement('div');
                thumbnail.className = 'thumbnail';
                
                const img = document.createElement('img');
                img.alt = item.name;
                
                // Use the path from API response if available
                if (item.path) {
                    img.src = item.path;
                } else {
                    img.src = this.constructImagePath(item);
                }
                
                // Add error handler
                img.onerror = () => {
                    console.error(`Failed to load image: ${img.src}`);
                    img.src = 'images/default-thumbnail.jpg';
                };
                
                thumbnail.appendChild(img);
                
                // Add name label
                const label = document.createElement('div');
                label.className = 'thumbnail-label';
                label.textContent = item.name;
                thumbnail.appendChild(label);
                
                gridContainer.appendChild(thumbnail);
                
                // Add click event for thumbnail
                thumbnail.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();  // Prevent event from bubbling up
                    
                    console.log(`FeaturedProducts: Showing search result ${index}`);
                    
                    // Hide search results and show the carousel with thumbnails
                    this.searchResultsContainer.style.display = 'none';
                    this.mainCarouselContainer.style.display = 'block';
                    this.thumbnailsContainer.style.display = 'grid';
                    this.thumbnailsContainer.style.height = '35%';
                    
                    // Make sure the layout positions the thumbnails at the bottom and carousel at the top
                    this.modal.querySelector('.modal-body').style.flexDirection = 'column-reverse';
                    
                    // Set the current image index for the search results
                    this.currentImageIndex = index;
                    this.loadSearchResultImage(index);
                });
            });
        },
        
        loadSearchResultImage(index) {
            console.log(`FeaturedProducts: Loading search result image ${index}`);
            
            const item = this.searchResults[index];
            if (!item) {
                console.error('Search result not found:', index);
                return;
            }
            
            // Clear the carousel container
            this.carouselContainer.innerHTML = '';
            
            // Create a slide for the image
            const slide = document.createElement('div');
            slide.className = 'main-carousel-slide active';
            
            // Create the image
            const img = document.createElement('img');
            img.alt = item.name;
            img.className = 'carousel-image';
            
            // Use the path from API response if available
            if (item.path) {
                img.src = item.path;
            } else {
                img.src = this.constructImagePath(item);
            }
            
            // Add error handler
            img.onerror = () => {
                console.error(`Failed to load image: ${img.src}`);
                img.src = 'images/default-thumbnail.jpg';
            };
            
            slide.appendChild(img);
            this.carouselContainer.appendChild(slide);
            
            // Show the carousel
            this.showSlide(0);
        },
        
        displayNoResults() {
            this.thumbnailsContainer.innerHTML = `
                <div class="no-results">
                    <p>No matching products found.</p>
                </div>
            `;
            this.carouselContainer.innerHTML = '';
        },
    };

    // Initialize components
    categoryCarousel.init();
    productModal.init('', [], '');

    // Handle window resize
    let resizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            categoryCarousel.init();
        }, 250);
    });

    // Initialize search functionality
    const searchInput = document.getElementById('product-search');
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            const query = e.target.value.trim();
            
            // Find all category items
            const categoryItems = document.querySelectorAll('.category-item');
            
            if (query.length > 0) {
                // If there's an open modal, filter within that
                if (productModal.modal && productModal.modal.style.display === 'block') {
                    productModal.handleSearch(query);
                } else {
                    // Filter category items by name
                    categoryItems.forEach(item => {
                        const name = item.querySelector('h4').textContent.toLowerCase();
                        if (name.includes(query.toLowerCase())) {
                            item.style.display = 'block';
                        } else {
                            item.style.display = 'none';
                        }
                    });
                }
            } else {
                // Reset display when search is cleared
                if (productModal.modal && productModal.modal.style.display === 'block') {
                    productModal.loadImages();
                } else {
                    categoryItems.forEach(item => {
                        item.style.display = 'block';
                    });
                }
            }
        });
    }
});
