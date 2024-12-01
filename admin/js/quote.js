class ProductDetails {
    constructor(productData, onProductChange) {
        this.productData = productData;
        this.onProductChange = onProductChange;
        this.initializeElements();
        this.initializeEventListeners();
        this.populateTypeSelect();
    }

    initializeElements() {
        this.typeSelect = $('#type');
        this.sizeSelect = $('#size');
        this.modelSelect = $('#model');
        this.stoneColorSelect = $('#stoneColor');
        this.lengthInput = $('#length');
        this.breadthInput = $('#breadth');
        this.quantityInput = $('#quantity');
    }

    initializeEventListeners() {
        this.typeSelect.on('change', () => {
            this.updateSizes();
            this.notifyChange();
        });
        
        this.sizeSelect.on('change', () => {
            this.updateModels();
            this.notifyChange();
        });
        
        this.modelSelect.on('change', () => this.notifyChange());
        this.stoneColorSelect.on('change', () => this.notifyChange());
        
        const inputs = [this.lengthInput, this.breadthInput, this.quantityInput];
        inputs.forEach(input => {
            input.on('change keyup', () => this.notifyChange());
        });
    }

    populateTypeSelect() {
        this.typeSelect.empty().append('<option value="">Select Type</option>');
        this.sizeSelect.empty().append('<option value="">Select Size</option>');
        this.modelSelect.empty().append('<option value="">Select Model</option>');

        const types = [...new Set(this.productData.models.map(p => p.type))];
        types.forEach(type => {
            this.typeSelect.append(`<option value="${type}">${type}</option>`);
        });
    }

    updateSizes() {
        const selectedType = this.typeSelect.val();
        this.sizeSelect.empty().append('<option value="">Select Size</option>');
        this.modelSelect.empty().append('<option value="">Select Model</option>');

        if (selectedType) {
            const sizes = [...new Set(
                this.productData.models
                    .filter(p => p.type.toLowerCase() === selectedType.toLowerCase())
                    .map(p => p.size)
            )].sort((a, b) => parseFloat(a) - parseFloat(b));

            sizes.forEach(size => {
                this.sizeSelect.append(`<option value="${size}">${size}</option>`);
            });
        }
    }

    updateModels() {
        const selectedType = this.typeSelect.val();
        const selectedSize = this.sizeSelect.val();
        this.modelSelect.empty().append('<option value="">Select Model</option>');

        if (selectedType && selectedSize) {
            const models = this.productData.models
                .filter(p => 
                    p.type.toLowerCase() === selectedType.toLowerCase() && 
                    p.size === selectedSize
                )
                .map(p => p.model);

            models.forEach(model => {
                this.modelSelect.append(`<option value="${model}">${model}</option>`);
            });
        }
    }

    getSelectedProduct() {
        const colorId = this.stoneColorSelect.val();
        return {
            type: this.typeSelect.val(),
            size: this.sizeSelect.val(),
            model: this.modelSelect.val(),
            color_id: colorId ? parseInt(colorId) : null,  // Convert to integer
            length: parseFloat(this.lengthInput.val()) || 0,
            breadth: parseFloat(this.breadthInput.val()) || 0,
            quantity: parseInt(this.quantityInput.val()) || 1
        };
    }

    notifyChange() {
        if (this.onProductChange) {
            this.onProductChange(this.getSelectedProduct());
        }
    }

    reset() {
        this.typeSelect.val('').trigger('change');
        this.sizeSelect.empty().append('<option value="">Select Size</option>');
        this.modelSelect.empty().append('<option value="">Select Model</option>');
        this.stoneColorSelect.val('');
        this.lengthInput.val('');
        this.breadthInput.val('');
        this.quantityInput.val('1');
    }
}

class ProductSummary {
    constructor(productData) {
        this.productData = productData;
        this.initializeElements();
        this.currentMeasurements = null;
    }

    initializeElements() {
        this.sqftDisplay = $('#sqft');
        this.cuftDisplay = $('#cubicFeet');
        this.basePriceDisplay = $('#basePrice');
        this.totalPriceDisplay = $('#totalPrice');
    }

    updateSummary(product) {
        if (!this.isValidProduct(product)) {
            this.reset();
            return null;
        }

        const measurements = this.calculateMeasurements(product);
        if (measurements) {
            this.displayMeasurements(measurements);
            return measurements;
        }

        this.reset();
        return null;
    }

    isValidProduct(product) {
        return product && product.type && product.size && product.model && 
               product.length > 0 && product.breadth > 0 && product.quantity > 0;
    }

