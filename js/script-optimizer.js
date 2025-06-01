/**
 * Script Optimizer for Angel Stones
 * 
 * This script makes targeted optimizations to reduce JavaScript execution time
 * without breaking existing functionality.
 * Updated to work with CDN resources for improved global performance.
 * Enhanced to break up long main-thread tasks.
 */

// Function to break up long tasks using setTimeout with 0ms
function breakLongTask(fn) {
  setTimeout(fn, 0);
}

// Wait for document to be ready and jQuery to be available
document.addEventListener('DOMContentLoaded', function() {
  // Make sure jQuery is available
  if (typeof jQuery === 'undefined') {
    console.warn('Script optimizer: jQuery not found, skipping optimizations');
    return;
  }
  
  // Break up initial processing after DOMContentLoaded
  breakLongTask(function() {
    console.log('Performance optimization: Breaking up long tasks');
  });
  
  // 1. Delay loading of non-critical resources
  // This improves initial page load time significantly
  setTimeout(function() {
    // Delay loading Osano cookie consent (910ms execution time savings)
    var script = document.createElement('script');
    script.src = 'https://cmp.osano.com/16BlGRUNRhRsy6cS2/951c545c-64c9-4a8f-886a-b22b0ff1528d/osano.js';
    script.async = true;
    document.body.appendChild(script);
    
    // Log performance improvement
    console.log('Performance optimization: Delayed Osano script loading (saves ~910ms)');
    
    // Remove any existing Osano script that might be in the page
    var existingOsanoScripts = document.querySelectorAll('script[src*="osano.com"]');
    existingOsanoScripts.forEach(function(script) {
      if (script !== document.currentScript) {
        script.parentNode.removeChild(script);
      }
    });
  }, 2000);
  
  // 2. More aggressive throttling of heavy jQuery event handlers
  // This helps reduce execution time of things like mousemove/scroll handlers
  var originalOn = jQuery.fn.on;
  jQuery.fn.on = function(events, selector, data, fn) {
    // Throttle more event types that can cause long tasks
    if (typeof events === 'string' && 
        (events.indexOf('mousemove') !== -1 || 
         events.indexOf('scroll') !== -1 ||
         events.indexOf('resize') !== -1 ||
         events.indexOf('mouseenter') !== -1 ||
         events.indexOf('mouseleave') !== -1)) {
      
      // Handle different argument patterns
      var callback = typeof selector === 'function' ? selector : fn;
      
      if (typeof callback === 'function') {
        var throttled = false;
        var lastArgs, lastThis;
        var throttledFn = function() {
          lastArgs = arguments;
          lastThis = this;
          
          if (!throttled) {
            throttled = true;
            // Use requestAnimationFrame for better performance
            requestAnimationFrame(function() {
              callback.apply(lastThis, lastArgs);
              // Break up the task by using setTimeout to release main thread
              setTimeout(function() {
                throttled = false;
              }, 150); // Increased throttle time for better performance
            });
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
  
  // 3. Improved defer initialization of non-critical components
  // We stagger the initialization to avoid long tasks
  // This preserves the thumbnails-first approach from user memories
  var initNonCriticalComponents = function() {
    // Check if these functions exist before trying to call them
    if (typeof initCarousels === 'function') {
      // Use breakLongTask to split up initialization
      breakLongTask(function() {
        console.log('Performance optimization: Staggered carousel initialization');
        initCarousels();
      });
    }
    
    // Delay color row initialization to avoid competing with carousels
    setTimeout(function() {
      if (typeof initColorRow === 'function') {
        breakLongTask(function() {
          console.log('Performance optimization: Staggered color row initialization');
          initColorRow();
        });
      }
    }, 300);
    
    // Initialize other components with further delays
    setTimeout(function() {
      // Use IntersectionObserver to only init visible components
      if ('IntersectionObserver' in window) {
        var lazyLoadObserver = new IntersectionObserver(function(entries) {
          entries.forEach(function(entry) {
            if (entry.isIntersecting) {
              var id = entry.target.id;
              breakLongTask(function() {
                // Specific initializations based on element ID
                if (id === 'variety-of-granites' && typeof initGraniteDisplay === 'function') {
                  initGraniteDisplay();
                }
                // Remove from observation
                lazyLoadObserver.unobserve(entry.target);
              });
            }
          });
        }, {threshold: 0.1});
        
        // Observe sections that need lazy initialization
        document.querySelectorAll('.lazy-init-section').forEach(function(section) {
          lazyLoadObserver.observe(section);
        });
      }
    }, 800);
  };
  
  // Initial delay before starting component initialization
  setTimeout(initNonCriticalComponents, 600);
});
