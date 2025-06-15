/**
 * Specials Section Integration
 * This file loads the required libraries and scripts for the Specials section
 */
document.addEventListener('DOMContentLoaded', function() {
    // Load required CSS
    function loadStylesheet(url) {
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = url;
        document.head.appendChild(link);
    }
    
    // Load required JS
    function loadScript(url, callback) {
        const script = document.createElement('script');
        script.src = url;
        script.onload = callback;
        document.head.appendChild(script);
    }
    
    // Load Specials CSS
    loadStylesheet('/css/specials/specials.css');
    
    // Check if PDF.js is loaded
    if (typeof pdfjsLib === 'undefined') {
        // Load PDF.js first
        loadScript('https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.11.338/pdf.min.js', function() {
            // Set worker
            pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.11.338/pdf.worker.min.js';
            
            // Then load turn.js
            loadScript('https://cdnjs.cloudflare.com/ajax/libs/turn.js/3/turn.min.js', function() {
                // Now load our custom scripts
                loadScript('/js/specials/pdf-viewer.js', function() {
                    loadScript('/js/specials/specials-loader.js');
                });
            });
        });
    } else {
        // Load turn.js if PDF.js is already loaded
        loadScript('https://cdnjs.cloudflare.com/ajax/libs/turn.js/3/turn.min.js', function() {
            loadScript('/js/specials/pdf-viewer.js', function() {
                loadScript('/js/specials/specials-loader.js');
            });
        });
    }
});
