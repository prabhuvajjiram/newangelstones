document.addEventListener('DOMContentLoaded', function() {
    // Helper function to add cache buster to URL
    function addCacheBuster(url, category) {
        // Add timestamp cache buster for all categories
        const timestamp = Date.now();
        return url.includes('?') ? `${url}&v=${timestamp}` : `${url}?v=${timestamp}`;
    }
    
    // Shuffle array function for randomizing images
    function shuffleArray(array) {
        const shuffled = [...array];
        for (let i = shuffled.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]];
        }
        return shuffled;
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
        
        // Shuffle array method
        shuffleArray(array) {
            return shuffleArray(array); // Use the global shuffle function
        },
        
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
                            
                            // Shuffle images for random order on every load
                            console.log('Before shuffle:', data.files.slice(0, 5).map(f => f.name));
                            const files = shuffleArray(data.files);
                            
                            this.images = files;
                            resolve(files);
                        } else {
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
/**
 * Color Carousel - Dynamic color image loader
 * For Angel Stones website
 * @module colorCarousel
 */
(function($) {
    'use strict';

    // Colors container
    let colors = [];
    let currentColorIndex = 0;
    let isLoading = false;
    let lastUpdateTime = 0;
    const CACHE_DURATION = 5 * 60 * 1000; // 5 minutes cache
    let touchStartX = 0;
    let touchEndX = 0;

    /**
     * Initialize the color carousel and modal
     */
    function initColorCarousel() {
        // Show loading state
        const $container = $('#variety-of-granites .owl-carousel');
        $container.html(`
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading colors...</span>
                </div>
                <p class="mt-2">Loading color options...</p>
            </div>
        `);

        // Fetch all color images from the directory
        fetchColorImages()
            .then(() => {
                if (colors.length > 0) {
                    populateColorDisplay();
                    setupEventListeners();
                } else {
                    showError('No color images found');
                }
            })
            .catch(error => {
                console.error('Error initializing color carousel:', error);
                showError('Failed to load colors. Please try again later.');
            });
    }

    /**
     * Show error message
     */
    function showError(message) {
        const $container = $('#variety-of-granites .owl-carousel');
        $container.html(`
            <div class="alert alert-warning m-3">
                <i class="bi bi-exclamation-triangle me-2"></i>
                ${message}
                <button class="btn btn-sm btn-outline-primary ms-3" onclick="window.location.reload()">
                    <i class="bi bi-arrow-clockwise"></i> Retry
                </button>
            </div>
        `);
    }

    /**
     * Fetch all color images from the server
     */
    function fetchColorImages() {
        return new Promise((resolve, reject) => {
            if (isLoading) return;
            
            const now = Date.now();
            // Use cached data if it's still fresh
            if (colors.length > 0 && (now - lastUpdateTime) < CACHE_DURATION) {
                return resolve();
            }

            isLoading = true;
            const timestamp = now; // Add cache busting parameter
            
            $.ajax({
                url: `get_color_images.php?_=${timestamp}`,
                dataType: 'json',
                cache: false,
                success: (data) => {
                    isLoading = false;
                    if (data && data.success && data.colors && data.colors.length > 0) {
                        colors = data.colors;
                        lastUpdateTime = now;
                        console.log(`Loaded ${colors.length} color images from ${data.directory}`);
                        resolve();
                    } else {
                        reject(data?.error || 'No color images found');
                    }
                },
                error: (xhr, status, error) => {
                    isLoading = false;
                    console.error('Error fetching color images:', status, error);
                    reject(`Failed to load colors: ${error || 'Unknown error'}`);
                }
            });
        });
    }

    /**
     * Populate the color display with scrollable row
     */
    function populateColorDisplay() {
        const $container = $('#variety-of-granites .owl-carousel');
        
        // Replace owl carousel with scrollable row
        $container.removeClass('owl-carousel owl-theme');
        $container.empty();
        $container.addClass('color-scroll-container');

        // Add colors to scrollable row
        colors.forEach((color, index) => {
            const safeName = $('<div>').text(color.name).html(); // Escape HTML in color name
            
            // Format color name for URL
            const formattedName = color.name.toLowerCase().replace(/\s+/g, '-');
            
            // Create color item with schema.org attributes
            const item = `
                <div class="color-item" 
                     itemprop="itemListElement" 
                     itemscope 
                     itemtype="https://schema.org/ListItem"
                     data-color-name="${color.name}">
                    <meta itemprop="position" content="${index + 1}" />
                    <div class="color-item-inner" 
                         itemscope 
                         itemtype="https://schema.org/Product" 
                         itemid="#${formattedName}">
                        <meta itemprop="name" content="${color.name} Granite">
                        <meta itemprop="description" content="${color.description || 'Premium quality ' + color.name + ' granite'}">
                        <meta itemprop="image" content="${color.image}">
                        <div itemprop="offers" itemscope itemtype="https://schema.org/Offer">
                            <meta itemprop="priceCurrency" content="USD">
                            <meta itemprop="price" content="0">
                            <link itemprop="availability" href="https://schema.org/InStock" />
                        </div>
                        <img src="${color.thumbnail || color.image}" 
                             alt="${color.name} Granite" 
                             loading="lazy"
                             class="img-fluid"
                             itemprop="image">
                        <div class="color-name" itemprop="name">${color.name}</div>
                    </div>
                </div>`;
            $container.append(item);
        });

        // Add loading animation to images
        $container.find('img').on('load', function() {
            $(this).closest('.color-scroll-item').addClass('loaded');
        });

        // Add or update the View All Colors button
        const $carouselContainer = $container.closest('.col-md-12');
        $carouselContainer.find('.view-all-colors-container').remove();
        
        $carouselContainer.append(`
            <div class="view-all-colors-container text-center mt-4">
                <button class="btn btn-primary view-all-colors-btn">
                    <i class="bi bi-grid-3x3-gap me-2"></i>
                    View All ${colors.length} Colors
                </button>
            </div>
        `);
        
        // Update the carousel header with count
        const $header = $container.closest('.section-padding').find('.section-header .title');
        if ($header.length && !$header.find('.badge').length) {
            $header.append(` <span class="badge bg-primary">${colors.length} colors</span>`);
        }
    }

    /**
     * Setup event listeners for interactions
     */
    function setupEventListeners() {
        // View All Colors button click
        $(document).on('click', '.view-all-colors-btn', function(e) {
            e.preventDefault();
            showAllColorsModal();
        });

        // Color image click in scrollable row
        $(document).on('click', '.color-scroll-item', function(e) {
            const index = $(this).data('index');
            showColorFullscreen(index);
        });

        // Color image click in modal
        $(document).on('click', '.color-grid-item', function(e) {
            const index = $(this).data('index');
            showColorFullscreen(index);
        });

        // Next/Previous buttons in fullscreen view
        $(document).on('click', '.color-fullscreen-nav', function(e) {
            e.stopPropagation();
            if ($(this).hasClass('color-fullscreen-prev')) {
                navigateColors('prev');
            } else {
                navigateColors('next');
            }
        });

        // Close fullscreen view on background click
        $(document).on('click', '.color-fullscreen-container', function(e) {
            if (e.target === this) {
                closeColorFullscreen();
            }
        });

        // Close fullscreen view on X button click
        $(document).on('click', '.color-fullscreen-close', function(e) {
            e.stopPropagation();
            closeColorFullscreen();
        });

        // Keyboard navigation
        $(document).on('keydown', function(e) {
            if ($('.color-fullscreen-container').is(':visible')) {
                if (e.key === 'Escape') {
                    closeColorFullscreen();
                } else if (e.key === 'ArrowLeft') {
                    navigateColors('prev');
                    e.preventDefault();
                } else if (e.key === 'ArrowRight') {
                    navigateColors('next');
                    e.preventDefault();
                }
            }
        });

        // Touch events for mobile swipe
        $(document).on('touchstart', '.color-fullscreen-container', function(e) {
            touchStartX = e.originalEvent.touches[0].clientX;
        });

        $(document).on('touchmove', '.color-fullscreen-container', function(e) {
            e.preventDefault();
            touchEndX = e.originalEvent.touches[0].clientX;
        });

        $(document).on('touchend', '.color-fullscreen-container', function() {
            handleSwipe();
        });

        // Add horizontal scroll indicator for desktop
        $('.color-scroll-container').on('mouseenter', function() {
            if (this.scrollWidth > this.clientWidth) {
                $(this).addClass('scrollable-hint');
                setTimeout(() => {
                    $(this).removeClass('scrollable-hint');
                }, 1500);
            }
        });
    }

    /**
     * Handle swipe events for touch devices
     */
    function handleSwipe() {
        if (Math.abs(touchEndX - touchStartX) > 50) {
            if (touchEndX < touchStartX) {
                navigateColors('next');
            } else if (touchEndX > touchStartX) {
                navigateColors('prev');
            }
        }
    }

    /**
     * Show the All Colors modal with grid view
     */
    function showAllColorsModal() {
        const modalId = 'all-colors-modal';
        let modalHtml = `
            <div class="modal fade" id="${modalId}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">All Colors (${colors.length})</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3">
        `;

        // Add color grid items
        colors.forEach((color, index) => {
            const safeName = $('<div>').text(color.name).html();
            modalHtml += `
                <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                    <div class="color-grid-item text-center" data-index="${index}">
                        <div class="color-grid-image mb-2">
                            <img src="${color.path}" 
                                 class="img-fluid rounded shadow-sm" 
                                 alt="${safeName}"
                                 onerror="this.onerror=null; this.src='images/placeholder.jpg';">
                        </div>
                        <div class="color-grid-name small">${safeName}</div>
                    </div>
                </div>
            `;
        });

        modalHtml += `
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Remove existing modal if any
        $(`#${modalId}`).remove();
        
        // Add new modal to body and show it
        $('body').append(modalHtml);
        const modal = new bootstrap.Modal(document.getElementById(modalId));
        modal.show();

        // Clean up modal on hide
        $(`#${modalId}`).on('hidden.bs.modal', function() {
            $(this).remove();
        });
    }

    /**
     * Show fullscreen view of a color
     */
    function showColorFullscreen(index) {
        if (index < 0 || index >= colors.length) return;
        
        currentColorIndex = index;
        const color = colors[index];
        const safeName = $('<div>').text(color.name).html();
        
        // Create fullscreen container if it doesn't exist
        if ($('.color-fullscreen-container').length === 0) {
            $('body').append(`
                <div class="color-fullscreen-container">
                    <div class="color-fullscreen-content">
                        <img src="${color.path}" alt="${safeName}" class="img-fluid">
                        <div class="color-fullscreen-caption">${safeName}</div>
                    </div>
                    <button class="color-fullscreen-nav color-fullscreen-prev">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    <button class="color-fullscreen-nav color-fullscreen-next">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                    <button class="color-fullscreen-close">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            `);
        } else {
            // Update existing fullscreen view
            const $fullscreen = $('.color-fullscreen-container');
            $fullscreen.find('img').attr('src', color.path).attr('alt', safeName);
            $fullscreen.find('.color-fullscreen-caption').text(safeName);
        }
        
        // Show fullscreen view
        $('body').addClass('color-fullscreen-active');
    }

    /**
     * Close fullscreen view
     */
    function closeColorFullscreen() {
        $('body').removeClass('color-fullscreen-active');
        
        // Keep the element in DOM but hide it for better performance
        $('.color-fullscreen-container')
            .one('transitionend', function() {
                if (!$('body').hasClass('color-fullscreen-active')) {
                    $(this).remove();
                }
            });
    }

    /**
     * Navigate between colors in fullscreen view
     */
    function navigateColors(direction) {
        if (direction === 'prev') {
            currentColorIndex = (currentColorIndex - 1 + colors.length) % colors.length;
        } else if (direction === 'next') {
            currentColorIndex = (currentColorIndex + 1) % colors.length;
        } else if (typeof direction === 'number') {
            // Direct index navigation
            currentColorIndex = Math.max(0, Math.min(direction, colors.length - 1));
        }
        
        // Update the fullscreen view
        showColorFullscreen(currentColorIndex);
    }

    /**
     * Add CSS styles for the color carousel
     * @private
     */
    function addColorCarouselStyles() {
        if ($('#color-carousel-styles').length) {
            return;
        }

        const styles = `
            /* Color Carousel Styles */
            .color-scroll-container {
                display: flex;
                overflow-x: auto;
                padding: 1rem 0.5rem;
                scrollbar-width: thin;
                scroll-behavior: smooth;
                scroll-snap-type: x mandatory;
                gap: 1rem;
            }
            
            .color-scroll-container::-webkit-scrollbar {
                height: 6px;
            }
            
            .color-scroll-container::-webkit-scrollbar-thumb {
                background-color: rgba(0, 0, 0, 0.2);
                border-radius: 3px;
            }
            
            .color-scroll-container.scrollable-hint {
                background: linear-gradient(to right, #fff, #f8f9fa, #fff);
            }
            
            .color-scroll-item {
                flex: 0 0 auto;
                width: 150px;
                cursor: pointer;
                transition: transform 0.2s ease;
                scroll-snap-align: start;
            }
            
            .color-scroll-item:hover {
                transform: translateY(-5px);
            }
            
            .color-scroll-image {
                position: relative;
                overflow: hidden;
                border-radius: 8px;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
                aspect-ratio: 1;
            }
            
            .color-scroll-image img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                transition: transform 0.3s ease;
            }
            
            .color-scroll-item:hover .color-scroll-image img {
                transform: scale(1.05);
            }
            
            .color-overlay {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: linear-gradient(to bottom, rgba(0,0,0,0.1) 0%, rgba(0,0,0,0.4) 100%);
                opacity: 0;
                transition: opacity 0.3s ease;
            }
            
            .color-scroll-item:hover .color-overlay {
                opacity: 1;
            }
            
            .color-scroll-item .caption {
                margin-top: 0.5rem;
                font-size: 0.85rem;
                color: #333;
            }
            
            /* Fullscreen View */
            .color-fullscreen-container {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: rgba(0, 0, 0, 0.9);
                z-index: 2000;
                display: flex;
                align-items: center;
                justify-content: center;
                opacity: 0;
                visibility: hidden;
                transition: opacity 0.3s ease, visibility 0.3s ease;
            }
            
            .color-fullscreen-active .color-fullscreen-container {
                opacity: 1;
                visibility: visible;
            }
            
            .color-fullscreen-content {
                max-width: 90%;
                max-height: 90vh;
                position: relative;
                text-align: center;
            }
            
            .color-fullscreen-content img {
                max-height: 80vh;
                max-width: 100%;
                border-radius: 8px;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            }
            
            .color-fullscreen-caption {
                color: #fff;
                margin-top: 1rem;
                font-size: 1.1rem;
                text-shadow: 0 1px 3px rgba(0, 0, 0, 0.5);
            }
            
            .color-fullscreen-nav {
                position: fixed;
                top: 50%;
                transform: translateY(-50%);
                width: 50px;
                height: 50px;
                background-color: rgba(255, 255, 255, 0.2);
                border: none;
                border-radius: 50%;
                color: white;
                font-size: 1.5rem;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                transition: background-color 0.2s ease;
                z-index: 2100;
            }
            
            .color-fullscreen-nav:hover {
                background-color: rgba(255, 255, 255, 0.3);
            }
            
            .color-fullscreen-prev {
                left: 20px;
            }
            
            .color-fullscreen-next {
                right: 20px;
            }
            
            .color-fullscreen-close {
                position: fixed;
                top: 20px;
                right: 20px;
                width: 40px;
                height: 40px;
                background-color: rgba(255, 255, 255, 0.2);
                border: none;
                border-radius: 50%;
                color: white;
                font-size: 1.2rem;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                transition: background-color 0.2s ease;
                z-index: 2100;
            }
            
            .color-fullscreen-close:hover {
                background-color: rgba(255, 255, 255, 0.3);
            }
            
            /* Grid View in Modal */
            .color-grid-item {
                cursor: pointer;
                transition: transform 0.2s ease;
            }
            
            .color-grid-item:hover {
                transform: translateY(-3px);
            }
            
            .color-grid-image {
                position: relative;
                overflow: hidden;
                border-radius: 6px;
                box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
                aspect-ratio: 1;
                margin-bottom: 0.5rem;
            }
            
            .color-grid-image img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                transition: transform 0.3s ease;
            }
            
            .color-grid-item:hover .color-grid-image img {
                transform: scale(1.05);
            }
            
            .color-grid-name {
                color: #333;
                font-weight: 500;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            
            /* Responsive Adjustments */
            @media (max-width: 767.98px) {
                .color-scroll-item {
                    width: 120px;
                }
                
                .color-fullscreen-nav {
                    width: 40px;
                    height: 40px;
                    font-size: 1.2rem;
                }
                
                .color-fullscreen-close {
                    width: 36px;
                    height: 36px;
                    font-size: 1.1rem;
                }
            }
            
            @media (max-width: 575.98px) {
                .color-scroll-item {
                    width: 100px;
                }
                
                .color-scroll-item .caption {
                    font-size: 0.75rem;
                }
                
                .color-fullscreen-caption {
                    font-size: 0.95rem;
                }
            }
        `;
        
        $('<style id="color-carousel-styles">' + styles + '</style>').appendTo('head');
    }

    // Initialize on document ready
    $(document).ready(function() {
        // Add styles first
        addColorCarouselStyles();
        
        // Then initialize the carousel if the container exists
        if ($('#variety-of-granites').length) {
            initColorCarousel();
        }
    });

})(jQuery);
/**
 * Color Gallery Functionality
 * Handles color selection and image display
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize color click handlers
    initColorGallery();
    
    // Function to initialize color gallery
    function initColorGallery() {
        // Get all color items
        const colorItems = document.querySelectorAll('.color-item');
        
        // Add click event to each color item
        colorItems.forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Get color name from data attribute or text content
                const colorName = this.getAttribute('data-color-name') || 
                                 this.textContent.trim();
                
                // Format the color name for the image URL
                const formattedName = colorName.toLowerCase().replace(/\s+/g, '');
                
                // Create the image URL
                const imageUrl = `https://www.theangelstones.com/images/colors/${formattedName}.jpg`;
                
                // Update the main image or open in a lightbox
                updateMainImage(imageUrl, colorName);
                
                // Update URL without page reload (for SPA behavior)
                updateUrl(colorName);
            });
        });
    }
    
    // Function to update the main image
    function updateMainImage(imageUrl, colorName) {
        // Find the main image container - adjust selector as needed
        const mainImage = document.querySelector('.main-color-image');
        
        if (mainImage) {
            // Create loading state
            mainImage.style.opacity = '0.7';
            
            // Create new image for preloading
            const img = new Image();
            img.onload = function() {
                // Update image source and fade in
                mainImage.src = imageUrl;
                mainImage.alt = `${colorName} Granite`;
                mainImage.title = colorName;
                mainImage.style.opacity = '1';
                
                // Dispatch custom event if needed by other scripts
                document.dispatchEvent(new CustomEvent('colorImageChanged', {
                    detail: {
                        imageUrl: imageUrl,
                        colorName: colorName
                    }
                }));
            };
            
            // Start loading the image
            img.src = imageUrl;
        }
    }
    
    // Function to update URL without page reload
    function updateUrl(colorName) {
        const formattedName = colorName.toLowerCase().replace(/\s+/g, '-');
        const newUrl = `${window.location.pathname}?color=${encodeURIComponent(formattedName)}`;
        
        // Update URL without reloading the page
        window.history.pushState({ color: formattedName }, '', newUrl);
        
        // Update page title (optional)
        document.title = `${colorName} Granite | Angel Stones`;
    }
    
    // Handle browser back/forward buttons
    window.addEventListener('popstate', function(event) {
        if (event.state && event.state.color) {
            const colorName = event.state.color.replace(/-/g, ' ');
            const formattedName = colorName.replace(/\s+/g, '');
            const imageUrl = `https://www.theangelstones.com/images/colors/${formattedName}.jpg`;
            updateMainImage(imageUrl, colorName);
        }
    });
    
    // Check for color parameter in URL on page load
    function checkUrlForColor() {
        const urlParams = new URLSearchParams(window.location.search);
        const colorParam = urlParams.get('color');
        
        if (colorParam) {
            const colorName = colorParam.replace(/-/g, ' ');
            const formattedName = colorParam.replace(/-/g, '');
            const imageUrl = `https://www.theangelstones.com/images/colors/${formattedName}.jpg`;
            updateMainImage(imageUrl, colorName);
        }
    }
    
    // Run URL check on page load
    checkUrlForColor();
});
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
            // Add cache-busting parameter
            const response = await fetch('/crm/ajax/get_active_promotions.php?v=' + Date.now());
            
            // Attempt to get response text regardless of response.ok for better error reporting
            const responseText = await response.text();

            if (!response.ok) {
                // Include responseText in the error message if available
                throw new Error(`HTTP error! status: ${response.status}. Response: ${responseText}`);
            }
            
            let data;
            try {
                data = JSON.parse(responseText); 
            } catch (parseError) {
                // Log the raw response text that caused the JSON parsing error
                console.error('Error parsing JSON from /crm/ajax/get_active_promotions.php:', parseError);
                console.error('Raw response causing error:', responseText);
                this.banner.style.display = 'none'; 
                return; 
            }
            
            // Check for success flag and promotions array in response
            if (data.success && Array.isArray(data.promotions) && data.promotions.length > 0) {
                this.promotions = data.promotions;
                this.showPromotion(0);
                if (this.promotions.length > 1) {
                    this.startCarousel();
                }
            } else {
                 // Log details if data.success is false or promotions array is missing/empty
                console.log('No active promotions, or unsuccessful/invalid response. Data:', data);
                this.banner.style.display = 'none';
            }
        } catch (error) {
            // This will catch errors from fetch itself, or the new Error thrown for !response.ok, or re-thrown parseError
            console.error('Error loading promotions:', error);
            this.banner.style.display = 'none';
        }
    }

    showPromotion(index) {
        const promotion = this.promotions[index];
        if (!promotion) {
            console.error('No promotion at index:', index);
            return;
        }
        
        const content = this.banner.querySelector('.promotion-content');
        if (!content) {
            console.error('Promotion content element not found');
            return;
        }
        
        // Use linkUrl (camelCase from API) or default to promotions page
        const promoLink = promotion.linkUrl || '/promotions.html';
        const imageUrl = promotion.imageUrl || '';
        const isExternal = promotion.type === 'event' && promotion.linkUrl && promotion.linkUrl.startsWith('http');
        const target = isExternal ? '_blank' : '_self';
        
        // Debug logging
        console.log('Showing promotion:', {
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
        
        this.initializeControls();
    }

    startCarousel() {
        this.carouselInterval = setInterval(() => {
            this.currentIndex = (this.currentIndex + 1) % this.promotions.length;
            this.showPromotion(this.currentIndex);
        }, 5000); 
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new PromotionBanner();
});
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
/**
 * Legal Modals Handler
 * Loads legal document content dynamically into modals
 */
document.addEventListener('DOMContentLoaded', function() {
    // Cache for loaded content
    const contentCache = {};
    
    // Helper function to extract and clean content from HTML pages
    function extractContentFromHTML(html) {
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        
        // Create a container for the cleaned content
        const container = document.createElement('div');
        
        // Extract the title
        const title = doc.querySelector('h1');
        if (title) {
            const titleElement = document.createElement('h1');
            titleElement.textContent = title.textContent.trim();
            container.appendChild(titleElement);
        }
        
        // Extract the effective date if it exists
        const paragraphs = doc.querySelectorAll('p');
        if (paragraphs.length > 0 && paragraphs[0].textContent.includes('Effective Date')) {
            const dateElement = document.createElement('p');
            dateElement.className = 'effective-date';
            dateElement.textContent = paragraphs[0].textContent.trim();
            container.appendChild(dateElement);
        }
        
        // Extract all other content
        const contentElements = doc.querySelectorAll('body > *');
        contentElements.forEach(element => {
            // Skip the title we already added
            if (element.tagName === 'H1') return;
            
            // Skip the first paragraph if it's the effective date we already added
            if (element.tagName === 'P' && element.textContent.includes('Effective Date') && 
                Array.from(paragraphs).indexOf(element) === 0) return;
            
            // Create a clean copy of the element
            const cleanElement = document.createElement(element.tagName);
            
            // Copy text content and clean it
            cleanElement.textContent = element.textContent.trim();
            
            // Copy important attributes for links
            if (element.tagName === 'A') {
                cleanElement.href = element.getAttribute('href');
                cleanElement.target = '_blank';
                cleanElement.rel = 'noopener';
            }
            
            // For lists, properly recreate list items
            if (element.tagName === 'UL' || element.tagName === 'OL') {
                element.querySelectorAll('li').forEach(li => {
                    const listItem = document.createElement('li');
                    listItem.textContent = li.textContent.trim();
                    cleanElement.appendChild(listItem);
                });
            }
            
            // Add the clean element to our container
            container.appendChild(cleanElement);
        });
        
        return container.innerHTML;
    }
    
    // Function to load content into modal
    function loadLegalContent(modalId, contentUrl) {
        const contentElement = document.getElementById(modalId + 'Content');
        
        // Return if already loaded
        if (contentCache[modalId]) {
            contentElement.innerHTML = contentCache[modalId];
            return;
        }
        
        // Set loading state
        contentElement.innerHTML = `
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p>Loading content...</p>
            </div>
        `;
        
        // Fetch the content
        fetch(contentUrl)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text();
            })
            .then(html => {
                // Extract and clean content
                const content = extractContentFromHTML(html);
                
                // Update modal content
                contentElement.innerHTML = content;
                
                // Cache the content
                contentCache[modalId] = content;
            })
            .catch(error => {
                console.error('Error loading content:', error);
                contentElement.innerHTML = `
                    <div class="alert alert-danger">
                        <p>Sorry, we couldn't load the content. Please try again later.</p>
                    </div>
                `;
            });
    }
    
    // Setup event listeners for each modal
    const modalMappings = [
        { id: 'privacyPolicy', url: 'privacy-policy.html' },
        { id: 'termsOfService', url: 'terms-of-service.html' },
        { id: 'smsTerms', url: 'sms-terms.html' }
    ];
    
    modalMappings.forEach(modal => {
        const modalElement = document.getElementById(modal.id + 'Modal');
        
        if (modalElement) {
            // Load content when modal is shown
            modalElement.addEventListener('show.bs.modal', function() {
                loadLegalContent(modal.id, modal.url);
            });
        }
    });
    
    // Add improved styling for legal content in modals
    const style = document.createElement('style');
    style.textContent = `
        .legal-modal .modal-body {
            padding: 2rem;
            color: #f8f9fa;
            background-color: #212529;
        }
        .legal-modal .modal-header {
            border-bottom: 1px solid #444;
            background-color: #212529;
            color: #f8f9fa;
        }
        .legal-modal .modal-content {
            background-color: #212529;
            border: 1px solid #444;
        }
        .legal-modal .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%);
        }
        .legal-modal h1 {
            font-size: 1.8rem;
            margin-bottom: 1.5rem;
            color: #f8f9fa;
            border-bottom: 1px solid #444;
            padding-bottom: 0.5rem;
        }
        .legal-modal h2 {
            font-size: 1.4rem;
            margin-top: 2rem;
            margin-bottom: 1rem;
            color: #f8f9fa;
        }
        .legal-modal p {
            font-size: 1rem;
            line-height: 1.7;
            margin-bottom: 1rem;
            color: #f8f9fa;
        }
        .legal-modal .effective-date {
            font-style: italic;
            margin-bottom: 1.5rem;
            color: #adb5bd;
        }
        .legal-modal ul, .legal-modal ol {
            padding-left: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .legal-modal li {
            margin-bottom: 0.5rem;
            color: #f8f9fa;
        }
        .legal-modal a {
            color: #d4af37;
            text-decoration: none;
        }
        .legal-modal a:hover {
            text-decoration: underline;
        }
    `;
    document.head.appendChild(style);
});
/**
 * Deep Linking support for Angel Granites product categories and inventory
 * 
 * This script enables deep linking to product categories via URL parameters
 * and to the inventory modal via URL hash.
 * For example: 
 * - ?category=monuments will open the monuments category
 * - #inventory will open the inventory modal
 */

