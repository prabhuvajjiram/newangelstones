# Design Document

## Overview

This design document outlines the enhancements to the order quote form to provide dynamic color selection, improved price validation, and mobile responsiveness. The solution will maintain the existing form structure while adding new functionality through PHP backend processing and JavaScript frontend enhancements.

## Architecture

### Component Structure
```
order_quote_form.php (Main Form)
├── PHP Color Loading Service
├── JavaScript Form Validation
├── Mobile-Responsive CSS
└── Price Validation System
```

### Data Flow
1. **Page Load**: PHP scans images/colors directory and generates color options
2. **User Interaction**: JavaScript handles form interactions and validations
3. **Form Submission**: Enhanced validation runs before submission
4. **Mobile Adaptation**: CSS media queries adapt layout for different screen sizes

## Components and Interfaces

### 1. Dynamic Color Loading Service (Reusing Existing)

**Existing Service:** `get_color_images.php`
- Already implemented and functional
- Returns JSON with color data including name, path, filename, size, and metadata
- Handles multiple image formats (jpg, jpeg, png, webp)
- Includes proper error handling and debugging
- Sorts colors alphabetically

**Integration Approach:**
```javascript
// Load colors via AJAX when form loads
async function loadColors() {
    try {
        const response = await fetch('get_color_images.php');
        const data = await response.json();
        
        if (data.success) {
            populateColorDropdowns(data.colors);
        } else {
            console.error('Failed to load colors:', data.error);
            // Fall back to hardcoded colors
        }
    } catch (error) {
        console.error('Error loading colors:', error);
        // Fall back to hardcoded colors
    }
}

function populateColorDropdowns(colors) {
    const colorSelects = document.querySelectorAll('.granite-color');
    
    colorSelects.forEach(select => {
        // Clear existing options except first and last (placeholder and "Other")
        const firstOption = select.children[0];
        const lastOption = select.children[select.children.length - 1];
        select.innerHTML = '';
        select.appendChild(firstOption);
        
        // Add dynamic colors
        colors.forEach(color => {
            const option = document.createElement('option');
            option.value = color.name;
            option.textContent = color.name;
            option.dataset.image = color.path;
            select.appendChild(option);
        });
        
        // Re-add "Other" option
        select.appendChild(lastOption);
    });
}
```

**Integration Points:**
- Load colors via AJAX when form initializes
- Replace hardcoded color options in line ~1422-1430
- Maintain "Other" option functionality
- Preserve existing form validation
- Add fallback to hardcoded colors if service fails

### 2. Enhanced Color Selection Interface

**HTML Structure (Updated):**
```html
<div class="mb-1">
    <label class="form-label small mb-1 required-field">Granite Color</label>
    <select class="form-select form-select-sm granite-color" name="products[0][color]" required>
        <option value="">Select Granite Color</option>
        <!-- Dynamic colors will be loaded here via JavaScript -->
        <option value="other">Other (Specify)</option>
    </select>
    <input type="text" class="form-control form-control-sm mt-1 d-none" 
           name="products[0][custom_color]" placeholder="Enter custom color">
    <div class="invalid-feedback">Please select a granite color.</div>
</div>
```

**JavaScript Integration:**
```javascript
// Initialize colors when page loads
document.addEventListener('DOMContentLoaded', function() {
    loadColors();
});

// Handle "Other" option selection
$(document).on('change', '.granite-color', function() {
    const customColorInput = $(this).siblings('input[name*="custom_color"]');
    if ($(this).val() === 'other') {
        customColorInput.removeClass('d-none').attr('required', true);
    } else {
        customColorInput.addClass('d-none').removeAttr('required').val('');
    }
});
```

### 3. Price Validation System

