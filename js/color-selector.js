/**
 * Enhanced Color Selector for Angel Stones Order Form
 * Provides dynamic color loading with backward compatibility
 */

class ColorSelector {
    constructor(options = {}) {
        this.options = {
            apiEndpoint: 'get_color_images.php',
            cacheKey: 'angel_stones_colors',
            cacheExpiry: 30 * 60 * 1000, // 30 minutes in milliseconds
            ...options
        };
        
        this.colors = [];
        this.colorCache = null;
        
        // Fallback colors if API fails
        this.fallbackColors = [
            'Absolute Black',
            'Alaska White', 
            'Black Galaxy',
            'Blue Pearl',
            'Colonial White',
            'Costa Esmeralda'
        ];
        
        this.init();
    }
    
    /**
     * Initialize the color selector
     */
    async init() {
        console.log('Initializing ColorSelector...');
        
        // Load colors and initialize dropdowns
        await this.initializeColors();
        
        // Set up global event handlers
        this.setupGlobalHandlers();
    }
    
    /**
     * Check if cached data is still valid
     */
    isCacheValid() {
        try {
            const cached = localStorage.getItem(this.options.cacheKey);
            if (!cached) return false;
            
            const data = JSON.parse(cached);
            const now = Date.now();
            
            return data.timestamp && (now - data.timestamp) < this.options.cacheExpiry;
        } catch (error) {
            console.warn('Error checking cache validity:', error);
            return false;
        }
    }
    
    /**
     * Get colors from cache
     */
    getCachedColors() {
        try {
            const cached = localStorage.getItem(this.options.cacheKey);
            if (!cached) return null;
            
            const data = JSON.parse(cached);
            return data.colors;
        } catch (error) {
            console.warn('Error retrieving cached colors:', error);
            return null;
        }
    }
    
    /**
     * Save colors to cache
     */
    setCachedColors(colors) {
        try {
            const data = {
                colors: colors,
                timestamp: Date.now()
            };
            localStorage.setItem(this.options.cacheKey, JSON.stringify(data));
            console.log('Colors cached successfully');
        } catch (error) {
            console.warn('Error caching colors:', error);
        }
    }
    