document.addEventListener('DOMContentLoaded', function() {
    // Function to handle inventory modal deep linking via hash
    function handleInventoryDeepLink() {
        if (window.location.hash === '#inventory') {
            console.log('Deep linking: Opening inventory modal from hash');
            
            // Wait a moment for all scripts to load
            setTimeout(function() {
                // Try to find and click the inventory link
                const sideInventoryLink = document.getElementById('sideInventoryLink');
                const inventoryLink = document.getElementById('inventoryLink');
                
                if (sideInventoryLink) {
                    console.log('Deep linking: Clicking side inventory link');
                    sideInventoryLink.click();
                } else if (inventoryLink) {
                    console.log('Deep linking: Clicking footer inventory link');
                    inventoryLink.click();
                } else {
                    console.log('Deep linking: No inventory link found, trying direct modal open');
                    // Try to open the modal directly if available
                    if (typeof window.openInventoryModal === 'function') {
                        window.openInventoryModal();
                    }
                }
            }, 800);
        }
    }
    
    // Function to handle category opening based on URL parameters
    function handleCategoryDeepLink() {
        const urlParams = new URLSearchParams(window.location.search);
        const categoryParam = urlParams.get('category');
        
        if (categoryParam) {
            // Scroll to the featured products section
            const featuredProductsSection = document.getElementById('featured-products');
            if (featuredProductsSection) {
                featuredProductsSection.scrollIntoView({ behavior: 'smooth' });
                
                // Find the corresponding collection ID and category name
                let collectionId = '';
                let categoryName = '';
                
                switch(categoryParam) {
                    case 'mbna_2025':
                        collectionId = 'mbna_2025-collection';
                        categoryName = 'MBNA_2025';
                        break;
                    case 'monuments':
                        collectionId = 'monuments-collection';
                        categoryName = 'Monuments';
                        break;
                    case 'columbarium':
                        collectionId = 'columbarium-collection';
                        categoryName = 'Columbarium';
                        break;
                    case 'designs':
                        collectionId = 'Designs-collection';
                        categoryName = 'Designs';
                        break;
                    case 'benches':
                        collectionId = 'benches-collection';
                        categoryName = 'Benches';
                        break;
                }
                
                if (collectionId) {
                    // Allow time for page to render and smooth scroll
                    setTimeout(function() {
                        // Try multiple approaches to open the collection
                        
                        // APPROACH 1: Find and click the link with data-collection attribute
                        const categoryLinks = document.querySelectorAll('.category-link');
                        let targetLink = null;
                        
                        for (const link of categoryLinks) {
                            if (link.getAttribute('data-collection') === collectionId) {
                                targetLink = link;
                                break;
                            }
                        }
                        
                        if (targetLink) {
                            console.log('Deep linking: Found target link for', categoryParam);
                            // Try both jQuery click and native click
                            if (typeof jQuery !== 'undefined') {
                                jQuery(targetLink).trigger('click');
                            } else {
                                targetLink.click();
                            }
                            
                            // APPROACH 2: Direct modal creation if clicking doesn't work
                            setTimeout(function() {
                                // If clicking didn't work, try to create/show the modal directly
                                const modalExists = document.querySelector('#category-modal');
                                const modalDisplayed = modalExists && 
                                    window.getComputedStyle(modalExists).display !== 'none';
                                
                                if (!modalDisplayed && window.showCategoryModal && categoryName) {
                                    console.log('Deep linking: Attempting direct modal creation for', categoryName);
                                    // If the page has the showCategoryModal function, call it directly
                                    try {
                                        window.showCategoryModal(categoryName, []);
                                    } catch(e) {
                                        console.error('Deep linking: Error showing category modal:', e);
                                    }
                                }
                            }, 300);
                        }
                    }, 800);
                }
            }
        }
    }
    
    // Update browser history when a category link is clicked
    document.querySelectorAll('.category-link').forEach(function(link) {
        link.addEventListener('click', function() {
            // Don't prevent default - let the original click handler work
            
            // Update the URL without reloading the page
            const href = this.getAttribute('href');
            if (href && href.startsWith('?category=')) {
                const categoryValue = href.replace('?category=', '');
                const newUrl = window.location.pathname + '?category=' + categoryValue;
                
                // Update browser history state
                window.history.pushState({ category: categoryValue }, '', newUrl);
            }
        });
    });
    
    // Handle back/forward navigation
    window.addEventListener('popstate', function() {
        // Re-handle the URL parameters when navigation changes
        handleCategoryDeepLink();
    });
    
    // Initial check for deep links on page load
    handleCategoryDeepLink();
    handleInventoryDeepLink();
    
    // Update browser history when inventory link is clicked
    const inventoryLinks = [document.getElementById('sideInventoryLink'), document.getElementById('inventoryLink')];
    inventoryLinks.forEach(function(link) {
        if (link) {
            link.addEventListener('click', function(e) {
                // Don't prevent default here - let the original click handler work
                
                // Update the URL without reloading the page
                window.history.pushState({ inventory: true }, '', '#inventory');
            });
        }
    });
});
/**
 * Inventory Modal
 * 
 * This script creates a modal dialog that displays inventory data
 * from the monument.business API via a PHP proxy.
 * 
 * Version: 2.2.0 - Improved cache management (2025-07-14)
 * Version: 2.1.0 - Product codes hidden (2025-07-14)
 */
