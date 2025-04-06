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
            width: 100%;
            box-sizing: border-box;
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

        .thumbnail-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
        }

        /* Responsive thumbnail adjustments */
        @media (max-width: 768px) {
            .thumbnail-grid {
                grid-template-columns: repeat(1, 1fr);
                gap: 20px;
                padding: 10px;
                height: auto;
                display: flex;
                flex-direction: column;
            }
            
            .thumbnail-item {
                max-width: 100%;
                height: auto;
                width: 100%;
                border: 1px solid rgba(255, 255, 255, 0.1);
                margin-bottom: 20px;
                flex: 0 0 auto;
                position: relative;
            }
            
            .thumbnail-item img {
                width: 100%;
                max-height: 450px;
                height: auto;
                object-fit: contain;
                display: block;
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

        .fullscreen-view {
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

        .fullscreen-image-container {
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

        .fullscreen-nav {
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

        .fullscreen-nav:hover {
            background: black;
            border-color: rgba(255,255,255,0.5);
            transform: translateY(-50%) scale(1.1);
        }

        .fullscreen-prev {
            left: 20px;
        }

        .fullscreen-next {
            right: 20px;
        }

        .fullscreen-nav[disabled] {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .fullscreen-close {
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

        .fullscreen-close:hover {
            background: black;
            border-color: rgba(255, 255, 255, 0.5);
            transform: scale(1.1);
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
                grid-template-columns: repeat(1, 1fr);
                gap: 10px;
            }
            
            .fullscreen-nav {
                width: 35px;
                height: 35px;
                font-size: 18px;
            }
            
            .fullscreen-close {
                width: 35px;
                height: 35px;
                font-size: 18px;
            }
        }

        /* Mobile-specific thumbnail styles */
        .mobile-view-active .thumbnail-grid {
            display: flex !important;
            flex-direction: column !important;
            gap: 20px !important;
            height: auto !important;
            max-height: none !important;
            overflow-y: auto !important;
        }
        
        .mobile-view-active .thumbnail-item {
            width: 100% !important;
            height: auto !important;
            margin-bottom: 20px !important;
            flex: 0 0 auto !important;
            position: static !important;
        }
        
        .mobile-view-active .thumbnail-item img {
            width: 100% !important;
            max-height: 450px !important;
            height: auto !important;
            object-fit: contain !important;
            display: block !important;
        }

        .thumbnail-name {
            text-align: center;
            padding: 8px 5px;
            font-size: 14px;
            color: #333;
            background: rgba(255,255,255,0.8);
            margin-top: 5px;
            border-radius: 4px;
            word-break: break-word;
            white-space: normal;
        }
        
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
            
            // Check if mobile view
            const isMobile = window.innerWidth <= 768;
            if (isMobile) {
                modal.classList.add('mobile-view-active');
            }
            
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
            container.innerHTML = '<div class="no-results">No images found for this category.</div>';
            return;
        }
        
        const categoryName = variations[index];
        
        // Fetch images for this category
        const apiUrl = `get_directory_files.php?directory=images/products/${encodeURIComponent(categoryName)}`;
        
        console.log('Fetching directory:', categoryName, 'URL:', apiUrl);
        
        fetch(apiUrl)
            .then(response => response.json())
            .then(data => {
                // Log the response for debugging
                console.log('API Response for category', categoryName, ':', data);
                
                // Process the response
                if (!data.success || !data.files || data.files.length === 0) {
                    // Try the next variation
                    tryLoadCategory(variations, index + 1, container);
                    return;
                }
                
                // Clear loading message
                container.innerHTML = '';
                
                // Check if mobile and add a class to the container
                const isMobile = window.innerWidth <= 768;
                if (isMobile) {
                    container.classList.add('mobile-view-active');
                }
                
                // We found images, process them
                processImages(data.files, categoryName, container);
                
                // Cache this successful variation for future use
                localStorage.setItem('successful_category_' + categoryName.toLowerCase(), categoryName);
                
                // FIXED: Set up close button event listener after content is loaded
                setupModalCloseListeners();
            })
            .catch(error => {
                console.error('Error loading category:', categoryName, error);
                // Try the next variation
                tryLoadCategory(variations, index + 1, container);
            });
    }
    
    // Helper function to process images and add them to the grid
    function processImages(files, categoryName, container) {
        if (!Array.isArray(files) || files.length === 0) {
            container.innerHTML = '<div class="no-results">No images found in this category.</div>';
            return;
        }
        
        console.log('Processing files for category:', categoryName, 'Files:', files);
        
        // Always set the default image pattern to capitalized first letter
        const capitalizedCategory = categoryName.charAt(0).toUpperCase() + categoryName.slice(1);
        if (!window.imagePatternCache) {
            window.imagePatternCache = {};
        }
        window.imagePatternCache[categoryName] = `images/products/${capitalizedCategory}/{filename}.{extension}`;
        
        // Sort files by name
        files.sort((a, b) => {
            // Assuming files have a name property
            return a.name.localeCompare(b.name);
        });
        
        // Check if files are objects or strings and normalize
        let processedFiles = [];
        files.forEach(file => {
            // Handle string filenames
            if (typeof file === 'string') {
                processedFiles.push(file);
            } 
            // Handle object filenames
            else if (typeof file === 'object' && file !== null) {
                if (file.name) processedFiles.push(file.name);
                else if (file.filename) processedFiles.push(file.filename);
                else if (file.path) {
                    const pathParts = file.path.split('/');
                    processedFiles.push(pathParts[pathParts.length - 1]);
                }
            }
        });
        
        if (processedFiles.length === 0) {
            container.innerHTML = '<div class="no-results">Unable to process images for this category.</div>';
            return;
        }
        
        console.log('Processed file names for ' + categoryName + ':', processedFiles);
        
        // Convert files to image objects with proper paths
        const imageObjects = processedFiles.map(file => {
            // If the file is already an object with a path (from API), use it directly
            if (typeof file === 'object' && file.path) {
                return {
                    name: file.name,
                    path: file.path, // Use the exact path from the API
                    category: categoryName,
                    originalName: file.name,
                    fileType: file.type,
                    fileSize: file.size,
                    extension: file.path.split('.').pop() // Extract extension from path
                };
            }
            
            // Otherwise, handle as before
            const hasExtension = typeof file === 'string' && /\.(jpg|jpeg|png|gif|webp|svg)$/i.test(file);
            const baseName = hasExtension ? file.replace(/\.[^/.]+$/, '') : file;
            const extension = hasExtension ? file.split('.').pop().toLowerCase() : 'jpg';
            
            return {
                name: baseName,
                path: constructImagePath(categoryName, file, extension),
                category: categoryName,
                originalName: file,
                extension: extension
            };
        });
        
        // Filter out duplicate image names to prevent showing duplicates
        const uniqueImages = [];
        const seenNames = new Set();
        
        imageObjects.forEach(img => {
            if (!seenNames.has(img.name)) {
                seenNames.add(img.name);
                uniqueImages.push(img);
            } else {
                console.log(`Filtered duplicate image: ${img.name}`);
            }
        });
        
        // Store for fullscreen navigation
        currentImages = uniqueImages;
        currentCategory = categoryName;
        
        // Clear loading indicator
        container.innerHTML = '';
        
        // Check if we're on mobile
        const isMobile = window.innerWidth <= 768;
        
        // If on mobile, create a different layout structure
        if (isMobile) {
            container.style.display = 'block';
            container.style.padding = '15px';
            container.style.height = 'auto';
            container.style.overflow = 'auto';
            
            uniqueImages.forEach((image, index) => {
                // Create thumbnail container
                const thumbDiv = document.createElement('div');
                thumbDiv.className = 'thumbnail-item mobile-thumbnail';
                thumbDiv.style.marginBottom = '20px';
                thumbDiv.style.width = '100%';
                thumbDiv.style.display = 'block';
                thumbDiv.style.height = 'auto';
                
                // Create image element
                const img = document.createElement('img');
                img.className = 'thumbnail-image';
                
                // Extract filename without extension
                const basename = image.name;
                
                // Add default extension for image path if needed
                let imageSrc = image.path;
                
                img.src = imageSrc;
                img.alt = basename;
                img.style.width = '100%';
                img.style.height = 'auto';
                img.style.maxHeight = '450px';
                img.style.objectFit = 'contain';
                img.style.display = 'block';
                
                // Add onerror handler to try different extensions
                img.onerror = function() {
                    console.log('Failed to load image:', image.path);
                    
                    // Try the alternate extension if base path doesn't have extension
                    if (!imageSrc.match(/\.(jpg|jpeg|png|gif|webp|svg)$/i)) {
                        // For MBNA_2025, try PNG first (we know from server data these are PNG)
                        // For all other categories, try JPG first
                        const extensions = categoryName.toLowerCase() === 'mbna 2025' || 
                                           categoryName === 'MBNA_2025' ? 
                                           ['png', 'jpg'] : ['jpg', 'png'];
                        
                        // If we're already using the first extension, try the second
                        if (imageSrc.endsWith(`.${extensions[0]}`)) {
                            console.log(`Trying alternate extension: ${extensions[1]}`);
                            this.src = `${imageSrc.substring(0, imageSrc.lastIndexOf('.'))}.${extensions[1]}`;
                            
                            // Set up final error handler for if second extension fails
                            this.onerror = function() {
                                console.error('Failed with both extensions, using default image');
                                this.src = 'images/default-thumbnail.jpg';
                                
                                // Add error message
                                const errorDiv = document.createElement('div');
                                errorDiv.className = 'image-error-container';
                                errorDiv.innerHTML = `
                                    <div class="image-error-icon">⚠️</div>
                                    <div class="image-error-text">Image not found: ${basename}</div>
                                `;
                                thumbDiv.appendChild(errorDiv);
                            };
                            return;
                        }
                        
                        // First attempt with first extension
                        console.log(`Trying first extension: ${extensions[0]}`);
                        this.src = `${imageSrc}.${extensions[0]}`;
                        
                        // Set up error handler for second attempt
                        this.onerror = function() {
                            console.log(`Trying second extension: ${extensions[1]}`);
                            this.src = `${imageSrc}.${extensions[1]}`;
                            
                            // Set up final error handler
                            this.onerror = function() {
                                console.error('Failed with both extensions, using default image');
                                this.src = 'images/default-thumbnail.jpg';
                                
                                // Add error message
                                const errorDiv = document.createElement('div');
                                errorDiv.className = 'image-error-container';
                                errorDiv.innerHTML = `
                                    <div class="image-error-icon">⚠️</div>
                                    <div class="image-error-text">Image not found: ${basename}</div>
                                `;
                                thumbDiv.appendChild(errorDiv);
                            };
                        };
                        return;
                    }
                    
                    // If we've reached here, we couldn't load the image with a specified extension
                    console.log('Using default image');
                    this.src = 'images/default-thumbnail.jpg';
                    
                    // Add error message
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'image-error-container';
                    errorDiv.innerHTML = `
                        <div class="image-error-icon">⚠️</div>
                        <div class="image-error-text">Image not found: ${basename}</div>
                    `;
                    thumbDiv.appendChild(errorDiv);
                };
                
                // Add click event to show fullscreen image
                thumbDiv.addEventListener('click', () => {
                    showFullscreenImage(index);
                });
                
                // Add name display
                const nameDiv = document.createElement('div');
                nameDiv.className = 'thumbnail-name';
                nameDiv.textContent = basename;
                
                thumbDiv.appendChild(img);
                thumbDiv.appendChild(nameDiv);
                container.appendChild(thumbDiv);
            });
        } else {
            // For desktop, continue to use the grid layout
            uniqueImages.forEach((image, index) => {
                // Create thumbnail container
                const thumbDiv = document.createElement('div');
                thumbDiv.className = 'thumbnail-item';
                
                // Create image element
                const img = document.createElement('img');
                img.className = 'thumbnail-image';
                
                // Extract filename without extension
                const basename = image.name;
                
                // Set image source with default extension if needed
                let imageSrc = image.path;
                
                img.src = imageSrc;
                img.alt = basename;
                
                // Add onerror handler to try different extensions
                img.onerror = function() {
                    console.error('Failed to load image:', image.path);
                    
                    // Instead of trying multiple extensions sequentially,
                    // we'll try using the default placeholder image right away
                    console.log('Using default image instead of trying multiple extensions');
                    this.src = 'images/default-thumbnail.jpg';
                    this.style.display = 'block';
                    
                    // Add error message directly without trying to replace loading div
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'image-error-container';
                    errorDiv.innerHTML = `
                        <div class="image-error-icon">⚠️</div>
                        <div class="image-error-text">Image not found: ${basename}</div>
                    `;
                    thumbDiv.appendChild(errorDiv);
                };
                
                // Add click event to show fullscreen image
                thumbDiv.addEventListener('click', () => {
                    showFullscreenImage(index);
                });
                
                // Add name display
                const nameDiv = document.createElement('div');
                nameDiv.className = 'thumbnail-name';
                nameDiv.textContent = basename;
                
                thumbDiv.appendChild(img);
                thumbDiv.appendChild(nameDiv);
                container.appendChild(thumbDiv);
            });
        }
    }
    
    // Function to show fullscreen image with navigation
    function showFullscreenImage(index) {
        // Prevent scrolling on the background
        document.body.style.overflow = 'hidden';
        document.body.style.position = 'fixed';
        document.body.style.width = '100%';
        
        // Create fullscreen view
        const fullscreenView = document.createElement('div');
        fullscreenView.className = 'fullscreen-view';
        
        // Get current image path
        const currentImage = currentImages[index];
        let imagePath;
        
        if (typeof currentImage === 'string') {
            imagePath = currentImage;
        } else if (typeof currentImage === 'object' && currentImage !== null) {
            imagePath = currentImage.path || '';
        } else {
            console.error('Invalid image data for fullscreen view:', currentImage);
            return;
        }
        
        console.log('Showing fullscreen image:', imagePath);
        
        // Create fullscreen content
        fullscreenView.innerHTML = `
            <div class="fullscreen-image-container">
                <img src="${imagePath}" class="fullscreen-image" alt="Fullscreen image">
                <button class="fullscreen-close">&times;</button>
                <button class="fullscreen-nav fullscreen-prev">&lt;</button>
                <button class="fullscreen-nav fullscreen-next">&gt;</button>
            </div>
        `;
        
        // Append to body
        document.body.appendChild(fullscreenView);
        
        // Handle navigation
        const prevButton = fullscreenView.querySelector('.fullscreen-prev');
        const nextButton = fullscreenView.querySelector('.fullscreen-next');
        const closeButton = fullscreenView.querySelector('.fullscreen-close');
        
        // Add event listeners
        function handleKeyDown(e) {
            if (e.key === 'ArrowLeft') {
                navigateFullscreen('prev');
            } else if (e.key === 'ArrowRight') {
                navigateFullscreen('next');
            } else if (e.key === 'Escape') {
                closeFullscreen();
            }
        }
        
        // Navigation function
        function navigateFullscreen(direction) {
            let newIndex;
            if (direction === 'prev') {
                newIndex = (index - 1 + currentImages.length) % currentImages.length;
            } else {
                newIndex = (index + 1) % currentImages.length;
            }
            
            // Update image
            const newImage = currentImages[newIndex];
            let newImagePath;
            
            if (typeof newImage === 'string') {
                newImagePath = newImage;
            } else if (typeof newImage === 'object' && newImage !== null) {
                newImagePath = newImage.path || '';
            } else {
                console.error('Invalid new image data:', newImage);
                return;
            }
            
            console.log('Navigating to image:', newImagePath);
            
            const fullscreenImage = fullscreenView.querySelector('.fullscreen-image');
            fullscreenImage.src = newImagePath;
            
            // Update index
            index = newIndex;
        }
        
        // Close function
        function closeFullscreen() {
            document.body.removeChild(fullscreenView);
            document.removeEventListener('keydown', handleKeyDown);
            // Restore scrolling
            document.body.style.overflow = '';
            document.body.style.position = '';
            document.body.style.width = '';
        }
        
        // Add event listeners
        prevButton.addEventListener('click', () => navigateFullscreen('prev'));
        nextButton.addEventListener('click', () => navigateFullscreen('next'));
        closeButton.addEventListener('click', closeFullscreen);
        
        // Add keyboard navigation
        document.addEventListener('keydown', handleKeyDown);
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
        if (!searchTerm || searchTerm.length < 2) {
            return;
        }
        
        // Create search modal
        const searchModal = document.createElement('div');
        searchModal.className = 'category-modal';
        searchModal.id = 'search-modal';
        
        // Check if mobile view
        const isMobile = window.innerWidth <= 768;
        if (isMobile) {
            searchModal.classList.add('mobile-view-active');
        }
        
        searchModal.innerHTML = `
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
        
        document.body.appendChild(searchModal);
        
        // Add event listener to close button
        const closeButton = searchModal.querySelector('.modal-close');
        closeButton.addEventListener('click', function() {
            closeSearchModal(searchModal);
        });
        
        // Close when clicking outside the content
        searchModal.addEventListener('click', (e) => {
            if (e.target === searchModal) {
                closeSearchModal(searchModal);
            }
        });
        
        // Get the thumbnail grid
        const thumbnailGrid = searchModal.querySelector('.thumbnail-grid');
        
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
                const modalTitle = searchModal.querySelector('.category-modal-title');
                modalTitle.textContent = `Search Results for "${searchTerm}" (${currentImages.length} found)`;
                
                if (!currentImages.length) {
                    thumbnailGrid.innerHTML = '<div class="no-results">No results found. Try a different search term.</div>';
                    return;
                }
                
                // Process search results
                processSearchResults(currentImages, thumbnailGrid, searchTerm, searchModal);
            })
            .catch(error => {
                thumbnailGrid.innerHTML = '<div class="no-results">Error performing search. Please try again later.</div>';
            });
    }
    
    // Function to process search results
    function processSearchResults(searchResultsArray, thumbnailGrid, searchTerm, searchModal) {
        // Reset current images array
        currentImages = [];
        
        // Process results and add to thumbnailGrid
        if (searchResultsArray && searchResultsArray.length > 0) {
            console.log('Search results raw data:', searchResultsArray);
            
            // Reset for new collection of valid items
            let validResults = [];
            
            // Convert all results to a standardized format with strings, not objects
            searchResultsArray.forEach(result => {
                // For string items
                if (typeof result === 'string') {
                    validResults.push({
                        name: result.split('/').pop().replace(/\.[^/.]+$/, ''),
                        path: result,
                        category: result.includes('/') ? result.split('/')[result.split('/').length - 2] : ''
                    });
                } 
                // For object items
                else if (typeof result === 'object' && result !== null) {
                    const name = result.name || '';
                    const path = result.path || '';
                    const category = result.category || (path.includes('/') ? path.split('/')[path.split('/').length - 2] : '');
                    
                    if (name || path) {
                        validResults.push({
                            name: name || path.split('/').pop().replace(/\.[^/.]+$/, ''),
                            path: path || `images/products/${category}/${name}`,
                            category: category
                        });
                    }
                }
            });
            
            console.log('Processed valid search results:', validResults);
            currentImages = validResults;
            
            // Clear previous results
            thumbnailGrid.innerHTML = '';
            
            // Update title with result count
            const modalTitle = searchModal.querySelector('.category-modal-title');
            modalTitle.textContent = `Search Results for "${searchTerm}" (${currentImages.length} found)`;
            
            if (!currentImages.length) {
                thumbnailGrid.innerHTML = '<div class="no-results">No results found. Try different search terms.</div>';
                return;
            }
            
            // Check if mobile
            const isMobile = window.innerWidth <= 768;
            
            // Add each image as a thumbnail
            currentImages.forEach((item, index) => {
                // Create thumbnail element
                const thumbnail = document.createElement('div');
                thumbnail.className = 'thumbnail-item';
                
                if (isMobile) {
                    thumbnail.style.marginBottom = '20px';
                    thumbnail.style.width = '100%';
                    thumbnail.style.display = 'block'; 
                    thumbnail.style.height = 'auto';
                }
                
                // Create image element
                const img = document.createElement('img');
                img.className = 'thumbnail-image';
                
                // Make sure path is properly formed and has an extension
                let imageSrc = item.path;
                if (!imageSrc.startsWith('images/')) {
                    imageSrc = `images/products/${item.category}/${item.name}.jpg`;
                } else if (!imageSrc.match(/\.(jpg|jpeg|png|gif|webp|svg)$/i)) {
                    // If path exists but has no extension, add .jpg
                    imageSrc = `${imageSrc}.jpg`;
                }
                
                img.src = imageSrc;
                img.alt = item.name;
                
                // Add onerror handler to try different extensions
                img.onerror = function() {
                    console.error('Failed to load search result image:', imageSrc);
                    
                    // Try the alternate extension if base path doesn't have extension
                    if (!imageSrc.match(/\.(jpg|jpeg|png|gif|webp|svg)$/i)) {
                        // For MBNA_2025, try PNG first (we know from server data these are PNG)
                        // For all other categories, try JPG first
                        const extensions = item.category.toLowerCase() === 'mbna 2025' || 
                                           item.category === 'MBNA_2025' ? 
                                           ['png', 'jpg'] : ['jpg', 'png'];
                        
                        // If we're already using the first extension, try the second
                        if (imageSrc.endsWith(`.${extensions[0]}`)) {
                            console.log(`Trying alternate extension: ${extensions[1]}`);
                            this.src = `${imageSrc.substring(0, imageSrc.lastIndexOf('.'))}.${extensions[1]}`;
                            
                            // Set up final error handler for if second extension fails
                            this.onerror = function() {
                                console.error('Failed with both extensions, using default image');
                                this.src = 'images/default-thumbnail.jpg';
                                
                                // Add error message
                                const errorDiv = document.createElement('div');
                                errorDiv.className = 'image-error-container';
                                errorDiv.innerHTML = `
                                    <div class="image-error-icon">⚠️</div>
                                    <div class="image-error-text">Image not found: ${item.name}</div>
                                `;
                                thumbnail.appendChild(errorDiv);
                            };
                            return;
                        }
                        
                        // First attempt with first extension
                        console.log(`Trying first extension: ${extensions[0]}`);
                        this.src = `${imageSrc}.${extensions[0]}`;
                        
                        // Set up error handler for second attempt
                        this.onerror = function() {
                            console.log(`Trying second extension: ${extensions[1]}`);
                            this.src = `${imageSrc}.${extensions[1]}`;
                            
                            // Set up final error handler
                            this.onerror = function() {
                                console.error('Failed with both extensions, using default image');
                                this.src = 'images/default-thumbnail.jpg';
                                
                                // Add error message
                                const errorDiv = document.createElement('div');
                                errorDiv.className = 'image-error-container';
                                errorDiv.innerHTML = `
                                    <div class="image-error-icon">⚠️</div>
                                    <div class="image-error-text">Image not found: ${item.name}</div>
                                `;
                                thumbnail.appendChild(errorDiv);
                            };
                        };
                        return;
                    }
                    
                    // If we've reached here, we couldn't load the image with a specified extension
                    console.log('Using default image');
                    this.src = 'images/default-thumbnail.jpg';
                    
                    // Add error message
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'image-error-container';
                    errorDiv.innerHTML = `
                        <div class="image-error-icon">⚠️</div>
                        <div class="image-error-text">Image not found: ${item.name}</div>
                    `;
                    thumbnail.appendChild(errorDiv);
                };
                
                if (isMobile) {
                    img.style.width = '100%';
                    img.style.height = 'auto';
                    img.style.maxHeight = '450px';
                    img.style.objectFit = 'contain';
                    img.style.display = 'block';
                }
                
                // Add click event to show fullscreen view
                thumbnail.addEventListener('click', () => {
                    showFullscreenImage(index);
                });
                
                // Add name display
                const nameDiv = document.createElement('div');
                nameDiv.className = 'thumbnail-name';
                nameDiv.textContent = item.name;
                
                thumbnail.appendChild(img);
                thumbnail.appendChild(nameDiv);
                thumbnailGrid.appendChild(thumbnail);
            });
        } else {
            thumbnailGrid.innerHTML = '<div class="no-results">No results found. Try different search terms.</div>';
        }
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
                
                // Process search results
                processSearchResults(currentImages, thumbnailGrid, searchTerm, modal);
            })
            .catch(error => {
                thumbnailGrid.innerHTML = '<div class="no-results">Error performing search. Please try again later.</div>';
            });
    }
    
    // Function to determine the best image path pattern for a category
    function determineCategoryImagePattern(categoryName, sampleImage) {
        // Log to help debug
        console.log(`Determining image pattern for ${categoryName} with sample: ${sampleImage}`);
        
        // Try to detect if category name should be capitalized
        const capitalizedCategory = categoryName.charAt(0).toUpperCase() + categoryName.slice(1);
        
        // Cache for this category
        if (!window.imagePatternCache) {
            window.imagePatternCache = {};
        }
        
        // If we already detected a pattern for this category, use it
        if (window.imagePatternCache[categoryName]) {
            console.log(`Using cached pattern for ${categoryName}: ${window.imagePatternCache[categoryName]}`);
            return window.imagePatternCache[categoryName];
        }
        
        // Default to capitalized category name - this is what API returns
        const bestPattern = `images/products/${capitalizedCategory}/{filename}.{extension}`;
        window.imagePatternCache[categoryName] = bestPattern;
        
        console.log(`Using default capitalized pattern: ${bestPattern}`);
        return bestPattern;
    }
    
    // Function to construct image path based on determined pattern
    function constructImagePath(categoryName, filename, extension = null) {
        console.log(`Constructing path for ${filename} in category ${categoryName}`);
        
        // First check if server directory is known
        let categoryForPath = categoryName;
        
        // Handle "MBNA 2025" category specifically since we know the exact format
        if (categoryName.toLowerCase() === 'mbna 2025') {
            // Use exact directory name that exists on server
            categoryForPath = 'MBNA_2025';
            console.log('Using exact server directory "MBNA_2025" for MBNA 2025 category');
            
            // MBNA_2025 uses PNG files by default
            if (!extension) {
                extension = 'png';
                console.log('Setting default extension to PNG for MBNA_2025 category');
            }
        }
        // For MBNA and similar acronym categories, preserve capitalization
        else if (/^mbna|^ibm|^hp|^ge/i.test(categoryName)) {
            categoryForPath = categoryName.toUpperCase();
            console.log('Using uppercase for acronym category in path:', categoryForPath);
        } else {
            categoryForPath = categoryName.charAt(0).toUpperCase() + categoryName.slice(1);
        }
        
        // Check if we have the server-provided directory name
        if (window.serverCategoryNames && window.serverCategoryNames[categoryName]) {
            categoryForPath = window.serverCategoryNames[categoryName];
            console.log('Using server-provided category name:', categoryForPath);
        }
        
        // Check if filename already has an extension
        const hasExtension = typeof filename === 'string' && /\.(jpg|jpeg|png|gif|webp|svg)$/i.test(filename);
        
        // If filename already has extension, just use it directly with proper category capitalization
        if (hasExtension) {
            return `images/products/${categoryForPath}/${filename}`;
        }
        
        // If extension is explicitly provided, use it
        if (extension) {
            return `images/products/${categoryForPath}/${filename}.${extension}`;
        }
        
        // Otherwise return base path without extension - the image loader will try jpg and png
        return `images/products/${categoryForPath}/${filename}`;
    }
    
    // Function to handle image loading errors
    function handleImageError(img, basename, container) {
        console.error('Failed to load image:', img.src);
        
        // Log the failure for debugging
        console.log('Using default image instead of trying multiple extensions');
        
        // Set a default placeholder image
        img.src = 'images/default-thumbnail.jpg';
        img.style.display = 'block';
        
        // Add visual error indication
        const errorDiv = document.createElement('div');
        errorDiv.className = 'image-error-container';
        errorDiv.innerHTML = `
            <div class="image-error-icon">⚠️</div>
            <div class="image-error-text">Image not found: ${basename}</div>
        `;
        container.appendChild(errorDiv);
    }
    
    // Function to handle category view
    function handleCategoryView(categoryId, categoryName) {
        // Clear the container
        const container = document.getElementById('category-thumbnails');
        container.innerHTML = '';
        
        // Set the current category
        currentCategory = categoryName;
        
        // Add loading indicator
        addLoadingIndicator(container);
        
        console.log('Fetching directory:', categoryName);
        
        // Extra check to handle case-sensitivity issues in category names
        let directoryPath = categoryName;
        
        // Special case for "mbna 2025" category - use exact directory name from server
        if (categoryName.toLowerCase() === 'mbna 2025') {
            // Use exact directory name that exists on server
            directoryPath = 'MBNA_2025';
            console.log('Using exact directory name "MBNA_2025" for MBNA 2025 category');
        }
        // For MBNA and similar acronym categories, preserve capitalization
        else if (/^mbna|^ibm|^hp|^ge/i.test(categoryName)) {
            directoryPath = categoryName.toUpperCase();
            console.log('Using uppercase for acronym category:', directoryPath);
        }
        
        // Use AJAX to fetch files from the directory
        const apiUrl = `get_directory_files.php?directory=images/products/${directoryPath}`;
        console.log('Fetching directory:', directoryPath, 'URL:', apiUrl);
        
        fetch(apiUrl)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Server returned ${response.status}: ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                // Log the response for debugging
                console.log('API Response for category', directoryPath, ':', data);
                
                if (data.success) {
                    // Store file extensions to use for loading images
                    window.fileExtensions = window.fileExtensions || {};
                    window.fileExtensions[categoryName] = {};
                    
                    // Store the original server-provided category name from API
                    if (data.originalDirectory) {
                        console.log('Server provided original directory:', data.originalDirectory);
                        window.serverCategoryNames = window.serverCategoryNames || {};
                        window.serverCategoryNames[categoryName] = data.originalDirectory;
                    } else {
                        // Store the category name we used for the API call
                        window.serverCategoryNames = window.serverCategoryNames || {};
                        window.serverCategoryNames[categoryName] = directoryPath;
                    }
                    
                    // Extract file extensions from paths
                    if (data.files && Array.isArray(data.files)) {
                        data.files.forEach(file => {
                            if (file && file.path && file.name) {
                                const extension = file.path.split('.').pop().toLowerCase();
                                window.fileExtensions[categoryName][file.name] = extension;
                                console.log(`Mapped ${file.name} to extension: ${extension}`);
                            }
                        });
                    }
                    
                    // Always set the pattern to use the exact directory name
                    if (!window.imagePatternCache) {
                        window.imagePatternCache = {};
                    }
                    window.imagePatternCache[categoryName] = `images/products/${directoryPath}/{filename}.{extension}`;
                    
                    const processedFiles = processServerResponseFiles(data.files, categoryName);
                    processCategory(processedFiles, categoryName);
                } else {
                    console.error('API returned success: false for category', categoryName);
                    showError(`Failed to fetch files for ${categoryName}`);
                }
            })
            .catch(error => {
                console.error('Error fetching directory:', error);
                showError(`Failed to load category ${categoryName}. Error: ${error.message}`);
            });
    }
    
    // Function to process server response files
    function processServerResponseFiles(files, categoryName) {
        // Convert files to image objects with proper paths
        const imageObjects = files.map(file => {
            // If the file is already an object with a path (from API), use it directly
            if (typeof file === 'object' && file.path) {
                return {
                    name: file.name,
                    path: file.path, // Use the exact path from the API
                    category: categoryName,
                    originalName: file.name,
                    fileType: file.type,
                    fileSize: file.size,
                    extension: file.path.split('.').pop() // Extract extension from path
                };
            }
            
            // Otherwise, handle as before
            const hasExtension = typeof file === 'string' && /\.(jpg|jpeg|png|gif|webp|svg)$/i.test(file);
            const baseName = hasExtension ? file.replace(/\.[^/.]+$/, '') : file;
            const extension = hasExtension ? file.split('.').pop().toLowerCase() : 'jpg';
            
            return {
                name: baseName,
                path: constructImagePath(categoryName, file, extension),
                category: categoryName,
                originalName: file,
                extension: extension
            };
        });
        
        return imageObjects;
    }
    
    // Function to add loading indicator
    function addLoadingIndicator(container) {
        const loadingIndicator = document.createElement('div');
        loadingIndicator.className = 'loading-thumbnail';
        loadingIndicator.innerHTML = '<div class="loading-spinner"></div>';
        container.appendChild(loadingIndicator);
    }
    
    // Function to show error message
    function showError(message) {
        const container = document.getElementById('category-thumbnails');
        container.innerHTML = `<div class="error-message">${message}</div>`;
    }
    
    // Function to process category
    function processCategory(files, categoryName) {
        if (!Array.isArray(files) || files.length === 0) {
            showError(`No images found for ${categoryName}`);
            return;
        }
        
        console.log('Processing files for category:', categoryName, 'Files:', files);
        
        // Always set the default image pattern to capitalized first letter
        const capitalizedCategory = categoryName.charAt(0).toUpperCase() + categoryName.slice(1);
        if (!window.imagePatternCache) {
            window.imagePatternCache = {};
        }
        window.imagePatternCache[categoryName] = `images/products/${capitalizedCategory}/{filename}.{extension}`;
        
        // Sort files by name
        files.sort((a, b) => {
            // Assuming files have a name property
            return a.name.localeCompare(b.name);
        });
        
        // Check if files are objects or strings and normalize
        let processedFiles = [];
        files.forEach(file => {
            // Handle string filenames
            if (typeof file === 'string') {
                processedFiles.push(file);
            } 
            // Handle object filenames
            else if (typeof file === 'object' && file !== null) {
                if (file.name) processedFiles.push(file.name);
                else if (file.filename) processedFiles.push(file.filename);
                else if (file.path) {
                    const pathParts = file.path.split('/');
                    processedFiles.push(pathParts[pathParts.length - 1]);
                }
            }
        });
        
        if (processedFiles.length === 0) {
            showError(`Unable to process images for ${categoryName}`);
            return;
        }
        
        console.log('Processed file names for ' + categoryName + ':', processedFiles);
        
        // Convert files to image objects with proper paths
        const imageObjects = processedFiles.map(file => {
            // If the file is already an object with a path (from API), use it directly
            if (typeof file === 'object' && file.path) {
                return {
                    name: file.name,
                    path: file.path, // Use the exact path from the API
                    category: categoryName,
                    originalName: file.name,
                    fileType: file.type,
                    fileSize: file.size,
                    extension: file.path.split('.').pop() // Extract extension from path
                };
            }
            
            // Otherwise, handle as before
            const hasExtension = typeof file === 'string' && /\.(jpg|jpeg|png|gif|webp|svg)$/i.test(file);
            const baseName = hasExtension ? file.replace(/\.[^/.]+$/, '') : file;
            const extension = hasExtension ? file.split('.').pop().toLowerCase() : 'jpg';
            
            return {
                name: baseName,
                path: constructImagePath(categoryName, file, extension),
                category: categoryName,
                originalName: file,
                extension: extension
            };
        });
        
        // Filter out duplicate image names to prevent showing duplicates
        const uniqueImages = [];
        const seenNames = new Set();
        
        imageObjects.forEach(img => {
            if (!seenNames.has(img.name)) {
                seenNames.add(img.name);
                uniqueImages.push(img);
            } else {
                console.log(`Filtered duplicate image: ${img.name}`);
            }
        });
        
        // Store for fullscreen navigation
        currentImages = uniqueImages;
        currentCategory = categoryName;
        
        // Clear loading indicator
        const container = document.getElementById('category-thumbnails');
        container.innerHTML = '';
        
        // Check if we're on mobile
        const isMobile = window.innerWidth <= 768;
        
        // If on mobile, create a different layout structure
        if (isMobile) {
            container.style.display = 'block';
            container.style.padding = '15px';
            container.style.height = 'auto';
            container.style.overflow = 'auto';
            
            uniqueImages.forEach((image, index) => {
                // Create thumbnail container
                const thumbDiv = document.createElement('div');
                thumbDiv.className = 'thumbnail-item mobile-thumbnail';
                thumbDiv.style.marginBottom = '20px';
                thumbDiv.style.width = '100%';
                thumbDiv.style.display = 'block';
                thumbDiv.style.height = 'auto';
                
                // Create image element
                const img = document.createElement('img');
                img.className = 'thumbnail-image';
                
                // Extract filename without extension
                const basename = image.name;
                
                // Add default extension for image path if needed
                let imageSrc = image.path;
                
                img.src = imageSrc;
                img.alt = basename;
                img.style.width = '100%';
                img.style.height = 'auto';
                img.style.maxHeight = '450px';
                img.style.objectFit = 'contain';
                img.style.display = 'block';
                
                // Add onerror handler to try different extensions
                img.onerror = function() {
                    console.log('Failed to load image:', image.path);
                    
                    // Try the alternate extension if base path doesn't have extension
                    if (!imageSrc.match(/\.(jpg|jpeg|png|gif|webp|svg)$/i)) {
                        // For MBNA_2025, try PNG first (we know from server data these are PNG)
                        // For all other categories, try JPG first
                        const extensions = categoryName.toLowerCase() === 'mbna 2025' || 
                                           categoryName === 'MBNA_2025' ? 
                                           ['png', 'jpg'] : ['jpg', 'png'];
                        
                        // If we're already using the first extension, try the second
                        if (imageSrc.endsWith(`.${extensions[0]}`)) {
                            console.log(`Trying alternate extension: ${extensions[1]}`);
                            this.src = `${imageSrc.substring(0, imageSrc.lastIndexOf('.'))}.${extensions[1]}`;
                            
                            // Set up final error handler for if second extension fails
                            this.onerror = function() {
                                console.error('Failed with both extensions, using default image');
                                this.src = 'images/default-thumbnail.jpg';
                                
                                // Add error message
                                const errorDiv = document.createElement('div');
                                errorDiv.className = 'image-error-container';
                                errorDiv.innerHTML = `
                                    <div class="image-error-icon">⚠️</div>
                                    <div class="image-error-text">Image not found: ${basename}</div>
                                `;
                                thumbDiv.appendChild(errorDiv);
                            };
                            return;
                        }
                        
                        // First attempt with first extension
                        console.log(`Trying first extension: ${extensions[0]}`);
                        this.src = `${imageSrc}.${extensions[0]}`;
                        
                        // Set up error handler for second attempt
                        this.onerror = function() {
                            console.log(`Trying second extension: ${extensions[1]}`);
                            this.src = `${imageSrc}.${extensions[1]}`;
                            
                            // Set up final error handler
                            this.onerror = function() {
                                console.error('Failed with both extensions, using default image');
                                this.src = 'images/default-thumbnail.jpg';
                                
                                // Add error message
                                const errorDiv = document.createElement('div');
                                errorDiv.className = 'image-error-container';
                                errorDiv.innerHTML = `
                                    <div class="image-error-icon">⚠️</div>
                                    <div class="image-error-text">Image not found: ${basename}</div>
                                `;
                                thumbDiv.appendChild(errorDiv);
                            };
                        };
                        return;
                    }
                    
                    // If we've reached here, we couldn't load the image with a specified extension
                    console.log('Using default image');
                    this.src = 'images/default-thumbnail.jpg';
                    
                    // Add error message
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'image-error-container';
                    errorDiv.innerHTML = `
                        <div class="image-error-icon">⚠️</div>
                        <div class="image-error-text">Image not found: ${basename}</div>
                    `;
                    thumbDiv.appendChild(errorDiv);
                };
                
                // Add click event to show fullscreen image
                thumbDiv.addEventListener('click', () => {
                    showFullscreenImage(index);
                });
                
                // Add name display
                const nameDiv = document.createElement('div');
                nameDiv.className = 'thumbnail-name';
                nameDiv.textContent = basename;
                
                thumbDiv.appendChild(img);
                thumbDiv.appendChild(nameDiv);
                container.appendChild(thumbDiv);
            });
        } else {
            // For desktop, continue to use the grid layout
            uniqueImages.forEach((image, index) => {
                // Create thumbnail container
                const thumbDiv = document.createElement('div');
                thumbDiv.className = 'thumbnail-item';
                
                // Create image element
                const img = document.createElement('img');
                img.className = 'thumbnail-image';
                
                // Extract filename without extension
                const basename = image.name;
                
                // Set image source with default extension if needed
                let imageSrc = image.path;
                
                img.src = imageSrc;
                img.alt = basename;
                
                // Add onerror handler to try different extensions
                img.onerror = function() {
                    console.error('Failed to load image:', image.path);
                    
                    // Instead of trying multiple extensions sequentially,
                    // we'll try using the default placeholder image right away
                    console.log('Using default image instead of trying multiple extensions');
                    this.src = 'images/default-thumbnail.jpg';
                    this.style.display = 'block';
                    
                    // Add error message directly without trying to replace loading div
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'image-error-container';
                    errorDiv.innerHTML = `
                        <div class="image-error-icon">⚠️</div>
                        <div class="image-error-text">Image not found: ${basename}</div>
                    `;
                    thumbDiv.appendChild(errorDiv);
                };
                
                // Add click event to show fullscreen image
                thumbDiv.addEventListener('click', () => {
                    showFullscreenImage(index);
                });
                
                // Add name display
                const nameDiv = document.createElement('div');
                nameDiv.className = 'thumbnail-name';
                nameDiv.textContent = basename;
                
                thumbDiv.appendChild(img);
                thumbDiv.appendChild(nameDiv);
                container.appendChild(thumbDiv);
            });
        }
    }
});
