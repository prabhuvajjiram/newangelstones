document.addEventListener('DOMContentLoaded', function() {
    // Get DOM elements
    const categoryGrid = document.getElementById('category-grid');
    
    if (!categoryGrid) {
        console.error('Category grid element not found');
        return;
    }
    
    // Add loading indicator
    categoryGrid.innerHTML = '<div class="loading">Loading categories...</div>';
    
    // Fetch categories
    fetch('get_directory_files.php?action=get_categories')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            console.log('Categories data:', data);
            
            // Clear loading indicator
            categoryGrid.innerHTML = '';
            
            if (!data.success || !data.categories || data.categories.length === 0) {
                console.warn('No categories found or invalid response');
                addFallbackCategories();
                return;
            }
            
            // Add categories to the grid
            data.categories.forEach(category => {
                if (category.thumbnail && category.display_name) {
                    const categoryElement = createCategoryElement(category);
                    categoryGrid.appendChild(categoryElement);
                }
            });
            
            // If no valid categories were added, add fallback
            if (categoryGrid.children.length === 0) {
                addFallbackCategories();
            }
            
            // Add click event listeners to all category links
            addCategoryClickHandlers();
        })
        .catch(error => {
            console.error('Error fetching categories:', error);
            categoryGrid.innerHTML = '<div class="loading">Error loading categories</div>';
            
            // Add fallback category after a short delay
            setTimeout(addFallbackCategories, 1000);
        });
    
    // Create a category element
    function createCategoryElement(category) {
        const div = document.createElement('div');
        div.className = 'category-item';
        
        const categoryId = category.name.toLowerCase().replace(/\s+/g, '-');
        const imageCount = category.image_count || 0;
        const countText = imageCount === 1 ? 'Design' : 'Designs';
        
        div.innerHTML = `
            <a href="#${categoryId}-collection" class="category-link">
                <div class="category-image">
                    <img src="${category.thumbnail}" alt="${category.display_name}" loading="lazy" onerror="this.src='images/default-thumbnail.jpg'">
                </div>
                <h4>${category.display_name}</h4>
                <span class="category-count">${imageCount} ${countText}</span>
            </a>
        `;
        
        return div;
    }
    
    // Add fallback categories
    function addFallbackCategories() {
        categoryGrid.innerHTML = '';
        
        const fallbackCategories = [
            {
                name: 'benches',
                display_name: 'Benches',
                image_count: 3,
                thumbnail: 'images/products/Benches/Founain2.png'
            },
            {
                name: 'MBNA_2025',
                display_name: 'MBNA 2025',
                image_count: 26,
                thumbnail: 'images/products/MBNA_2025/thumbnails/AG-116.png'
            },
            {
                name: 'monuments',
                display_name: 'Monuments',
                image_count: 3,
                thumbnail: 'images/products/Monuments/granite-monuments-project02.png'
            },
            {
                name: 'columbarium',
                display_name: 'Columbarium',
                image_count: 1,
                thumbnail: 'images/products/columbarium/customized-designs-project03.png'
            }
        ];
        
        fallbackCategories.forEach(category => {
            const categoryElement = createCategoryElement(category);
            categoryGrid.appendChild(categoryElement);
        });
        
        // Add click event listeners to all category links
        addCategoryClickHandlers();
    }
    
    // Function to add click handlers to all category links
    function addCategoryClickHandlers() {
        const categoryLinks = document.querySelectorAll('.category-link');
        categoryLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const categoryId = this.getAttribute('href').replace('#', '').replace('-collection', '');
                loadCategoryImages(categoryId);
            });
        });
    }
    
    // Function to load images for a specific category
    function loadCategoryImages(categoryId) {
        console.log('Loading images for category:', categoryId);
        
        // Create modal for displaying images
        const modal = document.createElement('div');
        modal.className = 'category-modal';
        modal.innerHTML = `
            <div class="category-modal-content">
                <span class="category-modal-close">&times;</span>
                <h2 class="category-modal-title">Loading...</h2>
                <div class="category-modal-images">
                    <div class="loading">Loading images...</div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Add event listener to close button
        const closeBtn = modal.querySelector('.category-modal-close');
        closeBtn.addEventListener('click', function() {
            document.body.removeChild(modal);
        });
        
        // Close modal when clicking outside of content
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                document.body.removeChild(modal);
            }
        });
        
        // Add keyboard event to close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && document.body.contains(modal)) {
                document.body.removeChild(modal);
            }
        });
        
        // Fetch images for the category
        const baseUrl = window.location.origin + window.location.pathname.replace(/\/[^/]*$/, '/');
        
        // Special handling for MBNA_2025
        let categoryPath = categoryId;
        if (categoryId.toLowerCase() === 'mbna_2025') {
            categoryPath = 'MBNA_2025';
        } else {
            // For other categories, just capitalize first letter
            categoryPath = categoryId.charAt(0).toUpperCase() + categoryId.slice(1);
        }
        
        console.log('Fetching images from:', `${baseUrl}get_directory_files.php?directory=images/products/${categoryPath}`);
        
        fetch(`${baseUrl}get_directory_files.php?directory=images/products/${categoryPath}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Category images data:', data);
                
                if (!data.success || !data.files || data.files.length === 0) {
                    console.error('No images found. Debug info:', data.debug);
                    throw new Error('No images found for this category');
                }
                
                // Update modal title
                const title = categoryId.replace(/_/g, ' ');
                modal.querySelector('.category-modal-title').textContent = title;
                
                // Clear existing images
                const imagesContainer = modal.querySelector('.category-modal-images');
                imagesContainer.innerHTML = '';
                
                // Store image files for navigation
                const imageFiles = data.files;
                
                // Create image elements
                imageFiles.forEach((file, index) => {
                    const imgContainer = document.createElement('div');
                    imgContainer.className = 'category-modal-image-container';
                    imgContainer.dataset.index = index;
                    
                    const img = document.createElement('img');
                    img.src = `${baseUrl}images/products/${categoryPath}/${file}`;
                    img.alt = file.replace(/\.[^/.]+$/, ''); // Remove file extension for alt text
                    img.loading = 'lazy';
                    img.onerror = function() {
                        console.error(`Failed to load image: ${this.src}`);
                        this.src = `${baseUrl}images/default-thumbnail.jpg`;
                    };
                    
                    imgContainer.appendChild(img);
                    
                    // Add filename below image
                    const filename = document.createElement('p');
                    filename.textContent = file;
                    imgContainer.appendChild(filename);
                    
                    // Add click event to open full-size image (on the container)
                    imgContainer.addEventListener('click', function() {
                        openFullSizeImageWithNavigation(categoryId, imageFiles, index);
                    });
                    
                    imagesContainer.appendChild(imgContainer);
                });
            })
            .catch(error => {
                console.error('Error fetching category images:', error);
                modal.querySelector('.category-modal-images').innerHTML = '<p>Error loading images. Please try again later.</p>';
            });
    }
    
    // Function to open full-size image with navigation
    function openFullSizeImageWithNavigation(categoryId, imageFiles, currentIndex) {
        const baseUrl = window.location.origin + window.location.pathname.replace(/\/[^/]*$/, '/');
        
        // Store current scroll position
        const scrollPosition = window.pageYOffset || document.documentElement.scrollTop;
        
        // Use the same case-sensitive path handling as in the main function
        let categoryPath = categoryId;
        if (categoryId.toLowerCase() === 'mbna_2025') {
            categoryPath = 'MBNA_2025';
        } else {
            categoryPath = categoryId.charAt(0).toUpperCase() + categoryId.slice(1);
        }

        // Remove any existing full-size image overlays
        const existingOverlays = document.querySelectorAll('.full-image-overlay');
        existingOverlays.forEach(overlay => overlay.remove());

        // Create overlay
        const overlay = document.createElement('div');
        overlay.className = 'full-image-overlay';
        
        // Create close button first so it's always on top
        const closeButton = document.createElement('button');
        closeButton.className = 'close-button';
        closeButton.innerHTML = '&times;';
        closeButton.setAttribute('aria-label', 'Close');
        closeButton.addEventListener('click', (e) => {
            e.stopPropagation();
            overlay.remove();
            enableScroll(scrollPosition);
        });
        overlay.appendChild(closeButton);
        
        // Create image container
        const imageContainer = document.createElement('div');
        imageContainer.className = 'full-image-container';
        
        // Create navigation container
        const navContainer = document.createElement('div');
        navContainer.className = 'navigation-container';
        
        // Create image element
        const img = document.createElement('img');
        img.className = 'full-size-image';
        img.src = `${baseUrl}images/products/${categoryPath}/${imageFiles[currentIndex]}`;
        img.alt = imageFiles[currentIndex].replace(/\.[^/.]+$/, '');
        
        // Add loading indicator
        const loadingIndicator = document.createElement('div');
        loadingIndicator.className = 'loading-indicator';
        loadingIndicator.textContent = 'Loading...';
        imageContainer.appendChild(loadingIndicator);
        
        // Handle image load
        img.onload = () => {
            loadingIndicator.remove();
        };
        
        // Handle image error
        img.onerror = () => {
            loadingIndicator.textContent = 'Error loading image';
            console.error(`Failed to load full-size image: ${img.src}`);
        };
        
        imageContainer.appendChild(img);
        
        // Add navigation buttons if there are multiple images
        if (imageFiles.length > 1) {
            // Previous button
            if (currentIndex > 0) {
                const prevButton = document.createElement('button');
                prevButton.className = 'nav-button prev';
                prevButton.innerHTML = '&#10094;';
                prevButton.setAttribute('aria-label', 'Previous image');
                prevButton.addEventListener('click', (e) => {
                    e.stopPropagation();
                    overlay.remove();
                    openFullSizeImageWithNavigation(categoryId, imageFiles, currentIndex - 1);
                });
                navContainer.appendChild(prevButton);
            }
            
            // Next button
            if (currentIndex < imageFiles.length - 1) {
                const nextButton = document.createElement('button');
                nextButton.className = 'nav-button next';
                nextButton.innerHTML = '&#10095;';
                nextButton.setAttribute('aria-label', 'Next image');
                nextButton.addEventListener('click', (e) => {
                    e.stopPropagation();
                    overlay.remove();
                    openFullSizeImageWithNavigation(categoryId, imageFiles, currentIndex + 1);
                });
                navContainer.appendChild(nextButton);
            }
        }
        
        // Add all elements to the overlay
        imageContainer.appendChild(navContainer);
        overlay.appendChild(imageContainer);
        
        // Close on overlay click (but not on image click)
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                e.stopPropagation();
                overlay.remove();
                enableScroll(scrollPosition);
            }
        });
        
        // Close on escape key
        document.addEventListener('keydown', function escapeHandler(e) {
            if (e.key === 'Escape') {
                overlay.remove();
                document.removeEventListener('keydown', escapeHandler);
                enableScroll(scrollPosition);
            }
        });
        
        // Prevent scrolling on mobile when overlay is open
        document.body.style.overflow = 'hidden';
        document.body.style.position = 'fixed';
        document.body.style.width = '100%';
        document.body.style.top = `-${scrollPosition}px`;
        
        overlay.addEventListener('touchmove', (e) => {
            e.preventDefault();
        }, { passive: false });
        
        // Function to enable scrolling
        function enableScroll(position) {
            document.body.style.overflow = '';
            document.body.style.position = '';
            document.body.style.width = '';
            document.body.style.top = '';
            window.scrollTo({
                top: position,
                behavior: 'instant'
            });
        }
        
        // Ensure scroll is re-enabled when overlay is removed
        overlay.addEventListener('remove', () => enableScroll(scrollPosition));
        
        document.body.appendChild(overlay);
    }
    
    // Lazy load hero video
    const video = document.getElementById('hero-video');
    if (!video) return;

    const source = video.querySelector('source');
    if (!source) return;
    
    // Function to load video
    function loadVideo() {
        // Set the actual video source
        source.src = source.dataset.src;
        // Load the video
        video.load();
        
        // Play when ready
        video.addEventListener('canplay', function onCanPlay() {
            video.classList.add('loaded');
            video.play().catch(function(error) {
                console.log("Video autoplay failed:", error);
                // If autoplay fails, show the poster image
                video.classList.remove('loaded');
                const poster = document.querySelector('.hero-poster');
                if (poster) {
                    poster.style.opacity = '1';
                }
            });
            video.removeEventListener('canplay', onCanPlay);
        });
    }

    // Load immediately on mobile devices
    if (window.innerWidth <= 768) {
        loadVideo();
        return;
    }
    
    // Use Intersection Observer for desktop
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    loadVideo();
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '50px'
        });
        
        observer.observe(video);
    } else {
        // Fallback
        loadVideo();
    }
    
    // Handle visibility change
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            video.pause();
        } else if (video.classList.contains('loaded')) {
            video.play().catch(function() {
                // Silently handle autoplay errors
            });
        }
    });
});
