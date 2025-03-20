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

            console.log('Category carousel initialized');
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
        
        init() {
            this.createModal();
            this.setupEventListeners();
            console.log('Product modal initialized');
        },

        createModal() {
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
                
                /* Navigation buttons */
                .nav-button {
                    position: absolute;
                    top: 50%;
                    transform: translateY(-50%);
                    width: 50px;
                    height: 50px;
                    background: rgba(0, 0, 0, 0.6);
                    border: none;
                    border-radius: 50%;
                    color: white;
                    font-size: 24px;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    cursor: pointer;
                    z-index: 10;
                    transition: background 0.3s;
                }
                
                .nav-button:hover {
                    background: rgba(0, 0, 0, 0.8);
                }
                
                .nav-button.prev {
                    left: 10px;
                }
                
                .nav-button.next {
                    right: 10px;
                }
                
                /* Thumbnails styles */
                .thumbnails-container {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 10px;
                    overflow-y: auto;
                    height: 35%;
                    padding: 10px;
                    background-color: #1a1a1a;
                    border-radius: 4px;
                }
                
                .thumbnail {
                    width: 120px;
                    height: 120px;
                    overflow: hidden;
                    border-radius: 4px;
                    border: 2px solid transparent;
                    cursor: pointer;
                    transition: all 0.3s;
                }
                
                .thumbnail.active {
                    border-color: #d6b772;
                }
                
                .thumbnail img {
                    width: 100%;
                    height: 100%;
                    object-fit: cover;
                    transition: transform 0.3s;
                }
                
                .thumbnail:hover img {
                    transform: scale(1.1);
                }
                
                /* Fullscreen styles */
                .fullscreen-view {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background-color: rgba(0, 0, 0, 0.95);
                    z-index: 2000;
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
                    background: rgba(0, 0, 0, 0.6);
                    border: none;
                    color: white;
                    font-size: 24px;
                    width: 40px;
                    height: 40px;
                    border-radius: 50%;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    cursor: pointer;
                }
                
                .fullscreen-nav {
                    background: #fff;
                    border: none;
                    padding: 10px;
                    cursor: pointer;
                }
                
                .fullscreen-prev {
                    left: 10px;
                }
                
                .fullscreen-next {
                    right: 10px;
                }
                
                /* Mobile Responsive */
                @media (max-width: 768px) {
                    .main-carousel-container {
                        height: 55%;
                    }
                    
                    .thumbnails-container {
                        height: 45%;
                    }
                    
                    .thumbnail {
                        width: 100px;
                        height: 100px;
                    }
                    
                    .nav-button {
                        width: 40px;
                        height: 40px;
                        font-size: 20px;
                    }
                }
            `;
            document.head.appendChild(modalStyles);

            this.modal = document.createElement('div');
            this.modal.className = 'collection-modal';
            this.modal.innerHTML = `
                <div class="modal-content">
                    <div class="modal-header">
                        <h2>Product Collection</h2>
                        <button class="close-modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="main-carousel-container">
                            <button class="nav-button prev"><i class="bi bi-chevron-left"></i></button>
                            <button class="nav-button next"><i class="bi bi-chevron-right"></i></button>
                            <div class="main-carousel-slides"></div>
                        </div>
                        <div class="thumbnails-container"></div>
                    </div>
                </div>
            `;
            document.body.appendChild(this.modal);

            // Cache elements
            this.closeBtn = this.modal.querySelector('.close-modal');
            this.carouselContainer = this.modal.querySelector('.main-carousel-slides');
            this.thumbnailsContainer = this.modal.querySelector('.thumbnails-container');
            this.prevButton = this.modal.querySelector('.nav-button.prev');
            this.nextButton = this.modal.querySelector('.nav-button.next');
        },

        setupEventListeners() {
            // Find all category links
            const categoryLinks = document.querySelectorAll('.category-link');
            
            // Add event listeners to category links
            categoryLinks.forEach(link => {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    const category = link.getAttribute('data-category');
                    console.log('Category clicked:', category);
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
                    const category = categoryLink.getAttribute('data-category');
                    console.log('Category clicked (delegated):', category);
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
                    this.close();
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
                        this.close();
                    }
                }
            });
        },

        openCategory(category) {
            this.categoryName = category;
            this.directory = `images/products/${category}`;
            
            this.modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
            
            // Show loading state
            const loadingIndicator = document.createElement('div');
            loadingIndicator.className = 'loading-indicator';
            loadingIndicator.innerHTML = `
                <div class="spinner">
                    <i class="bi bi-arrow-repeat spin"></i>
                </div>
                <p>Loading images...</p>
            `;
            
            // Add loading styles
            const loadingStyles = document.createElement('style');
            loadingStyles.textContent = `
                .loading-indicator {
                    position: absolute;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                    text-align: center;
                    color: #fff;
                }
                
                .spinner {
                    font-size: 3rem;
                    margin-bottom: 1rem;
                }
                
                .spin {
                    animation: spin 1s linear infinite;
                }
                
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
            `;
            document.head.appendChild(loadingStyles);
            
            this.modal.querySelector('.modal-body').appendChild(loadingIndicator);
            
            // Update modal title
            const displayName = this.categoryName.replace(/_/g, ' ');
            const modalTitle = this.modal.querySelector('.modal-header h2');
            modalTitle.textContent = `${displayName} Collection`;
            
            // Fetch images from the directory
            fetch(`get_directory_files.php?directory=${encodeURIComponent(this.directory)}&extension=png`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.files.length > 0) {
                        this.images = data.files;
                        
                        // Update modal title with image count
                        modalTitle.textContent = `${displayName} Collection (${this.images.length} Designs)`;
                        
                        this.loadImages();
                    } else {
                        console.error('Error loading images:', data.error || 'No images found');
                        // Display error message
                        loadingIndicator.innerHTML = `
                            <div class="error-icon">
                                <i class="bi bi-exclamation-triangle"></i>
                            </div>
                            <p>Error loading images. Please try again later.</p>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error fetching directory files:', error);
                    // Display error message
                    loadingIndicator.innerHTML = `
                        <div class="error-icon">
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>
                        <p>Error loading images. Please try again later.</p>
                    `;
                })
                .finally(() => {
                    // Remove loading indicator after images are loaded
                    setTimeout(() => {
                        if (loadingIndicator.parentNode) {
                            loadingIndicator.remove();
                        }
                    }, 500);
                });
        },

        loadImages() {
            // Clear containers first
            this.carouselContainer.innerHTML = '';
            this.thumbnailsContainer.innerHTML = '';
            
            this.images.forEach((imageName, index) => {
                // Create main carousel slide
                const slide = document.createElement('div');
                slide.className = `main-carousel-slide ${index === 0 ? 'active' : ''}`;
                
                const slideImg = document.createElement('img');
                slideImg.src = `${this.directory}/${imageName}`;
                slideImg.alt = `${this.categoryName.replace(/_/g, ' ')} ${imageName.replace('.png', '')}`;
                slideImg.loading = 'lazy';
                
                slide.appendChild(slideImg);
                this.carouselContainer.appendChild(slide);
                
                // Create thumbnail
                const thumbnail = document.createElement('div');
                thumbnail.className = `thumbnail ${index === 0 ? 'active' : ''}`;
                thumbnail.dataset.index = index;
                
                const thumbImg = document.createElement('img');
                const thumbnailSrc = `${this.directory}/thumbnails/${imageName}`;
                
                // Check if file exists by fetching the header
                fetch(thumbnailSrc, { method: 'HEAD' })
                    .then(response => {
                        if (response.ok) {
                            thumbImg.src = thumbnailSrc;
                        } else {
                            // If thumbnail doesn't exist, use the full-size image
                            thumbImg.src = `${this.directory}/${imageName}`;
                        }
                    })
                    .catch(() => {
                        // If there's an error, fallback to the full-size image
                        thumbImg.src = `${this.directory}/${imageName}`;
                    });
                
                thumbImg.alt = `Thumbnail ${imageName.replace('.png', '')}`;
                thumbImg.loading = 'lazy';
                
                thumbnail.appendChild(thumbImg);
                this.thumbnailsContainer.appendChild(thumbnail);
                
                // Add click events
                slideImg.addEventListener('click', () => this.openFullscreen(index));
                thumbnail.addEventListener('click', () => this.showSlide(index));
            });
            
            // Reset to first slide
            this.currentIndex = 0;
        },

        showSlide(index) {
            // Hide all slides
            const slides = this.carouselContainer.querySelectorAll('.main-carousel-slide');
            slides.forEach(slide => slide.classList.remove('active'));
            
            // Show selected slide
            if (slides[index]) {
                slides[index].classList.add('active');
            }
            
            // Update thumbnails
            const thumbnails = this.thumbnailsContainer.querySelectorAll('.thumbnail');
            thumbnails.forEach(thumb => thumb.classList.remove('active'));
            
            if (thumbnails[index]) {
                thumbnails[index].classList.add('active');
                
                // Scroll thumbnail into view if needed
                thumbnails[index].scrollIntoView({
                    behavior: 'smooth',
                    block: 'nearest',
                    inline: 'nearest'
                });
            }
            
            this.currentIndex = index;
        },

        showPrevSlide() {
            const newIndex = (this.currentIndex - 1 + this.images.length) % this.images.length;
            this.showSlide(newIndex);
        },

        showNextSlide() {
            const newIndex = (this.currentIndex + 1) % this.images.length;
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
            prevButton.className = 'fullscreen-nav fullscreen-prev';
            prevButton.innerHTML = '<i class="bi bi-chevron-left"></i>';
            
            const nextButton = document.createElement('button');
            nextButton.className = 'fullscreen-nav fullscreen-next';
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
        
        close() {
            this.modal.style.display = 'none';
            document.body.style.overflow = '';
            // Remove any open fullscreen views
            const fullscreenView = document.querySelector('.fullscreen-view');
            if (fullscreenView) {
                fullscreenView.remove();
            }
        }
    };

    // Initialize components
    categoryCarousel.init();
    productModal.init();

    // Handle window resize
    let resizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            categoryCarousel.init();
        }, 250);
    });
});
