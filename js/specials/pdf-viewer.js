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
                    
                    // Detect device type
                    const isMobile = window.innerWidth < 768;
                    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
                    
                    // Add a special class for iOS devices
                    if (isIOS) {
                        container.classList.add('ios-pdf-view');
                        document.body.classList.add('ios-pdf-active');
                    }
                    
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
                    
                    // Use different initialization based on device
                    if (isIOS) {
                        // Use simpler viewer for iOS for better compatibility
                        console.log('Using simple PDF viewer for iOS');
                        initSimpleViewer(container, pdf, numPages, pageNumSpan, prevBtn, nextBtn);
                    } else {
                        // Initialize turn.js with our configuration
                        $(container).turn(viewerConfig);
                    }
                    
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
 * @param {HTMLElement} targetElement Optional specific element to render into (for iOS simple viewer)
 */
async function renderPage(pdf, pageNumber, targetElement = null) {
    // If targetElement is provided (iOS simple viewer), use that, otherwise find page in turn.js
    const pageEl = targetElement || document.querySelector(`#pdf-book .pdf-page[data-page-number="${pageNumber}"]`);
    if (!pageEl) return; // Page element not found
    if (pageEl.querySelector('canvas')) {
        return;
    }
    
    try {
        const page = await pdf.getPage(pageNumber);
        const canvas = document.createElement('canvas');
        pageEl.appendChild(canvas);
        
        const context = canvas.getContext('2d');
        
        // Calculate scale to fit within container
        const containerWidth = pageEl.clientWidth;
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

/**
 * Initialize a simple viewer for iOS devices where turn.js has compatibility issues
 * This provides a stripped-down but reliable viewing experience
 * @param {HTMLElement} container - The container element
 * @param {Object} pdf - The PDF.js pdf document
 * @param {number} numPages - Number of pages in the PDF
 * @param {HTMLElement} pageNumSpan - Element to display current page number
 * @param {HTMLElement} prevBtn - Previous page button
 * @param {HTMLElement} nextBtn - Next page button
 */
function initSimpleViewer(container, pdf, numPages, pageNumSpan, prevBtn, nextBtn) {
    // Clear the container first
    container.innerHTML = '';
    container.classList.add('ios-simple-viewer');
    
    // Create a simple page container
    const pageContainer = document.createElement('div');
    pageContainer.className = 'ios-page-container';
    container.appendChild(pageContainer);
    
    // Create page navigation
    let currentPage = 1;
    pageNumSpan.textContent = currentPage;
    
    // Render initial page
    renderPage(pdf, currentPage, pageContainer);
    
    // Function to change pages
    function changePage(newPage) {
        if (newPage < 1 || newPage > numPages) return;
        
        currentPage = newPage;
        pageNumSpan.textContent = currentPage;
        renderPage(pdf, currentPage, pageContainer);
    }
    
    // Set up navigation buttons
    prevBtn.onclick = function() {
        changePage(currentPage - 1);
    };
    
    nextBtn.onclick = function() {
        changePage(currentPage + 1);
    };
    
    // Handle touch swipe for changing pages
    let startX, startY;
    
    container.addEventListener('touchstart', function(e) {
        startX = e.touches[0].clientX;
        startY = e.touches[0].clientY;
    }, { passive: true });
    
    container.addEventListener('touchend', function(e) {
        if (!startX || !startY) return;
        
        const endX = e.changedTouches[0].clientX;
        const endY = e.changedTouches[0].clientY;
        
        const diffX = startX - endX;
        const diffY = startY - endY;
        
        // Only handle horizontal swipes (avoid triggering on scrolls)
        if (Math.abs(diffX) > Math.abs(diffY) && Math.abs(diffX) > 50) {
            if (diffX > 0) {
                // Swipe left - next page
                changePage(currentPage + 1);
            } else {
                // Swipe right - previous page
                changePage(currentPage - 1);
            }
        }
        
        startX = null;
        startY = null;
    }, { passive: true });
    
    // Support keyboard navigation
    document.addEventListener('keydown', function(e) {
        if (document.getElementById('pdf-viewer-modal').classList.contains('show')) {
            if (e.key === 'ArrowLeft') {
                changePage(currentPage - 1);
            } else if (e.key === 'ArrowRight') {
                changePage(currentPage + 1);
            }
        }
    });
}
