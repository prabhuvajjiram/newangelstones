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
        fetch(`get_directory_files.php?directory=images/products/${categoryId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                console.log('Category images data:', data);
                
                if (!data.success || !data.files || data.files.length === 0) {
                    modal.querySelector('.category-modal-images').innerHTML = '<p>No images found for this category.</p>';
                    modal.querySelector('.category-modal-title').textContent = categoryId.replace(/_/g, ' ');
                    return;
                }
                
                // Update modal title
                const categoryName = document.querySelector(`a[href="#${categoryId}-collection"] h4`).textContent;
                modal.querySelector('.category-modal-title').textContent = categoryName;
                
                // Clear loading indicator
                const imagesContainer = modal.querySelector('.category-modal-images');
                imagesContainer.innerHTML = '';
                
                // Filter out non-image files and thumbnails directory
                const imageFiles = data.files.filter(file => {
                    if (file.toLowerCase() === 'thumbnails') return false;
                    const fileExt = file.split('.').pop().toLowerCase();
                    return ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(fileExt);
                });
                
                // Add images to modal
                imageFiles.forEach((file, index) => {
                    // Create image element
                    const imgContainer = document.createElement('div');
                    imgContainer.className = 'category-modal-image-container';
                    imgContainer.dataset.index = index;
                    
                    const img = document.createElement('img');
                    img.src = `images/products/${categoryId}/${file}`;
                    img.alt = file;
                    img.loading = 'lazy';
                    img.onerror = function() {
                        this.src = 'images/default-thumbnail.jpg';
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
        // Remove any existing full-size image overlays
        const existingOverlays = document.querySelectorAll('.full-image-overlay');
        existingOverlays.forEach(overlay => {
            document.body.removeChild(overlay);
        });
        
        const totalImages = imageFiles.length;
        const currentFile = imageFiles[currentIndex];
        
        const fullImg = document.createElement('div');
        fullImg.className = 'full-image-overlay';
        fullImg.innerHTML = `
            <div class="full-image-container">
                <span class="full-image-close">&times;</span>
                <div class="full-image-wrapper">
                    <button class="nav-button prev-button" ${currentIndex === 0 ? 'disabled' : ''}>&lt;</button>
                    <img src="images/products/${categoryId}/${currentFile}" alt="${currentFile}">
                    <button class="nav-button next-button" ${currentIndex === totalImages - 1 ? 'disabled' : ''}>&gt;</button>
                </div>
                <div class="full-image-info">
                    <p class="full-image-name">${currentFile}</p>
                    <p class="full-image-counter">${currentIndex + 1} of ${totalImages}</p>
                </div>
            </div>
        `;
        
        document.body.appendChild(fullImg);
        
        // Add event listener to close button
        const closeBtn = fullImg.querySelector('.full-image-close');
        closeBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            document.body.removeChild(fullImg);
        });
        
        // Close when clicking outside the image container
        fullImg.addEventListener('click', function(e) {
            if (e.target === fullImg) {
                document.body.removeChild(fullImg);
            }
        });
        
        // Add navigation button event listeners
        const prevButton = fullImg.querySelector('.prev-button');
        const nextButton = fullImg.querySelector('.next-button');
        
        prevButton.addEventListener('click', function(e) {
            e.stopPropagation();
            if (currentIndex > 0) {
                openFullSizeImageWithNavigation(categoryId, imageFiles, currentIndex - 1);
            }
        });
        
        nextButton.addEventListener('click', function(e) {
            e.stopPropagation();
            if (currentIndex < totalImages - 1) {
                openFullSizeImageWithNavigation(categoryId, imageFiles, currentIndex + 1);
            }
        });
        
        // Add keyboard navigation
        const keyHandler = function(e) {
            if (!document.body.contains(fullImg)) {
                document.removeEventListener('keydown', keyHandler);
                return;
            }
            
            switch (e.key) {
                case 'Escape':
                    document.body.removeChild(fullImg);
                    document.removeEventListener('keydown', keyHandler);
                    break;
                case 'ArrowLeft':
                    if (currentIndex > 0) {
                        openFullSizeImageWithNavigation(categoryId, imageFiles, currentIndex - 1);
                    }
                    break;
                case 'ArrowRight':
                    if (currentIndex < totalImages - 1) {
                        openFullSizeImageWithNavigation(categoryId, imageFiles, currentIndex + 1);
                    }
                    break;
            }
        };
        
        document.addEventListener('keydown', keyHandler);
    }
});
