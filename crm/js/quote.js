class QuoteManager {
    constructor() {
        this.productData = window.QUOTE_DATA.productData;
        this.quoteData = window.QUOTE_DATA.quoteData;
        
        // Initialize cart items from session storage or window data
        try {
            const storedCart = sessionStorage.getItem('cartItems');
            this.cartItems = storedCart ? JSON.parse(storedCart) : 
                           (window.QUOTE_DATA.cartItems || []);
            console.log('Initialized cart with items:', this.cartItems);
        } catch (e) {
            console.error('Error initializing cart:', e);
            this.cartItems = [];
        }
        
        this.currentProduct = null;
        this.currentMeasurements = null;
        
        console.log('Initialized with product data:', this.productData);
        
        this.initializeElements();
        this.initializeEventListeners();
        
        // Initial cart display update
        this.updateCartDisplay();
    }

    initializeElements() {
        // Initialize dropdowns
        this.productTypeSelect = $('#productType');
        this.productSizeSelect = $('#productSize');
        this.productModelSelect = $('#productModel');
        this.stoneColorSelect = $('#stoneColor');
        this.specialMonumentSelect = $('#specialMonument').prop('required', true);
        
        // Initialize measurement inputs
        this.lengthInput = $('#length');
        this.breadthInput = $('#breadth');
        this.quantityInput = $('#quantity').val('1');
        
        // Initialize summary displays
        this.squareFeetDisplay = $('#sqft');
        this.cubicFeetDisplay = $('#cubicFeet');
        this.basePriceDisplay = $('#basePrice');
        this.totalPriceDisplay = $('#totalPrice');

        // Initialize add to cart button
        this.addToCartBtn = $('#addToCartBtn').prop('disabled', true);

        // Initialize cart totals container
        this.cartTotalsContainer = $('#cartTotals');

        // Initialize other elements
        this.customerSelect = $('#customer_select');
        this.finalCommissionRate = document.getElementById('finalCommissionRate');
        this.modalSubtotal = document.getElementById('modalSubtotal');
        this.modalCommission = document.getElementById('modalCommission');
        this.modalTotal = document.getElementById('modalTotal');
        
        // Initialize modal
        const commissionModalEl = document.getElementById('commissionModal');
        if (commissionModalEl) {
            this.commissionModal = new bootstrap.Modal(commissionModalEl);
        }

        // Add change handlers for measurements and validation
        [this.lengthInput, this.breadthInput, this.quantityInput].forEach(input => {
            input.on('input', () => this.updateMeasurements());
        });

        // Add change handlers for product selection
        [
            this.productTypeSelect,
            this.productSizeSelect,
            this.productModelSelect,
            this.stoneColorSelect,
            this.specialMonumentSelect
        ].forEach(input => {
            input.on('change', () => {
                this.updateAddToCartState();
                this.updatePrice();
            });
        });
    }

    initializeEventListeners() {
        // Product selection handlers
        this.productTypeSelect.on('change', () => {
            const type = this.productTypeSelect.val();
            this.handleProductTypeChange(type);
        });

        this.productSizeSelect.on('change', () => {
            const type = this.productTypeSelect.val();
            const size = this.productSizeSelect.val();
            this.handleSizeChange(type, size);
        });

        this.productModelSelect.on('change', () => {
            const selectedModel = this.productModelSelect.find('option:selected');
            if (selectedModel.val()) {
                this.currentProduct = {
                    type: this.productTypeSelect.val(),
                    size: this.productSizeSelect.val(),
                    model: selectedModel.val(),
                    base_price: parseFloat(selectedModel.data('price')) || 0
                };
                this.updatePrice();
            }
        });

        // Price update handlers
        $('#stoneColor, #specialMonument').on('change', () => this.updatePrice());

        // Customer selection handler
        this.customerSelect.on('change', (e) => {
            const selectedOption = $(e.target).find('option:selected');
            $('#customer_id').val($(e.target).val());
            $('#customer_email').val(selectedOption.data('email'));
        });

        // Set initial customer if provided
        if (this.quoteData.customer_id) {
            this.customerSelect.val(this.quoteData.customer_id).trigger('change');
        }

        // Cart handlers
        $('#addToCartBtn').on('click', (e) => {
            e.preventDefault();
            const item = this.createCartItem();
            if (item) {
                this.addToCart(item);
                this.resetForm();
            }
        });

        // Quote generation handlers
        if (this.finalCommissionRate) {
            this.finalCommissionRate.addEventListener('input', () => this.updateModalTotals());
        }

        $('#generateQuoteBtn').on('click', (e) => {
            e.preventDefault();
            this.showCommissionModal();
        });

        $('#finalizeQuoteBtn').on('click', (e) => {
            e.preventDefault();
            this.saveQuote();
        });
    }

    handleProductTypeChange(type) {
        // Store current cart
        const currentCart = [...this.getCartItems()];

        console.log('Product type changed to:', type);
        console.log('Available data for type:', this.productData[type]);

        if (!type) {
            this.productSizeSelect.html('<option value="">Select Size</option>').prop('disabled', true);
            this.productModelSelect.html('<option value="">Select Model</option>').prop('disabled', true);
            return;
        }

        const typeData = this.productData[type];
        if (!typeData || !typeData.sizes) {
            console.error('No sizes found for type:', type);
            return;
        }

        // Enable and populate size dropdown
        this.productSizeSelect.prop('disabled', false);
        this.productSizeSelect.html('<option value="">Select Size</option>');
        
        typeData.sizes.forEach(size => {
            this.productSizeSelect.append(`<option value="${size}">${size}</option>`);
        });

        // Reset and disable model dropdown
        this.productModelSelect.html('<option value="">Select Model</option>').prop('disabled', true);

        // Handle special monument field based on product type
        const specialMonumentContainer = this.specialMonumentSelect.closest('.col-md-3');
        if (type === 'sertop') {
            this.specialMonumentSelect.prop('required', true);
            specialMonumentContainer.show();
        } else {
            this.specialMonumentSelect.prop('required', false);
            this.specialMonumentSelect.val('');
            specialMonumentContainer.hide();
        }

        // Restore cart
        this.cartItems = currentCart;
        this.updateCartDisplay();

        // Update measurements after type change
        this.updateMeasurements();
    }

    handleSizeChange(type, size) {
        console.log('Size changed to:', size, 'for type:', type);
        console.log('Available models:', this.productData[type]?.models?.[size]);

        if (!type || !size) {
            this.productModelSelect.html('<option value="">Select Model</option>').prop('disabled', true);
            return;
        }

        const typeData = this.productData[type];
        const models = typeData?.models?.[size];
        
        if (!models || !models.length) {
            console.error('No models found for type/size:', type, size);
            return;
        }

        // Enable and populate model dropdown
        this.productModelSelect.prop('disabled', false);
        this.productModelSelect.html('<option value="">Select Model</option>');
        
        models.forEach(model => {
            this.productModelSelect.append(`<option value="${model.id}" data-price="${model.base_price}" data-thickness_inches="${model.thickness_inches}">${model.name}</option>`);
        });

        // Update measurements after size change
        this.updateMeasurements();
    }

    createCartItem() {
        if (!this.currentProduct || !this.currentMeasurements) {
            console.error('Missing product or measurements', {
                currentProduct: this.currentProduct,
                currentMeasurements: this.currentMeasurements
            });
            return null;
        }

        const selectedModel = this.productModelSelect.find('option:selected');
        const selectedColor = this.stoneColorSelect.find('option:selected');
        const selectedMonument = this.specialMonumentSelect.find('option:selected');

        if (!selectedModel.val()) {
            console.error('No model selected');
            return null;
        }

        // Calculate measurements using ProductCalculations
        const result = ProductCalculations.calculateTotalPrice(
            this.currentMeasurements,
            {
                base_price: parseFloat(selectedModel.data('price')) || 0,
                type: this.currentProduct.type,
                size: this.currentProduct.size,
                thickness_inches: parseFloat(selectedModel.data('thickness_inches')) || 4.00
            },
            this.getSelectedMarkup(this.stoneColorSelect),
            this.getSelectedMarkup(this.specialMonumentSelect)
        );

        console.log('Creating cart item with calculations:', result);

        const item = {
            type: this.currentProduct.type,
            size: this.currentProduct.size,
            model_id: selectedModel.val(),
            model_name: selectedModel.text(),
            color_id: selectedColor.val() || null,
            color_name: selectedColor.val() ? selectedColor.text() : null,
            special_monument_id: selectedMonument.val() || null,
            special_monument_name: selectedMonument.val() ? selectedMonument.text() : null,
            length: parseFloat(this.currentMeasurements.length) || 0,
            breadth: parseFloat(this.currentMeasurements.breadth) || 0,
            quantity: parseInt(this.currentMeasurements.quantity) || 1,
            sqft: result.sqft || 0,
            cubic_feet: result.cubicFeet || 0,
            base_price: result.basePrice || 0,
            total_price: result.totalPrice || 0
        };

        console.log('Created cart item:', item);
        return item;
    }

    addToCart(item) {
        if (!item) {
            console.error('Cannot add null item to cart');
            return;
        }

        if (!Array.isArray(this.cartItems)) {
            console.log('Initializing cartItems array');
            this.cartItems = [];
        }

        console.log('Adding item to cart:', item);
        this.cartItems.push(item);
        this.saveCartState();
        this.updateCartDisplay();
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
        const items = this.cartItems;
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
                model: item.model_id,
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
        
        const cartItems = this.cartItems;
        if (cartItems.length === 0) {
            alert('Please add items to the quote first.');
            return;
        }
        
        this.updateModalTotals();
        this.commissionModal.show();
    }

    updateModalTotals() {
        const cartItems = this.cartItems;
        const subtotal = cartItems.reduce((sum, item) => sum + item.total_price, 0);
        const commissionRate = parseFloat($('#finalCommissionRate').val()) || 0;
        const commission = (subtotal * commissionRate) / 100;
        const total = subtotal + commission;

        this.modalSubtotal.textContent = '$' + subtotal.toFixed(2);
        this.modalCommission.textContent = '$' + commission.toFixed(2);
        this.modalTotal.textContent = '$' + total.toFixed(2);
    }

    updateMeasurements() {
        const length = parseFloat(this.lengthInput.val()) || 0;
        const breadth = parseFloat(this.breadthInput.val()) || 0;
        const quantity = parseInt(this.quantityInput.val()) || 1;

        if (length && breadth && this.currentProduct) {
            this.currentMeasurements = {
                length: length,
                breadth: breadth,
                quantity: quantity
            };

            const result = ProductCalculations.calculateTotalPrice(
                this.currentMeasurements,
                {
                    base_price: this.currentProduct.base_price,
                    type: this.currentProduct.type,
                    size: this.currentProduct.size,
                    thickness_inches: 4.00 // Default thickness for markers
                },
                this.getSelectedMarkup(this.stoneColorSelect),
                this.getSelectedMarkup(this.specialMonumentSelect)
            );

            this.updateDisplays(result);
        } else {
            this.resetDisplays();
        }
        
        this.updateAddToCartState();
    }

    getSelectedMarkup(selectElement) {
        const selected = selectElement.find('option:selected');
        return selected.val() ? parseFloat(selected.data('increase')) || 0 : 0;
    }

    updatePrice() {
        if (!this.currentProduct || !this.currentMeasurements) {
            this.resetDisplays();
            return;
        }

        const result = ProductCalculations.calculateTotalPrice(
            this.currentMeasurements,
            {
                base_price: this.currentProduct.base_price,
                type: this.currentProduct.type,
                size: this.currentProduct.size,
                thickness_inches: 4.00 // Default thickness for markers
            },
            this.getSelectedMarkup(this.stoneColorSelect),
            this.getSelectedMarkup(this.specialMonumentSelect)
        );

        this.updateDisplays(result);
        this.updateAddToCartState();
    }

    updateDisplays(result) {
        this.squareFeetDisplay.text(result.sqft.toFixed(2));
        this.cubicFeetDisplay.text(result.cubicFeet.toFixed(2));
        this.basePriceDisplay.text(result.basePrice.toFixed(2));
        this.totalPriceDisplay.text(result.totalPrice.toFixed(2));
    }

    resetDisplays() {
        this.squareFeetDisplay.text('0.00');
        this.cubicFeetDisplay.text('0.00');
        this.basePriceDisplay.text('0.00');
        this.totalPriceDisplay.text('0.00');
    }

    resetForm() {
        // Store current cart items
        const currentCart = [...this.getCartItems()];
        
        // Reset dropdowns
        this.productTypeSelect.val('');
        this.productSizeSelect.val('').prop('disabled', true);
        this.productModelSelect.val('').prop('disabled', true);
        this.stoneColorSelect.val('');
        
        // Reset special monument and ensure it's visible for next use
        this.specialMonumentSelect.val('');
        this.specialMonumentSelect.closest('.col-md-3').show();

        // Reset measurements
        this.lengthInput.val('');
        this.breadthInput.val('');
        this.quantityInput.val('1');

        // Reset displays
        this.resetDisplays();

        // Reset internal state
        this.currentProduct = null;
        this.currentMeasurements = null;

        // Disable add to cart button
        this.addToCartBtn.prop('disabled', true);

        // Restore cart items
        this.cartItems = currentCart;
        
        // Update cart display
        this.updateCartDisplay();
    }

    updateAddToCartState() {
        const productType = this.productTypeSelect.val();
        const isValid = Boolean(
            productType &&
            this.productSizeSelect.val() &&
            this.productModelSelect.val() &&
            this.stoneColorSelect.val() &&
            parseFloat(this.lengthInput.val()) > 0 &&
            parseFloat(this.breadthInput.val()) > 0 &&
            parseInt(this.quantityInput.val()) > 0 &&
            (productType !== 'sertop' || this.specialMonumentSelect.val())
        );
        
        this.addToCartBtn.prop('disabled', !isValid);
    }

    saveCartState() {
        try {
            sessionStorage.setItem('cartItems', JSON.stringify(this.cartItems));
            console.log('Cart state saved:', this.cartItems);
        } catch (e) {
            console.error('Error saving cart state:', e);
        }
    }

    loadCartState() {
        try {
            const stored = sessionStorage.getItem('cartItems');
            if (stored) {
                this.cartItems = JSON.parse(stored);
                console.log('Cart state loaded:', this.cartItems);
            }
        } catch (e) {
            console.error('Error loading cart state:', e);
            this.cartItems = [];
        }
        this.updateCartDisplay();
    }

    removeFromCart(index) {
        if (!Array.isArray(this.cartItems) || index < 0 || index >= this.cartItems.length) {
            console.error('Invalid cart operation:', { index, cartItems: this.cartItems });
            return;
        }

        console.log('Removing item at index:', index);
        this.cartItems.splice(index, 1);
        this.saveCartState();
        this.updateCartDisplay();
    }

    getCartItems() {
        if (!Array.isArray(this.cartItems)) {
            this.loadCartState();
        }
        return this.cartItems || [];
    }

    updateCartDisplay() {
        const cartBody = $('#cartTableBody');
        cartBody.empty();

        const items = this.getCartItems();
        console.log('Updating cart display with items:', items);

        if (!Array.isArray(items) || items.length === 0) {
            $('#cartBasePrice').text('$0.00');
            $('#cartTotal').text('$0.00');
            $('#generateQuoteBtn').prop('disabled', true);
            return;
        }

        items.forEach((item, index) => {
            const sqft = parseFloat(item.sqft) || 0;
            const cubicFeet = parseFloat(item.cubic_feet) || 0;
            const basePrice = parseFloat(item.base_price) || 0;
            const totalPrice = parseFloat(item.total_price) || 0;
            const splMonument = item.type.toUpperCase() === 'SERTOP' ? 
                (item.special_monument_name || 'None') : 'None';

            cartBody.append(`
                <tr>
                    <td>${(item.type || '').toUpperCase()}</td>
                    <td>${splMonument}</td>
                    <td>${item.model_name || ''}</td>
                    <td>${item.color_name || '-'}</td>
                    <td>${item.length} x ${item.breadth} x ${item.size}</td>
                    <td>${item.quantity || 1}</td>
                    <td>${sqft.toFixed(2)}</td>
                    <td>${cubicFeet.toFixed(2)}</td>
                    <td class="text-end">$${basePrice.toFixed(2)}</td>
                    <td class="text-end">$${totalPrice.toFixed(2)}</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-danger btn-sm" data-index="${index}">
                            <i class="bi bi-trash-fill"></i>
                        </button>
                    </td>
                </tr>
            `);
        });

        // Add click handler for delete buttons
        cartBody.find('.btn-danger').on('click', (e) => {
            const index = $(e.currentTarget).data('index');
            this.removeFromCart(index);
        });

        this.updateTotals();
        $('#generateQuoteBtn').prop('disabled', false);
    }

    updateTotals() {
        const items = this.getCartItems();
        if (!Array.isArray(items) || items.length === 0) {
            $('#cartBasePrice').text('$0.00');
            $('#cartTotal').text('$0.00');
            return;
        }

        let totalBasePrice = 0;
        let totalPrice = 0;

        items.forEach(item => {
            totalBasePrice += parseFloat(item.base_price) || 0;
            totalPrice += parseFloat(item.total_price) || 0;
        });

        $('#cartBasePrice').text('$' + totalBasePrice.toFixed(2));
        $('#cartTotal').text('$' + totalPrice.toFixed(2));
    }
}

// Initialize when document is ready
$(document).ready(() => {
    window.quoteManager = new QuoteManager();
});