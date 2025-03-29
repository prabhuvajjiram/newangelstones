// Product Categories JavaScript
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM Content Loaded - New implementation');
    
    // Get DOM elements
    const categoryGrid = document.getElementById('category-grid');
    const searchInput = document.getElementById('product-search');
    
    if (!categoryGrid) {
        console.error('Category grid element not found');
        return;
    }

    // Track current state
    let currentCategory = '';
    let currentImages = [];
    let currentImageIndex = 0;
    let currentModal = null;
    
    // Update category counts when the page loads
    updateCategoryCounts();
    
    // Function to update category counts
    function updateCategoryCounts() {
        // Get all category items
        const categoryItems = document.querySelectorAll('.category-item');
        console.log(`Updating counts for ${categoryItems.length} categories`);
        
        // Process each category item
        categoryItems.forEach(item => {
            const link = item.querySelector('.category-link');
            const countSpan = item.querySelector('.category-count');
            
            if (link && countSpan) {
                // Extract category name
                const href = link.getAttribute('href');
                const categoryName = href.substring(1).replace(/-collection/g, '');
                
                console.log(`Fetching count for category: ${categoryName}`);
                
                // Call API to get image count
                fetch(`get_directory_files.php?directory=images/products/${encodeURIComponent(categoryName)}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! Status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log(`Category ${categoryName} response:`, data);
                        
                        if (data && data.success && Array.isArray(data.files)) {
                            // Update the count display
                            const count = data.files.length;
                            countSpan.textContent = `${count} ${count === 1 ? 'Design' : 'Designs'}`;
                            console.log(`Updated ${categoryName} count to ${count}`);
                            
                            // Also update any hardcoded counts in the HTML to match real values
                            const categoryCounts = document.querySelectorAll(`.category-item a[href="#${categoryName}-collection"] .category-count`);
                            categoryCounts.forEach(span => {
                                span.textContent = `${count} ${count === 1 ? 'Design' : 'Designs'}`;
                            });
                            
                            // If this is the Monuments category and we have exactly 28 files, force a reload
                            // This is a special fix to ensure the 28 monument images display correctly
                            if (categoryName.toLowerCase() === 'monuments' && count === 28) {
                                console.log('Special case: Monuments category with 28 images detected');
                                
                                // Store the files in localStorage for faster access
                                localStorage.setItem('monuments_files', JSON.stringify(data.files));
                            }
                        } else {
                            console.error(`Invalid data format for ${categoryName}:`, data);
                            // Set a "?" if we couldn't get the count but don't show zero
                            if (countSpan.textContent.includes('0')) {
                                countSpan.textContent = '? Designs';
                            }
                        }
                    })
                    .catch(error => {
                        console.error(`Error getting count for ${categoryName}:`, error);
                        // Don't show zero if there's an error
                        if (countSpan.textContent.includes('0')) {
                            countSpan.textContent = '? Designs';
                        }
                    });
            }
        });
    }

    // Add styles for thumbnail grid and fullscreen view
    const styleEl = document.createElement('style');
    styleEl.textContent = `
        .category-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
            z-index: 10005;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow-y: auto;
            padding: 20px;
        }

        .category-modal-content {
            position: relative;
            background: rgba(0, 0, 0, 0.95);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 20px;
            width: 90%;
            max-width: 1200px;
            max-height: 90vh;
            overflow-y: auto;
            border-radius: 8px;
            color: #fff;
        }

        .modal-close {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            font-size: 24px;
            transition: all 0.3s ease;
            z-index: 10006;
        }

        .modal-close:hover {
            background: black;
            border-color: rgba(255, 255, 255, 0.5);
            transform: scale(1.1);
        }

        .category-modal-title {
            text-align: center;
            margin-bottom: 20px;
            font-size: 24px;
            color: #fff;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .thumbnail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
            padding: 15px;
            max-height: 70vh;
            overflow-y: auto;
        }

        .thumbnail-item {
            cursor: pointer;
            border-radius: 5px;
            overflow: hidden;
            position: relative;
            transition: transform 0.3s, box-shadow 0.3s;
            aspect-ratio: 1;
            border: 2px solid transparent;
        }

        .thumbnail-item:hover {
            transform: scale(1.05);
            box-shadow: 0 0 15px rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.5);
        }

        .thumbnail-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .thumbnail-name {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 5px;
            font-size: 12px;
            text-align: center;
        }

        .full-size-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.95);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10006;
        }

        .full-size-modal-content {
            position: relative;
            max-width: 90%;
            max-height: 90%;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .full-size-image-container {
            position: relative;
            max-width: 100%;
            max-height: 80vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .fullscreen-image {
            max-width: 100%;
            max-height: 80vh;
            object-fit: contain;
        }

        .nav-button {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0, 0, 0, 0.7);
            color: white;
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            font-size: 24px;
            z-index: 10007;
            transition: all 0.3s;
        }

        .nav-button:hover {
            background: black;
            border-color: rgba(255,255,255,0.5);
            transform: translateY(-50%) scale(1.1);
        }

        .nav-button.prev {
            left: 20px;
        }

        .nav-button.next {
            right: 20px;
        }

        .nav-button[disabled] {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .no-results {
            text-align: center;
            color: #fff;
            font-size: 18px;
            padding: 20px;
        }

        .image-counter {
            position: absolute;
            bottom: 20px;
            left: 20px;
            color: #fff;
            font-size: 18px;
        }

        .image-title {
            position: absolute;
            bottom: 20px;
            right: 20px;
            color: #fff;
            font-size: 18px;
        }

        @media (max-width: 768px) {
            .thumbnail-grid {
                grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
                gap: 10px;
            }
            
            .nav-button {
                width: 35px;
                height: 35px;
                font-size: 18px;
            }
            
            .modal-close {
                width: 35px;
                height: 35px;
                font-size: 18px;
            }
        }
    `;
    document.head.appendChild(styleEl);

    // Function to show category images in thumbnail grid
    function showCategory(categoryId) {
        currentCategory = categoryId;
        const categoryName = categoryId.replace(/-collection/g, '');
        
        console.log(`Showing category: ${categoryName}`);
        
        // Create modal if it doesn't exist
        if (!document.getElementById('category-modal')) {
            console.log('Creating new category modal');
            const modal = document.createElement('div');
            modal.className = 'category-modal';
            modal.id = 'category-modal';
            
            modal.innerHTML = `
                <div class="category-modal-content">
                    <div class="modal-header">
                        <h2 class="category-modal-title">${categoryName}</h2>
                        <button class="modal-close">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="search-container">
                            <input type="text" id="modal-search" placeholder="Search items...">
                            <button id="search-button">Search</button>
                        </div>
                        <div class="thumbnail-grid" id="category-thumbnails"></div>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // Setup search
            const searchInput = modal.querySelector('#modal-search');
            const searchButton = modal.querySelector('#search-button');
            
            searchButton.addEventListener('click', () => {
                searchImages(searchInput.value);
            });
            
            searchInput.addEventListener('keyup', (e) => {
                if (e.key === 'Enter') {
                    searchImages(searchInput.value);
                }
            });
            
            // Set up close button listeners right after creating the modal
            setupModalCloseListeners();
        }
        
        // Show the modal
        const modal = document.getElementById('category-modal');
        modal.classList.add('active');
        
        // Set modal title
        const modalTitle = modal.querySelector('.category-modal-title');
        modalTitle.textContent = categoryName;
        
        // Get thumbnails container
        const thumbnailGrid = modal.querySelector('.thumbnail-grid');
        thumbnailGrid.innerHTML = '<div class="loading">Loading...</div>';
        
        // Special case for monuments - we need to handle case sensitivity
        // Try different casing variations to find the right one
        const categoryVariations = [
            categoryName,                      // Original
            categoryName.toLowerCase(),        // All lowercase
            categoryName.toUpperCase(),        // All uppercase
            categoryName.charAt(0).toUpperCase() + categoryName.slice(1).toLowerCase() // First letter uppercase
        ];
        
        // Try each variation until one works
        tryLoadCategory(categoryVariations, 0, thumbnailGrid);
    }
    
    // Function to try loading a category with different case variations
    function tryLoadCategory(variations, index, container) {
        if (index >= variations.length) {
            // We've tried all variations without success
            container.innerHTML = '<div class="no-results">No images found in this category. Please check the directory name.</div>';
            return;
        }
        
        const categoryName = variations[index];
        console.log(`Trying category variation ${index+1}/${variations.length}: ${categoryName}`);
        
        // Fetch images for this category
        const apiUrl = `get_directory_files.php?directory=images/products/${encodeURIComponent(categoryName)}`;
        console.log(`Fetching images from: ${apiUrl}`);
        
        fetch(apiUrl)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('API Response:', data);
                
                // Process the response
                if (!data.success || !data.files || data.files.length === 0) {
                    console.log(`No images found with variation ${categoryName}, trying next variation`);
                    // Try the next variation
                    tryLoadCategory(variations, index + 1, container);
                    return;
                }
                
                // Clear loading message
                container.innerHTML = '';
                
                // We found images, process them
                processImages(data.files, categoryName, container);
                
                // Cache this successful variation for future use
                localStorage.setItem('successful_category_' + categoryName.toLowerCase(), categoryName);
                
                // FIXED: Set up close button event listener after content is loaded
                setupModalCloseListeners();
            })
            .catch(error => {
                console.error('Error fetching images:', error);
                // Try the next variation
                tryLoadCategory(variations, index + 1, container);
            });
    }
    
    // Function to set up close button listeners for the modal
    function setupModalCloseListeners() {
        const modal = document.getElementById('category-modal');
        if (!modal) return;
        
        // Add event listener to close button
        const closeButton = modal.querySelector('.modal-close');
        if (closeButton) {
            // Remove any existing listeners to prevent duplicates
            const newCloseButton = closeButton.cloneNode(true);
            closeButton.parentNode.replaceChild(newCloseButton, closeButton);
            
            // Add the new listener
            newCloseButton.addEventListener('click', function() {
                console.log('Close button clicked');
                closeModals();
            });
        }
        
        // Close when clicking outside the content
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                console.log('Clicked outside modal content');
                closeModals();
            }
        });
        
        // Close on ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                console.log('ESC key pressed');
                closeModals();
            }
        });
    }
    
    // Helper function to process images and add them to the grid
    function processImages(files, categoryName, container) {
        // If data structure is new format (objects), use directly, otherwise transform
        if (Array.isArray(files) && files.length > 0 && typeof files[0] === 'object' && files[0].path) {
            currentImages = files;
        } else if (Array.isArray(files)) {
            // Legacy format - convert string filenames to objects
            currentImages = files.map(file => {
                return {
                    name: typeof file === 'string' ? file.replace(/\.[^/.]+$/, "") : file,
                    path: typeof file === 'string' ? `images/products/${categoryName}/${file}` : file
                };
            });
        } else {
            currentImages = [];
        }
        
        console.log(`Found ${currentImages.length} images for category ${categoryName}:`, currentImages);
        
        if (currentImages.length === 0) {
            container.innerHTML = '<div class="no-results">No images found in this category.</div>';
            return;
        }
        
        // Add each image as a thumbnail - following the "thumbnails first" approach
        currentImages.forEach((image, index) => {
            const thumbnail = document.createElement('div');
            thumbnail.className = 'thumbnail-item';
            
            // Get image path - handle both string and object formats
            let imagePath = typeof image === 'string' ? image : 
                          (image.path ? image.path : `${currentCategory}/${image.name || image}`);
            
            const imageName = typeof image === 'string' ? image.replace(/\.[^/.]+$/, "") : 
                             (image.name || imagePath.split('/').pop().replace(/\.[^/.]+$/, ""));
            
            console.log(`Opening thumbnail image ${index}: ${imageName} at path ${imagePath}`);
            
            // Create the image element
            const img = document.createElement('img');
            img.src = imagePath;
            img.alt = imageName;
            img.loading = 'lazy';
            
            // Add onerror handler to use default thumbnail if image fails to load
            img.onerror = function() {
                console.error(`Failed to load image: ${imagePath}`);
                
                // Try alternative path formats before using default image
                if (imagePath.includes('images/products/')) {
                    // Try with just removing the prefix and keeping the subfolder
                    const pathParts = imagePath.split('images/products/');
                    if (pathParts.length > 1) {
                        const simplifiedPath = 'images/' + pathParts[1];
                        console.log(`Trying alternative path: ${simplifiedPath}`);
                        this.src = simplifiedPath;
                        this.onerror = function() {
                            // If that also fails, try direct path in images folder
                            const filename = imagePath.split('/').pop();
                            const directPath = 'images/' + filename;
                            console.log(`Trying direct path: ${directPath}`);
                            this.src = directPath;
                            this.onerror = function() {
                                // Finally fall back to default image if all attempts fail
                                console.log('All path attempts failed, using default image');
                                this.src = 'images/default-thumbnail.jpg';
                                this.onerror = null;
                            };
                        };
                    } else {
                        this.src = 'images/default-thumbnail.jpg';
                        this.onerror = null;
                    }
                } else {
                    this.src = 'images/default-thumbnail.jpg';
                    this.onerror = null;
                }
            };
            
            const nameDiv = document.createElement('div');
            nameDiv.className = 'thumbnail-name';
            nameDiv.textContent = imageName;
            
            thumbnail.appendChild(img);
            thumbnail.appendChild(nameDiv);
            
            // Add click event to show fullscreen view
            thumbnail.addEventListener('click', () => {
                showFullscreenImage(index);
            });
            
            container.appendChild(thumbnail);
        });
    }
    
    // Function to show fullscreen image with navigation
    function showFullscreenImage(index) {
        currentImageIndex = index;
        
        // Close any existing fullscreen views
        const existingFullscreen = document.getElementById('fullscreen-container');
        if (existingFullscreen) {
            document.body.removeChild(existingFullscreen);
        }
        
        // Get the current image
        const currentImage = currentImages[index];
        
        // Get image path and name (handling both string and object formats)
        let imagePath = typeof currentImage === 'string' ? currentImage : 
                      (currentImage.path ? currentImage.path : `${currentCategory}/${currentImage.name || currentImage}`);
        
        const imageName = typeof currentImage === 'string' ? currentImage.replace(/\.[^/.]+$/, "") : 
                         (currentImage.name || imagePath.split('/').pop().replace(/\.[^/.]+$/, ""));
        
        console.log(`Opening fullscreen image ${index}: ${imageName} at path ${imagePath}`);
        
        // Create fullscreen view
        const fullscreenView = document.createElement('div');
        fullscreenView.className = 'full-size-modal'; // Changed to match CSS class
        fullscreenView.id = 'fullscreen-container';
        
        fullscreenView.innerHTML = `
            <div class="full-size-modal-content">
                <div class="full-size-image-container">
                    <img src="${imagePath}" alt="${imageName}" class="fullscreen-image" onerror="this.src='images/default-thumbnail.jpg'; this.onerror=null;">
                </div>
                <button class="nav-button prev"${index === 0 ? ' disabled' : ''}>&lt;</button>
                <button class="nav-button next"${index === currentImages.length - 1 ? ' disabled' : ''}>&gt;</button>
                <button class="modal-close">&times;</button>
                <div class="image-counter">${index + 1} / ${currentImages.length}</div>
                <div class="image-title">${imageName}</div>
            </div>
        `;
        
        document.body.appendChild(fullscreenView);
        
        // Add event listeners
        const closeButton = fullscreenView.querySelector('.modal-close');
        closeButton.addEventListener('click', () => {
            document.body.removeChild(fullscreenView);
        });
        
        // Close when clicking outside the image
        fullscreenView.addEventListener('click', (e) => {
            if (e.target === fullscreenView) {
                document.body.removeChild(fullscreenView);
            }
        });
        
        // Navigation
        const prevButton = fullscreenView.querySelector('.nav-button.prev');
        const nextButton = fullscreenView.querySelector('.nav-button.next');
        
        prevButton.addEventListener('click', () => {
            if (currentImageIndex > 0) {
                document.body.removeChild(fullscreenView);
                showFullscreenImage(currentImageIndex - 1);
            }
        });
        
        nextButton.addEventListener('click', () => {
            if (currentImageIndex < currentImages.length - 1) {
                document.body.removeChild(fullscreenView);
                showFullscreenImage(currentImageIndex + 1);
            }
        });
        
        // Keyboard navigation
        const handleKeyDown = (e) => {
            if (e.key === 'ArrowLeft' && currentImageIndex > 0) {
                document.body.removeChild(fullscreenView);
                showFullscreenImage(currentImageIndex - 1);
            } else if (e.key === 'ArrowRight' && currentImageIndex < currentImages.length - 1) {
                document.body.removeChild(fullscreenView);
                showFullscreenImage(currentImageIndex + 1);
            } else if (e.key === 'Escape') {
                document.body.removeChild(fullscreenView);
                document.removeEventListener('keydown', handleKeyDown);
            }
        };
        
        document.addEventListener('keydown', handleKeyDown);
    }
    
    // Function to close all modals
    function closeModals() {
        console.log('Closing all modals');
        
        // Remove category modal directly from DOM
        const categoryModal = document.getElementById('category-modal');
        if (categoryModal) {
            console.log('Found category modal, removing it');
            document.body.removeChild(categoryModal);
        }
        
        // Remove fullscreen modal directly from DOM
        const fullscreenContainer = document.getElementById('fullscreen-container');
        if (fullscreenContainer) {
            console.log('Found fullscreen container, removing it');
            document.body.removeChild(fullscreenContainer);
        }
        
        // Reset current images array
        currentImages = [];
        currentImageIndex = -1;
        
        // Remove keydown event listener
        document.removeEventListener('keydown', handleKeyDown);
    }
    
    // Handle keyboard events
    function handleKeyDown(e) {
        if (e.key === 'Escape') {
            closeModals();
        }
    }
    
    // Add event listeners to all category links
    console.log('Setting up category link event listeners');
    document.querySelectorAll('.category-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const href = this.getAttribute('href');
            // Extract category name from href
            const categoryName = href.substring(1); // Remove the '#' character
            console.log(`Category link clicked: ${categoryName}`);
            showCategory(categoryName);
        });
    });
    
    // Search functionality
    if (searchInput) {
        let searchTimeout;
        
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            
            searchTimeout = setTimeout(() => {
                const searchTerm = this.value.trim();
                
                if (searchTerm.length > 2) {
                    performSearch(searchTerm);
                } else if (currentModal) {
                    closeModals();
                }
            }, 300);
        });
        
        function performSearch(searchTerm) {
            console.log(`Searching for: ${searchTerm}`);
            
            // Close any open modals
            closeModals();
            
            // Create search results modal
            const modal = document.createElement('div');
            modal.className = 'category-modal';
            modal.innerHTML = `
                <div class="category-modal-content">
                    <button class="modal-close">&times;</button>
                    <h2 class="category-modal-title">Search Results for "${searchTerm}"</h2>
                    <div class="thumbnail-grid">
                        <div style="text-align: center; padding: 20px;">
                            <i class="bi bi-arrow-repeat spin"></i> Searching...
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            currentModal = modal;
            
            // Prevent body scrolling
            document.body.style.overflow = 'hidden';
            
            // Add event listener to close button
            const closeButton = modal.querySelector('.modal-close');
            closeButton.addEventListener('click', closeModals);
            
            // Close when clicking outside the content
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    closeModals();
                }
            });
            
            // Add escape key handler
            document.addEventListener('keydown', handleKeyDown);
            
            // Get the grid container
            const thumbnailGrid = modal.querySelector('.thumbnail-grid');
            
            // Perform search
            fetch(`get_directory_files.php?action=search&term=${encodeURIComponent(searchTerm)}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('API response:', data); // Debug log
                    
                    // Extract files from the API response
                    let imageFiles = [];
                    if (data && typeof data === 'object') {
                        if (data.success === true && Array.isArray(data.files)) {
                            // Successfully got files array from the response
                            imageFiles = data.files;
                        } else if (data.success === false) {
                            // API returned an error
                            throw new Error(data.error || 'Unknown API error');
                        }
                    } else if (Array.isArray(data)) {
                        // Direct array response
                        imageFiles = data;
                    }
                    
                    // Store the processed images
                    currentImages = imageFiles.map(file => {
                        // Check if file is already a complete object
                        if (typeof file === 'object' && file.path) {
                            return file;
                        }
                        
                        // Convert simple filename to object with path
                        const path = `images/products/${file}`;
                        return {
                            name: file.replace(/\.[^/.]+$/, ""), // Remove extension for display name
                            path: path
                        };
                    });
                    
                    // Clear the grid
                    thumbnailGrid.innerHTML = '';
                    
                    // Update title with result count
                    const modalTitle = modal.querySelector('.category-modal-title');
                    modalTitle.textContent = `Search Results for "${searchTerm}" (${currentImages.length} found)`;
                    
                    if (!currentImages.length) {
                        thumbnailGrid.innerHTML = '<div class="no-results">No results found. Try a different search term.</div>';
                        return;
                    }
                    
                    // Add each result as a thumbnail
                    currentImages.forEach((image, index) => {
                        const thumbnail = document.createElement('div');
                        thumbnail.className = 'thumbnail-item';
                        
                        // Just use the original image for thumbnails
                        const img = document.createElement('img');
                        img.src = image.path;
                        img.alt = image.name;
                        img.loading = 'lazy';
                        
                        // Add onerror handler to use default thumbnail if image fails to load
                        img.onerror = function() {
                            console.log(`Failed to load image: ${image.path}`);
                            this.src = 'images/default-thumbnail.jpg';
                        };
                        
                        const nameDiv = document.createElement('div');
                        nameDiv.className = 'thumbnail-name';
                        nameDiv.textContent = image.name;
                        
                        thumbnail.appendChild(img);
                        thumbnail.appendChild(nameDiv);
                        
                        // Add click event to show fullscreen view
                        thumbnail.addEventListener('click', () => {
                            showFullscreenImage(index);
                        });
                        
                        thumbnailGrid.appendChild(thumbnail);
                    });
                })
                .catch(error => {
                    console.error('Error searching:', error);
                    thumbnailGrid.innerHTML = '<div class="no-results">Error performing search. Please try again later.</div>';
                });
        }
    }
    
    console.log('Product Categories initialization complete');
});
