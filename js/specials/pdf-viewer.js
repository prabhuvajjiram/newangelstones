/**
 * PDF Viewer with Book-like Interface
 * For Angel Stones CRM Specials Section
 * Uses PDF.js and turn.js libraries
 */

/**
 * Initialize the PDF viewer with the given URL
 * @param {string} pdfUrl URL to the PDF file
 * @returns {Promise} Promise that resolves when PDF is loaded
 */
function initPdfViewer(pdfUrl) {
    return new Promise((resolve, reject) => {
        const container = document.getElementById('pdf-book');
        const prevBtn = document.getElementById('prev-page');
        const nextBtn = document.getElementById('next-page');
        const pageNumSpan = document.getElementById('page-num');
        const pageCountSpan = document.getElementById('page-count');
        
        if (!container) {
            reject(new Error('PDF container not found'));
            return;
        }
        
        // Reset container
        container.innerHTML = '';
        
        try {
            // Load the PDF document
            const loadingTask = pdfjsLib.getDocument(pdfUrl);
            
            loadingTask.promise
                .then(function(pdf) {
                    const numPages = pdf.numPages;
                    pageCountSpan.textContent = numPages;
                    
                    // Prepare container for turn.js
                    for (let i = 1; i <= numPages; i++) {
                        const pageDiv = document.createElement('div');
                        pageDiv.className = 'pdf-page';
                        pageDiv.dataset.pageNumber = i;
                        container.appendChild(pageDiv);
                    }
                    
                    // Get container dimensions
                    const containerWidth = container.parentElement.clientWidth;
                    const containerHeight = container.parentElement.clientHeight;
                    
                    // Detect if we're on mobile
                    const isMobile = window.innerWidth < 768;
                    
                    // Calculate dimensions maintaining aspect ratio
                    const aspectRatio = 1.414; // Standard PDF aspect ratio (A4)
                    const height = Math.min(containerHeight * 0.8, containerWidth / aspectRatio);
                    const width = height * aspectRatio;
                    
                    // Add mobile class if needed
                    if (isMobile) {
                        container.classList.add('mobile-pdf-view');
                    } else {
                        container.classList.remove('mobile-pdf-view');
                    }
                    
                    // Configure viewer based on device
                    const viewerConfig = {
                        width: width,
                        height: height,
                        autoCenter: true,
                        display: isMobile ? 'single' : 'double',  // Single page display on mobile
                        elevation: isMobile ? 0 : 50,
                        gradients: !isMobile,  // Disable gradients on mobile for performance
                        acceleration: true,
                        when: {
                            turning: function(event, page, view) {
                                pageNumSpan.textContent = page;
                            },
                            turned: function(event, page, view) {
                                // Once the page is turned, ensure it's rendered
                                view.forEach(pageNum => {
                                    if (pageNum > 0) {
                                        renderPage(pdf, pageNum);
                                    }
                                });
                                
                                // Render adjacent pages for smoother experience
                                const currentPage = $(container).turn('page');
                                if (currentPage > 1) renderPage(pdf, currentPage - 1);
                                if (currentPage < numPages) renderPage(pdf, currentPage + 1);
                            }
                        }
                    };
                    
                    // Initialize turn.js with our configuration
                    $(container).turn(viewerConfig);
                    
                    // Render first two pages initially
                    renderPage(pdf, 1);
                    if (numPages > 1) {
                        renderPage(pdf, 2);
                    }
                    
                    // Set initial page number
                    pageNumSpan.textContent = '1';
                    
                    // Hook up navigation buttons
                    prevBtn.onclick = function() {
                        $(container).turn('previous');
                    };
                    
                    nextBtn.onclick = function() {
                        $(container).turn('next');
                    };
                    
                    // Enable keyboard navigation
                    document.addEventListener('keydown', function(event) {
                        if (document.getElementById('pdf-viewer-modal').classList.contains('show')) {
                            if (event.key === 'ArrowLeft') {
                                $(container).turn('previous');
                            }
                            else if (event.key === 'ArrowRight') {
                                $(container).turn('next');
                            }
                        }
                    });
                    
                    // Handle resize for responsiveness
                    const resizeDebounce = debounce(function() {
                        if ($(container).data().turn) {
                            const newContainerWidth = container.parentElement.clientWidth;
                            const newHeight = Math.min(container.parentElement.clientHeight * 0.8, newContainerWidth / aspectRatio);
                            const newWidth = newHeight * aspectRatio;
                            
                            $(container).turn('size', newWidth, newHeight);
                            
                            // Re-render current pages after resize
                            const view = $(container).turn('view');
                            view.forEach(pageNum => {
                                if (pageNum > 0) {
                                    renderPage(pdf, pageNum);
                                }
                            });
                        }
                    }, 250);
                    
                    window.addEventListener('resize', resizeDebounce);
                    
                    // Enable touch gestures for mobile
                    enableTouchGestures(container);
                    
                    resolve();
                })
                .catch(function(error) {
                    console.error('Error loading PDF:', error);
                    reject(error);
                });
        } catch (error) {
            console.error('Error initializing PDF viewer:', error);
            reject(error);
        }
    });
}

/**
 * Render a specific page of the PDF
 * @param {Object} pdf PDF.js document
 * @param {number} pageNumber Page number to render
 */
async function renderPage(pdf, pageNumber) {
    const pageContainer = document.querySelector(`.pdf-page[data-page-number="${pageNumber}"]`);
    if (!pageContainer || pageContainer.querySelector('canvas')) {
        return;
    }
    
    try {
        const page = await pdf.getPage(pageNumber);
        const canvas = document.createElement('canvas');
        pageContainer.appendChild(canvas);
        
        const context = canvas.getContext('2d');
        
        // Calculate scale to fit within container
        const containerWidth = pageContainer.clientWidth;
        const viewport = page.getViewport({ scale: 1 });
        const scale = containerWidth / viewport.width;
        const scaledViewport = page.getViewport({ scale: scale });
        
        canvas.height = scaledViewport.height;
        canvas.width = scaledViewport.width;
        
        const renderContext = {
            canvasContext: context,
            viewport: scaledViewport
        };
        
        await page.render(renderContext).promise;
    } catch (error) {
        console.error(`Error rendering page ${pageNumber}:`, error);
    }
}

/**
 * Enable touch gestures for swiping pages
 * @param {HTMLElement} container PDF container element
 */
function enableTouchGestures(container) {
    let touchStartX = 0;
    let touchEndX = 0;
    
    container.addEventListener('touchstart', function(event) {
        touchStartX = event.changedTouches[0].screenX;
    }, false);
    
    container.addEventListener('touchend', function(event) {
        touchEndX = event.changedTouches[0].screenX;
        handleSwipe();
    }, false);
    
    function handleSwipe() {
        const threshold = 50; // Minimum swipe distance
        if (touchEndX < touchStartX - threshold) {
            // Swipe left - next page
            $(container).turn('next');
        } else if (touchEndX > touchStartX + threshold) {
            // Swipe right - previous page
            $(container).turn('previous');
        }
    }
}

/**
 * Debounce function to limit frequency of function calls
 * @param {Function} func Function to debounce
 * @param {number} wait Wait time in ms
 * @returns {Function} Debounced function
 */
function debounce(func, wait) {
    let timeout;
    return function() {
        const context = this;
        const args = arguments;
        clearTimeout(timeout);
        timeout = setTimeout(function() {
            func.apply(context, args);
        }, wait);
    };
}
