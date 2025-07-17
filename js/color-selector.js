/**
 * Enhanced Color Selector for Angel Stones Order Form
 * Provides dynamic color loading with backward compatibility
 */

class ColorSelector {
    constructor(options = {}) {
        this.options = {
            apiEndpoint: 'get_color_images.php',
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
     * Load colors from API or use fallback
     */
    async loadColors() {
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
                return data.colors;
            } else {
                throw new Error(data.error || 'Invalid response format');
            }
        } catch (error) {
            console.error('Error loading colors from service:', error);
            console.log('Falling back to hardcoded colors');
            
            // Return fallback colors in the expected format
            this.colorCache = this.fallbackColors.map(name => ({ name: name }));
            return this.colorCache;
        }
    }
    
    /**
     * Populate color dropdown with dynamic colors
     */
    populateColorDropdown($select, colors) {
        if (!$select || !colors) return;
        
        // Store current value to preserve selection
        const currentValue = $select.val();
        
        // Clear existing options except placeholder and "Other"
        $select.find('option').not(':first').not('[value="other"]').remove();
        
        // Add dynamic colors before "Other" option
        const $otherOption = $select.find('option[value="other"]');
        
        colors.forEach(color => {
            const $option = $('<option></option>')
                .attr('value', color.name)
                .text(color.name);
            
            if ($otherOption.length > 0) {
                $option.insertBefore($otherOption);
            } else {
                $select.append($option);
            }
        });
        
        // If "Other" option doesn't exist, add it
        if ($otherOption.length === 0) {
            $select.append('<option value="other">Other (Specify)</option>');
        }
        
        // Restore previous selection if it still exists
        if (currentValue && $select.find(`option[value="${currentValue}"]`).length > 0) {
            $select.val(currentValue);
        }
    }
    
    /**
     * Initialize colors for all existing dropdowns
     */
    async initializeColors() {
        console.log('Initializing dynamic colors...');
        
        // Add loading state to all color dropdowns
        $('.granite-color').each(function() {
            const $select = $(this);
            const originalHtml = $select.html();
            $select.html('<option value="">Loading colors...</option>');
            $select.data('original-html', originalHtml);
        });
        
        try {
            const colors = await this.loadColors();
            
            // Update all existing color dropdowns
            $('.granite-color').each((index, element) => {
                this.populateColorDropdown($(element), colors);
            });
            
            console.log('Color initialization complete');
        } catch (error) {
            console.error('Failed to initialize colors:', error);
            
            // Restore original HTML on error
            $('.granite-color').each(function() {
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