    calculateMeasurements(product) {
        const modelData = this.productData.models.find(m => 
            m.type === product.type && 
            m.size === product.size && 
            m.model === product.model
        );

        if (!modelData) return null;

        const sqft = (product.length * product.breadth) / 144;
        const cubicFeet = product.type.toLowerCase() === 'marker' 
            ? (product.length * product.breadth * 4) / 1728 
            : (product.length * product.breadth * parseFloat(product.size)) / 1728;

        const basePrice = modelData.base_price * sqft;
        const totalPrice = basePrice * product.quantity;

        return {
            sqft,
            cubicFeet,
            basePrice,
            totalPrice
        };
    }

    displayMeasurements(measurements) {
        this.sqftDisplay.text(measurements.sqft.toFixed(2));
        this.cuftDisplay.text(measurements.cubicFeet.toFixed(2));
        this.basePriceDisplay.text('$' + measurements.basePrice.toFixed(2));
        this.totalPriceDisplay.text('$' + measurements.totalPrice.toFixed(2));
        this.currentMeasurements = measurements;
    }

    reset() {
        this.sqftDisplay.text('0.00');
        this.cuftDisplay.text('0.00');
        this.basePriceDisplay.text('$0.00');
        this.totalPriceDisplay.text('$0.00');
        this.currentMeasurements = null;
    }
}

class CartManager {
    constructor(productData) {
        this.productData = productData;
        this.cart = [];
        this.initializeElements();
    }

    initializeElements() {
        this.cartTableBody = $('#cartTableBody');
        this.cartBasePrice = $('#cartBasePrice');
        this.cartTotal = $('#cartTotal');
        this.generateQuoteBtn = $('#generateQuoteBtn');
        
        // Bind delete handler
        $(document).on('click', '.delete-cart-item', (e) => {
            const index = $(e.currentTarget).data('index');
            this.removeItem(index);
        });
    }

    addItem(product, measurements) {
        if (!product || !measurements) return;
    
        const item = {
            type: product.type,
            size: product.size,
            model: product.model,
            color_id: product.color_id ? parseInt(product.color_id) : null,  // Ensure integer
            length: product.length,
            breadth: product.breadth,
            quantity: product.quantity,
            sqft: measurements.sqft,
            cubic_feet: measurements.cubicFeet,
            base_price: measurements.basePrice,
            total_price: measurements.totalPrice
        };
    
        this.cart.push(item);
        this.refreshDisplay();
    }

    removeItem(index) {
        this.cart.splice(index, 1);
        this.refreshDisplay();
    }

    refreshDisplay() {
        this.cartTableBody.empty();
        
        let totalBasePrice = 0;
        let totalPrice = 0;
        
        this.cart.forEach((item, index) => {
            const colorName = item.color_id ? this.getColorName(item.color_id) : '-';
            const row = $('<tr>');
            row.html(`
                <td>${item.type}</td>
                <td>${item.size}</td>
                <td>${item.model}</td>
                <td>${colorName}</td>
                <td>${item.length} x ${item.breadth}</td>
                <td>${item.quantity}</td>
                <td>${item.sqft.toFixed(2)}</td>
                <td>${item.cubic_feet.toFixed(2)}</td>
                <td class="text-end">$${item.base_price.toFixed(2)}</td>
                <td class="text-end">$${item.total_price.toFixed(2)}</td>
                <td class="text-center">
                    <button class="btn btn-danger btn-sm delete-cart-item" data-index="${index}">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            `);
            this.cartTableBody.append(row);
            
            totalBasePrice += item.base_price;
            totalPrice += item.total_price;
        });
        
        this.cartBasePrice.text('$' + totalBasePrice.toFixed(2));
        this.cartTotal.text('$' + totalPrice.toFixed(2));
        this.generateQuoteBtn.prop('disabled', this.cart.length === 0);
        
        // Update hidden input
        $('#items').val(JSON.stringify(this.cart));
    }

    getColorName(colorId) {
        if (!colorId) return '-';
        // Compare as integers
        const color = this.productData.stone_colors.find(c => c.id === parseInt(colorId));
        return color ? color.color_name : '-';
    }

    getCartItems() {
        return this.cart;
    }
}