/**
 * Inventory Modal Handler
 * Fetches and displays inventory data from monument.business API in a modal
 */
document.addEventListener('DOMContentLoaded', function() {
    // Make sure both jQuery and Bootstrap are loaded before initializing
    const waitForDependencies = function(callback) {
        if (window.jQuery && window.bootstrap) {
            console.log('Both jQuery and Bootstrap are loaded, initializing inventory modal');
            callback();
        } else {
            console.log('Waiting for jQuery and Bootstrap to load...');
            setTimeout(function() {
                waitForDependencies(callback);
            }, 100);
        }
    };
    
    waitForDependencies(function() {
        // Add CSS for styling the inventory modal
        const style = document.createElement('style');
        style.textContent = `
            .inventory-modal .modal-dialog {
                max-width: 98%;
                margin: 1rem auto;
                height: 95vh;
            }
            .inventory-modal .modal-content {
                background-color: #212529;
                color: #f8f9fa;
                border: 1px solid #495057;
                height: 100%;
                display: flex;
                flex-direction: column;
                font-size: 1.1rem;
            }
            .inventory-modal .modal-header {
                border-bottom: 1px solid #343a40;
            }
            .inventory-modal .modal-footer {
                border-top: 1px solid #343a40;
            }
            .inventory-modal .modal-footer.sticky-bottom {
                position: sticky;
                bottom: 0;
                background-color: #212529;
                z-index: 9;
            }
            .inventory-modal .modal-body {
                flex: 1;
                overflow-y: auto;
                overflow-x: auto;
                padding: 1rem;
                position: relative;
            }
            /* Scroll indicator styles */
            .inventory-modal .scroll-indicator {
                position: fixed;
                bottom: 20px;
                right: 20px;
                background: linear-gradient(135deg, #d4af37 0%, #f4d03f 100%);
                color: #000000;
                padding: 12px 24px;
                border-radius: 25px;
                border: 3px solid #ffffff;
                display: none;
                z-index: 1000;
                font-size: 1.1rem;
                font-weight: bold;
                box-shadow: 0 6px 12px rgba(0,0,0,0.5), 0 0 0 2px rgba(212, 175, 55, 0.3);
                animation: pulse 2s infinite;
                text-shadow: 1px 1px 2px rgba(255,255,255,0.5);
            }
            @keyframes pulse {
                0% { transform: scale(1); }
                50% { transform: scale(1.05); }
                100% { transform: scale(1); }
            }
            /* Navigation buttons for better accessibility */
            .inventory-modal .nav-buttons {
                position: fixed;
                top: 50%;
                transform: translateY(-50%);
                z-index: 1000;
            }
            .inventory-modal .nav-button-left {
                left: 10px;
            }
            .inventory-modal .nav-button-right {
                right: 10px;
            }
            .inventory-modal .nav-button {
                background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%);
                color: #ffffff;
                border: 4px solid #ffffff;
                padding: 18px 22px;
                border-radius: 50%;
                font-size: 1.8rem;
                cursor: pointer;
                box-shadow: 0 8px 16px rgba(0,0,0,0.7), 0 0 0 3px rgba(255, 107, 53, 0.4), inset 0 2px 4px rgba(255,255,255,0.3);
                transition: all 0.3s ease;
                font-weight: 900;
                text-shadow: 2px 2px 4px rgba(0,0,0,0.8);
                position: relative;
                z-index: 1001;
            }
            .inventory-modal .nav-button:hover {
                background: linear-gradient(135deg, #ff8c42 0%, #ff6b35 100%);
                transform: scale(1.2);
                box-shadow: 0 10px 20px rgba(0,0,0,0.8), 0 0 0 4px rgba(255, 140, 66, 0.6), inset 0 2px 4px rgba(255,255,255,0.4);
                border-color: #ffffff;
            }
            .inventory-modal .nav-button:disabled {
                background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
                color: #ffffff;
                border-color: #adb5bd;
                cursor: not-allowed;
                transform: none;
                box-shadow: 0 2px 4px rgba(0,0,0,0.3);
                text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
            }
            .inventory-modal .table-responsive {
                overflow-x: auto;
                overflow-y: auto;
            }
            .inventory-modal .table-scroll-wrapper {
                position: relative;
            }
            .inventory-modal .table-scroll-wrapper .table-responsive {
                margin-right: 14px;
            }
            .inventory-modal .scrollbar-track {
                position: absolute;
                top: 0;
                right: 0;
                width: 12px;
                height: 100%;
                background: #343a40;
            }
            .inventory-modal .scrollbar-thumb {
                position: absolute;
                top: 0;
                right: 0;
                width: 100%;
                background-color: #6c757d;
                border-radius: 6px;
                cursor: pointer;
            }
            .inventory-modal .inventory-table {
                width: 100%;
                margin-bottom: 1rem;
                color: #212529;
                border-collapse: separate;
                border-spacing: 0;
                table-layout: auto;
                min-width: 100%;
            }
            .inventory-modal .inventory-table th,
            .inventory-modal .inventory-table td {
                padding: 1rem;
                vertical-align: middle;
                border-top: 1px solid #dee2e6;
                font-size: 1.1rem;
                min-width: 120px;
            }
            .inventory-modal .inventory-table thead th {
                vertical-align: bottom;
                border-bottom: 2px solid #dee2e6;
                background-color: #f8f9fa;
                position: sticky;
                top: 0;
                z-index: 5;
                font-weight: bold;
                font-size: 1.2rem;
                color: #212529;
            }
            .inventory-modal .inventory-table tbody tr:hover {
                background-color: #f1f1f1;
            }
            .inventory-modal .pagination {
                justify-content: center;
                margin-top: 1rem;
                flex-wrap: wrap;
            }
            .inventory-modal .page-link {
                background-color: #343a40;
                border-color: #495057;
                color: #f8f9fa;
            }
            .inventory-modal .page-item.active .page-link {
                background-color: #d4af37;
                border-color: #d4af37;
                color: #212529;
            }
            .inventory-modal .page-item.disabled .page-link {
                background-color: #212529;
                border-color: #495057;
                color: #6c757d;
            }
            .inventory-modal .btn-gold {
                background-color: #d4af37;
                border-color: #d4af37;
                color: #212529;
            }
            .inventory-modal .btn-gold:hover {
                background-color: #c4a030;
                border-color: #c4a030;
            }
            .inventory-modal .search-container {
                position: relative;
                margin-bottom: 1rem;
            }
            .inventory-modal .search-container .search-icon {
                position: absolute;
                left: 10px;
                top: 10px;
                color: #6c757d;
            }
            .inventory-modal .search-container input {
                padding: 0.75rem 0.75rem 0.75rem 35px;
                background-color: #343a40;
                border-color: #495057;
                color: #f8f9fa;
                font-size: 1.1rem;
                border-width: 2px;
            }
            .inventory-modal .search-container input::placeholder {
                color: #6c757d;
            }
            .inventory-modal .summary-count {
                color: #adb5bd;
                font-size: 0.85rem;
                font-weight: normal;
                background: transparent;
                padding: 0;
                margin: 0;
                opacity: 0.8;
            }
            .inventory-modal .active-filter select,
            .inventory-modal .active-filter input {
                border-color: #d4af37;
                font-weight: bold;
            }
            .inventory-modal .search-highlight {
                background-color: #fff3cd !important;
            }
            .inventory-modal .inventory-filters {
                margin-bottom: 1rem;
            }
            .inventory-modal .sticky-filters {
                position: sticky;
                top: 0;
                z-index: 9;
                background-color: #212529;
                padding-top: 0.5rem;
                box-shadow: 0 2px 4px rgba(0,0,0,0.3);
            }
            .inventory-modal .column-filter {
                background-color: #343a40;
                border-color: #495057;
                color: #f8f9fa;
                font-size: 1rem;
                padding: 0.5rem;
                border-width: 2px;
                min-width: 100px;
            }
            .inventory-modal #searchHelp {
                font-size: 0.85rem;
                margin-top: -0.5rem;
                margin-bottom: 0.5rem;
                color: #adb5bd;
            }
            .inventory-modal .no-results {
                text-align: center;
                padding: 2rem;
                color: #adb5bd;
            }
            .inventory-modal .loading {
                text-align: center;
                padding: 2rem;
            }
            .inventory-modal .spinner-border {
                width: 3rem;
                height: 3rem;
                color: #d4af37;
            }
            .inventory-modal .modal-body::-webkit-scrollbar {
                width: 12px;
            }
            .inventory-modal .modal-body::-webkit-scrollbar-track {
                background: #343a40;
            }
            .inventory-modal .modal-body::-webkit-scrollbar-thumb {
                background-color: #6c757d;
                border-radius: 6px;
                border: 3px solid #343a40;
            }
            /* Enhanced responsive styles for better accessibility */
            @media (max-width: 1200px) {
                .inventory-modal .modal-dialog {
                    max-width: 100%;
                    margin: 0.5rem;
                }
                .inventory-modal .modal-body {
                    padding: 0.75rem;
                }
                .inventory-modal .inventory-table th,
                .inventory-modal .inventory-table td {
                    font-size: 1rem;
                    padding: 0.75rem;
                }
            }
            @media (max-width: 767.98px) {
                .inventory-modal .modal-dialog {
                    max-width: 100%;
                    margin: 0.25rem;
                    height: 98vh;
                }
                .inventory-modal .modal-content {
                    font-size: 0.9rem;
                }
                .inventory-modal .sticky-filters {
                    position: static;
                    box-shadow: none;
                }
                .inventory-modal .nav-button {
                    width: 35px;
                    height: 35px;
                    font-size: 0.8rem;
                }
                .inventory-modal .nav-button-left {
                    left: 5px;
                }
                .inventory-modal .nav-button-right {
                    right: 5px;
                }
                .inventory-modal .inventory-table th,
                .inventory-modal .inventory-table td {
                    font-size: 0.8rem;
                    padding: 0.4rem;
                }
                .inventory-modal .search-container input {
                    font-size: 0.9rem;
                    padding: 0.4rem 0.8rem;
                }
                .inventory-modal .column-filter {
                    font-size: 0.8rem;
                    padding: 0.3rem;
                }
                .inventory-modal .summary-count {
                    font-size: 0.75rem;
                }
            }
            @media (max-width: 480px) {
                .inventory-modal .modal-dialog {
                    margin: 0.1rem;
                    height: 99vh;
                }
                .inventory-modal .modal-content {
                    font-size: 0.8rem;
                }
                .inventory-modal .nav-button {
                    width: 30px;
                    height: 30px;
                    font-size: 0.7rem;
                }
                .inventory-modal .inventory-table th,
                .inventory-modal .inventory-table td {
                    font-size: 0.7rem;
                    padding: 0.3rem;
                }
                .inventory-modal .search-container input {
                    font-size: 0.8rem;
                    padding: 0.3rem 0.6rem;
                }
                .inventory-modal .column-filter {
                    font-size: 0.7rem;
                    padding: 0.2rem;
                }
            }
            /* High contrast mode support */
            @media (prefers-contrast: high) {
                .inventory-modal .modal-content {
                    border-width: 3px;
                    border-color: #ffffff;
                }
                .inventory-modal .inventory-table th,
                .inventory-modal .inventory-table td {
                    border-width: 2px;
                }
                .inventory-modal .btn {
                    border-width: 3px;
                }
            }
            /* Reduced motion support */
            @media (prefers-reduced-motion: reduce) {
                .inventory-modal .scroll-indicator {
                    animation: none;
                }
                .inventory-modal .nav-button {
                    transition: none;
                }
            }
            /* Split Panel Styles */
            .inventory-modal .split-view {
                display: flex;
                gap: 0;
                height: 600px;
            }
            .inventory-modal .table-panel {
                flex: 1 1 auto;
                min-width: 0;
                overflow: hidden;
                display: flex;
                flex-direction: column;
                transition: none;
            }
            .inventory-modal .table-panel.with-details {
                flex: 0 0 55%;
                min-width: 400px;
            }
            
            /* Mobile Responsive Styles */
            @media (max-width: 768px) {
                .inventory-modal .split-view {
                    flex-direction: column;
                    height: auto;
                }
                .inventory-modal .table-panel {
                    flex: 1 1 auto;
                    max-height: 40vh;
                }
                .inventory-modal .table-panel.with-details {
                    flex: 1 1 auto;
                    max-height: 30vh;
                }
                .inventory-modal .details-panel {
                    flex: 1 1 auto;
                    max-height: 50vh;
                    border-left: none;
                    border-top: 3px solid #d4af37;
                }
                .inventory-modal .modal-dialog {
                    max-width: 95%;
                    margin: 0.5rem;
                }
                .inventory-modal .stone-card {
                    margin-bottom: 0.75rem;
                    padding: 0.75rem;
                }
                .inventory-modal .details-header {
                    padding: 1rem;
                }
                .inventory-modal .details-header h5 {
                    font-size: 1.1rem;
                }
                .inventory-modal .details-header small {
                    font-size: 0.85rem;
                }
                .inventory-table {
                    font-size: 0.85rem;
                }
                .inventory-table th,
                .inventory-table td {
                    padding: 0.4rem !important;
                }
            }
            
            .inventory-modal .details-panel {
                flex: 0 0 45%;
                background-color: #f8f9fa;
                border-left: 2px solid #d4af37;
                overflow-y: auto;
                overflow-x: hidden;
                display: none;
                padding: 0;
                position: relative;
            }
            .inventory-modal .details-panel.show {
                display: block;
            }
            .inventory-modal .details-panel .close-details {
                position: sticky;
                top: 1rem;
                float: right;
                margin: 1rem 1rem 0 0;
                background: #fff;
                border: 2px solid #d4af37;
                border-radius: 50%;
                width: 36px;
                height: 36px;
                font-size: 1.8rem;
                line-height: 1;
                color: #333;
                cursor: pointer;
                z-index: 1000;
                display: flex;
                align-items: center;
                justify-content: center;
                box-shadow: 0 2px 8px rgba(0,0,0,0.3);
                transition: all 0.2s ease;
            }
            .inventory-modal .details-panel .close-details:hover {
                background: #d4af37;
                color: #fff;
                transform: rotate(90deg) scale(1.1);
            }
            .inventory-modal .stone-card {
                background-color: #fff;
                border: 1px solid #dee2e6;
                border-radius: 8px;
                padding: 1rem;
                margin-bottom: 1rem;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            .inventory-modal .stone-card h6 {
                color: #d4af37;
                font-weight: bold;
                margin-bottom: 0.75rem;
                border-bottom: 2px solid #d4af37;
                padding-bottom: 0.5rem;
            }
            .inventory-modal .info-row {
                display: flex;
                justify-content: space-between;
                padding: 0.5rem 0;
                border-bottom: 1px solid #e9ecef;
            }
            .inventory-modal .info-row:last-child {
                border-bottom: none;
            }
            .inventory-modal .info-label {
                font-weight: bold;
                color: #495057;
            }
            .inventory-modal .info-value {
                color: #212529;
            }
            .inventory-modal .details-header {
                background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
                color: #fff;
                padding: 1.5rem;
                margin: -1.5rem -1.5rem 1.5rem -1.5rem;
                border-radius: 0;
            }
            .inventory-modal .details-header h5 {
                color: #d4af37;
                margin: 0 0 0.5rem 0;
            }
            .inventory-modal .details-loading {
                text-align: center;
                padding: 3rem 1rem;
                color: #666;
            }
            .inventory-table tbody tr {
                cursor: pointer;
                transition: background-color 0.2s ease;
            }
            .inventory-table tbody tr:hover {
                background-color: #e9ecef !important;
            }
            .inventory-table tbody tr.selected {
                background-color: #fff3cd !important;
                border-left: 3px solid #d4af37;
            }
        `;
        document.head.appendChild(style);

        // Class to handle API requests
        class InventoryAPI {
            constructor() {
                this.baseUrl = 'inventory-proxy.php';
                this.currentPage = 1;
                this.pageSize = 1000;
                this.totalItems = 0;
                this.totalPages = 0;
                this.forceRefresh = false; // Flag to force refresh and bypass cache
                this.cacheExpiryMinutes = 1440; // Cache for 24 hours (1440 minutes) - inventory doesn't change frequently
                this.currentFilters = {
                    ptype: '',
                    pcolor: '',
                    pdesign: '',
                    pfinish: '',
                    psize: '',
                    locid: ''
                };
                
                // Cache configuration
                this.cacheKey = 'inventoryData';
                this.cacheExpiryKey = 'inventoryDataExpiry';
                this.expiryDuration = this.cacheExpiryMinutes * 60 * 1000; // Convert minutes to milliseconds
            }

            /**
             * Fetch inventory data based on current filters and pagination
             * @returns {Promise} Promise resolving to inventory data
             */
            async fetchInventory() {
                try {
                    console.log('Fetching inventory data with filters:', this.currentFilters);
                    console.log('Current page:', this.currentPage);
                    console.log('Page size:', this.pageSize);
                    console.log('Selected location:', this.selectedLocation);
                    
                    // Check if we have any filters applied
                    const hasFilters = Object.values(this.currentFilters).some(val => val !== '');
                    const hasSearch = document.getElementById('inventorySearch')?.value?.trim() !== '';
                    
                    // Only use cache for unfiltered data and when not forcing refresh
                    if (!hasFilters && !hasSearch && !this.forceRefresh) {
                        // Check for cached data
                        const now = Date.now();
                        const cached = localStorage.getItem(this.cacheKey);
                        const expiry = localStorage.getItem(this.cacheExpiryKey);
                        
                        if (cached && expiry && now < parseInt(expiry)) {
                            console.log('Using cached inventory data (expires in', Math.round((parseInt(expiry) - now)/1000), 'seconds)');
                            return JSON.parse(cached);
                        } else {
                            console.log('Cache expired or not found, fetching fresh data');
                        }
                    } else if (this.forceRefresh) {
                        console.log('Force refresh requested, bypassing cache');
                    } else {
                        console.log('Filters or search applied, bypassing cache');
                    }
                    
                    // Define the location IDs to fetch based on the selected location
                    let locationIds = ['45555', '45587']; // Default: fetch both locations (Elberton 45555 and Barre 45587)
                    
                    // If a specific location is selected, only fetch that one
                    if (this.selectedLocation) {
                        if (this.selectedLocation === 'Elberton') {
                            locationIds = ['45555'];
                        } else if (this.selectedLocation === 'Barre') {
                            locationIds = ['45587'];
                        }
                        // If any other location is selected that we don't recognize, keep both IDs
                    }
                    
                    console.log('Fetching data for locations:', locationIds);
                    
                    // Set a timeout for each request to prevent hanging
                    const timeout = 15000; // 15 seconds timeout per request
                    
                    // Function to fetch all pages for a single location
                    const fetchAllPagesForLocation = async (locid) => {
                        console.log(`Fetching ALL pages for location ${locid}...`);
                        const allItems = [];
                        let currentPage = 1;
                        let hasMorePages = true;
                        let totalItems = 0;
                        
                        while (hasMorePages) {
                            try {
                                // Prepare request parameters for this page
                                const params = {
                                    ...this.currentFilters,
                                    page: currentPage,
                                    pageSize: this.pageSize,
                                    locid: locid,
                                    token: '097EE598BBACB8A8182BC9D4D7D5CFE609E4DB2AF4A3F1950738C927ECF05B6A',
                                    timestamp: Date.now() // Prevent caching
                                };
                                
                                console.log(`Fetching page ${currentPage} for location ${locid}...`);
                                
                                // Create abort controller for this request
                                const abortController = new AbortController();
                                if (!window._inventoryAbortControllers) {
                                    window._inventoryAbortControllers = [];
                                }
                                window._inventoryAbortControllers.push(abortController);
                                
                                // Create query string
                                const queryString = new URLSearchParams(params).toString();
                                
                                // Create timeout promise
                                const timeoutPromise = new Promise((_, reject) => {
                                    const timeoutId = setTimeout(() => {
                                        abortController.abort();
                                        reject(new Error(`Request timeout for location ${locid} page ${currentPage} after ${timeout}ms`));
                                    }, timeout);
                                    
                                    if (!window._inventoryTimeouts) {
                                        window._inventoryTimeouts = [];
                                    }
                                    window._inventoryTimeouts.push(timeoutId);
                                });
                                
                                // Fetch data with timeout
                                const fetchPromise = fetch(`inventory-proxy.php?${queryString}`, {
                                    method: 'GET',
                                    headers: {
                                        'Cache-Control': 'no-cache',
                                        'X-Requested-With': 'XMLHttpRequest'
                                    },
                                    signal: abortController.signal
                                })
                                .then(response => {
                                    if (!response.ok) {
                                        throw new Error(`HTTP error! Status: ${response.status}`);
                                    }
                                    return response.json();
                                });
                                
                                const data = await Promise.race([fetchPromise, timeoutPromise]);
                                
                                // Validate response
                                if (!data || data.error) {
                                    console.error(`Error for location ${locid} page ${currentPage}:`, data?.error || 'Unknown error');
                                    hasMorePages = false;
                                    break;
                                }
                                
                                // Get items from response
                                const items = data.Data || data.data || [];
                                console.log(`Location ${locid} page ${currentPage}: Got ${items.length} items`);
                                
                                if (items.length > 0) {
                                    // Add location name to each item
                                    // Use actual location name from API if available, otherwise map by location ID
                                    const processedItems = items.map(item => ({
                                        ...item,
                                        Locationname: item.Locationname || item.locationname || 
                                            (locid === '45555' ? 'Elberton' : locid === '45587' ? 'Barre' : `Location ${locid}`)
                                    }));
                                    
                                    allItems.push(...processedItems);
                                    
                                    // Get total count from first page
                                    if (currentPage === 1) {
                                        totalItems = data.Total || data.total || data.totalItems || 0;
                                        console.log(`Location ${locid} total items from API: ${totalItems}`);
                                    }
                                }
                                
                                // Check if we have more pages - Multiple conditions:
                                // 1. If no items returned, we're done
                                // 2. If items.length < pageSize, we're on the last page
                                // 3. If we have totalItems and allItems.length >= totalItems, we have everything
                                if (items.length === 0) {
                                    hasMorePages = false;
                                    console.log(`Location ${locid}: No items on page ${currentPage}, stopping`);
                                } else if (items.length < this.pageSize) {
                                    hasMorePages = false;
                                    console.log(`Location ${locid}: Last page reached (got ${items.length} items, pageSize: ${this.pageSize})`);
                                } else if (totalItems > 0 && allItems.length >= totalItems) {
                                    hasMorePages = false;
                                    console.log(`Location ${locid}: All items fetched (${allItems.length} of ${totalItems})`);
                                } else {
                                    currentPage++;
                                    console.log(`Location ${locid}: Continuing to page ${currentPage} (have ${allItems.length} items so far)`);
                                }
                                
                            } catch (error) {
                                console.error(`Error fetching page ${currentPage} for location ${locid}:`, error);
                                hasMorePages = false;
                            }
                        }
                        
                        console.log(`Location ${locid}: Fetched total of ${allItems.length} items across ${currentPage} pages`);
                        return {
                            success: true,
                            Data: allItems,
                            Total: totalItems || allItems.length,
                            location: locid
                        };
                    };
                    
                    // Fetch all pages for each location in parallel
                    const requests = locationIds.map(locid => fetchAllPagesForLocation(locid));
                    
                    // Wait for all location requests to complete
                    const results = await Promise.allSettled(requests);
                    console.log('All location data fetched:', results);
                    
                    // Combine the results from all locations
                    const combinedData = {
                        Data: [],
                        Total: 0
                    };
                    
                    // Process each location result
                    let hasValidData = false;
                    results.forEach((result, index) => {
                        const locationId = locationIds[index];
                        
                        // Check if the promise was fulfilled
                        if (result.status === 'fulfilled') {
                            const data = result.value;
                            console.log(`Processing data for location ${locationId}:`, {
                                itemCount: data.Data?.length || 0,
                                total: data.Total
                            });
                            
                            // Check if we have data
                            if (data && data.success && Array.isArray(data.Data) && data.Data.length > 0) {
                                hasValidData = true;
                                combinedData.Data = [...combinedData.Data, ...data.Data];
                                combinedData.Total += parseInt(data.Total, 10) || data.Data.length;
                            } else {
                                console.warn(`No data for location ${locationId}`);
                            }
                        } else {
                            // Promise was rejected
                            console.error(`Request failed for location ${locationId}:`, result.reason);
                        }
                    });
                    
                    console.log(`Combined data: ${combinedData.Data.length} items total (expected: ${combinedData.Total})`);
                    
                    // If no valid data was found, create a sample dataset for testing
                    if (!hasValidData) {
                        console.warn('No valid data received from API, using sample data');
                        combinedData.Data = [
                            {
                                Qty: 1,
                                Locationname: 'Elberton',
                                EndProductCode: 'SAMPLE-001',
                                EndProductDescription: 'Sample Product 1',
                                Ptype: 'Base',
                                PColor: 'Blue Pearl',
                                PDesign: 'Standard Design',
                                PFinish: 'Polished',
                                Size: '2-6 X 1-2 X 0-6'
                            },
                            {
                                Qty: 2,
                                Locationname: 'Barre',
                                EndProductCode: 'SAMPLE-002',
                                EndProductDescription: 'Sample Product 2',
                                Ptype: 'Monument',
                                PColor: 'Gray',
                                PDesign: 'Custom Design',
                                PFinish: 'Rough',
                                Size: '3-0 X 2-0 X 1-0'
                            }
                        ];
                        combinedData.Total = combinedData.Data.length;
                    }
                    
                    // Cache the combined data if no filters are applied
                    // Reuse the hasFilters and hasSearch variables from earlier in the function
                    if (!hasFilters && !hasSearch && hasValidData) {
                        console.log(`Caching inventory data for ${this.cacheExpiryMinutes} minute(s)`);
                        const now = Date.now();
                        // Store the timestamp with the data for cache freshness checking
                        const dataWithTimestamp = {
                            ...combinedData,
                            _cachedAt: now
                        };
                        localStorage.setItem(this.cacheKey, JSON.stringify(dataWithTimestamp));
                        localStorage.setItem(this.cacheExpiryKey, now + this.expiryDuration);
                    }
                    return combinedData;
                } catch (error) {
                    console.error('Error fetching inventory data:', error);
                    // Return sample data in case of error to prevent UI from breaking
                    return {
                        Data: [
                            {
                                Qty: 1,
                                Locationname: 'Elberton',
                                EndProductCode: 'ERROR-001',
                                EndProductDescription: 'Error loading data. Please try again.',
                                Ptype: 'Error',
                                PColor: 'N/A',
                                PDesign: 'N/A',
                                PFinish: 'N/A',
                                Size: 'N/A'
                            }
                        ],
                        Total: 1
                    };
                }
            }

            /**
             * Set filters for inventory data
             * @param {Object} filters - Filter values
             */
            setFilters(filters) {
                this.currentFilters = { ...this.currentFilters, ...filters };
                this.currentPage = 1; // Reset to first page when filters change
            }

            /**
             * Go to specific page
             * @param {Number} page - Page number
             */
            setPage(page) {
                if (page >= 1 && page <= this.totalPages) {
                    this.currentPage = page;
                }
            }

            /**
             * Go to next page if available
             */
            nextPage() {
                if (this.currentPage < this.totalPages) {
                    this.currentPage++;
                    return true;
                }
                return false;
            }

            /**
             * Set force refresh flag to bypass cache
             * @param {Boolean} force - Whether to force refresh
             */
            setForceRefresh(force = true) {
                this.forceRefresh = force;
                return this;
            }
            
            /**
             * Reset force refresh flag after use
             */
            resetForceRefresh() {
                this.forceRefresh = false;
                return this;
            }
            
            /**
             * Go to previous page if available
             */
            prevPage() {
                if (this.currentPage > 1) {
                    this.currentPage--;
                    return true;
                }
                return false;
            }

            /**
             * Get unique values for a specific field from the inventory data
             * @param {Array} data - Inventory data
             * @param {String} field - Field name to extract unique values from
             * @returns {Array} Array of unique values
             */
            getUniqueValues(data, field) {
                // If data is not properly structured, return empty array
                if (!data) {
                    console.warn(`getUniqueValues: data is null or undefined`);
                    return [];
                }
                
                // Handle both uppercase and lowercase 'Data' property
                const dataArray = data.Data || data.data || [];
                
                if (!Array.isArray(dataArray)) {
                    console.warn(`getUniqueValues: data array is not an array, it's ${typeof dataArray}`);
                    return [];
                }
                
                // Create lowercase version of the field name for case-insensitive matching
                const fieldLower = field.toLowerCase();
                
                // Extract values, handling both exact case and lowercase field names
                const values = dataArray.map(item => {
                    // Try direct field access first
                    if (item[field] !== undefined) {
                        return item[field];
                    }
                    
                    // If not found, try case-insensitive matching
                    const keys = Object.keys(item);
                    const matchingKey = keys.find(key => key.toLowerCase() === fieldLower);
                    return matchingKey ? item[matchingKey] : null;
                }).filter(Boolean); // Remove null/undefined values
                
                console.log(`Found ${values.length} unique values for field ${field}`);
                return [...new Set(values)].sort();
            }
        }

        // Initialize API client
        let api = new InventoryAPI();

        // Keep a reference to the Bootstrap modal instance
        let inventoryModalInstance = null;

        // Handler references for event cleanup
        let closeBtnHandler;
        let refreshDataHandler;
        let searchInputHandler;
        let filterChangeHandler;
        let paginationClickHandler;
        let escKeyHandler;

        // Utility to decode HTML entities
        function decodeHtml(str) {
            const txt = document.createElement('textarea');
            txt.innerHTML = str;
            return txt.value;
        }

        // Image cache to avoid repeated API calls
        const imageCache = new Map();

        // Function to extract design code from item (AG-###, AS-###)
        function extractDesignCode(item) {
            // Try PDesign field first, then fall back to description
            const design = item.PDesign || item.pdesign || item.Design || item.design || '';
            const description = item.EndProductDescription || item.endproductdescription || '';
            const searchText = design || description;
            
            const match = searchText.match(/\b(AG|AS)-?\d+\b/i);
            return match ? match[0].toUpperCase() : null;
        }

        // Function to search for product images by design code
        async function searchProductImages(designCode) {
            if (!designCode) return [];

            // Check cache first
            if (imageCache.has(designCode)) {
                return imageCache.get(designCode);
            }

            try {
                // Search across all product categories
                const response = await fetch(`get_directory_files.php?search=${encodeURIComponent(designCode)}`);
                if (!response.ok) throw new Error('Image search failed');
                
                const data = await response.json();
                const images = [];

                if (data.success && data.files && Array.isArray(data.files)) {
                    // Filter for actual image files and deduplicate
                    const seenPaths = new Set();
                    data.files.forEach(file => {
                        // All files from the search API are already images, just deduplicate
                        if (file.path && !seenPaths.has(file.path)) {
                            seenPaths.add(file.path);
                            images.push({
                                path: file.path,
                                name: file.name || file.fullname || '',
                                category: file.category || ''
                            });
                        }
                    });
                }

                // Cache the result (even if empty to avoid repeated failed searches)
                imageCache.set(designCode, images);
                return images;
            } catch (error) {
                console.error(`Error searching images for ${designCode}:`, error);
                imageCache.set(designCode, []); // Cache empty result to avoid retry
                return [];
            }
        }

        function parseNumericValue(value) {
            if (value === null || value === undefined) return null;
            if (typeof value === 'number') return Number.isFinite(value) ? value : null;

            const str = String(value).replace(/,/g, '').trim();
            if (!str) return null;

            const match = str.match(/-?\d+(?:\.\d+)?/);
            if (!match) return null;

            const num = Number(match[0]);
            return Number.isFinite(num) ? num : null;
        }

        function parseDimensionTriplet(value) {
            if (value === null || value === undefined) return null;

            const str = String(value).trim();
            if (!str) return null;

            const match = str.match(/(\d+(?:\.\d+)?)\s*[x×*]\s*(\d+(?:\.\d+)?)\s*[x×*]\s*(\d+(?:\.\d+)?)/i);
            if (!match) return null;

            const a = parseNumericValue(match[1]);
            const b = parseNumericValue(match[2]);
            const c = parseNumericValue(match[3]);

            if (!a || !b || !c) return null;
            const unitHint = /\b(ft|feet|foot)\b|\'/i.test(str) ? 'ft' : null;
            return { length: a, width: b, height: c, unitHint };
        }

        function getUnitHintFromStone(stone) {
            if (!stone) return null;

            const unitCandidate =
                stone.Unit ?? stone.unit ?? stone.UOM ?? stone.uom ?? stone.Uom ?? stone.DimUnit ?? stone.dimUnit ?? stone.DimensionUnit ?? stone.dimensionUnit;
            if (unitCandidate === null || unitCandidate === undefined) return null;

            const str = String(unitCandidate).trim();
            if (!str) return null;

            if (/\b(ft|feet|foot)\b|\'/i.test(str)) return 'ft';
            if (/\b(in|inch|inches)\b|\"/i.test(str)) return 'in';
            return null;
        }

        function shouldTreatAsFeetByHeuristic(dims) {
            if (!dims) return false;
            const values = [dims.length, dims.width, dims.height].filter(v => typeof v === 'number' && Number.isFinite(v));
            if (values.length !== 3) return false;
            const min = Math.min(...values);
            const max = Math.max(...values);
            return min > 0 && min < 1 && max <= 20;
        }

        function toInchesIfNeeded(dims, unitHint) {
            if (!dims) return null;
            const hint = unitHint || null;

            const treatAsFeet = hint === 'ft' || (!hint && shouldTreatAsFeetByHeuristic(dims));
            if (!treatAsFeet) return { length: dims.length, width: dims.width, height: dims.height };

            return {
                length: dims.length * 12,
                width: dims.width * 12,
                height: dims.height * 12
            };
        }

        function getStoneDimensionsInches(stone) {
            if (!stone) return null;

            const unitHintFromStone = getUnitHintFromStone(stone);

            const length = parseNumericValue(
                stone.Length ?? stone.length ?? stone.L ?? stone.l ?? stone.Len ?? stone.len ?? stone.LengthIn ?? stone.lengthIn
            );
            const width = parseNumericValue(
                stone.Width ?? stone.width ?? stone.W ?? stone.w ?? stone.Wid ?? stone.wid ?? stone.WidthIn ?? stone.widthIn
            );
            const height = parseNumericValue(
                stone.Height ?? stone.height ?? stone.H ?? stone.h ?? stone.Ht ?? stone.ht ?? stone.HeightIn ?? stone.heightIn
            );

            if (length && width && height) {
                return toInchesIfNeeded({ length, width, height }, unitHintFromStone);
            }

            const dimSource =
                stone.Dimensions ?? stone.dimensions ?? stone.Dimension ?? stone.dimension ?? stone.Size ?? stone.size ?? stone.Measurement ?? stone.measurement;
            const parsed = parseDimensionTriplet(dimSource);
            if (parsed) {
                const unitHint = unitHintFromStone || parsed.unitHint;
                return toInchesIfNeeded({ length: parsed.length, width: parsed.width, height: parsed.height }, unitHint);
            }

            return null;
        }

        function computeEstimatedWeightPoundsFromInches(stone) {
            const dims = getStoneDimensionsInches(stone);
            if (!dims) return null;

            const { length, width, height } = dims;
            if (!(length > 0 && width > 0 && height > 0)) return null;

            const cubicFeet = (length * width * height) / 1728;
            const cubicMeters = cubicFeet / 35.28;
            const pounds = cubicMeters * 3000;

            if (!Number.isFinite(pounds) || pounds <= 0) return null;
            return pounds;
        }

        function renderStoneWeightRow(stone) {
            const apiWeight = parseNumericValue(stone?.Weight ?? stone?.weight);
            if (apiWeight) {
                return `
                    <div class="info-row">
                        <span class="info-label">Weight:</span>
                        <span class="info-value">${apiWeight} lbs</span>
                    </div>
                `;
            }

            const estimated = computeEstimatedWeightPoundsFromInches(stone);
            if (!estimated) return '';

            const rounded = Math.round(estimated);
            return `
                <div class="info-row">
                    <span class="info-label">Weight:</span>
                    <span class="info-value">${rounded} lbs</span>
                </div>
            `;
        }
        
        // Function to close item details panel
        window.closeItemDetails = function() {
            const detailsPanel = document.getElementById('detailsPanel');
            const tablePanel = document.getElementById('tablePanel');
            const selectedRows = document.querySelectorAll('.inventory-table tbody tr.selected');
            
            if (detailsPanel) {
                detailsPanel.classList.remove('show');
                // Reset content to default state
                const content = document.getElementById('detailsPanelContent');
                if (content) {
                    content.innerHTML = `
                        <div class="details-loading">
                            <i class="fas fa-hand-pointer fa-3x mb-3" style="color: #d4af37;"></i>
                            <p>Click on any item to view details</p>
                        </div>
                    `;
                }
            }
            if (tablePanel) {
                tablePanel.classList.remove('with-details');
            }
            selectedRows.forEach(row => row.classList.remove('selected'));
        };
        
        // Function to fetch detailed stone records for an item
        async function fetchItemDetails(endProductCode) {
            try {
                const response = await fetch('inventory-proxy.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=getDetails&epcode=${encodeURIComponent(endProductCode)}`
                });
                
                if (!response.ok) {
                    throw new Error('Failed to fetch item details');
                }
                
                const data = await response.json();
                return data;
            } catch (error) {
                console.error('Error fetching item details:', error);
                throw error;
            }
        }
        
        // Function to show item details in split panel
        async function showItemDetails(endProductCode, basicItem, clickedRow) {
            const detailsPanel = document.getElementById('detailsPanel');
            const tablePanel = document.getElementById('tablePanel');
            const content = document.getElementById('detailsPanelContent');
            
            if (!detailsPanel || !content) {
                console.error('Details panel not found');
                return;
            }
            
            // Remove previous selection and add to clicked row
            document.querySelectorAll('.inventory-table tbody tr.selected').forEach(row => {
                row.classList.remove('selected');
            });
            if (clickedRow) {
                clickedRow.classList.add('selected');
            }
            
            // Show panels
            tablePanel.classList.add('with-details');
            detailsPanel.classList.add('show');
            
            // Show loading state
            content.innerHTML = `
                <div class="details-loading">
                    <div class="spinner-border" role="status" style="color: #d4af37;">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-3">Loading stone details...</p>
                </div>
            `;
            
            try {
                const data = await fetchItemDetails(endProductCode);
                const stones = data.stones || [];
                
                if (stones.length === 0) {
                    content.innerHTML = `
                        <div class="details-header">
                            <h5>Item Details</h5>
                            <small style="color: #adb5bd;">${basicItem.description}</small>
                        </div>
                        <div style="padding: 1rem;">
                            <div class="stone-card">
                                <h6>Product Information</h6>
                                <div class="info-row">
                                    <span class="info-label">Type:</span>
                                    <span class="info-value">${basicItem.type}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Color:</span>
                                    <span class="info-value">${basicItem.color}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Size:</span>
                                    <span class="info-value">${basicItem.size}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Total Quantity:</span>
                                    <span class="info-value">${basicItem.quantity}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Location:</span>
                                    <span class="info-value">${basicItem.location}</span>
                                </div>
                            </div>
                            <p class="text-muted mt-3"><i class="fas fa-info-circle"></i> Detailed stone records not available.</p>
                        </div>
                    `;
                    return;
                }
                
                // Show basic info and individual stones
                let html = `
                    <div class="details-header">
                        <h5>Item Details</h5>
                        <small style="color: #adb5bd;">${basicItem.description}</small>
                    </div>
                    <div style="padding: 0;">
                        <div class="stone-card">
                            <h6><i class="fas fa-info-circle"></i> Product Summary</h6>
                            <div class="info-row">
                                <span class="info-label">Type:</span>
                                <span class="info-value">${basicItem.type}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Color:</span>
                                <span class="info-value">${basicItem.color}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Size:</span>
                                <span class="info-value">${basicItem.size}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Total Available:</span>
                                <span class="info-value" style="color: #28a745; font-weight: bold;">${basicItem.quantity}</span>
                            </div>
                        </div>
                        
                        <h6 class="mt-3 mb-3" style="color: #495057; padding: 0 1rem;"><i class="fas fa-cubes"></i> Individual Stones (${stones.length})</h6>
                    </div>
                `;
                
                stones.forEach((stone, index) => {
                    html += `
                        <div class="stone-card">
                            <h6>Stone #${index + 1}</h6>
                            ${stone.Container ? `
                                <div class="info-row">
                                    <span class="info-label">Container:</span>
                                    <span class="info-value">${stone.Container}</span>
                                </div>
                            ` : ''}
                            ${stone.CrateNo ? `
                                <div class="info-row">
                                    <span class="info-label">Crate Number:</span>
                                    <span class="info-value">${stone.CrateNo}</span>
                                </div>
                            ` : ''}
                            ${stone.LocationName ? `
                                <div class="info-row">
                                    <span class="info-label">Location:</span>
                                    <span class="info-value">${stone.LocationName}</span>
                                </div>
                            ` : ''}
                            ${stone.SublocationName ? `
                                <div class="info-row">
                                    <span class="info-label">Sublocation:</span>
                                    <span class="info-value">${stone.SublocationName}</span>
                                </div>
                            ` : ''}
                            ${renderStoneWeightRow(stone)}
                            ${stone.Status ? `
                                <div class="info-row">
                                    <span class="info-label">Status:</span>
                                    <span class="info-value">${stone.Status}</span>
                                </div>
                            ` : ''}
                            ${stone.StockId ? `
                                <div class="info-row">
                                    <span class="info-label">Stock ID:</span>
                                    <span class="info-value">${stone.StockId}</span>
                                </div>
                            ` : ''}
                            ${stone.Comments ? `
                                <div class="info-row">
                                    <span class="info-label">Notes:</span>
                                    <span class="info-value">${stone.Comments}</span>
                                </div>
                            ` : ''}
                        </div>
                    `;
                });
                
                content.innerHTML = html;
                
                // Load and display product images at the bottom
                const designCode = extractDesignCode(basicItem);
                if (designCode) {
                    const images = await searchProductImages(designCode);
                    if (images.length > 0) {
                        const imagesSection = document.createElement('div');
                        imagesSection.className = 'stone-card';
                        imagesSection.style.marginTop = '1rem';
                        imagesSection.innerHTML = `
                            <h6><i class="fas fa-images"></i> Product Images (${images.length})</h6>
                            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 0.75rem; margin-top: 0.75rem;">
                                ${images.map(img => `
                                    <div style="position: relative; padding-top: 100%; background: #f0f0f0; border-radius: 4px; overflow: hidden; cursor: pointer;" onclick="window.open('${img.path}', '_blank')">
                                        <img src="${img.path}" alt="${img.name}" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover;" onerror="this.parentElement.innerHTML='<span style=\'position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 0.7rem; color: #999;\'>Error</span>';">
                                    </div>
                                `).join('')}
                            </div>
                            <p class="text-muted mt-2" style="font-size: 0.85rem; margin-bottom: 0;"><i class="fas fa-info-circle"></i> Click any image to view full size</p>
                        `;
                        content.appendChild(imagesSection);
                    }
                }
            } catch (error) {
                content.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Error loading details</strong>
                        <p>${error.message}</p>
                    </div>
                `;
            }
        }
        
        // Function to create the modal HTML if it doesn't exist
        function createModal() {
            // Check if modal already exists
            if (document.getElementById('inventoryModal')) {
                console.log('Inventory modal already exists in DOM');
                return true; // Modal exists
            }
            
            console.log('Creating inventory modal...');
            
            try {
                // Create the modal element directly
                const modalDiv = document.createElement('div');
                modalDiv.className = 'modal fade inventory-modal';
                modalDiv.id = 'inventoryModal';
                modalDiv.tabIndex = '-1';
                modalDiv.setAttribute('aria-labelledby', 'inventoryModalLabel');
                modalDiv.setAttribute('aria-hidden', 'true');
                
                // Create modal dialog
                const modalDialog = document.createElement('div');
                modalDialog.className = 'modal-dialog modal-xl';
                modalDiv.appendChild(modalDialog);
                
                // Create modal content
                const modalContent = document.createElement('div');
                modalContent.className = 'modal-content';
                modalDialog.appendChild(modalContent);
                
                // Create modal header
                const modalHeader = document.createElement('div');
                modalHeader.className = 'modal-header';
                modalContent.appendChild(modalHeader);
                
                // Create modal title
                const modalTitle = document.createElement('h5');
                modalTitle.className = 'modal-title';
                modalTitle.id = 'inventoryModalLabel';
                modalTitle.textContent = 'Current Inventory';
                modalHeader.appendChild(modalTitle);
                
                // Create close button
                const closeButton = document.createElement('button');
                closeButton.type = 'button';
                closeButton.className = 'btn-close';
                closeButton.id = 'inventoryModalCloseBtn';
                closeButton.setAttribute('data-bs-dismiss', 'modal');
                closeButton.setAttribute('aria-label', 'Close');
                modalHeader.appendChild(closeButton);
                
                // Create modal body
                const modalBody = document.createElement('div');
                modalBody.className = 'modal-body';
                modalContent.appendChild(modalBody);
                
                // Create content container
                const contentDiv = document.createElement('div');
                contentDiv.id = 'inventoryModalContent';
                modalBody.appendChild(contentDiv);
                
                // Create scroll indicator
                const scrollIndicator = document.createElement('div');
                scrollIndicator.className = 'scroll-indicator';
                const icon = document.createElement('i');
                icon.className = 'fas fa-arrow-down';
                scrollIndicator.appendChild(icon);
                scrollIndicator.appendChild(document.createTextNode(' Scroll for more items'));
                modalBody.appendChild(scrollIndicator);


                
                // Create modal footer with sticky positioning
                const modalFooter = document.createElement('div');
                modalFooter.className = 'modal-footer sticky-bottom d-flex flex-wrap justify-content-between';
                modalContent.appendChild(modalFooter);

                // Reset filters button
                const resetBtn = document.createElement('button');
                resetBtn.type = 'button';
                resetBtn.className = 'btn btn-outline-secondary';
                resetBtn.id = 'resetFiltersBtn';
                resetBtn.textContent = 'Reset Filters';
                modalFooter.appendChild(resetBtn);

                // Summary count display
                const summaryDiv = document.createElement('div');
                summaryDiv.className = 'summary-count';
                summaryDiv.id = 'summaryCount';
                summaryDiv.textContent = 'Loading...';
                modalFooter.appendChild(summaryDiv);

                // Refresh data button
                const refreshBtn = document.createElement('button');
                refreshBtn.type = 'button';
                refreshBtn.className = 'btn btn-gold';
                refreshBtn.id = 'refreshInventoryBtn';
                const refreshIcon = document.createElement('i');
                refreshIcon.className = 'fas fa-sync-alt';
                refreshBtn.appendChild(refreshIcon);
                refreshBtn.appendChild(document.createTextNode(' Refresh Data'));
                modalFooter.appendChild(refreshBtn);

                // Close button
                const closeBtn = document.createElement('button');
                closeBtn.type = 'button';
                closeBtn.className = 'btn btn-secondary';
                closeBtn.id = 'closeInventoryBtn';
                closeBtn.textContent = 'Close';
                modalFooter.appendChild(closeBtn);
                
                // Append the complete modal to the document body
                document.body.appendChild(modalDiv);
                
                // Verify the modal was added to the DOM
                const modalCheck = document.getElementById('inventoryModal');
                console.log('Modal created and added to DOM:', !!modalCheck);

                return !!modalCheck; // Return true if modal was created successfully
            } catch (error) {
                console.error('Error creating modal:', error);
                return false; // Failed to create modal
            }
        }

        // Function to add listeners to modal buttons
        function addModalButtonListeners() {
            const footerBtn = document.getElementById('closeInventoryBtn');
            const headerBtn = document.getElementById('inventoryModalCloseBtn');
            const refreshBtn = document.getElementById('refreshInventoryBtn');
            const resetBtn = document.getElementById('resetFiltersBtn');

            // Close button handlers
            [footerBtn, headerBtn].forEach(btn => {
                if (btn) {
                    // Ensure button triggers the Bootstrap dismissal
                    btn.setAttribute('data-bs-dismiss', 'modal');
                    // Remove any existing listener to avoid duplicates
                    btn.removeEventListener('click', closeBtnHandler);
                }
            });

            closeBtnHandler = function() {
                if (inventoryModalInstance) {
                    inventoryModalInstance.hide();
                }
            };

            [footerBtn, headerBtn].forEach(btn => {
                if (btn) {
                    btn.addEventListener('click', closeBtnHandler);
                }
            });
            
            // Refresh button handler - force refresh data bypassing cache
            if (refreshBtn) {
                // Remove any existing listener to avoid duplicates
                refreshBtn.removeEventListener('click', refreshDataHandler);
                
                refreshDataHandler = function() {
                    console.log('Force refreshing inventory data...');
                    // Set force refresh flag to bypass cache
                    api.setForceRefresh(true);
                    loadInventoryData().then(() => {
                        // Reset the flag after use
                        api.resetForceRefresh();
                    });
                };
                
                refreshBtn.addEventListener('click', refreshDataHandler);
            }
        }

        // Function to load and display inventory data
        async function loadInventoryData() {
            const contentDiv = document.getElementById('inventoryModalContent');
            if (!contentDiv) {
                console.error('Inventory modal content div not found');
                return;
            }
            
            // Initialize timeout tracking array if it doesn't exist
            if (!window._inventoryTimeouts) {
                window._inventoryTimeouts = [];
            }
            
            // Show loading indicator
            contentDiv.innerHTML = `
                <div class="loading">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading inventory data...</p>
                </div>
            `;
            
            try {
                // Set a timeout to prevent the request from hanging indefinitely
                let timeoutId;
                const timeoutPromise = new Promise((_, reject) => {
                    timeoutId = setTimeout(() => {
                        console.log('Request timeout triggered after 15 seconds');
                        reject(new Error('Request timeout after 15 seconds'));
                    }, 15000);
                    
                    // Track this timeout for cleanup
                    window._inventoryTimeouts.push(timeoutId);
                });
                
                // Race between the API call and the timeout
                const data = await Promise.race([
                    api.fetchInventory(),
                    timeoutPromise
                ]);
                
                // Clear the timeout since the request completed
                if (timeoutId) {
                    clearTimeout(timeoutId);
                    // Remove from tracking array
                    const index = window._inventoryTimeouts.indexOf(timeoutId);
                    if (index > -1) {
                        window._inventoryTimeouts.splice(index, 1);
                    }
                }
                
                // Check if we have data - handle both uppercase and lowercase 'data' property
                const inventoryItems = data?.Data || data?.data || [];
                console.log('Inventory items for rendering:', inventoryItems.length);

                // Apply column filters and search term client-side
                const searchVal = (document.getElementById('inventorySearch')?.value || '').toLowerCase().trim();
                const { ptype, pcolor, pdesign, pfinish, psize } = api.currentFilters;
                const locFilter = api.selectedLocation || '';

                const getFieldVal = (obj, field) => {
                    if (obj[field] !== undefined) return String(obj[field]);
                    const key = Object.keys(obj).find(k => k.toLowerCase() === field.toLowerCase());
                    return key ? String(obj[key]) : '';
                };

                let filteredItems = inventoryItems.filter(item => {
                    const typeMatch = !ptype || getFieldVal(item, 'Ptype').toLowerCase() === ptype.toLowerCase();
                    const colorMatch = !pcolor || getFieldVal(item, 'PColor').toLowerCase() === pcolor.toLowerCase();
                    const designMatch = !pdesign || getFieldVal(item, 'PDesign').toLowerCase() === pdesign.toLowerCase();
                    const finishMatch = !pfinish || getFieldVal(item, 'PFinish').toLowerCase() === pfinish.toLowerCase();
                    const sizeMatch = !psize || getFieldVal(item, 'Size').toLowerCase() === psize.toLowerCase();
                    const locationMatch = !locFilter || getFieldVal(item, 'Locationname').toLowerCase() === locFilter.toLowerCase();
                    const rowText = Object.values(item).join(' ').toLowerCase();
                    const searchMatch = !searchVal || rowText.includes(searchVal);
                    return typeMatch && colorMatch && designMatch && finishMatch && sizeMatch && locationMatch && searchMatch;
                });
                
                if (!Array.isArray(filteredItems) || filteredItems.length === 0) {
                    contentDiv.innerHTML = `
                        <div class="no-results">
                            <i class="fas fa-exclamation-circle fa-3x mb-3"></i>
                            <h4>No inventory items found</h4>
                            <p>Try adjusting your filters or refreshing the data.</p>
                            <button class="btn btn-gold mt-3" id="retryLoadBtn">
                                <i class="fas fa-sync-alt"></i> Retry
                            </button>
                        </div>
                    `;
                    
                    // Add retry button listener
                    const retryBtn = document.getElementById('retryLoadBtn');
                    if (retryBtn) {
                        retryBtn.addEventListener('click', loadInventoryData);
                    }
                    return;
                }
                
                // Get unique values for filters
                const productTypes = api.getUniqueValues({ Data: inventoryItems }, 'Ptype');
                const productColors = api.getUniqueValues({ Data: inventoryItems }, 'PColor');
                const productDesigns = api.getUniqueValues({ Data: inventoryItems }, 'PDesign');
                const productFinishes = api.getUniqueValues({ Data: inventoryItems }, 'PFinish');
                const productSizes = api.getUniqueValues({ Data: inventoryItems }, 'Size');
                const locations = api.getUniqueValues({ Data: inventoryItems }, 'Locationname');
                
                // Helper function to create option elements with selected state
                const escapeHtml = (str) => {
                    return String(str)
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#39;');
                };

                const createOptions = (items, selectedValue) => {
                    return items.map(item => {
                        const selected = item === selectedValue ? 'selected' : '';
                        const escapedVal = escapeHtml(item);
                        const escapedText = escapeHtml(item);
                        return `<option value="${escapedVal}" ${selected}>${escapedText}</option>`;
                    }).join('');
                };
                
                // Get current filter values
                const currentPtype = api.currentFilters.ptype || '';
                const currentPcolor = api.currentFilters.pcolor || '';
                const currentPdesign = api.currentFilters.pdesign || '';
                const currentPfinish = api.currentFilters.pfinish || '';
                const currentPsize = api.currentFilters.psize || '';
                const currentLocation = api.selectedLocation || '';
                
                console.log('Current filter values:', {
                    ptype: currentPtype,
                    pcolor: currentPcolor,
                    pdesign: currentPdesign,
                    pfinish: currentPfinish,
                    psize: currentPsize,
                    location: currentLocation
                });
                
                // Build the search HTML with selected values
                const searchHtml = `
                    <div class="inventory-filters sticky-filters">
                        <div class="search-container">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" class="form-control" id="inventorySearch" placeholder="Search by description, type, color, or any attribute..." value="${searchVal || ''}">
                        </div>
                        <div id="searchHelp" class="form-text">Search by description, type, color, or any attribute</div>
                    </div>
                    <div class="d-flex justify-content-end align-items-center mb-2">
                        <span id="activeBadge" class="badge bg-secondary" style="display:none;">Filters Active</span>
                    </div>
                `;
                
                // Build the table HTML with split-view structure
                const tableHtml = `
                    <div class="split-view">
                        <div class="table-panel" id="tablePanel">
                            <div class="table-scroll-wrapper" style="max-height: 65vh;">
                                <div id="inventoryTableContainer" class="table-responsive" style="max-height: 65vh; overflow-y: auto; overflow-x: auto;">
                                    <table id="inventoryTable" class="inventory-table table table-striped table-sm table-hover table-bordered align-middle w-100">
                                    <thead>
                                        <tr>
                                            <!-- Product Code column hidden per client request -->
                                            <th style="width: 80px;">Image</th>
                                            <th>Description</th>
                                            <th>
                                                Type
                                                <select class="form-select form-select-sm column-filter mt-1" id="typeFilter" data-col-index="2">
                                                    <option value="" ${currentPtype === '' ? 'selected' : ''}>All</option>
                                                    ${createOptions(productTypes, currentPtype)}
                                                </select>
                                            </th>
                                            <th>
                                                Color
                                                <select class="form-select form-select-sm column-filter mt-1" id="colorFilter" data-col-index="3">
                                                    <option value="" ${currentPcolor === '' ? 'selected' : ''}>All</option>
                                                    ${createOptions(productColors, currentPcolor)}
                                                </select>
                                            </th>
                                            <th>
                                                Design
                                                <select class="form-select form-select-sm column-filter mt-1" id="designFilter" data-col-index="4">
                                                    <option value="" ${currentPdesign === '' ? 'selected' : ''}>All</option>
                                                    ${createOptions(productDesigns, currentPdesign)}
                                                </select>
                                            </th>
                                            <th>
                                                Finish
                                                <select class="form-select form-select-sm column-filter mt-1" id="finishFilter" data-col-index="5">
                                                    <option value="" ${currentPfinish === '' ? 'selected' : ''}>All</option>
                                                    ${createOptions(productFinishes, currentPfinish)}
                                                </select>
                                            </th>
                                            <th>
                                                Size
                                                <select class="form-select form-select-sm column-filter mt-1" id="sizeFilter" data-col-index="6">
                                                    <option value="" ${currentPsize === '' ? 'selected' : ''}>All</option>
                                                    ${createOptions(productSizes, currentPsize)}
                                                </select>
                                            </th>
                                            <th>
                                                Location
                                                <select class="form-select form-select-sm column-filter mt-1" id="locationFilter" data-col-index="7">
                                                    <option value="" ${currentLocation === '' ? 'selected' : ''}>All</option>
                                                    ${createOptions(locations, currentLocation)}
                                                </select>
                                            </th>
                                            <th role="columnheader" tabindex="0">Quantity</th>
                                        </tr>
                                    </thead>
                                    <tbody id="inventoryTableBody">
                                        ${filteredItems.map(item => {
                                            // Helper function to get field value with case-insensitive matching
                                            const getField = (fieldName) => {
                                                if (item[fieldName] !== undefined) return item[fieldName];

                                                // Try lowercase matching
                                                const lowerField = fieldName.toLowerCase();
                                                const key = Object.keys(item).find(k => k.toLowerCase() === lowerField);
                                                return key ? item[key] : '';
                                            };

                                            const rowText = Object.values(item).join(' ').toLowerCase();
                                            const highlight = searchVal && rowText.includes(searchVal) ? ' search-highlight' : '';

                                            const designCode = extractDesignCode(item);
                                            return `
                                            <tr class="${highlight.trim()} clickable-row" data-code="${getField('EndProductCode')}" data-design="${designCode || ''}" style="cursor: pointer;">
                                                <!-- Product Code column hidden per client request -->
                                                <td style="padding: 0.25rem; text-align: center;">
                                                    <div class="inventory-thumbnail" data-design="${designCode || ''}" style="width: 60px; height: 60px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; border-radius: 4px; overflow: hidden;">
                                                        ${designCode ? '<span style="font-size: 0.7rem; color: #999;">Loading...</span>' : ''}
                                                    </div>
                                                </td>
                                                <td>${getField('EndProductDescription')}</td>
                                                <td>${getField('Ptype')}</td>
                                                <td>${getField('PColor')}</td>
                                                <td>${getField('PDesign')}</td>
                                                <td>${getField('PFinish')}</td>
                                                <td>${getField('Size')}</td>
                                                <td>${getField('Locationname')}</td>
                                                <td>${getField('Qty') || 0}</td>
                                            </tr>
                                            `;
                                        }).join('')}
                                    </tbody>
                                </table>
                                </div>
                                <div class="scrollbar-track"><div class="scrollbar-thumb"></div></div>
                            </div>
                        </div>
                        <div class="details-panel" id="detailsPanel">
                            <div style="position: sticky; top: 0; z-index: 100; background: #f8f9fa; padding: 0.5rem 0.5rem 0 0; text-align: right;">
                                <button class="close-details" onclick="window.closeItemDetails()">&times;</button>
                            </div>
                            <div id="detailsPanelContent" style="padding: 0 1.5rem 1.5rem 1.5rem;">
                                <div class="details-loading">
                                    <i class="fas fa-hand-pointer fa-3x mb-3" style="color: #d4af37;"></i>
                                    <p>Click on any item to view details</p>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                // Build pagination HTML
                const paginationHtml = buildPagination(api.currentPage, api.totalPages);

                // Combine all HTML
                contentDiv.innerHTML = searchHtml + tableHtml + paginationHtml;
                
                // Add event listeners for filters and pagination
                setupFilterListeners();
                setupPaginationListeners();
                setupSearchListener();
                setupScrollIndicator();
                filterTable();
                setupCustomScrollbar();
                setupKeyboardNavigation();
                setupNavigationButtons();
                
                // Set up row click listeners AFTER table is fully rendered
                setupRowClickListeners(filteredItems);
                
                // Load thumbnails after DOM is fully rendered
                setTimeout(() => loadThumbnails(), 100);
                
                // Load Font Awesome if not already loaded
                if (!document.querySelector('link[href*="font-awesome"]')) {
                    const fontAwesome = document.createElement('link');
                    fontAwesome.rel = 'stylesheet';
                    fontAwesome.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css';
                    document.head.appendChild(fontAwesome);
                }
                
                console.log('Inventory data loaded successfully');
            } catch (error) {
                console.error('Error loading inventory data:', error);
                
                // Clean up any pending timeouts that might have been created
                if (window._inventoryTimeouts && window._inventoryTimeouts.length > 0) {
                    console.log(`Cleaning up ${window._inventoryTimeouts.length} pending timeouts due to error`);
                    window._inventoryTimeouts.forEach(id => {
                        clearTimeout(id);
                    });
                    window._inventoryTimeouts = [];
                }
                
                contentDiv.innerHTML = `
                    <div class="no-results">
                        <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                        <h4>Error loading inventory data</h4>
                        <p>${error.message || 'An unknown error occurred'}</p>
                        <button class="btn btn-gold mt-3" id="retryLoadBtn">
                            <i class="fas fa-sync-alt"></i> Retry
                        </button>
                    </div>
                `;
                
                // Add retry button listener
                const retryBtn = document.getElementById('retryLoadBtn');
                if (retryBtn) {
                    retryBtn.removeEventListener('click', loadInventoryData); // Remove any existing listener
                    retryBtn.addEventListener('click', loadInventoryData);
                }
            }
        }
        
        // Function to build pagination controls
        function buildPagination(currentPage, totalPages) {
            if (totalPages <= 1) {
                return ''; // No pagination needed
            }
            
            let paginationHtml = '<nav aria-label="Inventory pagination"><ul class="pagination">';
            
            // Previous button
            paginationHtml += `
                <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${currentPage - 1}" aria-label="Previous">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>
            `;
            
            // Page numbers
            const maxPagesToShow = 5;
            let startPage = Math.max(1, currentPage - Math.floor(maxPagesToShow / 2));
            let endPage = Math.min(totalPages, startPage + maxPagesToShow - 1);
            
            // Adjust start page if we're near the end
            if (endPage - startPage + 1 < maxPagesToShow) {
                startPage = Math.max(1, endPage - maxPagesToShow + 1);
            }
            
            // First page
            if (startPage > 1) {
                paginationHtml += `
                    <li class="page-item">
                        <a class="page-link" href="#" data-page="1">1</a>
                    </li>
                `;
                
                if (startPage > 2) {
                    paginationHtml += `
                        <li class="page-item disabled">
                            <a class="page-link" href="#">...</a>
                        </li>
                    `;
                }
            }
            
            // Page numbers
            for (let i = startPage; i <= endPage; i++) {
                paginationHtml += `
                    <li class="page-item ${i === currentPage ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${i}">${i}</a>
                    </li>
                `;
            }
            
            // Last page
            if (endPage < totalPages) {
                if (endPage < totalPages - 1) {
                    paginationHtml += `
                        <li class="page-item disabled">
                            <a class="page-link" href="#">...</a>
                        </li>
                    `;
                }
                
                paginationHtml += `
                    <li class="page-item">
                        <a class="page-link" href="#" data-page="${totalPages}">${totalPages}</a>
                    </li>
                `;
            }
            
            // Next button
            paginationHtml += `
                <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${currentPage + 1}" aria-label="Next">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
            `;
            
            paginationHtml += '</ul></nav>';
            
            return paginationHtml;
        }
        
        // Event handlers are now defined at the top level for proper cleanup

        // Utility to enable/disable reset button
        function updateResetButtonState() {
            const resetBtn = document.getElementById('resetFiltersBtn');
            if (!resetBtn) return;
            const filterInputs = document.querySelectorAll('.column-filter, #inventorySearch');
            const anyActive = Array.from(filterInputs).some(el => el.value);
            resetBtn.disabled = !anyActive;
            if (anyActive) {
                resetBtn.classList.remove('disabled');
                const badge = document.getElementById('activeBadge');
                if (badge) badge.style.display = 'inline-block';
            } else {
                resetBtn.classList.add('disabled');
                const badge = document.getElementById('activeBadge');
                if (badge) badge.style.display = 'none';
            }
        }
        
        // Function to set up filter event listeners
        function setupFilterListeners() {
            const typeFilter = document.getElementById('typeFilter');
            const colorFilter = document.getElementById('colorFilter');
            const designFilter = document.getElementById('designFilter');
            const finishFilter = document.getElementById('finishFilter');
            const sizeFilter = document.getElementById('sizeFilter');
            const locationFilter = document.getElementById('locationFilter');
            const searchInput = document.getElementById('inventorySearch');

            const filterElements = [typeFilter, colorFilter, designFilter, finishFilter, sizeFilter, locationFilter];

            updateResetButtonState();
            
            // Define the filter change handler function
            filterChangeHandler = function() {
                // Update API filters - don't include locid as it's handled separately
                const filterValues = {
                    ptype: typeFilter ? decodeHtml(typeFilter.value) : '',
                    pcolor: colorFilter ? decodeHtml(colorFilter.value) : '',
                    pdesign: designFilter ? decodeHtml(designFilter.value) : '',
                    pfinish: finishFilter ? decodeHtml(finishFilter.value) : '',
                    psize: sizeFilter ? decodeHtml(sizeFilter.value) : ''
                };
                
                // Store the selected location in a separate property
                api.selectedLocation = locationFilter ? decodeHtml(locationFilter.value) : '';
                
                console.log('Setting filters:', filterValues);
                console.log('Selected location:', api.selectedLocation);

                filterElements.forEach(el => {
                    if (el && el.value) {
                        el.classList.add('active-filter');
                    } else if (el) {
                        el.classList.remove('active-filter');
                    }
                });

                api.setFilters(filterValues);
                filterTable();
                updateResetButtonState();
            };

            // Add event listeners to all filter dropdowns
            filterElements.forEach(filter => {
                if (filter) {
                    // Remove any existing listeners first to prevent duplicates
                    filter.removeEventListener('change', filterChangeHandler);
                    // Add the new listener
                    filter.addEventListener('change', filterChangeHandler);
                }
            });

            const resetBtn = document.getElementById('resetFiltersBtn');
            if (resetBtn) {
                resetBtn.addEventListener('click', () => {
                    filterElements.forEach(f => {
                        if (f) {
                            f.value = '';
                            f.classList.remove('active-filter');
                        }
                    });
                    if (searchInput) {
                        searchInput.value = '';
                        searchInput.parentElement.classList.remove('active-filter');
                    }
                    api.setFilters({ ptype:'', pcolor:'', pdesign:'', pfinish:'', psize:'' });
                    api.selectedLocation = '';
                    filterTable();
                    updateResetButtonState();
                });
                updateResetButtonState();
            }

            const refreshBtnInline = document.getElementById('refreshTableBtn');
            if (refreshBtnInline) {
                refreshBtnInline.addEventListener('click', loadInventoryData);
            }
        }
        
        // Function to load thumbnails for visible items
        async function loadThumbnails() {
            const thumbnails = document.querySelectorAll('.inventory-thumbnail[data-design]');
            if (thumbnails.length === 0) return;
            
            console.log(`Loading thumbnails for ${thumbnails.length} items...`);
            
            // Collect unique design codes first
            const designCodes = new Set();
            thumbnails.forEach(thumb => {
                const code = thumb.getAttribute('data-design');
                if (code && !thumb.querySelector('img') && !thumb.hasAttribute('data-loaded')) {
                    designCodes.add(code);
                }
            });
            
            // Batch fetch all images first (uses cache)
            const imagePromises = Array.from(designCodes).map(code => 
                searchProductImages(code).then(images => ({ code, images }))
            );
            
            const results = await Promise.all(imagePromises);
            const imageMap = new Map(results.map(r => [r.code, r.images]));
            
            // Now update all thumbnails
            thumbnails.forEach(thumbnail => {
                const designCode = thumbnail.getAttribute('data-design');
                if (!designCode || thumbnail.querySelector('img') || thumbnail.hasAttribute('data-loaded')) return;
                
                thumbnail.setAttribute('data-loaded', 'true');
                const images = imageMap.get(designCode) || [];
                
                if (images.length > 0) {
                    const img = document.createElement('img');
                    img.src = images[0].path;
                    img.alt = designCode;
                    img.style.cssText = 'width: 100%; height: 100%; object-fit: cover;';
                    img.loading = 'lazy'; // Native lazy loading
                    img.onerror = function() {
                        console.warn(`Failed to load image: ${images[0].path}`);
                        this.style.display = 'none';
                    };
                    img.onload = function() {
                        console.log(`Loaded: ${designCode}`);
                    };
                    thumbnail.innerHTML = '';
                    thumbnail.appendChild(img);
                } else {
                    thumbnail.innerHTML = '';
                }
            });
            
            console.log(`Loaded ${imageMap.size} unique design images`);
        }
        
        // Function to set up pagination event listeners
        function setupPaginationListeners() {
            const paginationLinks = document.querySelectorAll('.pagination .page-link');
            
            // Define the pagination click handler function
            paginationClickHandler = function(e) {
                e.preventDefault();
                
                const page = parseInt(this.getAttribute('data-page'));
                if (!isNaN(page)) {
                    api.setPage(page);
                    loadInventoryData();
                    
                    // Scroll to top of modal content
                    const modalBody = document.querySelector('.inventory-modal .modal-body');
                    if (modalBody) {
                        modalBody.scrollTop = 0;
                    }
                }
            };
            
            // Add event listeners to all pagination links
            paginationLinks.forEach(link => {
                // Remove any existing listeners first to prevent duplicates
                link.removeEventListener('click', paginationClickHandler);
                // Add the new listener
                link.addEventListener('click', paginationClickHandler);
            });
        }
        
        // Function to set up search functionality
        function setupSearchListener() {
            const searchInput = document.getElementById('inventorySearch');
            if (searchInput) {
                // Define the search input handler function
                searchInputHandler = function() {
                    if (searchInput.value) {
                        searchInput.parentElement.classList.add('active-filter');
                    } else {
                        searchInput.parentElement.classList.remove('active-filter');
                    }
                    filterTable();
                    updateResetButtonState();
                };

                // Remove any existing listeners first to prevent duplicates
                searchInput.removeEventListener('input', searchInputHandler);
                // Add the new listener
                searchInput.addEventListener('input', searchInputHandler);
            }
        }

        // Apply dropdown and text search filters to the table
        function filterTable() {
            const rows = document.querySelectorAll('#inventoryTable tbody tr');
            const searchVal = (document.getElementById('inventorySearch')?.value || '').toLowerCase();
            const selects = document.querySelectorAll('.column-filter');
            let visible = 0;

            rows.forEach(row => {
                row.style.display = 'table-row';
                let show = true;

                selects.forEach(select => {
                    const colIndex = parseInt(select.dataset.colIndex, 10);
                    const filterVal = decodeHtml(select.value).toLowerCase();
                    if (filterVal && !row.cells[colIndex].textContent.toLowerCase().includes(filterVal)) {
                        show = false;
                    }
                });

                if (show && searchVal) {
                    if (!row.textContent.toLowerCase().includes(searchVal)) {
                        show = false;
                    }
                }

                row.style.display = show ? 'table-row' : 'none';
                if (show) visible++;
            });

            const summary = document.getElementById('summaryCount');
            if (summary) {
                summary.textContent = `Showing ${visible} of ${rows.length}`;
            }
            updateResetButtonState();
            updateCustomScrollbar();
        }
        
        // Function to set up scroll indicator
        function setupScrollIndicator() {
            const modalBody = document.querySelector('.inventory-modal .modal-body');
            const scrollIndicator = document.querySelector('.inventory-modal .scroll-indicator');

            if (modalBody && scrollIndicator) {
                // Show scroll indicator if content is scrollable
                if (modalBody.scrollHeight > modalBody.clientHeight) {
                    scrollIndicator.style.display = 'block';
                    
                    // Define a named function for the scroll handler so we can remove it later
                    const scrollHandler = function() {
                        scrollIndicator.style.display = 'none';
                    };
                    
                    // Store the handler reference on the modalBody element for later cleanup
                    modalBody._scrollHandler = scrollHandler;
                    
                    // Add the scroll event listener
                    modalBody.addEventListener('scroll', scrollHandler, { once: true });
                    
                    // Hide indicator after 5 seconds anyway
                    // Store the timeout ID for later cleanup
                    modalBody._scrollTimeout = setTimeout(() => {
                        scrollIndicator.style.display = 'none';
                    }, 5000);
                }
            }


            
            // Set up refresh button listener
            const refreshBtn = document.getElementById('refreshInventoryBtn');
            if (refreshBtn) {
                // Remove any existing listeners first
                refreshBtn.removeEventListener('click', loadInventoryData);
                // Add the new listener
                refreshBtn.addEventListener('click', loadInventoryData);
            }
        }

        // Set up custom scrollbar for the table
        function setupCustomScrollbar() {
            const container = document.getElementById('inventoryTableContainer');
            const track = document.querySelector('.inventory-modal .scrollbar-track');
            const thumb = document.querySelector('.inventory-modal .scrollbar-thumb');

            if (!container || !track || !thumb) return;

            const updateThumb = () => {
                const { scrollHeight, clientHeight, scrollTop } = container;
                const ratio = clientHeight / scrollHeight;
                const thumbHeight = Math.max(clientHeight * ratio, 20);
                thumb.style.height = thumbHeight + 'px';
                const maxTop = clientHeight - thumbHeight;
                const top = scrollTop / (scrollHeight - clientHeight) * maxTop;
                thumb.style.top = top + 'px';
            };

            const onScroll = () => updateThumb();
            container.addEventListener('scroll', onScroll);

            let startY = 0;
            let startTop = 0;

            const onMouseDown = (e) => {
                startY = e.clientY;
                startTop = parseFloat(thumb.style.top) || 0;
                document.addEventListener('mousemove', onMouseMove);
                document.addEventListener('mouseup', onMouseUp);
                e.preventDefault();
            };

            const onMouseMove = (e) => {
                const delta = e.clientY - startY;
                const maxTop = track.clientHeight - thumb.offsetHeight;
                let newTop = Math.min(Math.max(startTop + delta, 0), maxTop);
                const scrollRatio = newTop / maxTop;
                container.scrollTop = scrollRatio * (container.scrollHeight - container.clientHeight);
            };

            const onMouseUp = () => {
                document.removeEventListener('mousemove', onMouseMove);
                document.removeEventListener('mouseup', onMouseUp);
            };

            thumb.addEventListener('mousedown', onMouseDown);

            container._customScroll = { onScroll, onMouseDown, updateThumb };
            updateThumb();
        }

        function updateCustomScrollbar() {
            const container = document.getElementById('inventoryTableContainer');
            if (container && container._customScroll) {
                container._customScroll.updateThumb();
            }
        }

        // Function to set up navigation buttons for horizontal scrolling
        function setupNavigationButtons() {
            const leftBtn = document.querySelector('.nav-button-left');
            const rightBtn = document.querySelector('.nav-button-right');
            const container = document.getElementById('inventoryTableContainer');

            if (leftBtn && rightBtn && container) {
                leftBtn.addEventListener('click', () => {
                    container.scrollBy({ left: -200, behavior: 'smooth' });
                });

                rightBtn.addEventListener('click', () => {
                    container.scrollBy({ left: 200, behavior: 'smooth' });
                });

                // Update button states based on scroll position
                const updateNavButtons = () => {
                    const { scrollLeft, scrollWidth, clientWidth } = container;
                    leftBtn.disabled = scrollLeft <= 0;
                    rightBtn.disabled = scrollLeft >= scrollWidth - clientWidth - 1;
                };

                container.addEventListener('scroll', updateNavButtons);
                updateNavButtons(); // Initial state
            }
        }

        // Function to set up row click listeners for showing details
        function setupRowClickListeners(items) {
            const rows = document.querySelectorAll('#inventoryTable tbody tr.clickable-row');
            
            rows.forEach(row => {
                row.addEventListener('click', function(e) {
                    // Prevent event bubbling
                    e.stopPropagation();
                    
                    // Don't trigger if clicking on a dropdown, filter, or button
                    if (e.target.closest('.dropdown, select, button, input')) {
                        return;
                    }
                    
                    const code = this.getAttribute('data-code');
                    if (code) {
                        // Find the item data
                        const item = items.find(i => {
                            const itemCode = i.EndProductCode || i.endProductCode || i.code;
                            return itemCode === code;
                        });
                        
                        if (item) {
                            const basicItem = {
                                description: item.EndProductDescription || item.description || '',
                                type: item.Ptype || item.type || '',
                                color: item.PColor || item.color || '',
                                size: item.Size || item.size || '',
                                quantity: item.Qty || item.quantity || 0,
                                location: item.Locationname || item.location || ''
                            };
                            showItemDetails(code, basicItem, this);
                        }
                    }
                });
            });
        }
        
        // Function to set up keyboard navigation for accessibility
        function setupKeyboardNavigation() {
            const container = document.getElementById('inventoryTableContainer');
            const table = document.getElementById('inventoryTable');

            if (container && table) {
                // Make container focusable for keyboard navigation
                container.setAttribute('tabindex', '0');
                
                // Add keyboard navigation to table container
                container.addEventListener('keydown', (e) => {
                    switch (e.key) {
                        case 'ArrowLeft':
                            e.preventDefault();
                            container.scrollBy({ left: -100, behavior: 'smooth' });
                            break;
                        case 'ArrowRight':
                            e.preventDefault();
                            container.scrollBy({ left: 100, behavior: 'smooth' });
                            break;
                        case 'ArrowUp':
                            e.preventDefault();
                            container.scrollBy({ top: -50, behavior: 'smooth' });
                            break;
                        case 'ArrowDown':
                            e.preventDefault();
                            container.scrollBy({ top: 50, behavior: 'smooth' });
                            break;
                        case 'Home':
                            e.preventDefault();
                            container.scrollTo({ left: 0, top: 0, behavior: 'smooth' });
                            break;
                        case 'End':
                            e.preventDefault();
                            container.scrollTo({ 
                                left: container.scrollWidth, 
                                top: container.scrollHeight, 
                                behavior: 'smooth' 
                            });
                            break;
                    }
                });

                // Add focus management for table rows
                const rows = table.querySelectorAll('tbody tr');
                rows.forEach((row, index) => {
                    row.addEventListener('keydown', (e) => {
                        let targetRow = null;
                        switch (e.key) {
                            case 'ArrowUp':
                                e.preventDefault();
                                targetRow = rows[Math.max(0, index - 1)];
                                break;
                            case 'ArrowDown':
                                e.preventDefault();
                                targetRow = rows[Math.min(rows.length - 1, index + 1)];
                                break;
                        }
                        if (targetRow) {
                            targetRow.focus();
                        }
                    });
                });
            }
        }

        // Function to clean up all event listeners
        function cleanupEventListeners() {
            console.log('Cleaning up inventory modal event listeners...');
            
            // Clean up filter listeners
            const filterSelects = document.querySelectorAll('.column-filter');
            filterSelects.forEach(select => {
                if (select && filterChangeHandler) {
                    select.removeEventListener('change', filterChangeHandler);
                }
            });
            
            // Clean up pagination listeners
            const paginationLinks = document.querySelectorAll('.pagination .page-link');
            paginationLinks.forEach(link => {
                if (link && paginationClickHandler) {
                    link.removeEventListener('click', paginationClickHandler);
                }
            });
            
            // Clean up search listener
            const searchInput = document.getElementById('inventorySearch');
            if (searchInput && searchInputHandler) {
                searchInput.removeEventListener('input', searchInputHandler);
            }
            
            // Clean up refresh button listener
            const refreshBtn = document.getElementById('refreshInventoryBtn');
            if (refreshBtn && refreshDataHandler) {
                refreshBtn.removeEventListener('click', refreshDataHandler);
            }
            const refreshBtnInline = document.getElementById('refreshTableBtn');
            if (refreshBtnInline) {
                refreshBtnInline.removeEventListener('click', loadInventoryData);
            }

            // Clean up close button listeners
            const footerBtn = document.getElementById('closeInventoryBtn');
            const headerBtn = document.getElementById('inventoryModalCloseBtn');
            [footerBtn, headerBtn].forEach(btn => {
                if (btn && closeBtnHandler) {
                    btn.removeEventListener('click', closeBtnHandler);
                }
            });
            
            // Clean up retry button listener
            const retryBtn = document.getElementById('retryLoadBtn');
            if (retryBtn) {
                retryBtn.removeEventListener('click', loadInventoryData);
            }
            
            // Clean up scroll event listener and timeout
            const modalBody = document.querySelector('.inventory-modal .modal-body');
            if (modalBody) {
                // Clean up scroll handler if it exists
                if (modalBody._scrollHandler) {
                    modalBody.removeEventListener('scroll', modalBody._scrollHandler);
                    delete modalBody._scrollHandler;
                }


                // Clear any pending timeout
                if (modalBody._scrollTimeout) {
                    clearTimeout(modalBody._scrollTimeout);
                    delete modalBody._scrollTimeout;
                }
            }

            // Clean up custom scrollbar listeners
            const container = document.getElementById('inventoryTableContainer');
            if (container && container._customScroll) {
                container.removeEventListener('scroll', container._customScroll.onScroll);
                const thumb = document.querySelector('.inventory-modal .scrollbar-thumb');
                if (thumb) {
                    thumb.removeEventListener('mousedown', container._customScroll.onMouseDown);
                }
                delete container._customScroll;
            }
            
            // Clean up any other timers or intervals
            if (window._inventoryTimeouts) {
                window._inventoryTimeouts.forEach(timeoutId => {
                    clearTimeout(timeoutId);
                });
                window._inventoryTimeouts = [];
            }
            
            // Clean up any pending fetch requests
            if (window._inventoryAbortControllers) {
                window._inventoryAbortControllers.forEach(controller => {
                    try {
                        controller.abort();
                    } catch (e) {
                        console.error('Error aborting fetch request:', e);
                    }
                });
                window._inventoryAbortControllers = [];
            }

            const modalElement = document.getElementById('inventoryModal');
            if (modalElement && escKeyHandler) {
                window.removeEventListener('keydown', escKeyHandler, true);
                escKeyHandler = null;
            }
        }

        // Function to open the modal and load data
        function openModal() {
            console.log('Opening inventory modal...');
            
            // Create modal if it doesn't exist and check if creation was successful
            const modalCreated = createModal();
            
            if (modalCreated) {
                console.log('Modal created successfully, proceeding to show it');
                
                // Use Bootstrap 5 syntax for modals
                const modalElement = document.getElementById('inventoryModal');
                if (modalElement) {
                    try {
                        const existingInstance = bootstrap.Modal.getInstance(modalElement);
                        if (existingInstance) {
                            existingInstance.dispose();
                        }

                        inventoryModalInstance = new bootstrap.Modal(modalElement, { keyboard: false });
                        inventoryModalInstance.show();

                        if (escKeyHandler) {
                            window.removeEventListener('keydown', escKeyHandler, true);
                        }
                        escKeyHandler = function(event) {
                            if (event.key !== 'Escape') return;

                            const detailsPanel = document.getElementById('detailsPanel');
                            const tablePanel = document.getElementById('tablePanel');
                            const detailsOpen =
                                (detailsPanel && detailsPanel.classList.contains('show')) ||
                                (tablePanel && tablePanel.classList.contains('with-details')) ||
                                document.querySelectorAll('.inventory-table tbody tr.selected').length > 0;
                            if (detailsOpen) {
                                event.preventDefault();
                                event.stopImmediatePropagation();
                                if (typeof window.closeItemDetails === 'function') {
                                    window.closeItemDetails();
                                }
                                return;
                            }

                            if (inventoryModalInstance) {
                                event.preventDefault();
                                event.stopImmediatePropagation();
                                inventoryModalInstance.hide();
                            }
                        };
                        window.addEventListener('keydown', escKeyHandler, true);

                        loadInventoryData();
                        addModalButtonListeners();
                        
                        // Add event listener for when modal is shown
                        const shownHandler = function() {
                            console.log('Modal shown event fired');
                        };
                        
                        // Add event listener for when modal is hidden
                        const hiddenHandler = function() {
                            console.log('Modal hidden event fired');

                            if (typeof window.closeItemDetails === 'function') {
                                window.closeItemDetails();
                            }
                            
                            // Use the centralized cleanup function to remove all event listeners
                            cleanupEventListeners();
                            
                            // Remove modal event listeners
                            modalElement.removeEventListener('shown.bs.modal', shownHandler);
                            modalElement.removeEventListener('hidden.bs.modal', hiddenHandler);
                            
                            // Reset handler references after cleanup
                            closeBtnHandler = null;
                            refreshDataHandler = null;
                            searchInputHandler = null;
                            filterChangeHandler = null;
                            paginationClickHandler = null;
                            
                            console.log('All event listeners cleaned up successfully');
                        };
                        
                        modalElement.addEventListener('shown.bs.modal', shownHandler);
                        modalElement.addEventListener('hidden.bs.modal', hiddenHandler);
                    } catch (error) {
                        console.error('Error showing modal with Bootstrap:', error);
                        
                        // Fallback method if Bootstrap modal fails
                        inventoryModalInstance = null;
                        modalElement.classList.add('show');
                        modalElement.style.display = 'block';
                        document.body.classList.add('modal-open');

                        if (escKeyHandler) {
                            window.removeEventListener('keydown', escKeyHandler, true);
                        }
                        escKeyHandler = function(event) {
                            if (event.key !== 'Escape') return;

                            const detailsPanel = document.getElementById('detailsPanel');
                            if (detailsPanel && detailsPanel.classList.contains('show')) {
                                event.preventDefault();
                                event.stopImmediatePropagation();
                                if (typeof window.closeItemDetails === 'function') {
                                    window.closeItemDetails();
                                }
                                return;
                            }

                            if (modalElement.classList.contains('show')) {
                                event.preventDefault();
                                event.stopImmediatePropagation();
                                modalElement.classList.remove('show');
                                modalElement.style.display = 'none';
                                document.body.classList.remove('modal-open');
                                const backdrop = document.querySelector('.modal-backdrop');
                                if (backdrop) backdrop.remove();
                                cleanupEventListeners();
                            }
                        };
                        window.addEventListener('keydown', escKeyHandler, true);
                        
                        // Create backdrop manually if needed
                        if (!document.querySelector('.modal-backdrop')) {
                            const backdrop = document.createElement('div');
                            backdrop.className = 'modal-backdrop fade show';
                            document.body.appendChild(backdrop);
                        }
                        
                        loadInventoryData();
                        addModalButtonListeners();
                    }
                } else {
                    console.error('Inventory modal element not found even though createModal returned true');
                }
            } else {
                console.error('Failed to create inventory modal');
            }
        }

        // Function to set up inventory link handler
        function setupInventoryLinkHandler() {
            // Try to find the inventory links by ID first
            let inventoryLink = document.getElementById('inventoryLink');
            let sideInventoryLink = document.getElementById('sideInventoryLink');
            let found = false;
            
            // Set up event listener for footer inventory link if it exists
            if (inventoryLink) {
                console.log('Found footer inventory link by ID');
                inventoryLink.addEventListener('click', function(e) {
                    console.log('Footer inventory link clicked');
                    e.preventDefault();
                    e.stopPropagation();
                    openModal();
                    return false;
                });
                found = true;
            }
            
            // Set up event listener for side menu inventory link if it exists
            if (sideInventoryLink) {
                console.log('Found side menu inventory link by ID');
                sideInventoryLink.addEventListener('click', function(e) {
                    console.log('Side menu inventory link clicked');
                    e.preventDefault();
                    e.stopPropagation();
                    openModal();
                    return false;
                });
                found = true;
            }
            
            // If neither link was found by ID, try to find by text content
            if (!found) {
                console.log('Inventory links not found by ID, trying to find by text content');
                const links = document.querySelectorAll('a');
                links.forEach(link => {
                    if (link.textContent.trim().toLowerCase().includes('inventory')) {
                        console.log('Found inventory link by text content');
                        link.addEventListener('click', function(e) {
                            console.log('Inventory link clicked (found by text)');
                            e.preventDefault();
                            e.stopPropagation();
                            openModal();
                            return false;
                        });
                        
                        found = true;
                    }
                });
                
                if (!found) {
                    console.error('Could not find any inventory links by ID or text content');
                }
            }
        }

        // Function to initialize the inventory modal
        function init() {
            console.log('Initializing inventory modal...');
            
            // Initialize global arrays for tracking resources
            window._inventoryTimeouts = [];
            window._inventoryAbortControllers = [];
            
            // Set up inventory link handler
            setupInventoryLinkHandler();
            
            // Create modal and add button listeners
            const modalCreated = createModal();
            if (modalCreated) {
                addModalButtonListeners();
            }
        }

        // Initialize the modal
        init();
        
        // Expose the openModal function globally for deep linking
        window.openInventoryModal = openModal;
    });
});
