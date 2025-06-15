# Implementation Plan for Angel Stones "Specials" Section

## Architecture Overview

We'll implement the Specials section as a fully integrated component within the existing Single Page Application (SPA) architecture. The implementation will use modals for displaying PDF content rather than separate pages, maintaining the seamless SPA experience for users.

## Technology Stack

- **Frontend**: 
  - HTML5/CSS3
  - Bootstrap 5.3.2 (existing dependency)
  - jQuery (existing dependency)
  - PDF.js for PDF rendering (Mozilla's open-source PDF viewer)
  - turn.js for book-like page flipping effects

- **Backend**: 
  - PHP 8.0+ (as used in the existing XAMPP setup)
  - AJAX for asynchronous data loading
  - File system storage for PDF files

## Implementation Steps

### 1. Project Setup (Day 1)

- Create directories for the Specials section within the existing SPA structure:
  ```
  /images/
    /specials/
      /pdfs/         # Store PDF files here
      /thumbnails/   # Store thumbnail images
  /js/
    /specials/       # JavaScript components for specials functionality
  /css/
    /specials/       # CSS styles for specials section
  ```

- Create AJAX endpoints for specials data:
  ```
  /api/specials.php  # API endpoint for specials data
  ```

- Import required libraries (via CDN or local files):
  - PDF.js (for rendering PDFs)
  - turn.js (for page flip animation)

### 2. Backend Implementation (Days 1-2)

#### AJAX API for Specials Management

- Create a PHP API endpoint to serve specials data:

```php
// api/specials.php
<?php
require_once '../includes/SpecialsManager.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? 'list';
$manager = new SpecialsManager();

switch ($action) {
    case 'list':
        echo json_encode($manager->getAllSpecials());
        break;
    case 'get':
        $id = $_GET['id'] ?? '';
        echo json_encode($manager->getSpecialById($id));
        break;
    // Other actions as needed
    default:
        echo json_encode(['error' => 'Invalid action']);
}
```

- Create the SpecialsManager class:

```php
// includes/SpecialsManager.php
class SpecialsManager {
    private $pdfDirectory;
    private $thumbnailDirectory;
    
    public function __construct() {
        $this->pdfDirectory = __DIR__ . '/../images/specials/pdfs/';
        $this->thumbnailDirectory = __DIR__ . '/../images/specials/thumbnails/';
    }
    
    public function getAllSpecials() {
        // Return list of all available specials with metadata
        $specials = [];
        foreach (glob($this->pdfDirectory . '*.pdf') as $pdfFile) {
            $specials[] = $this->getSpecialMetadata($pdfFile);
        }
        return $specials;
    }
    
    public function getSpecialById($id) {
        // Get a specific special by ID
        $filePath = $this->pdfDirectory . $id . '.pdf';
        if (file_exists($filePath)) {
            return $this->getSpecialMetadata($filePath);
        }
        return null;
    }
    
    private function getSpecialMetadata($pdfFile) {
        // Extract metadata and ensure thumbnail exists
        $filename = basename($pdfFile);
        $id = pathinfo($filename, PATHINFO_FILENAME);
        
        // Check if thumbnail exists, generate if not
        $thumbnailPath = $this->thumbnailDirectory . $id . '.jpg';
        if (!file_exists($thumbnailPath)) {
            $this->generateThumbnail($pdfFile, $thumbnailPath);
        }
        
        return [
            'id' => $id,
            'filename' => $filename,
            'url' => '/images/specials/pdfs/' . $filename,
            'thumbnail' => '/images/specials/thumbnails/' . $id . '.jpg',
            // Add other metadata as needed
        ];
    }
    
    public function generateThumbnail($pdfFile, $outputPath) {
        // Generate thumbnail from first page of PDF
        // Implementation depends on available PDF tools
    }
}
```

- Add an admin section to the existing CRM for managing specials

### 3. Frontend Implementation (Days 3-5)

#### SPA Integration

- Add the Specials section to the homepage above Featured Products:

```html
<!-- Insert in index.php before the Featured Products section -->
<section id="specials" class="specials-section">
    <div class="container">
        <h2 class="section-title">Special Offers</h2>
        <div class="specials-container">
            <div class="row" id="specials-row">
                <!-- Specials will be loaded here via JavaScript -->
            </div>
        </div>
    </div>
</section>
```

#### Modal PDF Viewer Component

- Create a modal component for the PDF viewer to maintain SPA experience:

```html
<!-- Add to index.php, outside of other containers -->
<div class="modal fade" id="pdf-viewer-modal" tabindex="-1" aria-labelledby="pdfViewerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="pdfViewerModalLabel">Special Offer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="pdf-viewer-container">
                    <div id="pdf-book" class="pdf-book">
                        <!-- PDF pages will be loaded here -->
                    </div>
                    <div class="pdf-controls">
                        <button id="prev-page" class="btn btn-primary"><i class="bi bi-chevron-left"></i></button>
                        <span id="page-num"></span> / <span id="page-count"></span>
                        <button id="next-page" class="btn btn-primary"><i class="bi bi-chevron-right"></i></button>
                        <a id="download-pdf" class="btn btn-secondary"><i class="bi bi-download"></i> Download PDF</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
```

### 4. JavaScript Implementation (Days 5-7)

#### AJAX Loading and PDF Rendering

- Create JavaScript to load specials data and display thumbnails in the SPA:

```javascript
// js/specials/specials-loader.js
const SpecialsModule = (function() {
    // Private variables and functions
    let specials = [];
    
    function loadSpecials() {
        return fetch('/api/specials.php?action=list')
            .then(response => response.json())
            .then(data => {
                specials = data;
                renderSpecials();
                return data;
            });
    }
    
    function renderSpecials() {
        const container = document.getElementById('specials-row');
        if (!container) return;
        
        container.innerHTML = '';
        
        specials.forEach(special => {
            const specialCol = document.createElement('div');
            specialCol.className = 'col-md-6 col-lg-4 mb-4';
            
            const specialCard = document.createElement('div');
            specialCard.className = 'special-card h-100';
            specialCard.dataset.specialId = special.id;
            specialCard.addEventListener('click', () => openSpecialModal(special.id));
            
            specialCard.innerHTML = `
                <div class="special-thumbnail">
                    <img src="${special.thumbnail}" alt="${special.filename}" class="img-fluid">
                    <div class="special-overlay">
                        <span class="view-special">View Special</span>
                    </div>
                </div>
            `;
            
            specialCol.appendChild(specialCard);
            container.appendChild(specialCol);
        });
    }
    
    function openSpecialModal(specialId) {
        const modal = document.getElementById('pdf-viewer-modal');
        const modalInstance = new bootstrap.Modal(modal);
        
        // Clear previous PDF
        document.getElementById('pdf-book').innerHTML = '';
        
        // Get special details
        fetch(`/api/specials.php?action=get&id=${specialId}`)
            .then(response => response.json())
            .then(special => {
                document.getElementById('pdfViewerModalLabel').textContent = 
                    `Special Offer: ${special.filename.replace('.pdf', '')}`;
                
                // Set download link
                document.getElementById('download-pdf').href = special.url;
                
                // Initialize PDF viewer
                initPdfViewer(special.url);
                
                // Show modal
                modalInstance.show();
            });
    }
    
    // Public API
    return {
        init: function() {
            loadSpecials();
        },
        refresh: function() {
            loadSpecials();
        }
    };
})();

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    SpecialsModule.init();
});
```

#### PDF Viewer with Book-like Interface

```javascript
// js/specials/pdf-viewer.js
function initPdfViewer(pdfUrl) {
    const container = document.getElementById('pdf-book');
    const prevBtn = document.getElementById('prev-page');
    const nextBtn = document.getElementById('next-page');
    const pageNumSpan = document.getElementById('page-num');
    const pageCountSpan = document.getElementById('page-count');
    
    // Load the PDF document
    const loadingTask = pdfjsLib.getDocument(pdfUrl);
    loadingTask.promise.then(function(pdf) {
        // Initialize turn.js after we know how many pages
        const numPages = pdf.numPages;
        pageCountSpan.textContent = numPages;
        
        // Prepare container for turn.js
        container.innerHTML = '';
        for (let i = 1; i <= numPages; i++) {
            const pageDiv = document.createElement('div');
            pageDiv.className = 'pdf-page';
            pageDiv.dataset.pageNumber = i;
            container.appendChild(pageDiv);
        }
        
        // Initialize turn.js
        $(container).turn({
            width: container.offsetWidth,
            height: container.offsetHeight * 0.75,
            autoCenter: true,
            elevation: 50,
            gradients: true,
            acceleration: true,
            when: {
                turning: function(event, page, view) {
                    pageNumSpan.textContent = page;
                }
            }
        });
        
        // Render each page
        for (let i = 1; i <= numPages; i++) {
            renderPage(pdf, i);
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
        
        // Handle resize for responsiveness
        window.addEventListener('resize', function() {
            if ($(container).data().turn) {
                $(container).turn('size', container.offsetWidth, container.offsetHeight * 0.75);
            }
        });
    });
}

async function renderPage(pdf, pageNumber) {
    const pageContainer = document.querySelector(`.pdf-page[data-page-number="${pageNumber}"]`);
    if (!pageContainer) return;
    
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
        console.error('Error rendering page:', error);
    }
}
```

### 5. Responsive Design (Days 7-8)

- Implement responsive layouts for different screen sizes:

```css
/* specials/css/specials.css */
.specials-section {
    padding: 3rem 0;
    background-color: #f8f9fa;
}

.special-preview {
    margin: 0 15px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
    cursor: pointer;
}

.special-preview:hover {
    transform: translateY(-5px);
}

.pdf-viewer-container {
    max-width: 100%;
    overflow: hidden;
}

.pdf-book {
    margin: 0 auto;
}

/* Responsive adjustments */
@media (max-width: 767px) {
    .pdf-controls {
        flex-direction: column;
    }
    
    .pdf-book {
        width: 100% !important;
        height: auto !important;
    }
}
```

### 6. Testing Phase (Days 9-10)

- Test on multiple browsers (Chrome, Firefox, Edge, Safari)
- Test on different devices (desktop, tablet, mobile)
- Performance testing for PDF loading times
- Accessibility testing

### 7. Deployment (Day 10)

- Merge code to production
- Add initial PDF specials
- Final QA check

## File Structure

```
/images/specials/
  /pdfs/
    special1.pdf
    special2.pdf
  /thumbnails/
    special1.jpg
    special2.jpg
/js/specials/
  pdf-viewer.js
  specials-loader.js
/css/specials/
  specials.css
/api/
  specials.php
/includes/
  SpecialsManager.php
```

## Dependencies to Add

1. **PDF.js**
   - Version: Latest stable (currently 3.6.172)
   - Purpose: PDF rendering in browser
   - URL: https://mozilla.github.io/pdf.js/

2. **turn.js**
   - Version: 4.1.0
   - Purpose: Book-like page flipping effects
   - URL: http://www.turnjs.com/

3. **Slick Carousel**
   - Version: 1.8.1
   - Purpose: Responsive carousel for multiple PDFs
   - URL: https://kenwheeler.github.io/slick/

## Integration with Existing Code

To integrate the Specials section into the homepage, we'll need to:

1. Identify the current homepage template file
2. Insert the Specials section HTML above the Featured Products section
3. Add the required CSS and JavaScript to the page header
4. Ensure the backend code is properly included in the page lifecycle

## Backend Admin Interface

For managing the specials, we'll create a simple admin interface that:

1. Lists all current special PDFs
2. Allows uploading new PDFs
3. Allows reordering/prioritizing specials
4. Provides options to archive old specials

## Mobile Considerations

- Adjust page flip animation to use swipe gestures on touch devices
- Optimize PDF loading to reduce data usage on mobile networks
- Ensure all controls are touch-friendly with appropriate sizing

## Performance Optimizations

- Generate and cache thumbnails server-side
- Implement lazy loading for PDF content
- Use PDF.js worker threads for better performance
- Optimize PDF files for web viewing before upload

## Analytics Integration (Future Phase)

- Track PDF views
- Track page interactions within PDFs
- Measure conversion from PDF specials to product views/purchases
