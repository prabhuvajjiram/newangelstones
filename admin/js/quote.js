class QuoteManager {
    constructor() {
        this.cart = [];
        this.initialize();
    }

    initialize() {
        if (!window.QUOTE_DATA) {
            console.error('Quote data not initialized!');
            return;
        }

        this.productData = window.QUOTE_DATA.productData || {};
        this.quoteData = window.QUOTE_DATA.quoteData || {};
        
        console.log('Initialized with product data:', this.productData);
        
        this.initializeElements();
        this.setupEventListeners();
        this.refreshCartTable();

        // If we have a customer ID, select it
        if (this.quoteData.customer_id) {
            this.customerSelect.val(this.quoteData.customer_id).trigger('change');
        }
    }

    initializeElements() {
        // Initialize form
        this.quoteForm = $('#quoteForm');
        
        // Initialize selects
        this.customerSelect = $('#customer_select');
        this.typeSelect = $('#type');
        this.sizeSelect = $('#size');
        this.modelSelect = $('#model');
        this.stoneColorSelect = $('#stoneColor');
        
        // Initialize inputs
        this.lengthInput = $('#length');
        this.breadthInput = $('#breadth');
        this.quantityInput = $('#quantity');
        this.addToCartBtn = $('#addToCartBtn');
        
        // Initialize displays
        this.sqftDisplay = $('#sqft');
        this.cuftDisplay = $('#cubicFeet');
        this.basePriceDisplay = $('#basePrice');
        this.totalPriceDisplay = $('#totalPrice');
        this.cartTable = $('#cartTable');
        
        // Initialize modal
        const commissionModalEl = document.getElementById('commissionModal');
        if (commissionModalEl) {
            this.commissionModal = new bootstrap.Modal(commissionModalEl);
        }
        
        this.finalCommissionRate = document.getElementById('finalCommissionRate');
        this.modalSubtotal = document.getElementById('modalSubtotal');
        this.modalCommission = document.getElementById('modalCommission');
        this.modalTotal = document.getElementById('modalTotal');

        // Populate initial dropdowns
        this.populateTypeSelect();
    }

    setupEventListeners() {
        // Customer select change handler
        this.customerSelect.on('change', () => {
            const selectedOption = this.customerSelect.find('option:selected');
            if (selectedOption.length) {
                const customerId = selectedOption.val();
                const customerEmail = selectedOption.data('email');
                
                $('#customer_id').val(customerId || '');
                $('#customer_email').val(customerEmail || '');

                // Clear error message if customer is selected
                if (customerId) {
                    $('.alert-danger').fadeOut();
                }
            }
        });
        
        // Product type change handler
        this.typeSelect.on('change', () => this.handleTypeChange());
        
        // Size change handler
        this.sizeSelect.on('change', () => this.handleSizeChange());
        
        // Model change handler
        this.modelSelect.on('change', () => this.handleModelChange());
        
        // Measurement inputs change handler
        this.lengthInput.on('input', () => this.updateCalculations());
        this.breadthInput.on('input', () => this.updateCalculations());
        this.quantityInput.on('input', () => this.updateCalculations());
        
        // Add to cart button handler
        this.addToCartBtn.on('click', () => this.addToCart());
        
        // Commission rate change listener
        if (this.finalCommissionRate) {
            this.finalCommissionRate.addEventListener('input', () => this.updateModalTotals());
        }

        // Form submission handler
        this.quoteForm.on('submit', (e) => {
            e.preventDefault();
            if (!this.customerSelect.val()) {
                alert('Please select a customer');
                return false;
            }
            return true;
        });
    }

    addToCart() {
        // Check if customer is selected
        const customerId = $('#customer_id').val();
        if (!customerId) {
            alert('Please select a customer first');
            return;
        }

        const type = this.typeSelect.val();
        const size = this.sizeSelect.val();
        const model = this.modelSelect.val();
        const stoneColor = this.stoneColorSelect.val();
        const length = parseFloat(this.lengthInput.val()) || 0;
        const breadth = parseFloat(this.breadthInput.val()) || 0;
        const quantity = parseInt(this.quantityInput.val()) || 1;
        
        // Get calculated values
        const sqft = parseFloat(this.sqftDisplay.text()) || 0;
        const cuft = parseFloat(this.cuftDisplay.text()) || 0;
        const basePrice = parseFloat(this.basePriceDisplay.text().replace('$', '')) || 0;
        const totalPrice = parseFloat(this.totalPriceDisplay.text().replace('$', '')) || 0;

        // Validate required fields
        if (!type || !size || !model || !length || !breadth || !quantity) {
            alert('Please fill in all required fields');
            return;
        }

        // Validate stone color
        if (!stoneColor) {
            alert('Please select a stone color');
            return;
        }

        // Create cart item
        const cartItem = {
            type,
            size,
            model,
            color_id: stoneColor,  
            length,
            breadth,
            quantity,
            sqft,
            cuft,
            basePrice,
            totalPrice
        };

        console.log('Adding to cart:', cartItem);

        // Add to cart and refresh display
        this.cart.push(cartItem);
        this.refreshCartTable();
        
        // Reset form
        this.resetForm();
    }

    refreshCartTable() {
        this.cartTable.empty();

        let totalBasePrice = 0;
        let cartTotal = 0;

        this.cart.forEach((item, index) => {
            const row = $('<tr>');
            row.html(`
                <td>${item.type || ''}</td>
                <td>${item.size || ''}</td>
                <td>${item.model || ''}</td>
                <td>${item.color_id || ''}  
                <td>${item.length} x ${item.breadth}</td>
                <td>${item.quantity || 1}</td>
                <td>${item.sqft.toFixed(2)}</td>
                <td>${item.cuft.toFixed(2)}</td>
                <td>${this.formatCurrency(item.basePrice)}</td>
                <td>${this.formatCurrency(item.totalPrice)}</td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm" onclick="window.quoteManager.removeFromCart(${index})">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            `);
            this.cartTable.append(row);

            totalBasePrice += parseFloat(item.basePrice || 0);
            cartTotal += parseFloat(item.totalPrice || 0);
        });

        // Update totals row if there are items
        if (this.cart.length > 0) {
            const totalsRow = $('<tr>').addClass('table-info');
            totalsRow.html(`
                <td colspan="8" class="text-end"><strong>Totals:</strong></td>
                <td><strong>${this.formatCurrency(totalBasePrice)}</strong></td>
                <td><strong>${this.formatCurrency(cartTotal)}</strong></td>
                <td></td>
            `);
            this.cartTable.append(totalsRow);
        }

        // Update modal values
        this.updateModalTotals();
    }

    populateTypeSelect() {
        this.typeSelect.empty();
        this.typeSelect.append('<option value="">Select Type</option>');
        
        if (this.productData.types) {
            this.productData.types.forEach(type => {
                this.typeSelect.append(`<option value="${type}">${type.charAt(0).toUpperCase() + type.slice(1)}</option>`);
            });
        }
    }

    handleTypeChange() {
        const selectedType = this.typeSelect.val();
        
        // Reset dependent dropdowns
        this.sizeSelect.empty().append('<option value="">Select Size</option>');
        this.modelSelect.empty().append('<option value="">Select Model</option>');
        
        if (selectedType) {
            // Filter sizes for this type
            const sizes = this.productData.sizes.filter(size => {
                return this.productData.models.some(model => model.type === selectedType && model.size === size);
            });
            
            sizes.forEach(size => {
                this.sizeSelect.append(`<option value="${size}">${size} inch</option>`);
            });
        }
        
        this.updateCalculations();
    }

    handleSizeChange() {
        const selectedType = this.typeSelect.val();
        const selectedSize = this.sizeSelect.val();
        
        this.modelSelect.empty();
        this.modelSelect.append('<option value="">Select Model</option>');
        
        if (selectedType && selectedSize) {
            // Filter models for this type and size
            const models = this.productData.models.filter(model => 
                model.type === selectedType && model.size === selectedSize
            );
            
            models.forEach(model => {
                this.modelSelect.append(`<option value="${model.model}" 
                    data-base-price="${model.base_price}"
                    data-length="${model.length_inches || ''}"
                    data-breadth="${model.breadth_inches || ''}"
                >${model.model}</option>`);
            });
        }
        
        this.updateCalculations();
    }

    handleModelChange() {
        const selectedOption = this.modelSelect.find('option:selected');
        if (selectedOption.length) {
            const defaultLength = selectedOption.data('length');
            const defaultBreadth = selectedOption.data('breadth');
            
            if (defaultLength) this.lengthInput.val(defaultLength);
            if (defaultBreadth) this.breadthInput.val(defaultBreadth);
        }
        
        this.updateCalculations();
    }

    updateCalculations() {
        const type = this.typeSelect.val();
        const size = this.sizeSelect.val();
        const model = this.modelSelect.val();
        const length = parseFloat(this.lengthInput.val()) || 0;
        const breadth = parseFloat(this.breadthInput.val()) || 0;
        const quantity = parseInt(this.quantityInput.val()) || 1;

        console.log('Calculating measurements:', {
            type, size, model, length, breadth, quantity
        });

        // Reset fields if required values are missing
        if (!type || !size || !model) {
            this.resetDependentFields();
            return;
        }

        // Find the selected product
        const product = this.productData.models.find(p => p.type === type && p.size === size && p.model === model);
        if (!product) {
            console.error('Product not found:', { type, size, model });
            return;
        }

        let measurements = {
            sqft: 0,
            cuft: 0,
            basePrice: 0,
            totalPrice: 0
        };

        // Calculate based on product type
        switch (type.toUpperCase()) {
            case 'SERTOP':
            case 'BASE':
            case 'SLANT':
                const sqft = (length * breadth) / 144;
                const cuft = (length * breadth * parseFloat(size)) / 1728 * quantity;
                const basePrice = product.base_price * sqft;
                const totalPrice = basePrice * quantity;
                
                measurements = {
                    sqft: sqft,
                    cuft: cuft,
                    basePrice: basePrice,
                    totalPrice: totalPrice
                };
                break;

            case 'MARKER':
                const markerSqft = (length * breadth) / 144;
                const thickness = product.thickness || 4.00;
                const markerCuft = (length * breadth * thickness) / 1728 * quantity;
                const markerBasePrice = product.base_price * markerSqft;
                const markerTotalPrice = markerBasePrice * quantity;
                
                measurements = {
                    sqft: markerSqft,
                    cuft: markerCuft,
                    basePrice: markerBasePrice,
                    totalPrice: markerTotalPrice
                };
                break;
        }

        console.log('Calculated measurements:', measurements);

        // Update UI with calculated values
        this.sqftDisplay.text(measurements.sqft.toFixed(2));
        this.cuftDisplay.text(measurements.cuft.toFixed(2));
        this.basePriceDisplay.text('$' + measurements.basePrice.toFixed(2));
        this.totalPriceDisplay.text('$' + measurements.totalPrice.toFixed(2));
    }

    removeFromCart(index) {
        // Remove from cart array
        this.cart.splice(index, 1);

        // Refresh cart table
        this.refreshCartTable();
    }

    resetForm() {
        // Reset dropdowns
        this.typeSelect.val('');
        this.sizeSelect.empty().append('<option value="">Select Size</option>');
        this.modelSelect.empty().append('<option value="">Select Model</option>');
        this.stoneColorSelect.val('');

        // Reset input fields
        this.lengthInput.val('');
        this.breadthInput.val('');
        this.quantityInput.val('1');

        // Reset calculation fields
        this.resetDependentFields();
    }

    resetDependentFields() {
        $('#sqft').text('0.00');
        $('#cubicFeet').text('0.00');
        $('#basePrice').text('$0.00');
        $('#totalPrice').text('$0.00');
    }

    finalizeQuote() {
        // Validate cart
        if (this.cart.length === 0) {
            alert('Please add at least one item to the cart');
            return;
        }

        const customerId = $('#customer_id').val();
        if (!customerId) {
            alert('Please select a customer');
            return;
        }

        // Get final values
        const commissionRate = parseFloat(this.finalCommissionRate.value) || 0;
        
        // Calculate totals
        let subtotal = 0;
        this.cart.forEach(item => {
            subtotal += item.basePrice * item.quantity;
        });
        
        const commissionAmount = (subtotal * commissionRate) / 100;
        const totalAmount = subtotal + commissionAmount;
        
        // Prepare quote data
        const quoteData = {
            customer_id: customerId,
            commission_rate: commissionRate,
            total_amount: totalAmount,
            commission_amount: commissionAmount,
            subtotal: subtotal,
            items: this.cart.map(item => {
                const itemCommission = (item.basePrice * commissionRate) / 100;
                return {
                    product_type: item.type,
                    model: item.model,
                    size: item.size,
                    stone_color_id: item.color_id,
                    length: item.length,
                    breadth: item.breadth,
                    sqft: item.sqft,
                    cubic_feet: item.cuft,
                    quantity: item.quantity,
                    unit_price: item.basePrice,
                    total_price: item.basePrice + itemCommission,
                    commission_amount: itemCommission
                };
            })
        };

        // Create a hidden input for quote data
        const quoteDataInput = $('<input>')
            .attr('type', 'hidden')
            .attr('name', 'quote_data')
            .val(JSON.stringify(quoteData));

        // Add it to the form
        this.quoteForm.append(quoteDataInput);

        // Hide modal
        if (this.commissionModal) {
            const modalElement = document.getElementById('commissionModal');
            const modal = bootstrap.Modal.getInstance(modalElement);
            if (modal) {
                modalElement.removeAttribute('aria-hidden');
                modal.hide();
            }
        }

        console.log('Submitting quote data:', quoteData);
        
        // Submit the form
        this.quoteForm[0].submit();
    }

    showCommissionModal() {
        // Create modal if it doesn't exist
        if (!this.commissionModal) {
            const modalHtml = `
                <div class="modal fade" id="commissionModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Set Commission Rate</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="commission_rate" class="form-label">Commission Rate (%)</label>
                                    <input type="number" class="form-control" id="commission_rate" min="0" max="100" step="0.1" value="0">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-primary" id="saveQuoteBtn">Save Quote</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Add modal to body
            document.body.insertAdjacentHTML('beforeend', modalHtml);

            // Initialize Bootstrap modal
            this.commissionModal = new bootstrap.Modal(document.getElementById('commissionModal'));

            // Store commission rate input
            this.finalCommissionRate = document.getElementById('commission_rate');

            // Add event listener to save button
            document.getElementById('saveQuoteBtn').addEventListener('click', () => {
                this.finalizeQuote();
            });
        }

        // Show modal
        this.commissionModal.show();
    }

    updateModalTotals() {
        if (!this.modalSubtotal || !this.modalCommission || !this.modalTotal || !this.finalCommissionRate) {
            console.error('Modal elements not found');
            return;
        }

        const subtotal = this.calculateSubtotal();
        const commissionRate = parseFloat(this.finalCommissionRate.value) || 0;
        const commissionAmount = subtotal * (commissionRate / 100);
        const total = subtotal + commissionAmount;

        this.modalSubtotal.textContent = this.formatCurrency(subtotal);
        this.modalCommission.textContent = this.formatCurrency(commissionAmount);
        this.modalTotal.textContent = this.formatCurrency(total);
    }

    calculateSubtotal() {
        return this.cart.reduce((total, item) => total + parseFloat(item.totalPrice || 0), 0);
    }

    async saveQuote() {
        // Validate customer
        const customerId = $('#customer_id').val();
        if (!customerId) {
            alert('Please select a customer first');
            return;
        }

        // Validate cart
        if (this.cart.length === 0) {
            alert('Please add at least one item to the cart');
            return;
        }

        try {
            // Get commission rate
            const commissionRate = parseFloat(this.finalCommissionRate?.value) || 0;

            // Prepare quote data
            const quoteData = {
                customer_id: customerId,
                commission_rate: commissionRate,
                items: this.cart.map(item => ({
                    product_type: item.type,
                    model: item.model || '',
                    size: item.size || '',
                    color_id: item.color_id || null,
                    length: item.length || 0,
                    breadth: item.breadth || 0,
                    sqft: item.sqft || 0,
                    cubic_feet: item.cuft || 0,
                    quantity: item.quantity,
                    unit_price: parseFloat(item.basePrice),
                    total_price: parseFloat(item.basePrice) * (1 + commissionRate / 100)
                }))
            };

            // Save quote to database
            const response = await fetch('save_quote.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(quoteData)
            });

            if (!response.ok) {
                throw new Error('Failed to save quote');
            }

            const result = await response.json();
            
            if (result.success) {
                // Create a form to submit to preview_quote.php
                const form = $('<form>', {
                    'action': 'preview_quote.php',
                    'method': 'GET',
                    'style': 'display: none'
                });

                // Add quote ID
                form.append($('<input>', {
                    'type': 'hidden',
                    'name': 'id',
                    'value': result.quote_id
                }));

                // Add form to body and submit
                form.appendTo(document.body);
                
                // Hide modal before submitting
                if (this.commissionModal) {
                    this.commissionModal.hide();
                    // Wait for modal to finish hiding
                    setTimeout(() => {
                        form.submit();
                    }, 150);
                } else {
                    form.submit();
                }
            } else {
                throw new Error(result.message || 'Failed to save quote');
            }
        } catch (error) {
            console.error('Error saving quote:', error);
            alert('There was an error saving the quote. Please try again.');
        }
    }

    formatCurrency(amount) {
        return `$${parseFloat(amount).toFixed(2)}`;
    }

    refreshCustomerList() {
        $.ajax({
            url: 'get_customers.php',
            method: 'GET',
            success: (response) => {
                this.customerSelect.empty();
                this.customerSelect.append('<option value="">Select a customer</option>');
                
                response.customers.forEach(customer => {
                    this.customerSelect.append(
                        `<option value="${customer.id}" data-email="${customer.email}">${customer.name} (${customer.email})</option>`
                    );
                });
            },
            error: (xhr, status, error) => {
                alert('Error refreshing customer list: ' + error);
            }
        });
    }
}

// Initialize the quote manager when the document is ready
$(document).ready(() => {
    window.quoteManager = new QuoteManager();
});
