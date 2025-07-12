/**
 * Legal Modals Handler
 * Loads legal document content dynamically into modals
 */
document.addEventListener('DOMContentLoaded', function() {
    // Cache for loaded content
    const contentCache = {};
    
    // Helper function to extract and clean content from HTML pages
    function extractContentFromHTML(html) {
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        
        // Create a container for the cleaned content
        const container = document.createElement('div');
        
        // Extract the title
        const title = doc.querySelector('h1');
        if (title) {
            const titleElement = document.createElement('h1');
            titleElement.textContent = title.textContent.trim();
            container.appendChild(titleElement);
        }
        
        // Extract the effective date if it exists
        const paragraphs = doc.querySelectorAll('p');
        if (paragraphs.length > 0 && paragraphs[0].textContent.includes('Effective Date')) {
            const dateElement = document.createElement('p');
            dateElement.className = 'effective-date';
            dateElement.textContent = paragraphs[0].textContent.trim();
            container.appendChild(dateElement);
        }
        
        // Extract all other content
        const contentElements = doc.querySelectorAll('body > *');
        contentElements.forEach(element => {
            // Skip the title we already added
            if (element.tagName === 'H1') return;
            
            // Skip the first paragraph if it's the effective date we already added
            if (element.tagName === 'P' && element.textContent.includes('Effective Date') && 
                Array.from(paragraphs).indexOf(element) === 0) return;
            
            // Create a clean copy of the element
            const cleanElement = document.createElement(element.tagName);
            
            // Copy text content and clean it
            cleanElement.textContent = element.textContent.trim();
            
            // Copy important attributes for links
            if (element.tagName === 'A') {
                cleanElement.href = element.getAttribute('href');
                cleanElement.target = '_blank';
                cleanElement.rel = 'noopener';
            }
            
            // For lists, properly recreate list items
            if (element.tagName === 'UL' || element.tagName === 'OL') {
                element.querySelectorAll('li').forEach(li => {
                    const listItem = document.createElement('li');
                    listItem.textContent = li.textContent.trim();
                    cleanElement.appendChild(listItem);
                });
            }
            
            // Add the clean element to our container
            container.appendChild(cleanElement);
        });
        
        return container.innerHTML;
    }
    
    // Function to load content into modal
    function loadLegalContent(modalId, contentUrl) {
        const contentElement = document.getElementById(modalId + 'Content');
        
        // Return if already loaded
        if (contentCache[modalId]) {
            contentElement.innerHTML = contentCache[modalId];
            return;
        }
        
        // Set loading state
        contentElement.innerHTML = `
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p>Loading content...</p>
            </div>
        `;
        
        // Fetch the content
        fetch(contentUrl)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text();
            })
            .then(html => {
                // Extract and clean content
                const content = extractContentFromHTML(html);
                
                // Update modal content
                contentElement.innerHTML = content;
                
                // Cache the content
                contentCache[modalId] = content;
            })
            .catch(error => {
                console.error('Error loading content:', error);
                contentElement.innerHTML = `
                    <div class="alert alert-danger">
                        <p>Sorry, we couldn't load the content. Please try again later.</p>
                    </div>
                `;
            });
    }
    
    // Setup event listeners for each modal
    const modalMappings = [
        { id: 'privacyPolicy', url: 'privacy-policy.html' },
        { id: 'termsOfService', url: 'terms-of-service.html' },
        { id: 'smsTerms', url: 'sms-terms.html' }
    ];
    
    modalMappings.forEach(modal => {
        const modalElement = document.getElementById(modal.id + 'Modal');
        
        if (modalElement) {
            // Load content when modal is shown
            modalElement.addEventListener('show.bs.modal', function() {
                loadLegalContent(modal.id, modal.url);
            });
        }
    });
    
    // Add improved styling for legal content in modals
    const style = document.createElement('style');
    style.textContent = `
        .legal-modal .modal-body {
            padding: 2rem;
            color: #f8f9fa;
            background-color: #212529;
        }
        .legal-modal .modal-header {
            border-bottom: 1px solid #444;
            background-color: #212529;
            color: #f8f9fa;
        }
        .legal-modal .modal-content {
            background-color: #212529;
            border: 1px solid #444;
        }
        .legal-modal .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%);
        }
        .legal-modal h1 {
            font-size: 1.8rem;
            margin-bottom: 1.5rem;
            color: #f8f9fa;
            border-bottom: 1px solid #444;
            padding-bottom: 0.5rem;
        }
        .legal-modal h2 {
            font-size: 1.4rem;
            margin-top: 2rem;
            margin-bottom: 1rem;
            color: #f8f9fa;
        }
        .legal-modal p {
            font-size: 1rem;
            line-height: 1.7;
            margin-bottom: 1rem;
            color: #f8f9fa;
        }
        .legal-modal .effective-date {
            font-style: italic;
            margin-bottom: 1.5rem;
            color: #adb5bd;
        }
        .legal-modal ul, .legal-modal ol {
            padding-left: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .legal-modal li {
            margin-bottom: 0.5rem;
            color: #f8f9fa;
        }
        .legal-modal a {
            color: #d4af37;
            text-decoration: none;
        }
        .legal-modal a:hover {
            text-decoration: underline;
        }
    `;
    document.head.appendChild(style);
});
