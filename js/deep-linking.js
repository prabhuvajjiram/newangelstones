/**
 * Deep Linking support for Angel Granites product categories
 * 
 * This script enables deep linking to product categories via URL parameters.
 * For example: ?category=monuments will open the monuments category.
 */

document.addEventListener('DOMContentLoaded', function() {
    // Function to handle category opening based on URL parameters
    function handleCategoryDeepLink() {
        const urlParams = new URLSearchParams(window.location.search);
        const categoryParam = urlParams.get('category');
        
        if (categoryParam) {
            // Scroll to the featured products section
            const featuredProductsSection = document.getElementById('featured-products');
            if (featuredProductsSection) {
                featuredProductsSection.scrollIntoView({ behavior: 'smooth' });
                
                // Find and click the appropriate category link
                setTimeout(function() {
                    const collectionId = categoryParam === 'mbna_2025' ? 'mbna_2025-collection' :
                                        categoryParam === 'monuments' ? 'monuments-collection' :
                                        categoryParam === 'columbarium' ? 'columbarium-collection' :
                                        categoryParam === 'designs' ? 'Designs-collection' :
                                        categoryParam === 'benches' ? 'benches-collection' : '';
                    
                    if (collectionId) {
                        // Look for the category link with the matching data-collection attribute
                        const categoryLinks = document.querySelectorAll('.category-link');
                        let targetLink = null;
                        
                        for (const link of categoryLinks) {
                            if (link.getAttribute('data-collection') === collectionId) {
                                targetLink = link;
                                break;
                            }
                        }
                        
                        // If we found a link, simulate clicking it to open the modal
                        if (targetLink) {
                            // Check if there's a click event handler already attached
                            // If not, this will at least update the URL state for proper bookmarking
                            if (typeof jQuery !== 'undefined') {
                                jQuery(targetLink).trigger('click');
                            } else {
                                // Fallback to native click
                                targetLink.click();
                            }
                        }
                    }
                }, 800); // Small delay to ensure smooth scroll completes
            }
        }
    }
    
    // Update browser history when a category link is clicked
    document.querySelectorAll('.category-link').forEach(function(link) {
        link.addEventListener('click', function(e) {
            // Don't prevent default - let the original click handler work
            
            // Update the URL without reloading the page
            const categoryValue = this.getAttribute('href').replace('?category=', '');
            const newUrl = window.location.pathname + '?category=' + categoryValue;
            
            // Update browser history state
            window.history.pushState({ category: categoryValue }, '', newUrl);
        });
    });
    
    // Handle back/forward navigation
    window.addEventListener('popstate', function(event) {
        // Re-handle the URL parameters when navigation changes
        handleCategoryDeepLink();
    });
    
    // Initial check for deep link on page load
    handleCategoryDeepLink();
});
