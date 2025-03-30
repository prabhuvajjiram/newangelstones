// Product Categories JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Get DOM elements
    const categoryGrid = document.getElementById('category-grid');
    const searchInput = document.getElementById('product-search');
    
    if (!categoryGrid) {
        return;
    }

    // Track current state
    let currentCategory = '';
    let currentImages = [];
    let currentImageIndex = 0;
    let currentModal = null;
    
    // Function to reset the UI state and show all categories
    function resetUIState() {
        // Clear the search input
        if (searchInput) {
            searchInput.value = '';
        }
        
        // Make sure category grid is visible
        if (categoryGrid) {
            categoryGrid.style.display = 'grid';
        }
    }
    
    // Function to load and display categories
    function loadAndDisplayCategories() {
        // Clear any existing content
        if (categoryGrid) {
            categoryGrid.innerHTML = '<div class="loading">Loading categories...</div>';
        }
        
        // Fetch all categories
        fetch('get_directory_files.php?directory=images/products')
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    if (categoryGrid) {
                        categoryGrid.innerHTML = '<div class="error">Failed to load categories. Please try again later.</div>';
                    }
                    return;
                }
                
                if (!Array.isArray(data.files) || data.files.length === 0) {
                    if (categoryGrid) {
                        categoryGrid.innerHTML = '<div class="no-results">No product categories found.</div>';
                    }
                    return;
                }
                
                // Clear grid and add categories
                if (categoryGrid) {
                    categoryGrid.innerHTML = '';
                    
                    // Filter only directories (categories)
                    const categories = data.files.filter(file => file.type === 'directory');
                    
                    // Add each category
                    categories.forEach(category => {
                        // Create category card
                        const card = createCategoryCard(category.name, category.path);
                        categoryGrid.appendChild(card);
                    });
                    
                    // Update category counts
                    updateCategoryCounts();
                }
            })
            .catch(error => {
                if (categoryGrid) {
                    categoryGrid.innerHTML = '<div class="error">Failed to load categories. Please try again later.</div>';
                }
            });
    }
    
    // Initialize the page 
    // Removed: loadAndDisplayCategories();
    
    // Update category counts when the page loads
    updateCategoryCounts();
    
    // Function to update category counts
    function updateCategoryCounts() {
        // Get all category items
        const categoryItems = document.querySelectorAll('.category-item');
        
        // Process each category item
        categoryItems.forEach(item => {
            const link = item.querySelector('.category-link');
            const countSpan = item.querySelector('.category-count');
            
            if (link && countSpan) {
                // Extract category name
                const href = link.getAttribute('href');
                const categoryName = href.substring(1).replace(/-collection/g, '');
                
                // Call API to get image count
                fetch(`get_directory_files.php?directory=images/products/${encodeURIComponent(categoryName)}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! Status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data && data.success && Array.isArray(data.files)) {
                            // Update the count display
                            const count = data.files.length;
                            countSpan.textContent = `${count} ${count === 1 ? 'Design' : 'Designs'}`;
                            
                            // Also update any hardcoded counts in the HTML to match real values
                            const categoryCounts = document.querySelectorAll(`.category-item a[href="#${categoryName}-collection"] .category-count`);
                            categoryCounts.forEach(span => {
                                span.textContent = `${count} ${count === 1 ? 'Design' : 'Designs'}`;
                            });
                            
                            // If this is the Monuments category and we have exactly 28 files, force a reload
                            // This is a special fix to ensure the 28 monument images display correctly
                            if (categoryName.toLowerCase() === 'monuments' && count === 28) {
                                // Store the files in localStorage for faster access
                                localStorage.setItem('monuments_files', JSON.stringify(data.files));
                            }
                        } else {
                            // Set a "?" if we couldn't get the count but don't show zero
                            if (countSpan.textContent.includes('0')) {
                                countSpan.textContent = '? Designs';
                            }
                        }
                    })
                    .catch(error => {
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
            z-index: 10000;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            box-sizing: border-box;
        }

        .category-modal-content {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            background: rgba(0, 0, 0, 0.95);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            padding: 20px;
            max-height: 90vh;
            overflow-y: auto;
            box-sizing: border-box;
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
            grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
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
            max-width: 100%;
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

        /* Responsive thumbnail adjustments */
        @media (max-width: 768px) {
            .thumbnail-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
                padding: 10px;
            }
        }
        
        @media (max-width: 576px) {
            .thumbnail-grid {
                grid-template-columns: repeat(1, 1fr);
                gap: 8px;
                padding: 8px;
            }
            
            .thumbnail-item {
                max-width: 100%;
                margin: 0 auto;
                width: 100%;
            }
        }
        
        @media (max-width: 375px) {
            .thumbnail-grid {
                grid-template-columns: repeat(1, 1fr);
                gap: 6px;
                padding: 6px;
            }
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
                grid-template-columns: repeat(3, 1fr);
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
        
        // Create modal if it doesn't exist
        if (!document.getElementById('category-modal')) {
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
        
        // Fetch images for this category
        const apiUrl = `get_directory_files.php?directory=images/products/${encodeURIComponent(categoryName)}`;
        
        fetch(apiUrl)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                // Process the response
                if (!data.success || !data.files || data.files.length === 0) {
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
                closeModals();
            });
        }
        
        // Close when clicking outside the content
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModals();
            }
        });
        
        // Close on ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
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
            
            // Create the image element
            const img = document.createElement('img');
            img.src = imagePath;
            img.alt = imageName;
            img.loading = 'lazy';
            
            // Add onerror handler to use default thumbnail if image fails to load
            img.onerror = function() {
                // Try alternative path formats before using default image
                if (imagePath.includes('images/products/')) {
                    // Try with just removing the prefix and keeping the subfolder
                    const pathParts = imagePath.split('images/products/');
                    if (pathParts.length > 1) {
                        const simplifiedPath = 'images/' + pathParts[1];
                        this.src = simplifiedPath;
                        this.onerror = function() {
                            // If that also fails, try direct path in images folder
                            const filename = imagePath.split('/').pop();
                            const directPath = 'images/' + filename;
                            this.src = directPath;
                            this.onerror = function() {
                                // Finally fall back to default image if all attempts fail
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
        // Remove category modal directly from DOM
        const categoryModal = document.getElementById('category-modal');
        if (categoryModal) {
            document.body.removeChild(categoryModal);
        }
        
        // Remove fullscreen modal directly from DOM
        const fullscreenContainer = document.getElementById('fullscreen-container');
        if (fullscreenContainer) {
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
    
    // When closing search modal, handle category grid display
    function closeSearchModal(modal) {
        // Remove the modal
        if (modal && document.body.contains(modal)) {
            document.body.removeChild(modal);
        }
        
        // Clear the search input
        if (searchInput) {
            searchInput.value = '';
        }
        
        // IMPORTANT: Force reload the page to ensure categories are displayed
        // This is the most reliable way to reset the UI state
        window.location.reload();
    }
    
    // Add event listeners to all category links
    document.querySelectorAll('.category-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const href = this.getAttribute('href');
            // Extract category name from href
            const categoryName = href.substring(1); // Remove the '#' character
            
            showCategory(categoryName);
        });
    });
    
    // Function to handle search
    function searchImages(searchTerm) {
        if (!searchTerm || searchTerm.trim() === '') {
            alert('Please enter a search term');
            return;
        }
        
        // Create or reuse search modal
        let modal = document.getElementById('search-modal');
        if (!modal) {
            modal = document.createElement('div');
            modal.className = 'category-modal';
            modal.id = 'search-modal';
            
            modal.innerHTML = `
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="category-modal-title">Search Results for "${searchTerm}"</h2>
                        <button class="modal-close">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="thumbnail-grid">
                            <div style="text-align: center; padding: 20px;">
                                <i class="bi bi-arrow-repeat spin"></i> Searching...
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            
            // Add event listener to close button
            const closeButton = modal.querySelector('.modal-close');
            closeButton.addEventListener('click', function() {
                closeSearchModal(modal);
            });
            
            // Close when clicking outside the content
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    closeSearchModal(modal);
                }
            });
        } else {
            // Update the title
            const modalTitle = modal.querySelector('.category-modal-title');
            modalTitle.textContent = `Search Results for "${searchTerm}"`;
            
            // Clear the results
            const thumbnailGrid = modal.querySelector('.thumbnail-grid');
            thumbnailGrid.innerHTML = '<div style="text-align: center; padding: 20px;"><i class="bi bi-arrow-repeat spin"></i> Searching...</div>';
        }
        
        // Get the thumbnail grid
        const thumbnailGrid = modal.querySelector('.thumbnail-grid');
        
        // We'll search across all product categories
        const categories = ['Monuments', 'monuments', 'MONUMENTS', 'Benches', 'Ceramic', 'Accessories'];
        
        // Initialize results container
        let allResults = [];
        let searchPromises = [];
        
        // Search in each category
        for (const category of categories) {
            const searchPromise = fetch(`get_directory_files.php?directory=images/products/${encodeURIComponent(category)}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    // Extract files from the API response
                    let categoryFiles = [];
                    if (data && typeof data === 'object') {
                        if (data.success === true && Array.isArray(data.files)) {
                            // Successfully got files array from the response
                            categoryFiles = data.files;
                        } else if (data.success === false) {
                            // API returned an error but we'll continue with other categories
                            return [];
                        }
                    } else if (Array.isArray(data)) {
                        // Direct array response
                        categoryFiles = data;
                    }
                    
                    // Filter the files based on search term
                    const termLower = searchTerm.toLowerCase();
                    const matchingFiles = categoryFiles.filter(file => {
                        // For objects (with name and path)
                        if (typeof file === 'object') {
                            // Check name, path and original filename
                            const fileName = file.name ? file.name.toLowerCase() : '';
                            const filePath = file.path ? file.path.toLowerCase() : '';
                            // Get the original filename without path
                            const originalName = filePath.split('/').pop() || '';
                            
                            const matches = fileName.includes(termLower) || 
                                           filePath.includes(termLower) || 
                                           originalName.includes(termLower);
                            
                            return matches;
                        } 
                        // For strings (just filenames)
                        else if (typeof file === 'string') {
                            const fileName = file.toLowerCase();
                            // Remove extension for more flexible matching
                            const fileNameNoExt = fileName.replace(/\.[^/.]+$/, "");
                            
                            const matches = fileName.includes(termLower) || fileNameNoExt.includes(termLower);
                            
                            return matches;
                        }
                        
                        return false;
                    });
                    
                    // Add category info to each file
                    return matchingFiles.map(file => {
                        // If file is already an object
                        if (typeof file === 'object') {
                            return {
                                ...file,
                                category
                            };
                        }
                        
                        // Convert string to object
                        return {
                            name: file.replace(/\.[^/.]+$/, ""),
                            path: `images/products/${category}/${file}`,
                            category
                        };
                    });
                })
                .catch(error => {
                    console.warn(`Error searching ${category}:`, error);
                    return []; // Return empty array to continue with other categories
                });
            
            searchPromises.push(searchPromise);
        }
        
        // Also do a direct file search using a new function in get_directory_files.php
        const directSearchPromise = fetch(`get_directory_files.php?action=findFile&term=${encodeURIComponent(searchTerm)}`)
            .then(response => {
                if (!response.ok) {
                    // This might return 404 if the endpoint doesn't exist yet, but that's ok
                    return { success: false, files: [] };
                }
                return response.json();
            })
            .then(data => {
                if (data && data.success && Array.isArray(data.files)) {
                    return data.files.map(file => ({
                        ...file,
                        category: 'Search Results'
                    }));
                }
                return [];
            })
            .catch(error => {
                return [];
            });
        
        searchPromises.push(directSearchPromise);
        
        // Wait for all searches to complete
        Promise.all(searchPromises)
            .then(resultsArrays => {
                // Combine all results
                allResults = resultsArrays.flat();
                
                // Store as current images
                currentImages = allResults;
                
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
                    
                    // Normalize the image path
                    let imagePath = image.path;
                    // Fix duplicate paths
                    imagePath = imagePath.replace(/(images\/products\/)+/g, 'images/products/');
                    
                    // Create the image element
                    const img = document.createElement('img');
                    img.src = imagePath;
                    img.alt = image.name;
                    img.loading = 'lazy';
                    
                    // Add onerror handler with fallback attempts
                    img.onerror = function() {
                        // Try alternative path formats
                        if (imagePath.includes('images/products/')) {
                            // Try simplified path
                            const pathParts = imagePath.split('images/products/');
                            if (pathParts.length > 1) {
                                const simplifiedPath = 'images/' + pathParts[1];
                                this.src = simplifiedPath;
                                this.onerror = function() {
                                    // Try direct path as last resort
                                    const filename = imagePath.split('/').pop();
                                    const directPath = 'images/' + filename;
                                    this.src = directPath;
                                    this.onerror = function() {
                                        // Finally fall back to default
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
                    
                    // Create name label with category
                    const nameDiv = document.createElement('div');
                    nameDiv.className = 'thumbnail-name';
                    nameDiv.textContent = image.name + (image.category ? ` (${image.category})` : '');
                    
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
                thumbnailGrid.innerHTML = '<div class="no-results">Error performing search. Please try again later.</div>';
            });
    }
    
    // Set up search functionality for main search input
    if (searchInput) {
        // Debounce function to limit search rate
        let searchTimeout;
        
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            
            // Set a short timeout to avoid searching on every keystroke
            searchTimeout = setTimeout(() => {
                const searchTerm = this.value.trim();
                
                if (searchTerm.length >= 1) {
                    mainProductSearch(searchTerm);
                } else if (categoryGrid) {
                    // If search is cleared, make sure categories are visible
                    categoryGrid.style.display = 'grid';
                }
            }, 300);
        });
    }
    
    // Function to handle main product search
    function mainProductSearch(searchTerm) {
        // Create or reuse search modal
        let modal = document.getElementById('search-modal');
        if (!modal) {
            modal = document.createElement('div');
            modal.className = 'category-modal';
            modal.id = 'search-modal';
            
            modal.innerHTML = `
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="category-modal-title">Search Results for "${searchTerm}"</h2>
                        <button class="modal-close">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="thumbnail-grid">
                            <div style="text-align: center; padding: 20px;">
                                <i class="bi bi-arrow-repeat spin"></i> Searching...
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            
            // Add event listener to close button
            const closeButton = modal.querySelector('.modal-close');
            closeButton.addEventListener('click', function() {
                closeSearchModal(modal);
            });
            
            // Close when clicking outside the content
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    closeSearchModal(modal);
                }
            });
        } else {
            // Update the title
            const modalTitle = modal.querySelector('.category-modal-title');
            modalTitle.textContent = `Search Results for "${searchTerm}"`;
            
            // Clear the results
            const thumbnailGrid = modal.querySelector('.thumbnail-grid');
            thumbnailGrid.innerHTML = '<div style="text-align: center; padding: 20px;"><i class="bi bi-arrow-repeat spin"></i> Searching...</div>';
        }
        
        // Get the thumbnail grid
        const thumbnailGrid = modal.querySelector('.thumbnail-grid');
        
        // Direct server-side search using findFile action
        fetch(`get_directory_files.php?action=findFile&term=${encodeURIComponent(searchTerm)}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                // Store as current images
                if (data.success && Array.isArray(data.files)) {
                    currentImages = data.files;
                } else {
                    currentImages = [];
                }
                
                // Clear the grid
                thumbnailGrid.innerHTML = '';
                
                // Update title with result count
                const modalTitle = modal.querySelector('.category-modal-title');
                modalTitle.textContent = `Search Results for "${searchTerm}" (${currentImages.length} found)`;
                
                if (!currentImages.length) {
                    thumbnailGrid.innerHTML = '<div class="no-results">No results found. Try a different search term.</div>';
                    return;
                }
                
                // Add each result as a thumbnail - maintaining thumbnails first approach
                currentImages.forEach((image, index) => {
                    const thumbnail = document.createElement('div');
                    thumbnail.className = 'thumbnail-item';
                    
                    // Normalize the image path
                    let imagePath = image.path;
                    // Fix duplicate paths
                    imagePath = imagePath.replace(/(images\/products\/)+/g, 'images/products/');
                    
                    // Create the image element
                    const img = document.createElement('img');
                    img.src = imagePath;
                    img.alt = image.name;
                    img.loading = 'lazy';
                    
                    // Add onerror handler with fallback attempts
                    img.onerror = function() {
                        // Try alternative path formats
                        if (imagePath.includes('images/products/')) {
                            // Try simplified path
                            const pathParts = imagePath.split('images/products/');
                            if (pathParts.length > 1) {
                                const simplifiedPath = 'images/' + pathParts[1];
                                this.src = simplifiedPath;
                                this.onerror = function() {
                                    // Try direct path as last resort
                                    const filename = imagePath.split('/').pop();
                                    const directPath = 'images/' + filename;
                                    this.src = directPath;
                                    this.onerror = function() {
                                        // Finally fall back to default
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
                    
                    // Create name label with category
                    const nameDiv = document.createElement('div');
                    nameDiv.className = 'thumbnail-name';
                    nameDiv.textContent = image.name + (image.category ? ` (${image.category})` : '');
                    
                    thumbnail.appendChild(img);
                    thumbnail.appendChild(nameDiv);
                    
                    // Add click event to show fullscreen view - following thumbnails first approach
                    thumbnail.addEventListener('click', () => {
                        showFullscreenImage(index);
                    });
                    
                    thumbnailGrid.appendChild(thumbnail);
                });
            })
            .catch(error => {
                thumbnailGrid.innerHTML = '<div class="no-results">Error performing search. Please try again later.</div>';
            });
    }
});
