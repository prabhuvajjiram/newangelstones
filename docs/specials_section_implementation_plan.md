# Implementation Plan for Angel Stones "Specials" Section

## Architecture Overview

We'll implement the Specials section using a combination of HTML, CSS, JavaScript, and PHP to create a modern, responsive PDF display section above the Featured Products section on the homepage.

## Technology Stack

- **Frontend**: 
  - HTML5/CSS3
  - Bootstrap 5.3.2 (existing dependency)
  - jQuery (existing dependency)
  - PDF.js for PDF rendering (Mozilla's open-source PDF viewer)
  - turn.js for book-like page flipping effects

- **Backend**: 
  - PHP 8.0+ (as used in the existing XAMPP setup)
  - File system storage for PDF files

## Implementation Steps

### 1. Project Setup (Day 1)

- Create a new directory structure for the Specials section:
  ```
  /specials/
    /pdfs/         # Store PDF files here
    /thumbnails/   # Store thumbnail images
    index.php      # Controller for managing specials
    viewer.php     # PDF viewer page
  ```

- Import required libraries:
  - PDF.js (for rendering PDFs)
  - turn.js (for page flip animation)

### 2. Backend Implementation (Days 1-2)

#### PDF Management System

- Create a PHP class `SpecialsManager` that will:
  - Scan the /pdfs/ directory to find available specials
  - Generate and cache thumbnails
  - Provide metadata about each PDF (page count, creation date)

```php
// specials/SpecialsManager.php
class SpecialsManager {
    private $pdfDirectory;
    private $thumbnailDirectory;
    
    public function __construct() {
        $this->pdfDirectory = __DIR__ . '/pdfs/';
        $this->thumbnailDirectory = __DIR__ . '/thumbnails/';
    }
    
    public function getAllSpecials() {
        // Return list of all available specials with metadata
    }
    
    public function generateThumbnail($pdfFile) {
        // Generate thumbnail for a PDF file
    }
}
```

- Create an administrative interface for managing PDF specials:
  - Upload new PDFs
  - Set display order
  - Archive old specials

### 3. Frontend Implementation (Days 3-5)

#### Homepage Integration

- Modify the homepage to include the Specials section above Featured Products:

```php
// Add to the homepage template
<section id="specials" class="specials-section">
    <div class="container">
        <h2 class="section-title">Special Offers</h2>
        <div class="specials-carousel">
            <!-- PDF previews will be loaded here -->
        </div>
    </div>
</section>
```

#### PDF Viewer Component

- Create a responsive PDF viewer component with:
  - Thumbnail previews of available specials
  - Book-like interface for viewing PDFs
  - Navigation controls (next/previous pages, zoom)

```html
<!-- specials/viewer.php -->
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
```

### 4. JavaScript Implementation (Days 5-7)

#### PDF Rendering

- Implement PDF.js to render the PDF documents:

```javascript
// specials/js/pdf-viewer.js
async function renderPDF(url, canvasContainer, options) {
    const loadingTask = pdfjsLib.getDocument(url);
    const pdf = await loadingTask.promise;
    
    // Configure turn.js for page flipping effect
    $(canvasContainer).turn({
        width: options.width,
        height: options.height,
        autoCenter: true,
        elevation: 50,
        gradients: true,
        when: {
            turning: function(e, page, view) {
                // Update page number display
                document.getElementById('page-num').textContent = page;
            }
        }
    });
    
    // Render each page
    for (let i = 1; i <= pdf.numPages; i++) {
        const page = await pdf.getPage(i);
        const viewport = page.getViewport({ scale: options.scale });
        
        // Create page element
        const pageElement = document.createElement('div');
        const canvas = document.createElement('canvas');
        pageElement.appendChild(canvas);
        
        const context = canvas.getContext('2d');
        canvas.height = viewport.height;
        canvas.width = viewport.width;
        
        // Render PDF page to canvas
        const renderContext = {
            canvasContext: context,
            viewport: viewport
        };
        
        await page.render(renderContext).promise;
        $(canvasContainer).turn('addPage', pageElement);
    }
    
    document.getElementById('page-count').textContent = pdf.numPages;
}
```

#### Carousel for Multiple PDFs

- Implement a carousel to navigate between different special offer PDFs:

```javascript
// specials/js/specials-carousel.js
$(document).ready(function() {
    $('.specials-carousel').slick({
        dots: true,
        arrows: true,
        infinite: false,
        speed: 300,
        slidesToShow: 2,
        slidesToScroll: 1,
        responsive: [
            {
                breakpoint: 768,
                settings: {
                    slidesToShow: 1
                }
            }
        ]
    });
    
    // Handle clicking on a special offer preview
    $('.special-preview').click(function() {
        const pdfUrl = $(this).data('pdf-url');
        $('#pdf-viewer-modal').modal('show');
        renderPDF(pdfUrl, '#pdf-book', {
            width: 800,
            height: 600,
            scale: 1.5
        });
    });
});
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
/specials/
  /css/
    specials.css
  /js/
    pdf-viewer.js
    specials-carousel.js
  /pdfs/
    special1.pdf
    special2.pdf
  /thumbnails/
    special1.jpg
    special2.jpg
  index.php
  viewer.php
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