**JavaScript Implementation:**
```javascript
function validatePrice(priceInput) {
    const price = parseFloat(priceInput.value) || 0;
    const warningContainer = priceInput.closest('td').querySelector('.price-warning');
    
    // Remove existing warnings
    if (warningContainer) {
        warningContainer.remove();
    }
    
    // Add soft warning for $0 price
    if (price === 0 && priceInput.value !== '') {
        const warning = document.createElement('div');
        warning.className = 'price-warning text-warning small mt-1';
        warning.innerHTML = '<i class="bi bi-exclamation-triangle"></i> Price is $0.00 - please verify';
        priceInput.closest('div').appendChild(warning);
    }
    
    // Hard validation for negative prices
    if (price < 0) {
        priceInput.classList.add('is-invalid');
        return false;
    } else {
        priceInput.classList.remove('is-invalid');
        return true;
    }
}
```

### 4. Mobile Responsive Design

**CSS Media Queries:**
```css
/* Mobile-first approach */
@media (max-width: 768px) {
    .table-responsive {
        font-size: 12px;
    }
    
    .form-control-sm, .form-select-sm {
        height: 44px; /* Touch-friendly size */
        font-size: 16px; /* Prevent zoom on iOS */
    }
    
    .btn-sm {
        min-height: 44px;
        padding: 8px 16px;
    }
    
    /* Stack table columns on very small screens */
    @media (max-width: 576px) {
        .table thead {
            display: none;
        }
        
        .table tbody tr {
            display: block;
            border: 1px solid #dee2e6;
            margin-bottom: 10px;
            border-radius: 8px;
        }
        
        .table tbody td {
            display: block;
            text-align: left !important;
            border: none;
            padding: 8px 12px;
        }
        
        .table tbody td:before {
            content: attr(data-label) ": ";
            font-weight: bold;
            display: inline-block;
            width: 100px;
        }
    }
}
```

## Data Models

### Color Data Structure
```php
[
    'value' => 'string',      // Form value
    'display' => 'string',    // Display name
    'image' => 'string'       // Image path
]
```

### Form Validation State
```javascript
{
    isValid: boolean,
    errors: string[],
    warnings: string[],
    priceWarnings: number[]   // Array of row indices with $0 prices
}
```

## Error Handling

### Color Loading Errors
- **Directory not found**: Fall back to hardcoded color list
- **Permission issues**: Log error and use default colors
- **Invalid image files**: Skip and continue processing

### Price Validation Errors
- **Negative prices**: Hard validation error, prevent submission
- **Zero prices**: Soft warning, allow submission
- **Invalid format**: Convert to 0 and show warning

### Mobile Compatibility Errors
- **Touch target too small**: Ensure minimum 44px height
- **Viewport issues**: Add proper meta viewport tag
- **Input zoom on iOS**: Use 16px font size minimum

## Testing Strategy

### Unit Tests
1. **Color Loading Function**
   - Test with various image formats
   - Test with empty directory
   - Test with invalid files
   - Test color name formatting

2. **Price Validation**
   - Test zero price warning
   - Test negative price blocking
   - Test valid price acceptance
   - Test empty price handling

### Integration Tests
1. **Form Submission**
   - Test with dynamic colors selected
   - Test with custom color specified
   - Test with zero prices (should submit with warning)
   - Test with negative prices (should not submit)

### Mobile Testing
1. **Responsive Design**
   - Test on various screen sizes (320px to 1200px)
   - Test touch interactions
   - Test form usability on mobile devices
   - Test table responsiveness

### Browser Compatibility
- Chrome/Edge (latest 2 versions)
- Firefox (latest 2 versions)
- Safari (latest 2 versions)
- Mobile browsers (iOS Safari, Chrome Mobile)

## Implementation Considerations

### Performance
- Cache color list to avoid repeated directory scans
- Optimize images in colors directory for web
- Minimize JavaScript execution on mobile devices

### Accessibility
- Maintain proper ARIA labels
- Ensure keyboard navigation works
- Provide screen reader friendly error messages
- Maintain color contrast ratios

### Backwards Compatibility
- Preserve existing form field names
- Maintain existing JavaScript event handlers
- Keep existing CSS classes functional

### Security
- Sanitize file names when reading directory
- Validate image file types
- Escape HTML output for color names