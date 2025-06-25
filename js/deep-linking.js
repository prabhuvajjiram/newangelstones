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
    
    // Initial check for deep link on page load
    handleCategoryDeepLink();
});
