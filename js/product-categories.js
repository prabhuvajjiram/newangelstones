// Product Categories JavaScript

// Helper functions
function getBasename(filename) {
    return filename.split('.').slice(0, -1).join('.');
}

function getExtension(filename) {
    return filename.split('.').pop().toLowerCase();
}

function isImageFile(filename) {
    const ext = getExtension(filename);
    return ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext);
}

// Simple cache busting helper - adds a timestamp to URLs
function addCacheBuster(url, category) {
    // Skip cache busting for MBNA_2025 category
    if (category === 'MBNA_2025' || url.includes('/MBNA_2025/')) {
        return url;
    }
    
    // Add cache buster parameter
    const cacheBuster = `?v=${Date.now()}`;
    return url.includes('?') ? url : url + cacheBuster;
}

// Main functionality
document.addEventListener('DOMContentLoaded', function() {
    // Initialize state
    let categories = {};
    
    // Function to load and display categories
    async function loadAndDisplayCategories() {
        const container = document.querySelector('.category-grid');
        if (!container) return;

        // Show loading indicator
        container.innerHTML = '';
        showLoadingIndicator(container);

        try {
            // First, get all categories with cache-busting timestamp
            const timestamp = Date.now();
            const response = await fetch(`get_directory_files.php?directory=products&_=${timestamp}`);
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error || 'Failed to load categories');
            }

            // For each category, fetch its contents with unique timestamp for each request
            for (const file of data.files) {
                if (typeof file === 'object' && file.name) {
                    const categoryName = file.name;
                    try {
                        const categoryTimestamp = Date.now(); // New timestamp for each category request
                        const categoryResponse = await fetch(`get_directory_files.php?directory=products/${categoryName}&_=${categoryTimestamp}`);
                        const categoryData = await categoryResponse.json();
                        if (categoryData.success && categoryData.files) {
                            categories[categoryName] = categoryData.files;
                        }
                    } catch (error) {
                        console.error(`Error loading category ${categoryName}:`, error);
                    }
                }
            }

            console.log('Loaded categories:', categories);
            displayCategories();
        } catch (error) {
            console.error('Error loading categories:', error);
            container.innerHTML = `
                <div class="error-message">
                    <div>Failed to load categories</div>
                    <button onclick="location.reload()">Retry</button>
                </div>
            `;
        }
    }

    // Function to display categories
    function displayCategories() {
        const container = document.querySelector('.category-grid');
        if (!container) return;

        container.innerHTML = ''; // Clear container

        Object.entries(categories).forEach(([category, images]) => {
            const categoryItem = document.createElement('div');
            categoryItem.className = 'category-item';

            const link = document.createElement('a');
            link.href = `#${category.toLowerCase()}-collection`;
            link.className = 'category-link';
            link.setAttribute('data-category', category);

            // Create thumbnail container
            const thumbContainer = document.createElement('div');
            thumbContainer.className = 'category-image';

            // Add sample image if available
            if (images && images.length > 0) {
                const img = document.createElement('img');
                img.alt = category;
                img.src = addCacheBuster(images[0].path, category);
                // Simplified error handling
                img.onerror = function() {
                    console.error(`Failed to load image: ${img.src}`);
                    img.src = 'images/placeholder.png';
                };
                thumbContainer.appendChild(img);
            }

            // Add category name and count
            const name = document.createElement('h4');
            name.textContent = category.replace(/_/g, ' ');

            const count = document.createElement('span');
            count.className = 'category-count';
            count.textContent = `${images.length} designs`;

            link.appendChild(thumbContainer);
            link.appendChild(name);
            link.appendChild(count);
            categoryItem.appendChild(link);

            // Add click handler
            link.addEventListener('click', (e) => {
                e.preventDefault();
                showCategoryModal(category, images);
            });

            container.appendChild(categoryItem);
        });
    }

    // Search API function
    async function searchAPI(term) {
        try {
            // Add timestamp for cache busting
            const timestamp = Date.now();
            const response = await fetch(`get_directory_files.php?search=${encodeURIComponent(term)}&_=${timestamp}`);
            const data = await response.json();
            
            if (data.success && Array.isArray(data.files)) {
                return data.files;
            } else {
                console.error('Search API error:', data.error || 'Unknown error');
                return [];
            }
        } catch (error) {
            console.error('Search API error:', error);
            return [];
        }
    }

    // Search function
    async function handleSearch(searchTerm) {
        console.log('Searching for:', searchTerm);
        searchTerm = searchTerm.toLowerCase().trim();

        // Get all images from all categories
        const allImages = [];
        Object.entries(categories).forEach(([category, images]) => {
            images.forEach(image => {
                allImages.push({
                    ...image,
                    category: category
                });
            });
        });

        console.log('All images to search:', allImages);

        // Find matches across all categories
        const matches = allImages.filter(image => {
            const filename = image.name.toLowerCase();
            const productNumber = filename.split('.')[0].toLowerCase();
            return productNumber.includes(searchTerm);
        });

        console.log('Matches found:', matches);

        // Update display
        const container = document.querySelector('.category-grid');
        if (!container) return;

        container.innerHTML = '';

        if (searchTerm && matches.length > 0) {
            // Create search results grid
            const resultsGrid = document.createElement('div');
            resultsGrid.className = 'search-results-grid';

            // Add search summary
            const summary = document.createElement('div');
            summary.className = 'search-summary';
            summary.textContent = `Found ${matches.length} matching products`;
            container.appendChild(summary);

            // Add each matching image
            matches.forEach(image => {
                const resultItem = document.createElement('div');
                resultItem.className = 'search-result-item';

                const img = document.createElement('img');
                img.src = addCacheBuster(image.path, image.category);
                img.alt = image.name;
                img.onerror = function() {
                    console.error(`Failed to load image: ${img.src}`);
                    img.src = 'images/placeholder.png';
                };

                const label = document.createElement('div');
                label.className = 'result-label';
                label.textContent = image.name.split('.')[0];

                const categoryLabel = document.createElement('div');
                categoryLabel.className = 'category-label';
                categoryLabel.textContent = image.category;

                resultItem.appendChild(img);
                resultItem.appendChild(label);
                resultItem.appendChild(categoryLabel);

                // Add click handler
                resultItem.addEventListener('click', () => {
                    showFullscreenImage(image.path, image.name.split('.')[0]);
                });

                resultsGrid.appendChild(resultItem);
            });

            container.appendChild(resultsGrid);
        } else if (searchTerm) {
            // Show no results message
            container.innerHTML = `
                <div class="no-results-message">
                    <div style="text-align: center; padding: 20px; color: #888;">
                        No products found matching "${searchTerm}"
                    </div>
                </div>
            `;
        } else {
            // If no search term, show all categories
            displayCategories();
        }
    }

    // Add debounce function
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Update search input handler
    const searchInput = document.getElementById('product-search');
    if (searchInput) {
        searchInput.addEventListener('input', debounce((e) => {
            handleSearch(e.target.value);
        }, 300));

        searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                e.preventDefault();
                searchInput.value = '';
                handleSearch('');
            }
        });
    }

    // Initialize categories
    loadAndDisplayCategories();

    // Add styles
    const styles = document.createElement('style');
    styles.textContent = `
        .search-results-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            padding: 20px;
        }

        .search-result-item {
            position: relative;
            aspect-ratio: 1;
            background: #333;
            border-radius: 8px;
            overflow: hidden;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .search-result-item:hover {
            transform: scale(1.05);
        }

        .search-result-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .result-label {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 8px;
            text-align: center;
        }

        .category-label {
            position: absolute;
            top: 8px;
            right: 8px;
            background: rgba(0, 0, 0, 0.7);
            color: #d6b772;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.9em;
        }

        .search-summary {
            text-align: center;
            color: #d6b772;
            font-size: 1.2em;
            margin: 20px 0;
        }

        .no-results-message {
            text-align: center;
            padding: 40px;
            color: #888;
        }
    `;
    document.head.appendChild(styles);

    // Add this after your existing code but inside the DOMContentLoaded event listener

    function showCategoryModal(category, images) {
        let modal = document.getElementById('category-modal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'category-modal';
            modal.className = 'category-modal';
        }

        // Create modal content
        modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h2>${category.replace(/_/g, ' ')} Collection (${images.length} items)</h2>
                    <button class="close-modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="thumbnails-grid"></div>
                </div>
            </div>
        `;

        // Add modal styles if not already added
        if (!document.getElementById('category-modal-styles')) {
            const modalStyles = document.createElement('style');
            modalStyles.id = 'category-modal-styles';
            modalStyles.textContent = `
                .category-modal {
                    display: none;
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.9);
                    z-index: 1000;
                    overflow: hidden;
                }
                
                .category-modal .modal-content {
                    position: relative;
                    width: 90%;
                    max-width: 1200px;
                    height: 90vh;
                    margin: 5vh auto;
                    background: #222;
                    border-radius: 8px;
                    display: flex;
                    flex-direction: column;
                }
                
                .category-modal .modal-header {
                    padding: 15px 20px;
                    background: #333;
                    border-bottom: 1px solid #444;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }
                
                .category-modal .modal-body {
                    flex: 1;
                    overflow-y: auto;
                    padding: 20px;
                    -webkit-overflow-scrolling: touch;
                }
                
                .thumbnails-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                    gap: 20px;
                    padding: 10px;
                }
                
                .thumbnail-item {
                    position: relative;
                    aspect-ratio: 1;
                    background: #333;
                    border-radius: 4px;
                    overflow: hidden;
                    cursor: pointer;
                    transition: transform 0.3s ease;
                    will-change: transform;
                }
                
                .thumbnail-item:hover {
                    transform: scale(1.05);
                }
                
                .thumbnail-item img {
                    width: 100%;
                    height: 100%;
                    object-fit: cover;
                    backface-visibility: hidden;
                }
                
                .thumbnail-label {
                    position: absolute;
                    bottom: 0;
                    left: 0;
                    right: 0;
                    background: rgba(0, 0, 0, 0.7);
                    color: white;
                    padding: 8px;
                    text-align: center;
                    font-size: 14px;
                }
                
                .close-modal {
                    background: none;
                    border: none;
                    color: #fff;
                    font-size: 28px;
                    cursor: pointer;
                    padding: 10px;
                    line-height: 1;
                    width: 44px;
                    height: 44px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                
                .close-modal:hover {
                    color: #d6b772;
                }

                /* Tablet Styles */
                @media (max-width: 1024px) {
                    .thumbnails-grid {
                        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
                        gap: 15px;
                    }
                }

                /* Mobile Styles */
                @media (max-width: 768px) {
                    .category-modal .modal-content {
                        width: 100%;
                        height: 100%;
                        margin: 0;
                        border-radius: 0;
                    }
                    
                    .thumbnails-grid {
                        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                        gap: 12px;
                        padding: 12px;
                    }
                    
                    .category-modal .modal-header {
                        padding: 12px;
                    }
                    
                    .category-modal .modal-header h2 {
                        font-size: 18px;
                    }
                    
                    .thumbnail-label {
                        padding: 6px;
                        font-size: 12px;
                    }
                }

                /* Small Mobile Styles */
                @media (max-width: 480px) {
                    .thumbnails-grid {
                        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
                        gap: 8px;
                        padding: 8px;
                    }
                    
                    .category-modal .modal-body {
                        padding: 10px;
                    }
                }
            `;
            document.head.appendChild(modalStyles);
        }

        document.body.appendChild(modal);

        // Populate thumbnails
        const grid = modal.querySelector('.thumbnails-grid');
        images.forEach(image => {
            const thumb = document.createElement('div');
            thumb.className = 'thumbnail-item';

            const img = document.createElement('img');
            img.alt = image.name;
            
            // Add loading="lazy" for native lazy loading
            img.loading = 'lazy';
            
            // Add a low-quality placeholder
            img.style.filter = 'blur(5px)';
            img.style.transform = 'scale(1.1)';
            
            // Load image with fade-in effect
            img.onload = () => {
                img.style.filter = '';
                img.style.transform = '';
                img.style.transition = 'filter 0.3s ease, transform 0.3s ease';
            };

            // Use lazy loading
            lazyLoadImage(img, addCacheBuster(image.path, category));

            const label = document.createElement('div');
            label.className = 'thumbnail-label';
            label.textContent = image.name.split('.')[0];

            thumb.appendChild(img);
            thumb.appendChild(label);

            // Use passive event listener for better performance
            thumb.addEventListener('click', () => {
                showFullscreenImage(image.path, image.name.split('.')[0]);
            }, { passive: true });

            grid.appendChild(thumb);
        });

        // Show modal
        modal.style.display = 'block';

        // Add close handlers
        const closeBtn = modal.querySelector('.close-modal');
        const handleClose = () => {
            modal.style.display = 'none';
        };

        closeBtn.addEventListener('click', handleClose);
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        });

        document.addEventListener('keydown', function closeOnEscape(e) {
            if (e.key === 'Escape') {
                modal.style.display = 'none';
                document.removeEventListener('keydown', closeOnEscape);
            }
        });
    }

    // Function to display fullscreen image with navigation
    function showFullscreenImage(imagePath, productNumber, currentIndex) {
        // First, add the fullscreen-active class to the body to hide sidebar
        document.body.classList.add('fullscreen-active');
        
        // Create or retrieve fullscreen container
        let fullscreen = document.getElementById('fullscreen-view');
        const cleanup = new Set();

        if (!fullscreen) {
            fullscreen = document.createElement('div');
            fullscreen.id = 'fullscreen-view';
            fullscreen.className = 'fullscreen-view';
        }

        // Show loading indicator first
        fullscreen.innerHTML = `<div class="loading-indicator">
            <div class="spinner"></div>
            <div class="loading-text">Loading image...</div>
        </div>`;
        document.body.appendChild(fullscreen);
        fullscreen.style.display = 'flex';

        // Store current category images for navigation
        let currentCategory = '';
        let categoryImages = [];
        let imageIndex = 0;
        
        // Find the parent category modal
        const categoryModal = document.querySelector('.category-modal');
        if (categoryModal) {
            // Get all thumbnail items in this category
            const thumbnails = categoryModal.querySelectorAll('.thumbnail-item');
            if (thumbnails.length > 0) {
                categoryImages = Array.from(thumbnails).map(thumb => {
                    const img = thumb.querySelector('img');
                    const label = thumb.querySelector('.thumbnail-label');
                    return {
                        path: img.src,
                        name: label ? label.textContent : ''
                    };
                });
                
                // Find the index of the current image
                imageIndex = categoryImages.findIndex(img => img.path === imagePath);
                if (imageIndex === -1) imageIndex = 0;
            }
        }
        
        // Load image
        const img = new Image();
        
        // Add robust error handling for image loading
        img.onerror = function() {
            console.error(`Failed to load image: ${imagePath}`);
            img.src = 'images/placeholder.png';
        };
        
        img.onload = () => {
            // Determine if we need navigation buttons
            const showNavigation = categoryImages.length > 1;
            
            // Apply cache busting to image path
            const cachedImagePath = addCacheBuster(imagePath, currentCategory || 'monuments');
            
            fullscreen.innerHTML = `
                <div class="fullscreen-image-container">
                    <img src="${cachedImagePath}" class="fullscreen-image" alt="${productNumber}">
                    <div class="fullscreen-label">${productNumber}</div>
                    <button class="close-fullscreen">&times;</button>
                    ${showNavigation ? `
                        <button class="fullscreen-nav prev" ${imageIndex <= 0 ? 'disabled' : ''}>&lt;</button>
                        <button class="fullscreen-nav next" ${imageIndex >= categoryImages.length - 1 ? 'disabled' : ''}>&gt;</button>
                    ` : ''}
                </div>
            `;
            // Add close handler
            const closeBtn = fullscreen.querySelector('.close-fullscreen');
            const handleClose = () => {
                fullscreen.style.display = 'none';
                // Remove the fullscreen-active class when closing
                document.body.classList.remove('fullscreen-active');
                cleanup.forEach(fn => fn());
                cleanup.clear();
            };

            closeBtn.addEventListener('click', handleClose);
            cleanup.add(() => closeBtn.removeEventListener('click', handleClose));

            // Add navigation handlers if we have multiple images
            if (showNavigation) {
                const prevBtn = fullscreen.querySelector('.fullscreen-nav.prev');
                const nextBtn = fullscreen.querySelector('.fullscreen-nav.next');
                
                if (prevBtn) {
                    prevBtn.addEventListener('click', () => {
                        if (imageIndex > 0) {
                            imageIndex--;
                            const prevImage = categoryImages[imageIndex];
                            showFullscreenImage(prevImage.path, prevImage.name.split('.')[0], imageIndex);
                        }
                    });
                }
                
                if (nextBtn) {
                    nextBtn.addEventListener('click', () => {
                        if (imageIndex < categoryImages.length - 1) {
                            imageIndex++;
                            const nextImage = categoryImages[imageIndex];
                            showFullscreenImage(nextImage.path, nextImage.name.split('.')[0], imageIndex);
                        }
                    });
                }
                
                // Add touch swipe functionality for mobile devices
                let touchStartX = 0;
                let touchEndX = 0;
                let touchStartTime = 0;
                const imageContainer = fullscreen.querySelector('.fullscreen-image-container');
                
                // Touch start handler
                const handleTouchStart = (e) => {
                    // Only track primary touch
                    if (e.touches.length === 1) {
                        touchStartX = e.changedTouches[0].screenX;
                        touchStartTime = Date.now();
                    }
                };
                
                // Touch end handler
                const handleTouchEnd = (e) => {
                    // Ensure this is the same touch that started and has reasonable timing
                    if (e.changedTouches.length === 1 && Date.now() - touchStartTime > 100 && Date.now() - touchStartTime < 1000) {
                        touchEndX = e.changedTouches[0].screenX;
                        
                        // Handle swipe only if substantial horizontal movement
                        const swipeDistance = touchEndX - touchStartX;
                        const minSwipeDistance = 75; // Increased threshold for more deliberate swipes
                        
                        if (Math.abs(swipeDistance) > minSwipeDistance) {
                            if (swipeDistance > 0) {
                                // Swiped right - go to previous image
                                if (imageIndex > 0) {
                                    imageIndex--;
                                    const prevImage = categoryImages[imageIndex];
                                    showFullscreenImage(prevImage.path, prevImage.name.split('.')[0], imageIndex);
                                }
                            } else {
                                // Swiped left - go to next image
                                if (imageIndex < categoryImages.length - 1) {
                                    imageIndex++;
                                    const nextImage = categoryImages[imageIndex];
                                    showFullscreenImage(nextImage.path, nextImage.name.split('.')[0], imageIndex);
                                }
                            }
                        }
                    }
                };
                
                // Add touch event listeners
                imageContainer.addEventListener('touchstart', handleTouchStart, { passive: true });
                imageContainer.addEventListener('touchend', handleTouchEnd, { passive: true });
                
                // Add to cleanup
                cleanup.add(() => {
                    imageContainer.removeEventListener('touchstart', handleTouchStart);
                    imageContainer.removeEventListener('touchend', handleTouchEnd);
                });
            }

            // Close on outside click
            const handleOutsideClick = (e) => {
                if (e.target === fullscreen) handleClose();
            };
            fullscreen.addEventListener('click', handleOutsideClick);
            cleanup.add(() => fullscreen.removeEventListener('click', handleOutsideClick));

            // Handle keyboard navigation
            const handleKeyboard = (e) => {
                if (e.key === 'Escape') {
                    handleClose();
                } else if (showNavigation) {
                    if (e.key === 'ArrowLeft' && imageIndex > 0) {
                        imageIndex--;
                        const prevImage = categoryImages[imageIndex];
                        showFullscreenImage(prevImage.path, prevImage.name.split('.')[0], imageIndex);
                    } else if (e.key === 'ArrowRight' && imageIndex < categoryImages.length - 1) {
                        imageIndex++;
                        const nextImage = categoryImages[imageIndex];
                        showFullscreenImage(nextImage.path, nextImage.name.split('.')[0], imageIndex);
                    }
                }
            };
            document.addEventListener('keydown', handleKeyboard);
            cleanup.add(() => document.removeEventListener('keydown', handleKeyboard));
        };

        img.src = imagePath;
        
        // Add error handling for image loading
        img.onerror = function() {
            fullscreen.innerHTML = `
                <div class="fullscreen-image-container">
                    <div class="error-message">
                        <p>Failed to load image</p>
                    </div>
                    <button class="close-fullscreen">&times;</button>
                </div>
            `;
            
            const closeBtn = fullscreen.querySelector('.close-fullscreen');
            closeBtn.addEventListener('click', () => {
                fullscreen.style.display = 'none';
                document.body.classList.remove('fullscreen-active');
            });
        };
    }

    // Add loading indicator function
    function showLoadingIndicator(container) {
        const loader = document.createElement('div');
        loader.className = 'loading-indicator';
        loader.innerHTML = `
            <div class="spinner"></div>
            <div class="loading-text">Loading...</div>
        `;
        container.appendChild(loader);
    }

    // Add loading indicator styles
    const loadingStyles = document.createElement('style');
    loadingStyles.textContent = `
        .loading-indicator {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px;
            color: #d6b772;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid #333;
            border-top: 3px solid #d6b772;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 15px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .loading-text {
            font-size: 16px;
        }
    `;
    document.head.appendChild(loadingStyles);

    // Add this function for lazy loading images
    function lazyLoadImage(img, src) {
        // Add error handler
        img.onerror = function() {
            console.error(`Failed to load image: ${src}`);
            img.src = 'images/placeholder.png';
        };
        
        if ('loading' in HTMLImageElement.prototype) {
            // Browser supports native lazy loading
            img.loading = 'lazy';
            img.src = src;
        } else {
            // Fallback for browsers that don't support lazy loading
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        img.src = src;
                        observer.disconnect();
                    }
                });
            });
            observer.observe(img);
        }
    }

    // Add scroll optimization for modal
    function optimizeModalScroll(modalBody) {
        let ticking = false;
        modalBody.addEventListener('scroll', () => {
            if (!ticking) {
                window.requestAnimationFrame(() => {
                    // Your scroll handling code here
                    ticking = false;
                });
                ticking = true;
            }
        }, { passive: true });
    }

    // Simple lazy loading for images
    const images = document.querySelectorAll('img');
    
    if ('loading' in HTMLImageElement.prototype) {
        // Use native lazy loading
        images.forEach(img => {
            if (!img.hasAttribute('loading')) {
                img.loading = 'lazy';
            }
        });
    }

    // Simple performance optimization for scroll events
    let ticking = false;
    document.addEventListener('scroll', function() {
        if (!ticking) {
            window.requestAnimationFrame(function() {
                ticking = false;
            });
            ticking = true;
        }
    }, { passive: true });

    // Add CSS styles to ensure fullscreen images stay within screen bounds
    const fullscreenStyles = document.createElement('style');
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
        
        .fullscreen-image-container {
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
            width: auto;
            height: auto;
            object-fit: contain;
            margin: auto;
            display: block;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
        }
        
        .fullscreen-label {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 10px;
            text-align: center;
        }
        
        .fullscreen-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 50px;
            height: 50px;
            background-color: rgba(0, 0, 0, 0.5);
            color: white;
            font-size: 24px;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .fullscreen-nav.prev {
            left: 20px;
        }
        
        .fullscreen-nav.next {
            right: 20px;
        }
        
        .close-fullscreen {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 40px;
            height: 40px;
            background-color: rgba(0, 0, 0, 0.5);
            color: white;
            border: none;
            border-radius: 50%;
            font-size: 30px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Mobile optimizations */
        @media (max-width: 768px) {
            .fullscreen-image-container {
                width: 100%;
                height: 100%;
                padding: 10px;
            }
            
            .fullscreen-image {
                max-width: 95%;
                max-height: 70%;
            }
            
            .close-fullscreen {
                top: 10px;
                right: 10px;
                width: 36px;
                height: 36px;
                font-size: 24px;
            }
            
            .fullscreen-nav {
                width: 40px;
                height: 40px;
                font-size: 24px;
                background-color: rgba(0, 0, 0, 0.7);
            }
            
            .fullscreen-nav.prev {
                left: 10px;
            }
            
            .fullscreen-nav.next {
                right: 10px;
            }
            
            .fullscreen-label {
                padding: 5px;
                font-size: 14px;
            }
        }
        
        /* Small mobile optimizations */
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
            
            .close-fullscreen {
                width: 32px;
                height: 32px;
                font-size: 20px;
            }
            
            .fullscreen-label {
                font-size: 12px;
            }
        }
    `;
    document.head.appendChild(fullscreenStyles);
});
