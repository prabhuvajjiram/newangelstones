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
            // First, get all categories
            const response = await fetch('get_directory_files.php?directory=products/');
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error || 'Failed to load categories');
            }

            // For each category, fetch its contents
            for (const file of data.files) {
                if (typeof file === 'object' && file.name) {
                    const categoryName = file.name;
                    try {
                        const categoryResponse = await fetch(`get_directory_files.php?directory=products/${categoryName}`);
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
                img.src = images[0].path;
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

    // Search function
    function handleSearch(searchTerm) {
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
                img.src = image.path;
                img.alt = image.name;

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
            lazyLoadImage(img, image.path);

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
                        path: img.getAttribute('src'),
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
        img.onload = () => {
            // Determine if we need navigation buttons
            const showNavigation = categoryImages.length > 1;
            
            fullscreen.innerHTML = `
                <div class="fullscreen-image-container">
                    <img src="${imagePath}" class="fullscreen-image" alt="${productNumber}">
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
                            showFullscreenImage(prevImage.path, prevImage.name, imageIndex);
                        }
                    });
                }
                
                if (nextBtn) {
                    nextBtn.addEventListener('click', () => {
                        if (imageIndex < categoryImages.length - 1) {
                            imageIndex++;
                            const nextImage = categoryImages[imageIndex];
                            showFullscreenImage(nextImage.path, nextImage.name, imageIndex);
                        }
                    });
                }
                
                // Add touch swipe functionality for mobile devices
                let touchStartX = 0;
                let touchEndX = 0;
                const imageContainer = fullscreen.querySelector('.fullscreen-image-container');
                
                // Touch start handler
                const handleTouchStart = (e) => {
                    touchStartX = e.changedTouches[0].screenX;
                };
                
                // Touch end handler
                const handleTouchEnd = (e) => {
                    touchEndX = e.changedTouches[0].screenX;
                    handleSwipe();
                };
                
                // Handle swipe logic
                const handleSwipe = () => {
                    const minSwipeDistance = 50; // Minimum distance for a swipe to be registered
                    const swipeDistance = touchEndX - touchStartX;
                    
                    if (Math.abs(swipeDistance) < minSwipeDistance) return; // Not a significant swipe
                    
                    if (swipeDistance > 0) {
                        // Swiped right - go to previous image
                        if (imageIndex > 0) {
                            imageIndex--;
                            const prevImage = categoryImages[imageIndex];
                            showFullscreenImage(prevImage.path, prevImage.name, imageIndex);
                        }
                    } else {
                        // Swiped left - go to next image
                        if (imageIndex < categoryImages.length - 1) {
                            imageIndex++;
                            const nextImage = categoryImages[imageIndex];
                            showFullscreenImage(nextImage.path, nextImage.name, imageIndex);
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
                        showFullscreenImage(prevImage.path, prevImage.name, imageIndex);
                    } else if (e.key === 'ArrowRight' && imageIndex < categoryImages.length - 1) {
                        imageIndex++;
                        const nextImage = categoryImages[imageIndex];
                        showFullscreenImage(nextImage.path, nextImage.name, imageIndex);
                    }
                }
            };
            document.addEventListener('keydown', handleKeyboard);
            cleanup.add(() => document.removeEventListener('keydown', handleKeyboard));
        };

        img.src = imagePath;
        
        // Add error handling for image loading
        img.onerror = () => {
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
});
