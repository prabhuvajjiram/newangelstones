/**
 * Specials Module - Handles loading and displaying special offers
 * For Angel Stones CRM
 */
const SpecialsModule = (function() {
    // Private variables
    let specials = [];
    let modalInitialized = false;
    
    /**
     * Load specials data from API
     * @return {Promise} Promise resolving to specials data
     */
    function loadSpecials() {
        return fetch('/api/specials.php?action=list')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.specials) {
                    specials = data.specials;
                    renderSpecials();
                    return data.specials;
                } else {
                    console.error('Error loading specials:', data.error || 'Unknown error');
                    return [];
                }
            })
            .catch(error => {
                console.error('Error fetching specials data:', error);
                return [];
            });
    }
    
    /**
     * Render specials in the container
     */
    function renderSpecials() {
        const container = document.getElementById('specials-row');
        if (!container) return;
        
        // Clear container
        container.innerHTML = '';
        
        if (specials.length === 0) {
            // Hide the section if no specials are available
            const section = document.getElementById('specials');
            if (section) {
                section.style.display = 'none';
            }
            return;
        }
        
        // Show section if it was hidden
        const section = document.getElementById('specials');
        if (section) {
            section.style.display = 'block';
        }
        
        // Render each special
        specials.forEach((special, index) => {
            const specialCol = document.createElement('div');
            specialCol.className = 'col-md-6 col-lg-4 mb-4';
            
            const specialCard = document.createElement('div');
            specialCard.className = 'special-card h-100';
            specialCard.dataset.specialId = special.id;
            
            // Create card content
            specialCard.innerHTML = `
                <div class="special-thumbnail">
                    <img src="${special.thumbnail}" alt="${special.title}" class="img-fluid">
                    <div class="special-overlay">
                        <span class="view-special">View Special</span>
                    </div>
                </div>
                <div class="special-info">
                    <h5 class="special-title">${special.title}</h5>
                    <div class="special-meta">
                        <span class="special-size">${special.size}</span>
                    </div>
                </div>
            `;
            
            // Add click event
            specialCard.addEventListener('click', () => openSpecialModal(special.id));
            
            specialCol.appendChild(specialCard);
            container.appendChild(specialCol);
        });
    }
    
    /**
     * Open modal with PDF viewer for a special
     * @param {string} specialId ID of the special to view
     */
    function openSpecialModal(specialId) {
        if (!modalInitialized) {
            initModal();
        }
        
        const modal = document.getElementById('pdf-viewer-modal');
        if (!modal) return;
        
        const modalInstance = new bootstrap.Modal(modal);
        
        // Clear previous PDF
        document.getElementById('pdf-book').innerHTML = '';
        
        // Show loading indicator
        document.getElementById('pdf-loading').style.display = 'block';
        document.getElementById('pdf-content').style.display = 'none';
        
        // Get special details
        fetch(`/api/specials.php?action=get&id=${specialId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.special) {
                    const special = data.special;
                    
                    // Update modal title
                    document.getElementById('pdfViewerModalLabel').textContent = 
                        `${special.title}`;
                    
                    // Set download link
                    const downloadBtn = document.getElementById('download-pdf');
                    if (downloadBtn) {
                        downloadBtn.href = special.url;
                        downloadBtn.download = special.filename;
                    }
                    
                    // Initialize PDF viewer
                    initPdfViewer(special.url)
                        .then(() => {
                            // Hide loading indicator
                            document.getElementById('pdf-loading').style.display = 'none';
                            document.getElementById('pdf-content').style.display = 'block';
                        })
                        .catch(error => {
                            console.error('Error initializing PDF viewer:', error);
                            document.getElementById('pdf-loading').style.display = 'none';
                            document.getElementById('pdf-error').style.display = 'block';
                        });
                    
                    // Show modal
                    modalInstance.show();
                } else {
                    console.error('Error getting special details:', data.error || 'Unknown error');
                }
            })
            .catch(error => {
                console.error('Error fetching special details:', error);
            });
    }
    
    /**
     * Initialize modal HTML if not already in the document
     */
    function initModal() {
        // Check if modal already exists
        if (document.getElementById('pdf-viewer-modal')) {
            modalInitialized = true;
            return;
        }
        
        // Create modal HTML
        const modalHtml = `
            <div class="modal fade" id="pdf-viewer-modal" tabindex="-1" aria-labelledby="pdfViewerModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="pdfViewerModalLabel">Special Offer</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-0">
                            <div id="pdf-loading" class="text-center p-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-2">Loading PDF...</p>
                            </div>
                            <div id="pdf-error" class="text-center p-5" style="display: none;">
                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle"></i> 
                                    There was a problem loading this PDF. Please try again later.
                                </div>
                            </div>
                            <div id="pdf-content" class="pdf-viewer-container" style="display: none;">
                                <div id="pdf-book" class="pdf-book">
                                    <!-- PDF pages will be loaded here -->
                                </div>
                                <div class="pdf-controls">
                                    <button id="prev-page" class="btn btn-primary"><i class="bi bi-chevron-left"></i></button>
                                    <span id="page-num">1</span> / <span id="page-count">2</span>
                                    <button id="next-page" class="btn btn-primary"><i class="bi bi-chevron-right"></i></button>
                                    <a id="download-pdf" class="btn btn-secondary ms-3" download><i class="bi bi-download"></i> Download PDF</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Append modal to body
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        modalInitialized = true;
    }
    
    // Public API
    return {
        /**
         * Initialize the Specials module
         */
        init: function() {
            // Add specials section to page if not already present
            const specialsContainer = document.getElementById('specials');
            if (!specialsContainer) {
                // Find featured products section
                const featuredSection = document.querySelector('.featured-products');
                if (featuredSection) {
                    // Create specials section
                    const specialsHtml = `
                        <section id="specials" class="specials-section">
                            <div class="container">
                                <h2 class="section-title">Special Offers</h2>
                                <div class="specials-container">
                                    <div class="row" id="specials-row">
                                        <!-- Specials will be loaded here -->
                                    </div>
                                </div>
                            </div>
                        </section>
                    `;
                    
                    // Insert before featured products
                    featuredSection.insertAdjacentHTML('beforebegin', specialsHtml);
                }
            }
            
            // Load specials data
            loadSpecials();
        },
        
        /**
         * Refresh specials data
         */
        refresh: function() {
            loadSpecials();
        }
    };
})();

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Make sure PDF.js is loaded before initializing
    if (typeof pdfjsLib === 'undefined') {
        // Load PDF.js library if not already loaded
        const script = document.createElement('script');
        script.src = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.11.338/pdf.min.js';
        script.onload = function() {
            pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.11.338/pdf.worker.min.js';
            
            // Now load turn.js for page flipping
            const turnScript = document.createElement('script');
            turnScript.src = 'https://cdnjs.cloudflare.com/ajax/libs/turn.js/3/turn.min.js';
            turnScript.onload = function() {
                SpecialsModule.init();
            };
            document.head.appendChild(turnScript);
        };
        document.head.appendChild(script);
    } else {
        SpecialsModule.init();
    }
});