    /**
     * Load colors from cache first, then API if needed
     */
    async loadColors() {
        // Check cache first
        if (this.isCacheValid()) {
            const cachedColors = this.getCachedColors();
            if (cachedColors && Array.isArray(cachedColors)) {
                console.log(`Using cached colors (${cachedColors.length} items)`);
                this.colorCache = cachedColors;
                return cachedColors;
            }
        }
        
        // Load from API if cache is invalid or empty
        try {
            console.log('Loading colors from service...');
            const response = await fetch(this.options.apiEndpoint);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success && data.colors && Array.isArray(data.colors)) {
                console.log(`Successfully loaded ${data.colors.length} colors from service`);
                this.colorCache = data.colors;
                
                // Cache the results
                this.setCachedColors(data.colors);
                
                return data.colors;
            } else {
                throw new Error(data.error || 'Invalid response format');
            }
        } catch (error) {
            console.error('Error loading colors from service:', error);
            
            // Try to use expired cache as fallback
            const cachedColors = this.getCachedColors();
            if (cachedColors && Array.isArray(cachedColors)) {
                console.log('Using expired cache as fallback');
                this.colorCache = cachedColors;
                return cachedColors;
            }
            
            console.log('Falling back to hardcoded colors');
            
            // Return fallback colors in the expected format
            this.colorCache = this.fallbackColors.map(name => ({ name: name }));
            return this.colorCache;
        }
    }
    
    /**
     * Populate color dropdown with dynamic colors and accessibility features
     */
    populateColorDropdown($select, colors) {
        if (!$select || !colors) return;
        
        // Store current value to preserve selection
        const currentValue = $select.val();
        
        // Add accessibility attributes
        $select.attr({
            'aria-label': 'Select granite color',
            'aria-describedby': $select.attr('id') + '-help'
        });
        
        // Clear existing options except placeholder and "Other"
        $select.find('option').not(':first').not('[value="other"]').remove();
        
        // Add dynamic colors before "Other" option
        const $otherOption = $select.find('option[value="other"]');
        
        colors.forEach((color, index) => {
            const $option = $('<option></option>')
                .attr('value', color.name)
                .text(color.name)
                .attr('data-index', index);
            
            // Add accessibility description if color has additional info
            if (color.type) {
                $option.attr('title', `${color.name} - ${color.type}`);
            }
            
            if ($otherOption.length > 0) {
                $option.insertBefore($otherOption);
            } else {
                $select.append($option);
            }
        });
        
        // If "Other" option doesn't exist, add it with accessibility attributes
        if ($otherOption.length === 0) {
            $select.append('<option value="other" aria-describedby="custom-color-help">Other (Specify)</option>');
        }
        
        // Add help text for screen readers if it doesn't exist
        const helpId = $select.attr('id') + '-help';
        if (!$('#' + helpId).length) {
            $select.after(`<div id="${helpId}" class="sr-only">Choose from available granite colors or select Other to specify a custom color</div>`);
        }
        
        // Restore previous selection if it still exists
        if (currentValue && $select.find(`option[value="${currentValue}"]`).length > 0) {
            $select.val(currentValue);
        }
        
        // Announce to screen readers that colors have been loaded
        this.announceToScreenReader(`${colors.length} granite colors loaded and available for selection`);
    }
    
    /**
     * Announce messages to screen readers
     */
    announceToScreenReader(message) {
        // Create or update live region for screen reader announcements
        let $liveRegion = $('#color-selector-announcements');
        if (!$liveRegion.length) {
            $liveRegion = $('<div id="color-selector-announcements" aria-live="polite" aria-atomic="true" class="sr-only"></div>');
            $('body').append($liveRegion);
        }
        
        $liveRegion.text(message);
        
        // Clear the message after a delay to avoid repetition
        setTimeout(() => {
            $liveRegion.empty();
        }, 1000);
    }
    
    /**
     * Detect if running on mobile device
     */
    isMobileDevice() {
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ||
               window.innerWidth <= 768;
    }
    
    /**
     * Throttle function execution for performance
     */
    throttle(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        }
    }
    
    /**
     * Debounce function execution for performance
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    /**
     * Initialize colors for all existing dropdowns with mobile optimization
     */
    async initializeColors() {
        console.log('Initializing dynamic colors...');
        
        const isMobile = this.isMobileDevice();
        const batchSize = isMobile ? 3 : 10; // Process fewer items at once on mobile
        
        // Get all color dropdowns
        const $colorSelects = $('.granite-color');
        
        // Add loading state to all color dropdowns
        $colorSelects.each(function() {
            const $select = $(this);
            const originalHtml = $select.html();
            $select.html('<option value="">Loading colors...</option>');
            $select.data('original-html', originalHtml);
        });
        
        try {
            const colors = await this.loadColors();
            
            // Process dropdowns in batches for better mobile performance
            const processDropdownBatch = (startIndex) => {
                const endIndex = Math.min(startIndex + batchSize, $colorSelects.length);
                
                for (let i = startIndex; i < endIndex; i++) {
                    this.populateColorDropdown($($colorSelects[i]), colors);
                }
                
                // Continue with next batch if there are more dropdowns
                if (endIndex < $colorSelects.length) {
                    // Use requestAnimationFrame for smooth processing on mobile
                    if (isMobile) {
                        requestAnimationFrame(() => processDropdownBatch(endIndex));
                    } else {
                        setTimeout(() => processDropdownBatch(endIndex), 0);
                    }
                }
            };
            
            // Start processing
            processDropdownBatch(0);
            
            console.log('Color initialization complete');
        } catch (error) {
            console.error('Failed to initialize colors:', error);
            
            // Restore original HTML on error
            $colorSelects.each(function() {
                const $select = $(this);
                const originalHtml = $select.data('original-html');
                if (originalHtml) {
                    $select.html(originalHtml);
                }
            });
        }
    }
    
    /**
     * Set up global event handlers
     */
    setupGlobalHandlers() {
        // Handle new product rows being added
        $(document).on('product:added', (event, $row) => {
            console.log('New product row added, populating colors...');
            if (this.colorCache) {
                const $colorSelect = $row.find('.granite-color');
                if ($colorSelect.length) {
                    this.populateColorDropdown($colorSelect, this.colorCache);
                }
            }
        });
    }
}

// Initialize when document is ready
$(document).ready(function() {
    console.log('Document ready, initializing ColorSelector...');
    window.colorSelector = new ColorSelector();
});