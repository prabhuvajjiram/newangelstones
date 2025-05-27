/**
 * Script Optimizer for Angel Stones
 * 
 * This script makes targeted optimizations to reduce JavaScript execution time
 * without breaking existing functionality.
 */

// Wait for document to be ready and jQuery to be available
document.addEventListener('DOMContentLoaded', function() {
  // Make sure jQuery is available
  if (typeof jQuery === 'undefined') {
    console.warn('Script optimizer: jQuery not found, skipping optimizations');
    return;
  }
  
  // 1. Delay loading of Osano script (which had 910ms execution time)
  // This is safer than our previous approach - will run after page is ready
  setTimeout(function() {
    var script = document.createElement('script');
    script.src = 'https://cmp.osano.com/16BlGRUNRhRsy6cS2/951c545c-64c9-4a8f-886a-b22b0ff1528d/osano.js';
    script.async = true;
    document.body.appendChild(script);
    
    // Remove any existing Osano script that might be in the page
    var existingOsanoScripts = document.querySelectorAll('script[src*="osano.com"]');
    existingOsanoScripts.forEach(function(script) {
      if (script !== document.currentScript) {
        script.parentNode.removeChild(script);
      }
    });
  }, 2000);
  
  // 2. Throttle heavy jQuery event handlers (without breaking functionality)
  // This helps reduce execution time of things like mousemove/scroll handlers
  var originalOn = jQuery.fn.on;
  jQuery.fn.on = function(events, selector, data, fn) {
    if (typeof events === 'string' && 
        (events.indexOf('mousemove') !== -1 || 
         events.indexOf('scroll') !== -1)) {
      
      // Handle different argument patterns
      var callback = typeof selector === 'function' ? selector : fn;
      
      if (typeof callback === 'function') {
        var throttled = false;
        var throttledFn = function() {
          if (!throttled) {
            throttled = true;
            callback.apply(this, arguments);
            setTimeout(function() {
              throttled = false;
            }, 100);
          }
        };
        
        if (typeof selector === 'function') {
          return originalOn.call(this, events, throttledFn, data, fn);
        } else {
          return originalOn.call(this, events, selector, data, throttledFn);
        }
      }
    }
    
    return originalOn.apply(this, arguments);
  };
  
  // 3. Defer initialization of non-critical components
  // We'll use the thumbnails-first approach for better performance
  // This aligns with the existing implementation mentioned in user memories
  var initNonCriticalComponents = function() {
    // Check if these functions exist before trying to call them
    if (typeof initCarousels === 'function') {
      initCarousels();
    }
    
    if (typeof initColorRow === 'function') {
      initColorRow();
    }
  };
  
  // Delay initialization by 500ms
  setTimeout(initNonCriticalComponents, 500);
});
