document.addEventListener('DOMContentLoaded', function() {
    // Helper function to add cache buster to URL (exclude MBNA_2025 category)
    function addCacheBuster(url, category) {
        // Don't add cache buster for MBNA_2025 category
        if (category === 'MBNA_2025') {
            return url;
        }
        
        // Add timestamp cache buster
        const timestamp = Date.now();
        return url.includes('?') ? `${url}&v=${timestamp}` : `${url}?v=${timestamp}`;
    }
    
    // Add styles for fullscreen view if not already present
    if (!document.getElementById('featured-products-fullscreen-styles')) {
        const fullscreenStyles = document.createElement('style');
        fullscreenStyles.id = 'featured-products-fullscreen-styles';
        fullscreenStyles.textContent = `
            .fullscreen-view {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0, 0, 0, 0.9);
                z-index: 10000;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .fullscreen-container {
                position: relative;
                width: 90%;
                height: 90%;
                display: flex;
                align-items: center;
                justify-content: center;
                overflow: hidden;
            }
            
            .fullscreen-image {
                max-width: 90%;
                max-height: 80%;
                object-fit: contain;
                width: auto;
                height: auto;
                margin: auto;
                display: block;
                box-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
            }
            
            .fullscreen-info {
                position: absolute;
                bottom: 0;
                left: 0;
                width: 100%;
                background-color: rgba(0, 0, 0, 0.7);
                color: white;
                padding: 10px;
                text-align: center;
            }
            
            .fullscreen-close {
                position: absolute;
                top: 20px;
                right: 20px;
                font-size: 30px;
                color: white;
                background-color: rgba(0, 0, 0, 0.5);
                width: 40px;
                height: 40px;
                line-height: 40px;
                text-align: center;
                border-radius: 50%;
                cursor: pointer;
                z-index: 10001;
            }
            
            .fullscreen-nav {
                position: absolute;
                top: 50%;
                transform: translateY(-50%);
                background-color: rgba(0, 0, 0, 0.5);
                color: white;
                font-size: 30px;
                border: none;
                width: 50px;
                height: 50px;
                border-radius: 50%;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10001;
            }
            
            .fullscreen-nav.prev-nav {
                left: 20px;
            }
            
            .fullscreen-nav.next-nav {
                right: 20px;
            }
            
            .fullscreen-nav:disabled {
                opacity: 0.3;
                cursor: not-allowed;
            }
            
            /* Mobile styles */
            @media (max-width: 768px) {
                .fullscreen-container {
                    width: 100%;
                    height: 100%;
                    padding: 10px;
                }
                
                .fullscreen-image {
                    max-width: 95%;
                    max-height: 70%;
                }
                
                .fullscreen-close {
                    top: 10px;
                    right: 10px;
                    width: 36px;
                    height: 36px;
                    line-height: 36px;
                    font-size: 24px;
                }
                
                .fullscreen-nav {
                    width: 40px;
                    height: 40px;
                    font-size: 24px;
                    background-color: rgba(0, 0, 0, 0.7);
                }
                
                .fullscreen-nav.prev-nav {
                    left: 10px;
                }
                
                .fullscreen-nav.next-nav {
                    right: 10px;
                }
                
                .fullscreen-info {
                    padding: 5px;
                    font-size: 14px;
                }
                
                .fullscreen-info h3 {
                    margin: 0;
                    font-size: 14px;
                }
            }
            
            /* Small mobile styles */
            @media (max-width: 480px) {
                .fullscreen-image {
                    max-width: 95%;
                    max-height: 60%;
                }
                
                .fullscreen-nav {
                    width: 36px;
                    height: 36px;
                    font-size: 20px;
                }
                
                .fullscreen-close {
                    width: 32px;
                    height: 32px;
                    line-height: 32px;
                    font-size: 20px;
                }
                
                .fullscreen-info h3 {
                    font-size: 12px;
                }
            }
        `;
        document.head.appendChild(fullscreenStyles);
    }

    // Advanced image error handling - try multiple paths when an image fails to load
    function handleImageError(img, originalSrc) {
        console.log(`Image failed to load: ${originalSrc}, trying alternatives...`);
        
        // Parse the path
        const parts = originalSrc.split('/');
        const filename = parts[parts.length - 1];
        const basename = filename.split('.')[0];
        const extension = filename.split('.').pop();
        
        // Store original src for reference
        if (!img.dataset.originalSrc) {
            img.dataset.originalSrc = originalSrc;
        }
        
        // Track attempts to avoid infinite loops
        img.dataset.attempts = (parseInt(img.dataset.attempts || 0) + 1).toString();
        if (parseInt(img.dataset.attempts) > 3) {
            console.log(`Failed to load image after multiple attempts: ${originalSrc}`);
            return; // Stop trying after 3 attempts
        }
        
        // Prepare alternative paths to try
        const alternatives = [];
        
        // Extract the category from the path
        let category = '';
        const pathLower = originalSrc.toLowerCase();
        if (pathLower.includes('/monuments/')) {
            category = 'monuments';
        } else if (pathLower.includes('/mbna_2025/')) {
            category = 'MBNA_2025';
        } else {
            // Try to extract category from path segments
            for (let i = 0; i < parts.length; i++) {
                if (parts[i].toLowerCase() === 'products' && i+1 < parts.length) {
                    category = parts[i+1];
                    break;
                }
            }
        }
        
        // 1. Try changing case of category
        if (category) {
            // Possible case variations
            const categoryCases = [
                category.toLowerCase(),
                category.toUpperCase(),
                category.charAt(0).toUpperCase() + category.slice(1).toLowerCase()
            ];
            
            categoryCases.forEach(c => {
                if (c !== category) {
                    const newPath = originalSrc.replace(new RegExp(`/${category}/`, 'i'), `/${c}/`);
                    alternatives.push(addCacheBuster(newPath, c));
                }
            });
        }
        
        // 2. Try changing AS- to AG- and vice versa for monument images
        if (basename.startsWith('AS-')) {
            const agName = `AG-${basename.substring(3)}`;
            const agPath = originalSrc.replace(basename, agName);
            alternatives.push(addCacheBuster(agPath, category));
        } 
        else if (basename.startsWith('AG-')) {
            const asName = `AS-${basename.substring(3)}`;
            const asPath = originalSrc.replace(basename, asName);
            alternatives.push(addCacheBuster(asPath, category));
        }
        
        // 3. Try different extensions if appropriate
        if (category === 'monuments') {
            const extensions = ['jpg', 'jpeg', 'png', 'gif'];
            extensions.forEach(ext => {
                if (ext !== extension) {
                    const extPath = originalSrc.replace(`.${extension}`, `.${ext}`);
                    alternatives.push(addCacheBuster(extPath, category));
                }
            });
        }
        
        // Try the alternatives one by one
        tryNextAlternative(img, alternatives, 0);
    }
    
    // Helper function to try alternative paths one by one
    function tryNextAlternative(img, alternatives, index) {
        if (index >= alternatives.length) {
            // We've tried all alternatives, set a placeholder or empty src
            console.log(`All alternatives failed for ${img.dataset.originalSrc}`);
            img.src = 'images/placeholder.png'; // Fallback to placeholder
            return;
        }
        
        const nextSrc = alternatives[index];
        console.log(`Trying alternative path ${index+1}/${alternatives.length}: ${nextSrc}`);
        
        // Create a new image to test this path
        const testImg = new Image();
        testImg.onload = function() {
            // This alternative worked, use it
            console.log(`Alternative path successful: ${nextSrc}`);
            img.src = nextSrc;
        };
        testImg.onerror = function() {
            // Try the next alternative
            tryNextAlternative(img, alternatives, index + 1);
        };
        testImg.src = nextSrc;
    }
    
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
            
            // Handle directory path - use provided or construct from category
            if (directory) {
                this.directory = directory;
            } else if (categoryName) {
                this.directory = `images/products/${categoryName}`;
            } else {
                this.directory = 'images/products/';
            }
            
            console.log('FeaturedProducts: Using directory:', this.directory);
            
            // Make sure modal is created
            if (!this.modal) {
                this.createModal();
            }
            
            this.carouselContainer.innerHTML = '';
            this.thumbnailsContainer.innerHTML = '';
            
            this.initEvents();
        },
        
        constructImagePath(imageName) {
            if (!imageName) return '';
            
            // Default path
            let path = `${this.directory}/${imageName}`;
            
            // Check if the path has http prefix (full URL)
            if (imageName.startsWith('http://') || imageName.startsWith('https://')) {
                path = imageName;
            }
            
            // Add error handling when constructing paths
            this.validateImagePath(path, imageName);
            
            return path;
        },
        
        validateImagePath(path, imageName) {
            if (!path) return;
            
            // Create a test image to check if the resource exists
            const testImg = new Image();
            testImg.onerror = () => {
                console.log(`Image not found: ${path}, using fallback`);
                
                // Try alternative paths if original fails
                if (path.includes('/products/') && !path.includes('/products/placeholder.png')) {
                    // Update image sources that failed to load with placeholder
                    const imgElements = document.querySelectorAll(`img[src="${path}"]`);
                    if (imgElements.length > 0) {
                        imgElements.forEach(img => {
                            img.src = 'images/placeholder.png';
                        });
                    }
                }
            };
            
            // Start the test load
            testImg.src = path;
        },
        
        loadImages() {
            if (!this.images || this.images.length === 0) {
                console.error('No images to load');
                return;
            }
            
            // Clear containers
            this.carouselContainer.innerHTML = '';
            this.thumbnailsContainer.innerHTML = '';
            
            // Add images to carousel and thumbnails
            this.images.forEach((imageObj, index) => {
                // Create slide
                const slide = document.createElement('div');
                slide.className = 'main-carousel-slide';
                
                // Create image
                const img = document.createElement('img');
                img.className = 'carousel-image';
                img.alt = imageObj.name;
                
                // Set up image loading and error handling
                img.onload = () => {
                    // Fade in animation
                    img.style.opacity = '1';
                };
                
                // Simple error handling
                img.onerror = () => {
                    console.error(`Failed to load image: ${img.src}`);
                    img.src = 'images/placeholder.png';
                };
                
                // Set image source with cache busting
                img.src = addCacheBuster(imageObj.path, this.categoryName);
                
                // Add click handler to open fullscreen view
                img.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    this.openFullscreen(index);
                });
                
                slide.appendChild(img);
                this.carouselContainer.appendChild(slide);
                
                // Create thumbnail
                const thumbContainer = document.createElement('div');
                thumbContainer.className = 'thumbnail';
                
                // Create thumbnail image
                const thumb = document.createElement('img');
                thumb.alt = imageObj.name;
                
                // Set up thumbnail loading and error handling
                thumb.onload = () => {
                    // Fade in animation
                    thumb.style.opacity = '1';
                };
                
                // Simple error handling
                thumb.onerror = () => {
                    console.error(`Failed to load thumbnail: ${thumb.src}`);
                    thumb.src = 'images/placeholder.png';
                };
                
                // Set thumbnail source with cache busting
                thumb.src = addCacheBuster(imageObj.path, this.categoryName);
                
                thumbContainer.appendChild(thumb);
                
                // Add label to thumbnail
                const label = document.createElement('div');
                label.className = 'thumbnail-label';
                label.textContent = imageObj.name.replace(/\.[^/.]+$/, '');
                thumbContainer.appendChild(label);
                
                this.thumbnailsContainer.appendChild(thumbContainer);
                
                // Add click event handler
                thumbContainer.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();  // Prevent event from bubbling up
                    
                    console.log(`FeaturedProducts: Showing slide ${index}`);
                    
                    const allThumbs = this.thumbnailsContainer.querySelectorAll('.thumbnail');
                    allThumbs.forEach(t => t.classList.remove('active'));
                    thumbContainer.classList.add('active');
                    thumbContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
                    this.showSlide(index);
                });
            });
            
            // Update modal title with image count
            const title = document.getElementById('featured-products-modal-title');
            if (title) {
                title.textContent = `${this.categoryName.replace(/_/g, ' ')} Collection (${this.images.length} items)`;
            }
            
            // Show first image
            this.showSlide(0);
        },
        
        fetchImages(category) {
            return new Promise((resolve, reject) => {
                console.log(`Fetching images for category: ${category}`);
                
                // Handle special case for MBNA 2025 directory
                let categoryForPath = category;
                if (category.toLowerCase() === 'mbna 2025') {
                    categoryForPath = 'MBNA_2025';
                }
                
                // Add timestamp for cache busting
                const timestamp = Date.now();
                const url = `get_directory_files.php?directory=products/${categoryForPath}&_=${timestamp}`;
                
                fetch(url)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`Failed to fetch images: ${response.status} ${response.statusText}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success && data.files) {
                            console.log(`Found ${data.files.length} images for category: ${category}`);
                            this.images = data.files;
                            resolve(data.files);
                        } else {
                            console.error('No files found in response:', data);
                            reject(new Error('No files found in response'));
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching images:', error);
                        reject(error);
                    });
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
            this.categoryName = category;
            
            // First, fetch images for this category
            this.fetchImages(category)
                .then(images => {
                    if (images && images.length > 0) {
                        // Update modal title
                        const title = document.getElementById('featured-products-modal-title');
                        if (title) {
                            title.textContent = `${category.replace(/_/g, ' ')} Collection (${images.length} items)`;
                        }
                        
                        // Load images
                        this.loadImages();
                        
                        // Show first image
                        this.showSlide(0);
                        
                        // Display modal
                        if (this.modal) {
                            this.modal.style.display = 'block';
                        }
                    } else {
                        console.error('No images found for category:', category);
                    }
                })
                .catch(error => {
                    console.error('Error opening category:', error);
                });
        },
        
        showSlide(index) {
            if (!this.carouselContainer) return;
            
            // Validate index
            if (index < 0) index = 0;
            if (index >= this.images.length) index = this.images.length - 1;
            
            // Update current index
            this.currentIndex = index;
            
            // Get all slides and hide them
            const slides = this.carouselContainer.querySelectorAll('.main-carousel-slide');
            slides.forEach(slide => slide.classList.remove('active'));
            
            // Show the selected slide
            if (slides[index]) {
                slides[index].classList.add('active');
            }
            
            // Update thumbnails
            const thumbnails = this.thumbnailsContainer.querySelectorAll('.thumbnail');
            thumbnails.forEach((thumb, i) => {
                if (i === index) {
                    thumb.classList.add('active');
                    thumb.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
                } else {
                    thumb.classList.remove('active');
                }
            });
            
            // Update navigation buttons state
            if (this.prevButton) {
                this.prevButton.disabled = index === 0;
                this.prevButton.style.opacity = index === 0 ? '0.5' : '1';
            }
            
            if (this.nextButton) {
                this.nextButton.disabled = index === this.images.length - 1;
                this.nextButton.style.opacity = index === this.images.length - 1 ? '0.5' : '1';
            }
        },
        
        showPrevSlide() {
            this.showSlide(this.currentIndex - 1);
        },
        
        showNextSlide() {
            this.showSlide(this.currentIndex + 1);
        },
        
        closeModal() {
            if (this.modal) {
                this.modal.style.display = 'none';
            }
        },
        
        openFullscreen(index) {
            const fullscreenView = document.createElement('div');
            fullscreenView.className = 'fullscreen-view';
            fullscreenView.id = 'fullscreen-product-view';
            
            // Add fullscreen-view class to body to hide sidebar
            document.body.classList.add('fullscreen-view');
            
            // Get the correct image path with cache busting
            const currentImage = this.images[index];
            if (!currentImage) {
                console.error('No image found at index:', index);
                return;
            }
            
            const imgSrc = addCacheBuster(currentImage.path, this.categoryName);
            const productNumber = currentImage.name || '';
            
            // Create fullscreen HTML with navigation buttons
            fullscreenView.innerHTML = `
                <div class="fullscreen-container">
                    <img src="${imgSrc}" alt="${productNumber}" class="fullscreen-image">
                    <div class="fullscreen-info">
                        <h3>${productNumber}</h3>
                    </div>
                    <div class="fullscreen-close">&times;</div>
                    <button class="fullscreen-nav prev-nav" ${index <= 0 ? 'disabled' : ''}>&lt;</button>
                    <button class="fullscreen-nav next-nav" ${index >= this.images.length - 1 ? 'disabled' : ''}>&gt;</button>
                </div>
            `;
            
            document.body.appendChild(fullscreenView);
            
            // Close button handler
            const closeBtn = fullscreenView.querySelector('.fullscreen-close');
            closeBtn.addEventListener('click', () => {
                fullscreenView.remove();
                document.body.classList.remove('fullscreen-view');
            });
            
            // Close on clicking outside
            fullscreenView.addEventListener('click', (e) => {
                if (e.target === fullscreenView) {
                    fullscreenView.remove();
                    document.body.classList.remove('fullscreen-view');
                }
            });
            
            // Keyboard navigation
            const keyHandler = (e) => {
                if (e.key === 'Escape') {
                    fullscreenView.remove();
                    document.body.classList.remove('fullscreen-view');
                    document.removeEventListener('keydown', keyHandler);
                } else if (e.key === 'ArrowLeft' && index > 0) {
                    fullscreenView.remove();
                    document.removeEventListener('keydown', keyHandler);
                    this.openFullscreen(index - 1);
                } else if (e.key === 'ArrowRight' && index < this.images.length - 1) {
                    fullscreenView.remove();
                    document.removeEventListener('keydown', keyHandler);
                    this.openFullscreen(index + 1);
                }
            };
            
            document.addEventListener('keydown', keyHandler);
            
            // Navigation buttons
            const prevBtn = fullscreenView.querySelector('.prev-nav');
            if (prevBtn) {
                prevBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    if (index > 0) {
                        fullscreenView.remove();
                        this.openFullscreen(index - 1);
                    }
                });
            }
            
            const nextBtn = fullscreenView.querySelector('.next-nav');
            if (nextBtn) {
                nextBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    if (index < this.images.length - 1) {
                        fullscreenView.remove();
                        this.openFullscreen(index + 1);
                    }
                });
            }
            
            // Touch swipe support
            let touchStartX = 0;
            let touchEndX = 0;
            let touchStartTime = 0;
            
            fullscreenView.addEventListener('touchstart', (e) => {
                // Only track primary touch
                if (e.touches.length === 1) {
                    touchStartX = e.changedTouches[0].screenX;
                    touchStartTime = Date.now();
                }
            }, { passive: true });
            
            fullscreenView.addEventListener('touchend', (e) => {
                // Ensure this is the same touch that started and enough time has passed
                if (e.changedTouches.length === 1 && Date.now() - touchStartTime > 100 && Date.now() - touchStartTime < 1000) {
                    touchEndX = e.changedTouches[0].screenX;
                    const swipeDiff = touchEndX - touchStartX;
                    
                    // Only register significant horizontal swipes (more distance and not accidental)
                    if (Math.abs(swipeDiff) > 75) {
                        if (swipeDiff > 0 && index > 0) {
                            // Swipe right - previous image
                            fullscreenView.remove();
                            this.openFullscreen(index - 1);
                        } else if (swipeDiff < 0 && index < this.images.length - 1) {
                            // Swipe left - next image
                            fullscreenView.remove();
                            this.openFullscreen(index + 1);
                        }
                    }
                }
            }, { passive: true });
        },
        
        openFullscreenView(imgSrc) {
            // Create fullscreen view
            const fullscreenView = document.createElement('div');
            fullscreenView.className = 'fullscreen-view';
            
            // Add fullscreen-view class to body to hide sidebar
            document.body.classList.add('fullscreen-view');
            
            fullscreenView.innerHTML = `
                <div class="fullscreen-container">
                    <img src="${imgSrc}" alt="Product image" class="fullscreen-image">
                    <div class="fullscreen-close">&times;</div>
                </div>
            `;
            
            document.body.appendChild(fullscreenView);
            
            // Add event listeners
            const closeBtn = fullscreenView.querySelector('.fullscreen-close');
            closeBtn.addEventListener('click', () => {
                fullscreenView.remove();
                // Remove fullscreen-view class when closing
                document.body.classList.remove('fullscreen-view');
            });
            
            // Close on outside click
            fullscreenView.addEventListener('click', (e) => {
                if (e.target === fullscreenView) {
                    fullscreenView.remove();
                    // Remove fullscreen-view class when closing
                    document.body.classList.remove('fullscreen-view');
                }
            });
        },
        
        initEvents() {
            // Close modal button
            if (this.closeBtn) {
                this.closeBtn.addEventListener('click', () => {
                    this.closeModal();
                });
            }

            // Close modal when clicking outside of content
            if (this.modal) {
                this.modal.addEventListener('click', (e) => {
                    if (e.target === this.modal) {
                        this.closeModal();
                    }
                });
            }

            // Add navigation button handlers
            if (this.prevButton) {
                this.prevButton.addEventListener('click', () => {
                    this.showPrevSlide();
                });
            }
            
            if (this.nextButton) {
                this.nextButton.addEventListener('click', () => {
                    this.showNextSlide();
                });
            }

            // Keyboard navigation
            document.addEventListener('keydown', (e) => {
                if (this.modal && this.modal.style.display === 'block') {
                    if (e.key === 'Escape') {
                        this.closeModal();
                    } else if (e.key === 'ArrowLeft') {
                        this.showPrevSlide();
                    } else if (e.key === 'ArrowRight') {
                        this.showNextSlide();
                    }
                }
            });
            
            // Touch swipe support
            if (this.mainCarouselContainer) {
                let startX = 0;
                let currentX = 0;
                let isDragging = false;
                
                this.mainCarouselContainer.addEventListener('touchstart', (e) => {
                    startX = e.touches[0].clientX;
                    isDragging = true;
                }, { passive: true });
                
                this.mainCarouselContainer.addEventListener('touchmove', (e) => {
                    if (!isDragging) return;
                    currentX = e.touches[0].clientX;
                }, { passive: true });
                
                this.mainCarouselContainer.addEventListener('touchend', () => {
                    if (!isDragging) return;
                    
                    const diffX = startX - currentX;
                    const threshold = 50; // Minimum swipe distance
                    
                    if (Math.abs(diffX) > threshold) {
                        if (diffX > 0) {
                            // Swiped left - go to next
                            this.showNextSlide();
                        } else {
                            // Swiped right - go to previous
                            this.showPrevSlide();
                        }
                    }
                    
                    isDragging = false;
                }, { passive: true });
            }
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
                    handleImageError(img, img.src);
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
                handleImageError(img, img.src);
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

            // Add modal styles if not already added
            if (!document.getElementById('featured-products-modal-styles')) {
                const modalStyles = document.createElement('style');
                modalStyles.id = 'featured-products-modal-styles';
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
                `;
                document.head.appendChild(modalStyles);
            }

            // Cache elements
            this.closeBtn = this.modal.querySelector('.close-modal');
            this.carouselContainer = this.modal.querySelector('.main-carousel-slides');
            this.mainCarouselContainer = this.modal.querySelector('.main-carousel-container');
            this.thumbnailsContainer = this.modal.querySelector('.thumbnails-container');
            this.prevButton = this.modal.querySelector('.nav-button.prev');
            this.nextButton = this.modal.querySelector('.nav-button.next');
        },
    };

    // Initialize components
    productModal.init('', [], '');

    // Handle window resize
    let resizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            productModal.init('', [], '');
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
