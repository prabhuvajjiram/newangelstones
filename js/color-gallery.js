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
