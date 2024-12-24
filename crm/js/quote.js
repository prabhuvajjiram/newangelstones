class QuoteManager {
    constructor() {
        // Initialize with data from window.QUOTE_DATA
        this.productData = window.QUOTE_DATA.productData;
        this.quoteData = window.QUOTE_DATA.quoteData;
        
        // Initialize cart items from session storage or window data
        try {
            const storedCart = sessionStorage.getItem('cartItems');
            this.cartItems = storedCart ? JSON.parse(storedCart) : [];
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

        this.populateStoneColors();
        this.populateSpecialMonuments();
    }

    initializeEventListeners() {
        // Product selection handlers
        this.productTypeSelect.on('change', () => {
            const type = this.productTypeSelect.val();
            this.handleProductTypeChange(type);
            this.currentProduct = null; // Reset current product when type changes
            this.updatePrice();
        });

        this.productSizeSelect.on('change', () => {
            const type = this.productTypeSelect.val();
            const size = this.productSizeSelect.val();
            this.handleSizeChange(type, size);
            this.currentProduct = null; // Reset current product when size changes
            this.updatePrice();
        });

        $(document).on('click', '.remove-cart-item', (e) => {
            const row = $(e.target).closest('tr');
            const itemId = row.data('item-id'); // Add data-item-id to each row
            
            if (typeof itemId !== 'undefined') {
                this.removeFromCart(itemId);
            } else {
                // Fallback to index-based removal
                const index = row.index();
                this.removeFromCart(index);
            }
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
                console.log('Model changed, new current product:', this.currentProduct);
                this.updatePrice();
            }
        });

        // Price update handlers for all dropdowns and inputs
        const updatePriceElements = [
            this.stoneColorSelect,
            this.specialMonumentSelect,
            this.lengthInput,
            this.breadthInput,
            this.quantityInput
        ];

        updatePriceElements.forEach(element => {
            element.on('change input', () => {
                console.log('Input/dropdown changed:', element.attr('id'));
                if (this.currentProduct) {
                    this.updateMeasurements();
                }
            });
        });

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
        console.log('Product type changed to:', type);
        console.log('Product data available:', this.productData);
        
        // Clear dependent dropdowns
        this.productSizeSelect.empty().append('<option value="">Select Size</option>');
        this.productModelSelect.empty().append('<option value="">Select Model</option>');
        
        // Reset measurements and displays
        this.resetDisplays();
        this.currentProduct = null;
        this.currentMeasurements = null;
        
        // Only populate sizes if a valid product type is selected
        if (type && this.productData[type]) {
            const sizes = this.productData[type].sizes || [];
            console.log('Available sizes:', sizes);
            
            sizes.forEach(size => {
                this.productSizeSelect.append(`<option value="${size}">${this.getSizeDisplay(type, size)}</option>`);
            });
            
            // Enable size dropdown
            this.productSizeSelect.prop('disabled', false);
        } else {
            // Disable size dropdown if no type selected
            this.productSizeSelect.prop('disabled', true);
        }
        
        // Disable model dropdown until size is selected
        this.productModelSelect.prop('disabled', true);
        
        this.updateAddToCartState();
    }

    handleSizeChange(type, size) {
        console.log('Size changed to:', size, 'for type:', type);
        
        // Clear and disable model dropdown by default
        this.productModelSelect.empty().append('<option value="">Select Model</option>').prop('disabled', true);
        
        // Reset measurements and displays
        this.resetDisplays();
        this.currentProduct = null;
        this.currentMeasurements = null;
        
        if (!type || !size) {
            return;
        }
        
        const models = this.productData[type]?.models?.[size];
        console.log('Available models:', models);
        
        if (!models || !models.length) {
            console.error('No models found for type/size:', type, size);
            return;
        }
        
        // Enable and populate model dropdown
        this.productModelSelect.prop('disabled', false);
        
        models.forEach(model => {
            this.productModelSelect.append(`
                <option value="${model.id}" 
                    data-price="${model.base_price}"
                    data-length-inches="${model.length_inches || ''}"
                    data-breadth-inches="${model.breadth_inches || ''}"
                    data-thickness-inches="${model.thickness_inches || ''}"
                    data-size="${size}"
                >${model.name}</option>
            `);
        });
    }

    createCartItem() {
        if (!this.currentProduct || !this.currentMeasurements) {
            console.error('Missing product or measurements', {
                currentProduct: this.currentProduct,
                currentMeasurements: this.currentMeasurements
            });
            return null;
        }

        try {
            const selectedModel = this.productModelSelect.find('option:selected');
            const selectedColor = this.stoneColorSelect.find('option:selected');
            const selectedMonument = this.specialMonumentSelect.find('option:selected');

            if (!selectedModel.val()) {
                console.error('No model selected');
                return null;
            }

            // Get base price and measurements
            const basePrice = parseFloat(selectedModel.data('price'));
            const length = parseFloat(this.currentMeasurements.length);
            const breadth = parseFloat(this.currentMeasurements.breadth);
            const quantity = parseInt(this.currentMeasurements.quantity);
            
            // Get thickness based on product type
            let thickness;
            const type = this.currentProduct.type.toLowerCase();
            if (type === 'marker') {
                thickness = parseFloat(selectedModel.data('thickness-inches')); // Use thickness_inches for markers
                console.log('Marker thickness:', thickness);
                if (isNaN(thickness)) {
                    console.error('Invalid marker thickness, using length-inches as fallback');
                    thickness = parseFloat(selectedModel.data('length-inches')); // Fallback to length_inches
                }
            } else {
                thickness = parseFloat(selectedModel.data('size')); // Use size_inches for other types
            }

            // Validate thickness
            if (isNaN(thickness) || thickness <= 0) {
                console.error('Invalid thickness value:', thickness);
                throw new Error('Invalid thickness value');
            }

            // Calculate per unit measurements
            const sqft = (length * breadth) / 144;  // Square feet per unit
            const cubicFeetPerUnit = (length * breadth * thickness) / 1728;  // Cubic feet per unit
            
            // Calculate total cubic feet
            const totalCubicFeet = cubicFeetPerUnit * quantity;

            // Get markups
            const colorMarkup = this.getSelectedMarkup(this.stoneColorSelect);
            const monumentMarkup = this.getSelectedMarkup(this.specialMonumentSelect);

            // Calculate prices
            const basePricePerUnit = basePrice * sqft;  // Base price per unit
            const markupAmount = basePricePerUnit * ((colorMarkup + monumentMarkup) / 100);
            const totalPricePerUnit = basePricePerUnit + markupAmount;
            const totalPrice = totalPricePerUnit * quantity;

            console.log('Cart item calculations:', {
                basePrice, length, breadth, thickness, quantity,
                sqft, cubicFeetPerUnit, totalCubicFeet,
                colorMarkup, monumentMarkup,
                basePricePerUnit, markupAmount, totalPricePerUnit, totalPrice
            });

            return {
                type: this.currentProduct.type,
                size: this.currentProduct.size,
                model_id: selectedModel.val(),
                model_name: selectedModel.text(),
                color_id: selectedColor.val() || null,
                color_name: selectedColor.val() ? selectedColor.text() : null,
                special_monument_id: selectedMonument.val() || null,
                special_monument_name: selectedMonument.val() ? selectedMonument.text() : null,
                color_markup: colorMarkup,
                monument_markup: monumentMarkup,
                length: length,
                breadth: breadth,
                thickness: thickness,
                quantity: quantity,
                sqft: sqft,  // Square feet per unit
                cubic_feet: totalCubicFeet,  // Total cubic feet for all units
                base_price: basePricePerUnit,
                markup_amount: markupAmount,
                price_per_unit: totalPricePerUnit,
                total_price: totalPrice
            };
        } catch (error) {
            console.error('Error creating cart item:', error);
            return null;
        }
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
        const length = parseFloat(this.lengthInput.val());
        const breadth = parseFloat(this.breadthInput.val());
        const quantity = parseInt(this.quantityInput.val());

        if (length && breadth && this.currentProduct) {
            this.currentMeasurements = {
                length: length,
                breadth: breadth,
                quantity: quantity
            };

            const selectedModel = this.productModelSelect.find('option:selected');
            
            // Get thickness based on product type
            let thickness;
            const type = this.currentProduct.type.toLowerCase();
            if (type === 'marker') {
                thickness = parseFloat(selectedModel.data('thickness-inches')); // Use thickness_inches for markers
                console.log('Marker thickness:', thickness);
                if (isNaN(thickness)) {
                    console.error('Invalid marker thickness, using length-inches as fallback');
                    thickness = parseFloat(selectedModel.data('length-inches')); // Fallback to length_inches
                }
            } else {
                thickness = parseFloat(selectedModel.data('size')); // Use size_inches for other types
            }

            // Validate thickness
            if (isNaN(thickness) || thickness <= 0) {
                console.error('Invalid thickness value:', thickness);
                this.resetDisplays();
                return;
            }

            // Calculate per unit measurements
            const sqft = (length * breadth) / 144;  // Square feet per unit
            const cubicFeetPerUnit = (length * breadth * thickness) / 1728;  // Cubic feet per unit
            
            // Calculate total cubic feet
            const totalCubicFeet = cubicFeetPerUnit * quantity;

            // Calculate prices
            const basePrice = parseFloat(selectedModel.data('price'));
            const baseAmount = sqft * basePrice;  // Base price per unit
            const colorMarkup = this.getSelectedMarkup(this.stoneColorSelect);
            const monumentMarkup = this.getSelectedMarkup(this.specialMonumentSelect);
            const markupAmount = baseAmount * ((colorMarkup + monumentMarkup) / 100);
            const totalPrice = (baseAmount + markupAmount) * quantity;

            const result = {
                sqft: sqft,  // Per unit
                cubicFeet: totalCubicFeet,  // Total for all units
                basePrice: basePrice,
                totalPrice: totalPrice
            };

            console.log('Measurement calculation:', {
                measurements: { length, breadth, thickness, quantity },
                perUnit: { sqft, cubicFeetPerUnit },
                total: { cubicFeet: totalCubicFeet, price: totalPrice },
                markups: { color: colorMarkup, monument: monumentMarkup }
            });

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

        try {
            const selectedModel = this.productModelSelect.find('option:selected');
            const thickness = parseFloat(selectedModel.data('size'));
            const quantity = parseInt(this.currentMeasurements.quantity);

            // Calculate per unit measurements
            const sqft = (this.currentMeasurements.length * this.currentMeasurements.breadth) / 144;  // Square feet per unit
            const cubicFeetPerUnit = (this.currentMeasurements.length * this.currentMeasurements.breadth * thickness) / 1728;  // Cubic feet per unit
            
            // Calculate total cubic feet
            const totalCubicFeet = cubicFeetPerUnit * quantity;

            // Calculate prices
            const basePrice = this.currentProduct.base_price;
            const baseAmount = sqft * basePrice;  // Base price per unit
            const colorMarkup = this.getSelectedMarkup(this.stoneColorSelect);
            const monumentMarkup = this.getSelectedMarkup(this.specialMonumentSelect);
            const markupAmount = baseAmount * ((colorMarkup + monumentMarkup) / 100);
            const totalPrice = (baseAmount + markupAmount) * quantity;

            const result = {
                sqft: sqft,  // Per unit
                cubicFeet: totalCubicFeet,  // Total for all units
                basePrice: basePrice,
                totalPrice: totalPrice
            };

            console.log('Price calculation:', {
                measurements: this.currentMeasurements,
                perUnit: { sqft, cubicFeetPerUnit },
                total: { cubicFeet: totalCubicFeet, price: totalPrice },
                markups: { color: colorMarkup, monument: monumentMarkup }
            });

            this.updateDisplays(result);
        } catch (error) {
            console.error('Error updating price:', error);
            this.resetDisplays();
        }
        
        this.updateAddToCartState();
    }

    updateDisplays(result) {
        if (!result) {
            this.resetDisplays();
            return;
        }
        
        try {
            // Format numbers and handle potential NaN values
            const sqft = parseFloat(result.sqft) || 0;
            const cubicFeet = parseFloat(result.cubicFeet) || 0;
            const basePrice = parseFloat(result.basePrice) || 0;
            const totalPrice = parseFloat(result.totalPrice) || 0;

            this.squareFeetDisplay.text(sqft.toFixed(2));
            this.cubicFeetDisplay.text(cubicFeet.toFixed(2));
            this.basePriceDisplay.text(basePrice.toFixed(2));
            this.totalPriceDisplay.text(totalPrice.toFixed(2));
            
            console.log('Updated displays with:', { sqft, cubicFeet, basePrice, totalPrice });
        } catch (error) {
            console.error('Error updating displays:', error);
            this.resetDisplays();
        }
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

    removeFromCart(identifier) {
        let index;
        if (typeof identifier === 'number') {
            index = identifier;
        } else {
            // Find item by ID if using item IDs
            index = this.cartItems.findIndex(item => item.id === identifier);
        }
    
        if (index > -1 && index < this.cartItems.length) {
            this.cartItems.splice(index, 1);
            this.saveCartState();
            this.updateCartDisplay();
            this.updateTotals();
        }
    }

    getCartItems() {
        if (!Array.isArray(this.cartItems)) {
            this.loadCartState();
        }
        return this.cartItems || [];
    }

    updateCartDisplay() {
        const cartTableBody = document.getElementById('cartTableBody');
        const cartTotal = document.getElementById('cartTotal');
        const cartCubicFtTotal = document.getElementById('cartCubicFtTotal');
        const containerWarning = document.getElementById('containerWarning');
        
        let totalPrice = 0;
        let totalCubicFt = 0;
        
        cartTableBody.innerHTML = '';
        
        this.cartItems.forEach((item, index) => {
            const row = document.createElement('tr');
            
            // Format dimensions, handling marker thickness
            const thickness = item.product_type === 'marker' ? '4.00' : item.size;
            const dimensions = `${item.length}" × ${item.breadth}" × ${thickness}"`;
            
            row.innerHTML = `
                <td>${item.type}</td>
                <td>${item.special_monument_name || ''}</td>
                <td>${item.model_name}</td>
                <td>${item.color_name || '-'}</td>
                <td>${dimensions}</td>
                <td>${item.quantity}</td>
                <td>${item.sqft.toFixed(2)}</td>
                <td class="text-end">${item.cubic_feet.toFixed(2)}</td>
                <td class="text-end">$${item.base_price.toFixed(2)}</td>
                <td class="text-end">$${item.total_price.toFixed(2)}</td>
                <td class="text-center">
                    <button type="button" class="btn btn-danger btn-sm remove-cart-item">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            `;
            
            cartTableBody.appendChild(row);
            totalPrice += item.total_price;
            totalCubicFt += item.cubic_feet;
        });
        
        cartTotal.textContent = `$${totalPrice.toFixed(2)}`;
        cartCubicFtTotal.textContent = totalCubicFt.toFixed(2);
        
        // Update container capacity warning
        const containerCapacity = 205; // Standard container capacity
        const capacityPercentage = (totalCubicFt / containerCapacity) * 100;
        
        if (totalCubicFt > 0 && capacityPercentage < 90) {
            containerWarning.classList.remove('d-none');
        } else {
            containerWarning.classList.add('d-none');
        }
        
        // Enable/disable generate quote button
        const generateQuoteBtn = document.getElementById('generateQuoteBtn');
        generateQuoteBtn.disabled = this.cartItems.length === 0;
        
        // Update commission modal if it exists
        this.updateModalTotals();
    }

    updateTotals() {
        const items = this.getCartItems();
        if (!Array.isArray(items) || items.length === 0) {
            $('#cartCubicFtTotal').text('0.00');
            $('#cartTotal').text('$0.00');
            return;
        }

        let totalPrice = 0;
        let totalCubicFt = 0;

        items.forEach(item => {
            totalPrice += item.total_price;
            totalCubicFt += item.cubic_feet;
        });

        $('#cartCubicFtTotal').text(totalCubicFt.toFixed(2));
        $('#cartTotal').text('$' + totalPrice.toFixed(2));
    }

    populateStoneColors() {
        this.stoneColorSelect.empty().append('<option value="">Select Color</option>');
        if (this.quoteData.stone_colors) {
            this.quoteData.stone_colors.forEach(color => {
                this.stoneColorSelect.append(`<option value="${color.id}" data-increase="${color.price_increase}">${color.color_name}</option>`);
            });
        }
    }

    populateSpecialMonuments() {
        this.specialMonumentSelect.empty().append('<option value="">Select Special Monument</option>');
        if (this.quoteData.special_monuments) {
            this.quoteData.special_monuments.forEach(monument => {
                this.specialMonumentSelect.append(`<option value="${monument.id}" data-increase="${monument.price_increase_percentage}">${monument.name}</option>`);
            });
        }
    }

    getSizeDisplay(type, size) {
        if (type.toLowerCase() === 'marker') {
            return size + ' SQFT';
        }
        return size + ' inch';
    }
}

// Initialize when document is ready
$(document).ready(() => {
    window.quoteManager = new QuoteManager();
});