class QuoteManager {
    constructor() {
        this.productData = window.QUOTE_DATA.productData;
        this.quoteData = window.QUOTE_DATA.quoteData;
        
        // Initialize components
        this.productDetails = new ProductDetails(this.productData, (product) => {
            const measurements = this.productSummary.updateSummary(product);
            this.currentProduct = product;
            this.currentMeasurements = measurements;
        });
        
        this.productSummary = new ProductSummary(this.productData);
        this.cartManager = new CartManager(this.productData);
        
        // Initialize modal elements
        const commissionModalEl = document.getElementById('commissionModal');
        if (commissionModalEl) {
            this.commissionModal = new bootstrap.Modal(commissionModalEl);
        }
        this.finalCommissionRate = document.getElementById('finalCommissionRate');
        this.modalSubtotal = document.getElementById('modalSubtotal');
        this.modalCommission = document.getElementById('modalCommission');
        this.modalTotal = document.getElementById('modalTotal');
        
        // Initialize customer selection
        this.customerSelect = $('#customer_select');
        this.customerSelect.on('change', (e) => {
            const selectedOption = $(e.target).find('option:selected');
            $('#customer_id').val($(e.target).val());
            $('#customer_email').val(selectedOption.data('email'));
        });

        // Set initial customer if provided in URL
        if (this.quoteData.customer_id) {
            this.customerSelect.val(this.quoteData.customer_id).trigger('change');
        }
        
        // Initialize add to cart button
        $('#addToCartBtn').on('click', (e) => {
            e.preventDefault();
            if (this.currentProduct && this.currentMeasurements) {
                this.cartManager.addItem(this.currentProduct, this.currentMeasurements);
                this.productDetails.reset();
                this.productSummary.reset();
                this.currentProduct = null;
                this.currentMeasurements = null;
            }
        });
        
        // Initialize commission rate change
        if (this.finalCommissionRate) {
            this.finalCommissionRate.addEventListener('input', () => this.updateModalTotals());
        }
        
        // Initialize generate quote button (opens modal)
        $('#generateQuoteBtn').on('click', (e) => {
            e.preventDefault();
            this.showCommissionModal();
        });

        // Initialize finalize quote button (in modal)
        $('#finalizeQuoteBtn').on('click', (e) => {
            e.preventDefault();
            this.saveQuote();
        });
    }

    saveQuote() {
        // Validate customer selection
        const customerId = $('#customer_id').val();
        if (!customerId) {
            alert('Please select a customer first');
            this.commissionModal.hide();
            return;
        }

        // Validate commission rate
        const commissionRate = $('#finalCommissionRate').val();
        if (!commissionRate || isNaN(commissionRate)) {
            alert('Please enter a valid commission rate');
            return;
        }

        // Get items from cart
        const items = this.cartManager.getCartItems();
        if (items.length === 0) {
            alert('Cart is empty. Please add items to generate quote.');
            this.commissionModal.hide();
            return;
        }

        // Prepare data
        const data = {
            customer_id: customerId,
            customer_email: $('#customer_email').val(),
            commission_rate: parseFloat(commissionRate),
            items: items.map(item => ({
                product_type: item.type,
                model: item.model,
                size: item.size,
                color_id: item.color_id,
                length: parseFloat(item.length) || 0,
                breadth: parseFloat(item.breadth) || 0,
                sqft: parseFloat(item.sqft) || 0,
                cubic_feet: parseFloat(item.cubic_feet) || 0,
                quantity: parseInt(item.quantity) || 1,
                unit_price: parseFloat(item.base_price) || 0,
                total_price: parseFloat(item.total_price) || 0
            }))
        };

        // Send AJAX request
        $.ajax({
            url: 'ajax/save_quote.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(data),
            success: (response) => {
                if (response.success) {
                    alert('Quote saved successfully!');
                    // Redirect to preview with proper ID parameter
                    window.location.href = 'preview_quote.php?id=' + response.data.quote_id;
                } else {
                    // Check if we need to redirect due to session timeout
                    if (response.data && response.data.redirect) {
                        alert(response.message);
                        window.location.href = response.data.redirect;
                        return;
                    }
                    alert('Error: ' + response.message);
                }
            },
            error: (xhr, status, error) => {
                console.error('AJAX error:', error);
                console.error('Server response:', xhr.responseText);
                alert('Error saving quote. Please try again.');
            }
        });
    }

    showCommissionModal() {
        if (!this.commissionModal) return;
        
        const cartItems = this.cartManager.getCartItems();
        if (cartItems.length === 0) {
            alert('Please add items to the quote first.');
            return;
        }
        
        this.updateModalTotals();
        this.commissionModal.show();
    }

    updateModalTotals() {
        const cartItems = this.cartManager.getCartItems();
        const subtotal = cartItems.reduce((sum, item) => sum + item.total_price, 0);
        const commissionRate = parseFloat($('#finalCommissionRate').val()) || 0;
        const commission = (subtotal * commissionRate) / 100;
        const total = subtotal + commission;

        this.modalSubtotal.textContent = '$' + subtotal.toFixed(2);
        this.modalCommission.textContent = '$' + commission.toFixed(2);
        this.modalTotal.textContent = '$' + total.toFixed(2);
    }
}

// Initialize when document is ready
$(document).ready(() => {
    window.quoteManager = new QuoteManager();
});