<?php
// Include database connection if needed
// require_once 'path_to_db_config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order/Quote Form - Angel Stones</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f5f5f5;
            font-family: Arial, sans-serif;
            font-size: 13px;
            color: #333;
            line-height: 1.4;
        }
        .form-container {
            background-color: white;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 15px;
            margin: 15px auto;
            max-width: 1100px;
        }
        .form-section {
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: #fff;
        }
        .form-section h5 {
            background-color: #f0f0f0;
            color: #333;
            font-size: 13px;
            font-weight: bold;
            margin: 0;
            padding: 8px 10px;
            border-bottom: 1px solid #ddd;
        }
        .form-section-inner {
            padding: 10px;
        }
        .form-label {
            margin-bottom: 3px;
            font-weight: bold;
            font-size: 12px;
        }
        .form-control, .form-select {
            font-size: 12px;
            height: 28px;
            padding: 3px 8px;
            border-radius: 3px;
            border: 1px solid #ccc;
        }
        .form-control:focus, .form-select:focus {
            border-color: #66afe9;
            outline: 0;
            box-shadow: inset 0 1px 1px rgba(0,0,0,.075), 0 0 8px rgba(102,175,233,.6);
        }
        .btn {
            font-size: 12px;
            padding: 4px 10px;
            border-radius: 3px;
            line-height: 1.4;
        }
        .btn-sm {
            padding: 2px 8px;
            font-size: 11px;
        }
        .btn-primary {
            background-color: #428bca;
            border-color: #357ebd;
        }
        .btn-primary:hover {
            background-color: #3071a9;
            border-color: #285e8e;
        }
        .btn-outline-secondary {
            color: #333;
            background-color: #fff;
            border-color: #ccc;
        }
        .btn-outline-secondary:hover {
            background-color: #e6e6e6;
            color: #333;
            border-color: #adadad;
        }
        .input-group-text {
            font-size: 12px;
            padding: 0 6px;
            background-color: #f5f5f5;
            border: 1px solid #ccc;
            height: 28px;
        }
        .table {
            margin-bottom: 0;
            font-size: 12px;
        }
        .table th, .table td {
            padding: 6px;
            vertical-align: middle;
            border-color: #ddd;
        }
        .table thead th {
            background-color: #f5f5f5;
            font-weight: bold;
            border-bottom: 2px solid #ddd;
        }
        .table tfoot td {
            background-color: #f9f9f9;
            font-weight: bold;
        }
        .total-row {
            background-color: #f9f9f9;
        }
        .total-row td {
            font-weight: bold;
        }
        .required-field::after {
            content: " *";
            color: #d9534f;
        }
        .is-invalid {
            border-color: #d9534f;
        }
        .is-invalid:focus {
            border-color: #c9302c;
            box-shadow: 0 0 0 0.2rem rgba(217, 83, 79, 0.25);
        }
        .form-text {
            font-size: 11px;
            color: #777;
            margin-top: 3px;
        }
        .table-responsive {
            border: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <div class="form-header">
                <h4>Angel Stones - Order/Quote Form</h4>
            </div>
            
            <form id="orderQuoteForm" action="process_order_quote.php" method="POST" enctype="multipart/form-data">
                <!-- Sales Rep & Type Section -->
                <div class="form-section">
                    <div class="row g-2">
                        <div class="col-md-3">
                            <label for="salesRep" class="form-label required-field">Sales Rep</label>
                            <select class="form-select form-select-sm" id="salesRep" name="sales_rep" required>
                                <option value=""></option>
                                <option value="Martha">Martha</option>
                                <option value="Candiss">Candiss</option>
                                <option value="Mike">Mike</option>
                                <option value="Jeremy">Jeremy</option>
                                <option value="Angel">Angel</option>
                                <option value="Jim">Jim</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="formType" class="form-label required-field">Type</label>
                            <select class="form-select form-select-sm" id="formType" name="form_type" required>
                                <option value=""></option>
                                <option value="Order">Order</option>
                                <option value="Quote">Quote</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="poNumber" class="form-label">PO#</label>
                            <input type="text" class="form-control form-control-sm" id="poNumber" name="po_number">
                        </div>
                        <div class="col-md-3">
                            <label for="date" class="form-label required-field">Date</label>
                            <input type="date" class="form-control form-control-sm" id="date" name="date" required>
                        </div>
                    </div>
                </div>

                <!-- Customer Information Section -->
                <div class="form-section">
                    <h5>Customer Information</h5>
                    <div class="form-section-inner">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label for="customerName" class="form-label required-field">Customer Name</label>
                                <input type="text" class="form-control form-control-sm" id="customerName" name="customer_name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="customerCompany" class="form-label">Company</label>
                                <input type="text" class="form-control form-control-sm" id="customerCompany" name="customer_company">
                            </div>
                        </div>
                        <div class="row g-2 mt-1">
                            <div class="col-md-6">
                                <label for="customerEmail" class="form-label">Email</label>
                                <input type="email" class="form-control form-control-sm" id="customerEmail" name="customer_email">
                            </div>
                            <div class="col-md-6">
                                <label for="customerPhone" class="form-label required-field">Phone</label>
                                <input type="tel" class="form-control form-control-sm" id="customerPhone" name="customer_phone" required>
                            </div>
                        </div>
                        <div class="row g-2 mt-1">
                            <div class="col-12">
                                <label for="customerAddress" class="form-label">Address</label>
                                <input type="text" class="form-control form-control-sm" id="customerAddress" name="customer_address">
                            </div>
                        </div>
                        <div class="row g-2 mt-1">
                            <div class="col-md-4">
                                <label for="customerCity" class="form-label">City</label>
                                <input type="text" class="form-control form-control-sm" id="customerCity" name="customer_city">
                            </div>
                            <div class="col-md-4">
                                <label for="customerState" class="form-label">State</label>
                                <select class="form-select form-select-sm" id="customerState" name="customer_state">
                                    <option value=""></option>
                                    <option value="AL">Alabama</option>
                                    <option value="AK">Alaska</option>
                                    <option value="AZ">Arizona</option>
                                    <option value="AR">Arkansas</option>
                                    <option value="CA">California</option>
                                    <option value="CO">Colorado</option>
                                    <option value="CT">Connecticut</option>
                                    <option value="DE">Delaware</option>
                                    <option value="FL">Florida</option>
                                    <option value="GA">Georgia</option>
                                    <option value="HI">Hawaii</option>
                                    <option value="ID">Idaho</option>
                                    <option value="IL">Illinois</option>
                                    <option value="IN">Indiana</option>
                                    <option value="IA">Iowa</option>
                                    <option value="KS">Kansas</option>
                                    <option value="KY">Kentucky</option>
                                    <option value="LA">Louisiana</option>
                                    <option value="ME">Maine</option>
                                    <option value="MD">Maryland</option>
                                    <option value="MA">Massachusetts</option>
                                    <option value="MI">Michigan</option>
                                    <option value="MN">Minnesota</option>
                                    <option value="MS">Mississippi</option>
                                    <option value="MO">Missouri</option>
                                    <option value="MT">Montana</option>
                                    <option value="NE">Nebraska</option>
                                    <option value="NV">Nevada</option>
                                    <option value="NH">New Hampshire</option>
                                    <option value="NJ">New Jersey</option>
                                    <option value="NM">New Mexico</option>
                                    <option value="NY">New York</option>
                                    <option value="NC">North Carolina</option>
                                    <option value="ND">North Dakota</option>
                                    <option value="OH">Ohio</option>
                                    <option value="OK">Oklahoma</option>
                                    <option value="OR">Oregon</option>
                                    <option value="PA">Pennsylvania</option>
                                    <option value="RI">Rhode Island</option>
                                    <option value="SC">South Carolina</option>
                                    <option value="SD">South Dakota</option>
                                    <option value="TN">Tennessee</option>
                                    <option value="TX">Texas</option>
                                    <option value="UT">Utah</option>
                                    <option value="VT">Vermont</option>
                                    <option value="VA">Virginia</option>
                                    <option value="WA">Washington</option>
                                    <option value="WV">West Virginia</option>
                                    <option value="WI">Wisconsin</option>
                                    <option value="WY">Wyoming</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="customerZip" class="form-label">ZIP Code</label>
                                <input type="text" class="form-control form-control-sm" id="customerZip" name="customer_zip">
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Payment and Shipping Information -->
                <div class="form-section">
                    <h5>Payment & Shipping Details</h5>
                    <div class="form-section-inner">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label required-field">Payment Terms</label>
                                <div class="form-check">
                                    <input class="form-check-input payment-term" type="radio" name="payment_terms" id="payOnAck" value="Pay on ACK" required>
                                    <label class="form-check-label" for="payOnAck">Pay on ACK</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input payment-term" type="radio" name="payment_terms" id="fullPayment" value="FULL">
                                    <label class="form-check-label" for="fullPayment">FULL</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input payment-term" type="radio" name="payment_terms" id="oneThird" value="1/3rd">
                                    <label class="form-check-label" for="oneThird">1/3rd</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input payment-term" type="radio" name="payment_terms" id="oneHalf" value="1/2">
                                    <label class="form-check-label" for="oneHalf">1/2</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input payment-term" type="radio" name="payment_terms" id="cod" value="COD">
                                    <label class="form-check-label" for="cod">COD</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input payment-term" type="radio" name="payment_terms" id="payBeforeShipping" value="Pay before shipping">
                                    <label class="form-check-label" for="payBeforeShipping">Pay before shipping</label>
                                </div>
                                <div class="invalid-feedback">
                                    Please select a payment term.
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="firstOrder" name="first_order" value="1">
                                    <label class="form-check-label" for="firstOrder">First Order</label>
                                </div>
                                <div class="form-group mb-3">
                                    <label for="truckerInfo" class="form-label required-field">Trucker Information</label>
                                    <input type="text" class="form-control form-control-sm" id="truckerInfo" name="trucker_info" required>
                                    <div class="invalid-feedback">
                                        Please provide trucker information.
                                    </div>
                                </div>
                                <div class="form-group mb-3">
                                    <label for="termsDetails" class="form-label required-field">Terms</label>
                                    <textarea class="form-control form-control-sm" id="termsDetails" name="terms_details" rows="2" required></textarea>
                                    <div class="invalid-feedback">
                                        Please provide terms details.
                                    </div>
                                </div>
                                <div class="form-check mt-3">
                                    <input class="form-check-input" type="checkbox" id="sameAsBilling" name="same_as_billing" value="1">
                                    <label class="form-check-label" for="sameAsBilling">Ship to same as billing address</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Shipping Address Section (initially hidden, shows when needed) -->
                <div class="form-section" id="shippingAddressSection" style="display: none;">
                    <h5>Shipping Address</h5>
                    <div class="form-section-inner">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="shippingName" class="form-label">Name</label>
                                    <input type="text" class="form-control form-control-sm" id="shippingName">
                                </div>
                                <div class="mb-3">
                                    <label for="shippingCompany" class="form-label">Company</label>
                                    <input type="text" class="form-control form-control-sm" id="shippingCompany">
                                </div>
                                <div class="mb-3">
                                    <label for="shippingAddress1" class="form-label">Address Line 1</label>
                                    <input type="text" class="form-control form-control-sm" id="shippingAddress1">
                                </div>
                                <div class="mb-3">
                                    <label for="shippingAddress2" class="form-label">Address Line 2</label>
                                    <input type="text" class="form-control form-control-sm" id="shippingAddress2">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="shippingCity" class="form-label">City</label>
                                        <input type="text" class="form-control form-control-sm" id="shippingCity">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="shippingState" class="form-label">State</label>
                                        <select class="form-select form-select-sm" id="shippingState">
                                            <option value="">Select State</option>
                                            <!-- States will be populated by JavaScript -->
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="shippingZip" class="form-label">ZIP Code</label>
                                    <input type="text" class="form-control form-control-sm" id="shippingZip">
                                </div>
                                <div class="mb-3">
                                    <label for="shippingPhone" class="form-label">Phone</label>
                                    <input type="tel" class="form-control form-control-sm" id="shippingPhone">
                                </div>
                                <div class="mb-3">
                                    <label for="shippingEmail" class="form-label">Email</label>
                                    <input type="email" class="form-control form-control-sm" id="shippingEmail">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Product Information Section -->
                <div class="form-section">
                    <h5>Product Information</h5>
                    <div class="form-section-inner">
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm" id="productsTable">
                                <thead>
                                    <tr>
                                        <th width="5%" class="text-center">#</th>
                                        <th width="40%">Product Name/Code</th>
                                        <th width="15%" class="text-center">Quantity</th>
                                        <th width="20%" class="text-end">Price</th>
                                        <th width="15%" class="text-end">Total</th>
                                        <th width="5%" class="text-center">Remove</th>
                                    </tr>
                                </thead>
                                <tbody id="productContainer">
                                    <tr class="product-row">
                                <td class="text-center align-middle">1</td>
                                <td>
                                    <input type="text" class="form-control form-control-sm product-name mb-1" name="products[0][name]" placeholder="Product Name" required>
                                    
                                    <!-- Granite Color Dropdown -->
                                    <div class="mb-1">
                                        <select class="form-select form-select-sm granite-color" name="products[0][color]" required>
                                            <option value="">Select Granite Color</option>
                                            <option value="Absolute Black">Absolute Black</option>
                                            <option value="Alaska White">Alaska White</option>
                                            <option value="Black Galaxy">Black Galaxy</option>
                                            <option value="Blue Pearl">Blue Pearl</option>
                                            <option value="Colonial White">Colonial White</option>
                                            <option value="Costa Esmeralda">Costa Esmeralda</option>
                                            <option value="other">Other (Specify)</option>
                                        </select>
                                        <input type="text" class="form-control form-control-sm mt-1 d-none" name="products[0][custom_color]" placeholder="Enter custom color">
                                    </div>
                                    
                                    <!-- Product Type Checkboxes -->
                                    <div class="mb-2">
                                        <label class="form-label small mb-1 required-field">Product Type (Select at least one)</label>
                                        <div class="row g-2">
                                            <div class="col-6 col-md-4">
                                                <div class="form-check">
                                                    <input class="form-check-input product-type" type="checkbox" name="products[0][product_types][]" id="product_type_tablet_1" value="Tablet">
                                                    <label class="form-check-label small" for="product_type_tablet_1">Tablet</label>
                                                </div>
                                            </div>
                                            <div class="col-6 col-md-4">
                                                <div class="form-check">
                                                    <input class="form-check-input product-type" type="checkbox" name="products[0][product_types][]" id="product_type_slant_1" value="Slant Base">
                                                    <label class="form-check-label small" for="product_type_slant_1">Slant Base</label>
                                                </div>
                                            </div>
                                            <div class="col-6 col-md-4">
                                                <div class="form-check">
                                                    <input class="form-check-input product-type" type="checkbox" name="products[0][product_types][]" id="product_type_grass_1" value="Grass Marker">
                                                    <label class="form-check-label small" for="product_type_grass_1">Grass Marker</label>
                                                </div>
                                            </div>
                                            <div class="col-6 col-md-4">
                                                <div class="form-check">
                                                    <input class="form-check-input product-type" type="checkbox" name="products[0][product_types][]" id="product_type_hickey_1" value="Hickey">
                                                    <label class="form-check-label small" for="product_type_hickey_1">Hickey</label>
                                                </div>
                                            </div>
                                            <div class="col-6 col-md-4">
                                                <div class="form-check">
                                                    <input class="form-check-input product-type" type="checkbox" name="products[0][product_types][]" id="product_type_bench_1" value="Bench">
                                                    <label class="form-check-label small" for="product_type_bench_1">Bench</label>
                                                </div>
                                            </div>
                                            <div class="col-6 col-md-4">
                                                <div class="form-check">
                                                    <input class="form-check-input product-type" type="checkbox" name="products[0][product_types][]" id="product_type_vase_1" value="Vase">
                                                    <label class="form-check-label small" for="product_type_vase_1">Vase</label>
                                                </div>
                                            </div>
                                            <div class="col-6 col-md-4">
                                                <div class="form-check">
                                                    <input class="form-check-input product-type" type="checkbox" name="products[0][product_types][]" id="product_type_other_1" value="Other">
                                                    <label class="form-check-label small" for="product_type_other_1">Other</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="invalid-feedback">
                                            Please select at least one product type.
                                        </div>
                                    </div>

                                    <!-- Manufacturing Type -->
                                    <div class="mb-1">
                                        <label class="form-label small mb-1 required-field">Manufacturing Type</label>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input manufacturing-type" type="radio" name="products[0][manufacturing_type]" id="manufactured_1" value="manufactured" required>
                                            <label class="form-check-label" for="manufactured_1">Manufactured</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input manufacturing-type" type="radio" name="products[0][manufacturing_type]" id="prefinished_1" value="prefinished">
                                            <label class="form-check-label" for="prefinished_1">Prefinished</label>
                                        </div>
                                        <div class="invalid-feedback">
                                            Please select a manufacturing type.
                                        </div>
                                        
                                        <!-- Manufacturing Options (initially hidden) -->
                                        <div class="manufacturing-options mt-1" style="display: none;">
                                            <select class="form-select form-select-sm" name="products[0][manufacturing_option]">
                                                <!-- Options will be populated by JavaScript -->
                                            </select>
                                        </div>
                                        

                                        
                                        <!-- Side Section -->
                                        <div class="mt-3 border-top pt-2">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <h6 class="small fw-bold mb-0">SIDES</h6>
                                                <span class="badge bg-secondary side-count">0 Sides</span>
                                            </div>
                                            <div class="sides-container" data-product-index="0">
                                                <!-- Sides will be added here dynamically -->
                                            </div>
                                            <button type="button" class="btn btn-sm btn-outline-primary mt-2 add-side" data-product-index="0">
                                                <i class="bi bi-plus"></i> Add Side
                                            </button>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center align-middle">
                                    <input type="number" class="form-control form-control-sm quantity text-center" name="products[0][quantity]" min="1" value="1" required>
                                </td>
                                <td class="align-middle">
                                    <div class="input-group input-group-sm">
                                        <span class="input-text">$</span>
                                        <input type="number" step="0.01" class="form-control form-control-sm price text-end" name="products[0][price]">
                                    </div>
                                </td>
                                <td class="align-middle">
                                    <div class="input-group input-group-sm">
                                        <span class="input-text">$</span>
                                        <input type="text" class="form-control form-control-sm total text-end" name="products[0][total]" value="0.00" readonly>
                                    </div>
                                </td>
                                <td class="text-center align-middle">
                                    <button type="button" class="btn btn-link btn-sm text-danger remove-product p-0" title="Remove">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="6" class="text-end">
                                            <button type="button" class="btn btn-outline-primary btn-sm" id="addProduct">
                                                <i class="bi bi-plus-lg"></i> Add Product
                                            </button>
                                        </td>
                                    </tr>
                                    <tr class="total-row">
                                        <td colspan="4" class="text-end">Subtotal:</td>
                                        <td class="text-end"><span id="subtotal">$0.00</span></td>
                                        <td></td>
                                    </tr>
                                    <tr class="total-row">
                                        <td colspan="4" class="text-end">Additional Charges:</td>
                                        <td class="text-end"><span id="additionalChargesTotal">$0.00</span></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="text-end">Tax:</td>
                                        <td class="text-end">
                                            <div class="input-group input-group-sm">
                                                <input type="number" step="0.01" min="0" max="100" class="form-control form-control-sm text-end" id="taxRate" value="0.00">
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </td>
                                        <td class="text-end"><span id="tax">$0.00</span></td>
                                        <td></td>
                                    </tr>
                                    <tr class="total-row fw-bold">
                                        <td colspan="4" class="text-end">Total:</td>
                                        <td class="text-end"><span id="grandTotal">$0.00</span></td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                <!-- Notes Section -->
                <div class="form-section">
                    <h5>Additional Notes</h5>
                    <div class="form-section-inner">
                        <div class="mb-2">
                            <label for="notes" class="form-label">Special Instructions or Notes</label>
                            <textarea class="form-control form-control-sm" id="notes" name="notes" rows="3"></textarea>
                        </div>
                    </div>
                </div>

                <!-- File Upload Section -->
                <div class="form-section">
                    <h5>File Attachments</h5>
                    <div class="form-section-inner">
                        <div class="mb-3">
                            <label for="fileUploads" class="form-label">Upload Files (Images, PDFs, Excel files - Max 10MB total)</label>
                            <input class="form-control form-control-sm" type="file" id="fileUploads" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.gif,.pdf,.xls,.xlsx">
                            <div class="form-text">You can select multiple files. Allowed types: JPG, PNG, GIF, PDF, XLS, XLSX</div>
                        </div>
                        <div id="uploadPreview" class="d-flex flex-wrap gap-2 mt-2"></div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="d-flex justify-content-between mt-4">
                    <div>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="saveDraft">
                            <i class="bi bi-save"></i> Save as Draft
                        </button>
                    </div>
                    <div>
                        <button type="reset" class="btn btn-outline-secondary btn-sm me-2">
                            <i class="bi bi-x-lg"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bi bi-send"></i> <span id="submitBtnText">Submit</span>
                            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true" id="submitSpinner"></span>
                        </button>
                    </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery and Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // US States array for shipping state dropdown
        console.log('Script loaded successfully!');
        const usStates = [
            'AL', 'AK', 'AZ', 'AR', 'CA', 'CO', 'CT', 'DE', 'FL', 'GA',
            'HI', 'ID', 'IL', 'IN', 'IA', 'KS', 'KY', 'LA', 'ME', 'MD',
            'MA', 'MI', 'MN', 'MS', 'MO', 'MT', 'NE', 'NV', 'NH', 'NJ',
            'NM', 'NY', 'NC', 'ND', 'OH', 'OK', 'OR', 'PA', 'RI', 'SC',
            'SD', 'TN', 'TX', 'UT', 'VT', 'VA', 'WA', 'WV', 'WI', 'WY'
        ];

        $(document).ready(function() {
            // Populate shipping state dropdown
            const $shippingState = $('#shippingState');
            usStates.forEach(state => {
                $shippingState.append(`<option value="${state}">${state}</option>`);
            });

            // Function to update shipping address visibility and copy data if needed
            function updateShippingAddressVisibility() {
                const $shippingSection = $('#shippingAddressSection');
                const isSameAsBilling = $('#sameAsBilling').is(':checked');
                
                if (isSameAsBilling) {
                    $shippingSection.hide();
                    // Copy billing address to shipping address
                    $('#shippingName').val($('#customerName').val());
                    $('#shippingCompany').val($('#customerCompany').val());
                    $('#shippingAddress1').val($('#customerAddress').val());
                    $('#shippingAddress2').val('');
                    $('#shippingCity').val($('#customerCity').val());
                    $('#shippingState').val($('#customerState').val());
                    $('#shippingZip').val($('#customerZip').val());
                    $('#shippingPhone').val($('#customerPhone').val());
                    $('#shippingEmail').val($('#customerEmail').val());
                } else {
                    $shippingSection.show();
                }
            }

            // Initialize shipping address visibility on page load
            updateShippingAddressVisibility();
            
            // Handle same as billing address checkbox changes
            $('#sameAsBilling').change(updateShippingAddressVisibility);

            // Handle granite color selection (show/hide custom color input)
            $(document).on('change', '.granite-color', function() {
                const $customColorInput = $(this).closest('tr').find('input[name$="[custom_color]"]');
                if ($(this).val() === 'other') {
                    $customColorInput.removeClass('d-none').prop('required', true);
                } else {
                    $customColorInput.addClass('d-none').prop('required', false).val('');
                }
            });

            // Handle DIGITIZATION toggle
$(document).on('change', '.digitization-toggle', function() {
    const $field = $(this).closest('.form-check').find('.digitization-field');
    $field.toggle(this.checked);
    if (!this.checked) {
        $field.find('input[type="text"]').val('');
    }
});

// Initialize on page load
$(document).ready(function() {
    $('.digitization-toggle:checked').each(function() {
        $(this).closest('.form-check').find('.digitization-field').show();
    });
});

            // Handle manufacturing type selection
            $(document).on('change', '.manufacturing-type', function() {
                const $row = $(this).closest('tr');
                const $optionsContainer = $row.find('.manufacturing-options');
                const $select = $optionsContainer.find('select');
                
                // Clear existing options
                $select.empty();
                
                // Add options based on selection
                if ($(this).val() === 'manufactured') {
                    $select.append([
                        '<option value="">Select Manufacturing Option</option>',
                        '<option value="inhouse">In-House</option>',
                        '<option value="outsource">Outsource</option>',
                        '<option value="inventory">Inventory</option>'
                    ].join(''));
                } else if ($(this).val() === 'prefinished') {
                    $select.append([
                        '<option value="">Select Prefinished Option</option>',
                        '<option value="import">Import</option>',
                        '<option value="inventory">Inventory</option>',
                        '<option value="outsource">Outsource</option>'
                    ].join(''));
                }
                
                // Show/hide the options
                $optionsContainer.toggle(!!$(this).val());
                $select.prop('required', !!$(this).val());
            });
            
            // Initialize the first row's manufacturing type if needed
            $('.manufacturing-type:checked').trigger('change');

            // Handle product type validation
            $(document).on('change', '.product-type', function() {
                const $row = $(this).closest('tr');
                const $productTypeContainer = $row.find('.product-type').closest('.mb-2');
                
                // Check if at least one product type is selected
                const anyChecked = $row.find('.product-type:checked').length > 0;
                $productTypeContainer.toggleClass('was-validated', !anyChecked);
                
                // Remove validation classes when at least one is checked
                if (anyChecked) {
                    $productTypeContainer.removeClass('is-invalid');
                }
            });
            
            // Handle manufacturing type validation
            $(document).on('change', '.manufacturing-type', function() {
                const $row = $(this).closest('tr');
                const $manufacturingContainer = $row.find('.manufacturing-type').closest('.mb-1');
                
                // Remove validation class when a manufacturing type is selected
                $manufacturingContainer.removeClass('is-invalid');
            });
            
            // Form validation
            function validateProductRow($row) {
                let isValid = true;
                
                // Validate product types
                const $productTypeContainer = $row.find('.product-type').closest('.mb-2');
                const hasProductType = $row.find('.product-type:checked').length > 0;
                
                if (!hasProductType) {
                    $productTypeContainer.addClass('is-invalid');
                    isValid = false;
                } else {
                    $productTypeContainer.removeClass('is-invalid');
                }
                
                // Validate manufacturing type
                const $manufacturingContainer = $row.find('.manufacturing-type').closest('.mb-1');
                const hasManufacturingType = $row.find('.manufacturing-type:checked').length > 0;
                
                if (!hasManufacturingType) {
                    $manufacturingContainer.addClass('is-invalid');
                    isValid = false;
                } else {
                    $manufacturingContainer.removeClass('is-invalid');
                }
                
                return isValid;
            }

            // Validate payment terms (at least one must be selected)
            $('form').on('submit', function(e) {
                let isValid = true;
                
                // Check payment terms
                if ($('input[name="payment_terms"]:checked').length === 0) {
                    e.preventDefault();
                    $('.payment-term').addClass('is-invalid');
                    $('html, body').animate({
                        scrollTop: $('.payment-term').first().offset().top - 100
                    }, 500);
                    return false;
                }
                
                // Validate trucker info if required
                if ($('#truckerInfo').val().trim() === '') {
                    e.preventDefault();
                    $('#truckerInfo').addClass('is-invalid');
                    $('html, body').animate({
                        scrollTop: $('#truckerInfo').offset().top - 100
                    }, 500);
                    return false;
                }
                
                // Validate Terms field (required)
                if ($('#termsDetails').val().trim() === '') {
                    e.preventDefault();
                    $('#termsDetails').addClass('is-invalid');
                    $('html, body').animate({
                        scrollTop: $('#termsDetails').offset().top - 100
                    }, 500);
                    return false;
                }
                
                // Validate each product row
                $('.product-row').each(function() {
                    if (!validateProductRow($(this))) {
                        if (isValid) { // Only scroll to first error
                            e.preventDefault();
                            $('html, body').animate({
                                scrollTop: $(this).offset().top - 100
                            }, 500);
                            isValid = false;
                        }
                    }
                });
                
                if (!isValid) {
                    e.preventDefault();
                    return false;
                }
                
                return true;
            });
            
            // Remove invalid class when user interacts with required fields
            $('.payment-term').on('change', function() {
                $('.payment-term').removeClass('is-invalid');
            });
            
            $('#truckerInfo').on('input', function() {
                $(this).removeClass('is-invalid');
            });
            
            $('#termsDetails').on('input', function() {
                $(this).removeClass('is-invalid');
            });
            // Set today's date as default
            const today = new Date().toISOString().split('T')[0];
            $('#date').val(today);
            
            // Force recalculation of all rows and order totals on page load
            $('.product-row').each(function() {
                updateRowTotal($(this));
            });
            
            let productCount = 1;
            
            // Add new product row
            $('#addProduct').click(function() {
                productCount++;
                const newRow = `
                    <tr class="product-row">
                        <td class="text-center align-middle">${productCount}</td>
                        <td>
                            <input type="text" class="form-control form-control-sm product-name mb-1" name="products[${productCount-1}][name]" placeholder="Product Name" required>
                            
                            <!-- Granite Color Dropdown -->
                            <div class="mb-1">
                                <select class="form-select form-select-sm granite-color" name="products[${productCount-1}][color]" required>
                                    <option value="">Select Granite Color</option>
                                    <option value="Absolute Black">Absolute Black</option>
                                    <option value="Alaska White">Alaska White</option>
                                    <option value="Black Galaxy">Black Galaxy</option>
                                    <option value="Blue Pearl">Blue Pearl</option>
                                    <option value="Colonial White">Colonial White</option>
                                    <option value="Costa Esmeralda">Costa Esmeralda</option>
                                    <option value="other">Other (Specify)</option>
                                </select>
                                <input type="text" class="form-control form-control-sm mt-1 d-none" name="products[${productCount-1}][custom_color]" placeholder="Enter custom color">
                            </div>
                            
                            <!-- Product Type Checkboxes -->
                            <div class="mb-2 product-type-container">
                                <label class="form-label small mb-1 required-field">Product Type (Select at least one)</label>
                                <div class="row g-2">
                                    <div class="col-6 col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input product-type" type="checkbox" name="products[${productCount-1}][product_types][]" id="product_type_tablet_${productCount}" value="Tablet">
                                            <label class="form-check-label small" for="product_type_tablet_${productCount}">Tablet</label>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input product-type" type="checkbox" name="products[${productCount-1}][product_types][]" id="product_type_slant_${productCount}" value="Slant Base">
                                            <label class="form-check-label small" for="product_type_slant_${productCount}">Slant Base</label>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input product-type" type="checkbox" name="products[${productCount-1}][product_types][]" id="product_type_grass_${productCount}" value="Grass Marker">
                                            <label class="form-check-label small" for="product_type_grass_${productCount}">Grass Marker</label>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input product-type" type="checkbox" name="products[${productCount-1}][product_types][]" id="product_type_hickey_${productCount}" value="Hickey">
                                            <label class="form-check-label small" for="product_type_hickey_${productCount}">Hickey</label>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input product-type" type="checkbox" name="products[${productCount-1}][product_types][]" id="product_type_bench_${productCount}" value="Bench">
                                            <label class="form-check-label small" for="product_type_bench_${productCount}">Bench</label>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input product-type" type="checkbox" name="products[${productCount-1}][product_types][]" id="product_type_vase_${productCount}" value="Vase">
                                            <label class="form-check-label small" for="product_type_vase_${productCount}">Vase</label>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input product-type" type="checkbox" name="products[${productCount-1}][product_types][]" id="product_type_other_${productCount}" value="Other">
                                            <label class="form-check-label small" for="product_type_other_${productCount}">Other</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="invalid-feedback">
                                    Please select at least one product type.
                                </div>
                            </div>

                            <!-- Manufacturing Type -->
                            <div class="mb-1 manufacturing-type-container">
                                <label class="form-label small mb-1 required-field">Manufacturing Type</label>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input manufacturing-type" type="radio" name="products[${productCount-1}][manufacturing_type]" id="manufactured_${productCount}" value="manufactured" required>
                                    <label class="form-check-label" for="manufactured_${productCount}">Manufactured</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input manufacturing-type" type="radio" name="products[${productCount-1}][manufacturing_type]" id="prefinished_${productCount}" value="prefinished">
                                    <label class="form-check-label" for="prefinished_${productCount}">Prefinished</label>
                                </div>
                                <div class="invalid-feedback">
                                    Please select a manufacturing type.
                                </div>
                                
                                <!-- Manufacturing Options (initially hidden) -->
                                <div class="manufacturing-options mt-1" style="display: none;">
                                    <select class="form-select form-select-sm" name="products[${productCount-1}][manufacturing_option]" required>
                                        <option value="">Select an option</option>
                                    </select>
                                </div>
                                



                                <!-- Total Charges -->
<div class="mt-3 border-top pt-2">
    <div class="row">
        <div class="col-6">
            <label class="form-label fw-medium">Additional Charges:</label>
        </div>
        <div class="col-6 text-end">
            <span class="fw-bold">$<span class="additional-charges">0.00</span></span>
        </div>
    </div>
    <div class="row mt-1">
        <div class="col-6">
            <label class="form-label fw-medium">Product Total:</label>
        </div>
        <div class="col-6 text-end">
            <span class="fw-bold">$<span class="product-total">0.00</span></span>
        </div>
    </div>
    <div class="row mt-1">
        <div class="col-6">
            <label class="form-label fw-medium">Grand Total:</label>
        </div>
        <div class="col-6 text-end">
            <span class="fw-bold fs-5">$<span class="grand-total">0.00</span></span>
        </div>
    </div>
</div>
                                <!-- Side Section -->
                                <div class="mt-3 border-top pt-2">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 class="small fw-bold mb-0">SIDES</h6>
                                        <span class="badge bg-secondary side-count">0 Sides</span>
                                    </div>
                                    <div class="sides-container" data-product-index="${productCount-1}">
                                        <!-- Sides will be added here dynamically -->
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-primary mt-2 add-side" data-product-index="${productCount-1}">
                                        <i class="bi bi-plus"></i> Add Side
                                    </button>
                                </div>
                            </div>
                        </td>
                        <td class="text-center align-middle">
                            <input type="number" class="form-control form-control-sm quantity text-center" name="products[${productCount-1}][quantity]" min="1" value="1" required>
                        </td>
                        <td class="align-middle">
                            <div class="input-group input-group-sm">
                                <span class="input-text">$</span>
                                <input type="number" step="0.01" class="form-control form-control-sm price text-end" name="products[${productCount-1}][price]" required>
                            </div>
                        </td>
                        <td class="align-middle">
                            <div class="input-group input-group-sm">
                                <span class="input-text">$</span>
                                <input type="text" class="form-control form-control-sm total text-end" name="products[${productCount-1}][total]" value="0.00" readonly>
                            </div>
                        </td>
                        <td class="text-center align-middle">
                            <button type="button" class="btn btn-link btn-sm text-danger remove-product p-0" title="Remove">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>`;
                
                const $newRow = $(newRow);
                $('#productContainer').append($newRow);
                
                // Add event listeners to the new row
                $newRow.find('.quantity, .price').on('input', calculateRowTotal);
                
                // Initialize granite color change handler
                $newRow.find('.granite-color').on('change', function() {
                    const $customColorInput = $(this).closest('tr').find('input[name$="[custom_color]"]');
                    if ($(this).val() === 'other') {
                        $customColorInput.removeClass('d-none').prop('required', true);
                    } else {
                        $customColorInput.addClass('d-none').prop('required', false).val('');
                    }
                });
                
                // Initialize manufacturing type change handler
                $newRow.find('.manufacturing-type').on('change', function() {
                    const $row = $(this).closest('tr');
                    const $optionsContainer = $row.find('.manufacturing-options');
                    const $select = $optionsContainer.find('select');
                    
                    // Clear existing options
                    $select.empty().append('<option value="">Select an option</option>');
                    
                    // Add options based on selection
                    if ($(this).val() === 'manufactured') {
                        $select.append([
                            '<option value="inhouse">In-House</option>',
                            '<option value="outsource">Outsource</option>',
                            '<option value="inventory">Inventory</option>'
                        ].join(''));
                    } else if ($(this).val() === 'prefinished') {
                        $select.append([
                            '<option value="import">Import</option>',
                            '<option value="inventory">Inventory</option>',
                            '<option value="outsource">Outsource</option>'
                        ].join(''));
                    }
                    
                    // Show/hide the options
                    $optionsContainer.toggle(!!$(this).val());
                });
                
                // Initialize product type validation
                $newRow.find('.product-type').on('change', function() {
                    const $container = $(this).closest('.product-type-container');
                    const anyChecked = $container.find('.product-type:checked').length > 0;
                    
                    if (anyChecked) {
                        $container.removeClass('is-invalid');
                    }
                });
                
                // Initialize manufacturing type validation
                $newRow.find('.manufacturing-type').on('change', function() {
                    $(this).closest('.manufacturing-type-container').removeClass('is-invalid');
                    
                    // Trigger change to update manufacturing options
                    $(this).trigger('change');
                });
                
                // Initialize manufacturing options validation
                $newRow.find('.manufacturing-options select').on('change', function() {
                    if ($(this).val()) {
                        $(this).removeClass('is-invalid');
                    }
                });
            });
            
            // Update row numbers when rows are removed
            function updateRowNumbers() {
                $('.product-row').each(function(index) {
                    $(this).find('td:first').text(index + 1);
                    // Update the array indices in the name attributes
                    $(this).find('input, select').each(function() {
                        const name = $(this).attr('name');
                        if (name) {
                            $(this).attr('name', name.replace(/\[\d+\]/, '[' + index + ']'));
                        }
                    });
                });
                productCount = $('.product-row').length;
            }
            
            // Calculate row total when quantity or price changes
            $(document).on('input', '.quantity, .price', function() {
                calculateRowTotal.call(this);
            });
            
            function calculateRowTotal() {
                const row = $(this).closest('tr');
                const quantity = parseFloat(row.find('.quantity').val()) || 0;
                const price = parseFloat(row.find('.price').val()) || 0;
                const total = (quantity * price).toFixed(2);
                row.find('.total').val(total);
                calculateOrderTotals();
            }
            
            function calculateTotals() {
                // This is now just a wrapper for calculateOrderTotals for backwards compatibility
                calculateOrderTotals();
            }
            // Special etching charge calculator to ensure we get the right value
function calculateEtchingCharge($side) {
    // Try every possible selector for etching charge inputs
    let etchingCharge = 0;
    
    // First check standalone section
    if ($side.find('.side-etching-toggle:checked').length) {
        // Try multiple selector patterns to find the charge input
        const selectors = [
            '.side-etching-options input.side-etching-charge',
            '.side-etching-options input[type="number"]',
            'input[name*="[etching][charge]"]',
            '.side-etching-charge'
        ];
        
        for (const selector of selectors) {
            const input = $side.find(selector);
            if (input.length) {
                const val = parseFloat(input.val()) || 0;
                if (val > 0) {
                    etchingCharge += val;
                    console.log('Found etching charge with selector:', selector, val);
                }
            }
        }
    }
    
    // Then check sandblast & etching section
    if ($side.find('input[name*="sandblast_etching"][name*="etching"]:checked').length) {
        const selectors = [
            'input[name*="sandblast_etching"][name*="etching_charge"]',
            '.side-etching-charge',
            'input[type="number"][name*="etching_charge"]'
        ];
        
        for (const selector of selectors) {
            const input = $side.find(selector);
            if (input.length) {
                const val = parseFloat(input.val()) || 0;
                if (val > 0) {
                    etchingCharge += val;
                    console.log('Found sandblast etching charge with selector:', selector, val);
                }
            }
        }
    }
    
    return etchingCharge;
}

// Calculate additional charges for a row
function calculateAdditionalCharges($row) {
    let total = 0;
    
    // Add S/B CARVING charge if enabled
    if ($row.find('.sb-carving-toggle:checked').length) {
        total += parseFloat($row.find('.sb-carving-charge .form-control').val()) || 0;
    }
    
    // Add ETCHING charge if enabled
    if ($row.find('.etching-toggle:checked').length) {
        total += parseFloat($row.find('.etching-charge').val()) || 0;
    }
    
    // Add DEDO charge if enabled
    if ($row.find('.dedo-toggle:checked').length) {
        total += parseFloat($row.find('.dedo-charge .form-control').val()) || 0;
    }
    
    // Add DOMESTIC ADD ON charge if enabled
    if ($row.find('.domestic-addon-toggle:checked').length) {
        total += parseFloat($row.find('.domestic-addon-charge .form-control').val()) || 0;
    }
    
    // Add DIGITIZATION charge if enabled
    if ($row.find('.digitization-toggle:checked').length) {
        total += parseFloat($row.find('.digitization-charge .form-control').val()) || 0;
    }
    
    // Add all charges from sides
    $row.find('.side-card').each(function() {
        const $side = $(this);
        
        // Add S/B Carving charge for this side
        if ($side.find('.side-sb-toggle:checked').length) {
            total += parseFloat($side.find('.side-sb-charge').val()) || 0;
        }
        
        // Use the special etching charge calculator function instead of direct selectors
        const etchingCharge = calculateEtchingCharge($side);
        if (etchingCharge > 0) {
            console.log('Adding etching charge:', etchingCharge);
            total += etchingCharge;
            
            // Set data attribute for debugging
            $side.attr('data-total-etching-charge', etchingCharge);
        }
        
        // Add DEDO charge for this side
        if ($side.find('.side-dedo-toggle:checked').length) {
            total += parseFloat($side.find('.side-dedo-charge .form-control').val()) || 0;
        }
        
        // Add DOMESTIC ADD ON charge for this side
        if ($side.find('.side-domestic-toggle:checked').length) {
            total += parseFloat($side.find('.side-domestic-charge').val()) || 0;
        }
        
        // Add DIGITIZATION charge for this side
        if ($side.find('.side-digitization-toggle:checked').length) {
            total += parseFloat($side.find('.side-digitization-charge').val()) || 0;
        }
        
        // Add any misc charges for this side
        total += parseFloat($side.find('.side-misc-charge').val()) || 0;
    });
    
    return parseFloat(total.toFixed(2));
}

// Update row totals when any input changes
function updateRowTotal($row) {
    const quantity = parseFloat($row.find('.quantity').val()) || 0;
    const price = parseFloat($row.find('.price').val()) || 0;
    const additionalCharges = calculateAdditionalCharges($row);
    
    const subtotal = price * quantity;
    const grandTotal = subtotal + additionalCharges;
    
    // Update the total input field (displayed in the table)
    $row.find('.total').val(subtotal.toFixed(2));
    
    $row.find('.subtotal').text(subtotal.toFixed(2));
    $row.find('.additional-charges').text(additionalCharges.toFixed(2));
    $row.find('.product-total').text(subtotal.toFixed(2));
    $row.find('.grand-total').text(grandTotal.toFixed(2));
    
    // Update hidden input for form submission
    $row.find('input[name$="[total]"], input[name$="[total_amount]"]').val(grandTotal.toFixed(2));
    
    // Recalculate order totals
    calculateOrderTotals();
}

// Calculate order totals
function calculateOrderTotals() {
    let subtotal = 0;
    let additionalCharges = 0;
    
    // Debug info
    console.log('Calculating order totals for ' + $('.product-row').length + ' product rows');
    
    $('.product-row').each(function() {
        const $row = $(this);
        const quantity = parseFloat($row.find('.quantity').val()) || 0;
        const price = parseFloat($row.find('.price').val()) || 0;
        const rowSubtotal = quantity * price;
        
        // Debug info for each row
        console.log('Row calculation - Quantity:', quantity, 'Price:', price, 'Subtotal:', rowSubtotal);
        
        subtotal += rowSubtotal;
        additionalCharges += calculateAdditionalCharges($row); // Recalculate to ensure accuracy
    });
    
    // Debug total subtotal
    console.log('Final subtotal:', subtotal, 'Additional charges:', additionalCharges);
    
    const taxRate = parseFloat($('#taxRate').val()) || 0;
    const tax = (subtotal + additionalCharges) * (taxRate / 100);
    const grandTotal = subtotal + additionalCharges + tax;
    
    // Update the display values
    $('#subtotal').text('$' + subtotal.toFixed(2));
    $('#additionalChargesTotal').text('$' + additionalCharges.toFixed(2));
    $('#tax').text('$' + tax.toFixed(2));
    $('#grandTotal').text('$' + grandTotal.toFixed(2));
    
    // Also update any hidden fields for form submission
    $('input[name="subtotal"]').val(subtotal.toFixed(2));
    $('input[name="additional_charges_total"]').val(additionalCharges.toFixed(2));
    $('input[name="tax_amount"]').val(tax.toFixed(2));
    $('input[name="grand_total"]').val(grandTotal.toFixed(2));
}

// Update totals when any charge or price changes
$(document).on('input', '.sb-charge, .etching-charge, .dedo-charge, .domestic-charge, .digitization-charge, .side-charge, .side-etching-charge, .side-sb-charge, .side-domestic-charge, .side-misc-charge, .quantity, .price', function() {
    updateRowTotal($(this).closest('tr'));
});

// Immediate update when etching charge is entered (both standalone and in card section)
$(document).on('change input', 'input[name$="[etching][charge]"], input[name$="[sandblast_etching][etching_charge]"]', function() {
    updateRowTotal($(this).closest('tr'));
});

// Recalculate when radio buttons or checkboxes in etching sections are changed
$(document).on('change', 'input[name*="etching"][type="radio"], input[name*="etching"][type="checkbox"]', function() {
    updateRowTotal($(this).closest('tr'));
});

// Recalculate totals when tax rate changes
$(document).on('input', '#taxRate', function() {
    // Ensure tax rate is between 0 and 100
    let taxRate = parseFloat($(this).val());
    if (isNaN(taxRate) || taxRate < 0) {
        $(this).val('0.00');
    } else if (taxRate > 100) {
        $(this).val('100.00');
    }
    calculateOrderTotals();
});

// Update toggle handlers to show/hide charge fields
$(document).on('change', '.dedo-toggle', function() {
    const $row = $(this).closest('tr');
    const $chargeField = $row.find('.dedo-charge');
    $chargeField.toggle(this.checked);
    if (!this.checked) {
        $chargeField.val('');
    }
    updateRowTotal($row);
});

$(document).on('change', '.domestic-addon-toggle', function() {
    const $formCheck = $(this).closest('.form-check');
    const $fields = $formCheck.find('.domestic-addon-fields');
    $fields.toggle(this.checked);
    if (!this.checked) {
        $fields.find('input[type="text"], .domestic-charge').val('');
    }
    updateRowTotal($(this).closest('tr'));
});

$(document).on('change', '.digitization-toggle', function() {
    const $formCheck = $(this).closest('.form-check');
    const $chargeField = $formCheck.find('.digitization-charge');
    const $textField = $formCheck.find('.digitization-field');
    $chargeField.toggle(this.checked);
    $textField.toggle(this.checked);
    if (!this.checked) {
        $chargeField.val('');
        $textField.find('input').val('');
    }
    updateRowTotal($(this).closest('tr'));
});

// Initialize all toggles on page load
$(document).ready(function() {
    // Initialize DEDO toggles
    $('.dedo-toggle').each(function() {
        const $row = $(this).closest('tr');
        const $chargeField = $row.find('.dedo-charge');
        $chargeField.toggle($(this).is(':checked'));
    });
    
    // Initialize DOMESTIC ADD ON toggles
    $('.domestic-addon-toggle').each(function() {
        const $formCheck = $(this).closest('.form-check');
        $formCheck.find('.domestic-addon-fields').toggle($(this).is(':checked'));
    });
    
    // Initialize DIGITIZATION toggles
    $('.digitization-toggle').each(function() {
        const $formCheck = $(this).closest('.form-check');
        const isChecked = $(this).is(':checked');
        $formCheck.find('.digitization-charge, .digitization-field').toggle(isChecked);
    });
});

$(document).on('change', '.etching-toggle', function() {
    const $options = $(this).closest('.form-check').find('.etching-options');
    $options.toggle(this.checked);
    if (!this.checked) {
        $options.find('input[type="radio"]').prop('checked', false);
        $options.find('.etching-charge').val('');
    }
    updateRowTotal($(this).closest('tr'));
});

// Update existing toggle handlers
$(document).on('change', '.sb-carving-toggle', function() {
    const $options = $(this).closest('.form-check').find('.sb-carving-options');
    $options.toggle(this.checked);
    if (!this.checked) {
        $options.find('input[type="radio"]').prop('checked', false);
        $options.find('.sb-charge').val('');
    }
    updateRowTotal($(this).closest('tr'));
});

$(document).on('change', '.etching-toggle', function() {
    const $options = $(this).closest('.form-check').find('.etching-options');
    $options.toggle(this.checked);
    if (!this.checked) {
        $options.find('input[type="radio"]').prop('checked', false);
        $options.find('.etching-charge').val('');
    }
    updateRowTotal($(this).closest('tr'));
});

$(document).on('change', '.domestic-addon-toggle', function() {
    const $fields = $(this).closest('.form-check').find('.domestic-addon-fields');
    $fields.toggle(this.checked);
    if (!this.checked) {
        $fields.find('input[type="text"], .domestic-charge').val('');
    }
    updateRowTotal($(this).closest('tr'));
});
            
            // Update submit button text based on form type
            $('#formType').change(function() {
                const type = $(this).val();
                const submitText = type === 'Order' ? 'Place Order' : 'Request Quote';
                $('#submitBtnText').text(submitText);
            });
            
            // Initialize form type
            $('#formType').trigger('change');
            
            // Save as draft
            $('#saveDraft').click(function() {
                // Validate form
                if (validateForm()) {
                    // Show saving state
                    const $btn = $(this);
                    const originalText = $btn.html();
                    $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');
                    
                    // Simulate save delay
                    setTimeout(function() {
                        // Reset button
                        $btn.prop('disabled', false).html(originalText);
                        
                        // Show success message
                        const alert = '<div class="alert alert-success alert-dismissible fade show" role="alert">' +
                                    '<i class="bi bi-check-circle-fill me-2"></i> Draft saved successfully!' +
                                    '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                                    '</div>';
                        
                        $('.form-container').prepend(alert);
                        
                        // Auto-hide alert after 3 seconds
                        setTimeout(function() {
                            $('.alert').alert('close');
                        }, 3000);
                    }, 1000);
                }
            });
            
            // Form submission
            $('#orderQuoteForm').on('submit', function(e) {
                e.preventDefault();
                
                if (validateForm()) {
                    // Show loading state
                    const submitBtn = $(this).find('button[type="submit"]');
                    const submitBtnText = submitBtn.find('#submitBtnText');
                    const spinner = submitBtn.find('#submitSpinner');
                    
                    submitBtn.prop('disabled', true);
                    submitBtnText.addClass('d-none');
                    spinner.removeClass('d-none');
                    
                    // Simulate form submission (replace with actual AJAX call)
                    setTimeout(function() {
                        // Reset form state
                        submitBtn.prop('disabled', false);
                        submitBtnText.removeClass('d-none');
                        spinner.addClass('d-none');
                        
                        // Show success message
                        const alert = '<div class="alert alert-success alert-dismissible fade show" role="alert">' +
                                    '<i class="bi bi-check-circle-fill me-2"></i> ' + 
                                    (document.getElementById('formType').value === 'Order' ? 'Order' : 'Quote') + 
                                    ' submitted successfully!' +
                                    '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                                    '</div>';
                        
                        $('.form-container').prepend(alert);
                        
                        // Auto-hide alert after 5 seconds
                        setTimeout(function() {
                            $('.alert').alert('close');
                        }, 5000);
                        
                        // Reset form if needed
                        // $('#orderQuoteForm')[0].reset();
                        // calculateTotals();
                    }, 1500);
                }
            });
            
            // Form validation function
            function validateForm() {
                let isValid = true;
                $('.is-invalid').removeClass('is-invalid');

                // Validate required fields
                $('[required]').each(function() {
                    if (!$(this).val()) {
                        $(this).addClass('is-invalid');
                        isValid = false;
                    }
                });

                // Validate at least one product
                if ($('.product-row').length === 0) {
                    alert('Please add at least one product');
                    return false;
                }

                // Validate each product row
                $('.product-row').each(function() {
                    const $row = $(this);
                    
                    // Validate product name
                    if (!$row.find('.product-name').val()) {
                        $row.find('.product-name').addClass('is-invalid');
                        isValid = false;
                    }
                    
                    // Validate quantity
                    if (!$row.find('.quantity').val() || parseFloat($row.find('.quantity').val()) <= 0) {
                        $row.find('.quantity').addClass('is-invalid');
                        isValid = false;
                    }
                    
                    // Validate price
                    if (!$row.find('.price').val() || parseFloat($row.find('.price').val()) < 0) {
                        $row.find('.price').addClass('is-invalid');
                        isValid = false;
                    }
                    
                    // Validate granite color
                    if (!$row.find('.granite-color').val()) {
                        $row.find('.granite-color').addClass('is-invalid');
                        isValid = false;
                    }
                    
                    // Validate at least one product type is selected
                    if ($row.find('.product-type:checked').length === 0) {
                        $row.find('.product-type-container').addClass('is-invalid');
                        isValid = false;
                    }
                    
                    // Validate manufacturing type is selected
                    if (!$row.find('.manufacturing-type:checked').length) {
                        $row.find('.manufacturing-type-container').addClass('is-invalid');
                        isValid = false;
                    }
                    
                    // Validate manufacturing option if manufacturing type is selected
                    const $manufacturingType = $row.find('.manufacturing-type:checked').val();
                    if ($manufacturingType && !$row.find('.manufacturing-options select').val()) {
                        $row.find('.manufacturing-options').addClass('is-invalid');
                        isValid = false;
                    }
                });

                return isValid;
            }
            
            // Handle remove button clicks
            $(document).on('click', '.remove-product', function() {
                if ($('.product-row').length > 1) {
                    if (confirm('Are you sure you want to remove this product?')) {
                        $(this).closest('tr').remove();
                        updateRowNumbers();
                        calculateTotals();
                    }
                } else {
                    alert('You need to have at least one product in the order.');
                }
            });
            
            // Initialize calculations
            calculateTotals();
            
            // Handle S/B carving toggle
            $(document).on('change', '.sb-carving-toggle', function() {
                const $sbOptions = $(this).closest('.form-check').find('.sb-carving-options');
                $sbOptions.toggle(this.checked);
                
                // Clear selections if unchecked
                if (!this.checked) {
                    $sbOptions.find('input[type="radio"]').prop('checked', false);
                    $sbOptions.find('.sb-charge').val('');
                    updateRowTotal($(this).closest('tr'));
                }
            });
            
            // Initialize S/B carving toggles on page load
            $('.sb-carving-toggle').each(function() {
                if (this.checked) {
                    $(this).closest('.form-check').find('.sb-carving-options').show();
                }
            });
            
            // Handle etching toggle
            $(document).on('change', '.etching-toggle', function() {
                const $etchingOptions = $(this).closest('.form-check').find('.etching-options');
                $etchingOptions.toggle(this.checked);
                
                // Clear selections if unchecked
                if (!this.checked) {
                    $etchingOptions.find('input[type="radio"]').prop('checked', false);
                    $etchingOptions.find('input[type="checkbox"]').prop('checked', false);
                }
            });
            
            // Initialize etching toggles on page load
            $('.etching-toggle').each(function() {
                if (this.checked) {
                    $(this).closest('.form-check').find('.etching-options').show();
                }
            });
            
            // Handle Add Side button click
            $(document).on('click', '.add-side', function() {
                const productIndex = $(this).data('product-index');
                const $sidesContainer = $(this).siblings('.sides-container');
                const sideIndex = $sidesContainer.children('.side-card').length;
                
                const newSideHtml = getSideTemplate(productIndex, sideIndex);
                $sidesContainer.append(newSideHtml);
                
                // Update side count display
                updateSideCount($sidesContainer);
            });
            
            // Side template function to generate HTML for a new side
            function getSideTemplate(productIndex, sideIndex) {
                return `
                    <div class="side-card card mb-2">
                        <div class="card-header py-2 bg-light d-flex justify-content-between align-items-center">
                            <span class="fw-bold small">Side ${sideIndex + 1}</span>
                            <button type="button" class="btn btn-sm btn-outline-danger remove-side">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                        <div class="card-body py-2">
                            <!-- Side Note -->
                            <div class="mb-2">
                                <label class="form-label small mb-1">Side Notes</label>
                                <textarea class="form-control form-control-sm side-notes" name="products[${productIndex}][sides][${sideIndex}][notes]" rows="2"></textarea>
                            </div>
                                                        <!-- SANDBLAST and ETCHING Section -->
                                <div class="card mb-3">
                                    <div class="card-header py-2 bg-light">
                                        <h6 class="mb-0 fw-bold">SANDBLAST and ETCHING</h6>
                                    </div>
                                    <div class="card-body py-2">
                                        <div class="row g-2">
                                            <!-- Column 1: BLANK and SHAPE DRAWING -->
                                            <div class="col-md-4">
                                                <div class="form-check mb-1">
                                                    <input class="form-check-input" type="checkbox" id="side_blank_${productIndex}_${sideIndex}" name="products[${productIndex}][sides][${sideIndex}][sandblast_etching][blank]" value="1">
                                                    <label class="form-check-label" for="side_blank_${productIndex}_${sideIndex}">BLANK</label>
                                                </div>
                                                <div class="mb-2">
                                                    <div class="form-check mb-1">
                                                        <input class="form-check-input" type="checkbox" id="side_shape_drawing_${productIndex}_${sideIndex}" name="products[${productIndex}][sides][${sideIndex}][sandblast_etching][shape_drawing]" value="1">
                                                        <label class="form-check-label fw-medium" for="side_shape_drawing_${productIndex}_${sideIndex}">SHAPE DRAWING</label>
                                                    </div>
                                                    <div class="ms-4">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" id="side_shape_dealer_${productIndex}_${sideIndex}" name="products[${productIndex}][sides][${sideIndex}][sandblast_etching][shape_drawing_dealer]" value="1">
                                                            <label class="form-check-label" for="side_shape_dealer_${productIndex}_${sideIndex}">DEALER</label>
                                                        </div>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" id="side_shape_company_${productIndex}_${sideIndex}" name="products[${productIndex}][sides][${sideIndex}][sandblast_etching][shape_drawing_company]" value="1">
                                                            <label class="form-check-label" for="side_shape_company_${productIndex}_${sideIndex}">COMPANY</label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- Column 2: SANDBLAST -->
                                            <div class="col-md-4">
                                                <div class="form-check mb-1">
                                                    <input class="form-check-input" type="checkbox" id="side_sandblast_${productIndex}_${sideIndex}" name="products[${productIndex}][sides][${sideIndex}][sandblast_etching][sandblast]" value="1">
                                                    <label class="form-check-label fw-medium" for="side_sandblast_${productIndex}_${sideIndex}">SANDBLAST</label>
                                                </div>
                                                <div class="ms-4">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="side_company_drafting_${productIndex}_${sideIndex}" name="products[${productIndex}][sides][${sideIndex}][sandblast_etching][company_drafting]" value="1">
                                                        <label class="form-check-label" for="side_company_drafting_${productIndex}_${sideIndex}">COMPANY DRAFTING</label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="side_customer_drafting_${productIndex}_${sideIndex}" name="products[${productIndex}][sides][${sideIndex}][sandblast_etching][customer_drafting]" value="1">
                                                        <label class="form-check-label" for="side_customer_drafting_${productIndex}_${sideIndex}">CUSTOMER DRAFTING</label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="side_customer_stencil_${productIndex}_${sideIndex}" name="products[${productIndex}][sides][${sideIndex}][sandblast_etching][customer_stencil]" value="1">
                                                        <label class="form-check-label" for="side_customer_stencil_${productIndex}_${sideIndex}">CUSTOMER STENCIL</label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="side_sandblast_with_order_${productIndex}_${sideIndex}" name="products[${productIndex}][sides][${sideIndex}][sandblast_etching][sandblast_with_order]" value="1">
                                                        <label class="form-check-label" for="side_sandblast_with_order_${productIndex}_${sideIndex}">WITH ORDER</label>
                                                    </div>
                                                </div>
                                            </div>
                                             <!-- Column 3: ETCHING -->
                                             <div class="col-md-4">
                                                 <div class="form-check mb-1">
                                                     <input class="form-check-input side-etching-toggle" type="checkbox" id="side_etching_option_${productIndex}_${sideIndex}" name="products[${productIndex}][sides][${sideIndex}][sandblast_etching][etching]" value="1">
                                                     <label class="form-check-label fw-medium" for="side_etching_option_${productIndex}_${sideIndex}">ETCHING</label>
                                                 </div>
                                                 <div class="ms-4 side-etching-options" style="display: none;">
                                                     <div class="form-check">
                                                         <input class="form-check-input" type="radio" name="products[${productIndex}][sides][${sideIndex}][sandblast_etching][etching_type]" id="side_etching_bw_${productIndex}_${sideIndex}" value="B&W">
                                                         <label class="form-check-label" for="side_etching_bw_${productIndex}_${sideIndex}">B&W</label>
                                                     </div>
                                                     <div class="form-check">
                                                         <input class="form-check-input" type="radio" name="products[${productIndex}][sides][${sideIndex}][sandblast_etching][etching_type]" id="side_etching_color_${productIndex}_${sideIndex}" value="COLOR">
                                                         <label class="form-check-label" for="side_etching_color_${productIndex}_${sideIndex}">COLOR</label>
                                                     </div>
                                                     <div class="form-check">
                                                         <input class="form-check-input" type="radio" name="products[${productIndex}][sides][${sideIndex}][sandblast_etching][etching_type]" id="side_etching_hand_${productIndex}_${sideIndex}" value="HAND">
                                                         <label class="form-check-label" for="side_etching_hand_${productIndex}_${sideIndex}">HAND</label>
                                                     </div>
                                                     <div class="form-check">
                                                         <input class="form-check-input" type="radio" name="products[${productIndex}][sides][${sideIndex}][sandblast_etching][etching_type]" id="side_etching_laser_${productIndex}_${sideIndex}" value="LASER">
                                                         <label class="form-check-label" for="side_etching_laser_${productIndex}_${sideIndex}">LASER</label>
                                                     </div>
                                                     <div class="form-check">
                                                         <input class="form-check-input" type="checkbox" id="side_etching_with_order_${productIndex}_${sideIndex}" name="products[${productIndex}][sides][${sideIndex}][sandblast_etching][etching_with_order]" value="1">
                                                         <label class="form-check-label" for="side_etching_with_order_${productIndex}_${sideIndex}">WITH ORDER</label>
                                                     </div>
                                                     <div class="mt-2">
                                                         <input type="number" class="form-control form-control-sm side-charge side-etching-charge" name="products[${productIndex}][sides][${sideIndex}][sandblast_etching][etching_charge]" step="0.01" min="0" placeholder="$">
                                                     </div>
                                                 </div>
                                             </div>
                                        </div>
                                    </div>
                                </div>
                            
                            <!-- S/B CARVING -->
                                <div class="form-check mb-2 border-top pt-2">
                                    <input class="form-check-input side-sb-toggle" type="checkbox" id="side_sb_${productIndex}_${sideIndex}" name="products[${productIndex}][sides][${sideIndex}][sb_carving][enabled]">
                                    <label class="form-check-label fw-medium" for="side_sb_${productIndex}_${sideIndex}">S/B CARVING</label>
                                    <div class="side-sb-options ms-4 mt-1" style="display: none;">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="products[${productIndex}][sides][${sideIndex}][sb_carving][type]" id="side_sb_flat_${productIndex}_${sideIndex}" value="FLAT">
                                            <label class="form-check-label" for="side_sb_flat_${productIndex}_${sideIndex}">FLAT</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="products[${productIndex}][sides][${sideIndex}][sb_carving][type]" id="side_sb_shaped_${productIndex}_${sideIndex}" value="SHARPED">
                                            <label class="form-check-label" for="side_sb_shaped_${productIndex}_${sideIndex}">SHARPED</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="products[${productIndex}][sides][${sideIndex}][sb_carving][option]" id="side_sb_lettering_${productIndex}_${sideIndex}" value="LETTERING">
                                            <label class="form-check-label" for="side_sb_lettering_${productIndex}_${sideIndex}">LETTERING</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="products[${productIndex}][sides][${sideIndex}][sb_carving][option]" id="side_sb_rose_${productIndex}_${sideIndex}" value="ROSE">
                                            <label class="form-check-label" for="side_sb_rose_${productIndex}_${sideIndex}">ROSE</label>
                                        </div>
                                        <div class="mt-2">
                                            <input type="number" class="form-control form-control-sm side-charge side-sb-charge" name="products[${productIndex}][sides][${sideIndex}][sb_carving][charge]" step="0.01" min="0" placeholder="$">
                                        </div>
                                    </div>
                                </div>
                            
                            <!-- ETCHING -->
                            <div class="form-check mb-2 border-top pt-2">
                                <input class="form-check-input side-etching-toggle" type="checkbox" id="side_etching_${productIndex}_${sideIndex}" name="products[${productIndex}][sides][${sideIndex}][etching][enabled]">
                                <label class="form-check-label fw-medium" for="side_etching_${productIndex}_${sideIndex}">ETCHING</label>
                                <div class="side-etching-options ms-4 mt-1" style="display: none;">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="products[${productIndex}][sides][${sideIndex}][etching][type]" id="side_etching_bw_standalone_${productIndex}_${sideIndex}" value="B&W">
                                        <label class="form-check-label" for="side_etching_bw_standalone_${productIndex}_${sideIndex}">B&W</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="products[${productIndex}][sides][${sideIndex}][etching][type]" id="side_etching_color_standalone_${productIndex}_${sideIndex}" value="COLOR">
                                        <label class="form-check-label" for="side_etching_color_standalone_${productIndex}_${sideIndex}">COLOR</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="products[${productIndex}][sides][${sideIndex}][etching][type]" id="side_etching_hand_standalone_${productIndex}_${sideIndex}" value="HAND">
                                        <label class="form-check-label" for="side_etching_hand_standalone_${productIndex}_${sideIndex}">HAND</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="products[${productIndex}][sides][${sideIndex}][etching][type]" id="side_etching_laser_standalone_${productIndex}_${sideIndex}" value="LASER">
                                        <label class="form-check-label" for="side_etching_laser_standalone_${productIndex}_${sideIndex}">LASER</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="side_etching_with_order_standalone_${productIndex}_${sideIndex}" name="products[${productIndex}][sides][${sideIndex}][etching][with_order]" value="1">
                                        <label class="form-check-label" for="side_etching_with_order_standalone_${productIndex}_${sideIndex}">WITH ORDER</label>
                                    </div>
                                    <div class="mt-2">
                                        <input type="number" class="form-control form-control-sm side-charge side-etching-charge" name="products[${productIndex}][sides][${sideIndex}][etching][charge]" step="0.01" min="0" placeholder="$">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- DEDO -->
                            <div class="form-check mb-2 border-top pt-2">
                                <input class="form-check-input side-dedo-toggle" type="checkbox" id="side_dedo_${productIndex}_${sideIndex}" name="products[${productIndex}][sides][${sideIndex}][dedo][enabled]">
                                <label class="form-check-label fw-medium" for="side_dedo_${productIndex}_${sideIndex}">Recess & Mount DEDO</label>
                                <div class="mt-2 side-dedo-charge" style="display: none; width: 180px;">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">$</span>
                                        <input type="number" class="form-control form-control-sm side-charge" name="products[${productIndex}][sides][${sideIndex}][dedo][charge]" step="0.01" min="0">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- DOMESTIC ADD ON -->
                            <div class="form-check mb-2 border-top pt-2">
                                <input class="form-check-input side-domestic-toggle" type="checkbox" id="side_domestic_${productIndex}_${sideIndex}" name="products[${productIndex}][sides][${sideIndex}][domestic_addon][enabled]">
                                <label class="form-check-label fw-medium" for="side_domestic_${productIndex}_${sideIndex}">DOMESTIC ADD ON</label>
                                <div class="side-domestic-fields ps-4 mt-2" style="display: none;">
                                    <div class="row g-2 mb-2">
                                        <div class="col-6">
                                            <label class="form-label small mb-0">(1)</label>
                                            <input type="text" class="form-control form-control-sm" name="products[${productIndex}][sides][${sideIndex}][domestic_addon][field1]">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small mb-0">(2)</label>
                                            <input type="text" class="form-control form-control-sm" name="products[${productIndex}][sides][${sideIndex}][domestic_addon][field2]">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <label class="form-label small mb-0">DOMESTIC ADD ON Charge ($)</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text">$</span>
                                                <input type="number" class="form-control form-control-sm side-charge side-domestic-charge" name="products[${productIndex}][sides][${sideIndex}][domestic_addon][charge]" step="0.01" min="0">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- DIGITIZATION -->
                            <div class="form-check mb-2 border-top pt-2">
                                <input class="form-check-input side-digitization-toggle" type="checkbox" id="side_digitization_${productIndex}_${sideIndex}" name="products[${productIndex}][sides][${sideIndex}][digitization][enabled]">
                                <label class="form-check-label fw-medium" for="side_digitization_${productIndex}_${sideIndex}">DIGITIZATION</label>
                                <div class="mt-2 side-digitization-charge" style="display: none; width: 180px;">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">$</span>
                                        <input type="number" class="form-control form-control-sm side-charge" name="products[${productIndex}][sides][${sideIndex}][digitization][charge]" step="0.01" min="0">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- CHARGES ($) -->
                            <div class="mb-2 border-top pt-2">
                                <div class="row">
                                    <div class="col-6">
                                        <label class="form-label small fw-medium">CHARGES ($)</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">$</span>
                                            <input type="number" class="form-control side-charge side-misc-charge" name="products[${productIndex}][sides][${sideIndex}][misc_charge]" step="0.01" min="0">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }
            
            // Handle remove side button click
            $(document).on('click', '.remove-side', function() {
                const $sideCard = $(this).closest('.side-card');
                const $sidesContainer = $sideCard.closest('.sides-container');
                $sideCard.remove();
                updateSideCount($sidesContainer);
                
                // Renumber remaining sides
                $sidesContainer.find('.side-card').each(function(index) {
                    $(this).find('h6').text(`Side ${index + 1}`);
                });
            });
            
            // Update side count badge
            function updateSideCount($sidesContainer) {
                const count = $sidesContainer.children('.side-card').length;
                $sidesContainer.siblings('.d-flex').find('.side-count')
                    .text(count === 1 ? '1 Side' : `${count} Sides`)
                    .toggleClass('bg-secondary', count === 0)
                    .toggleClass('bg-primary', count > 0);
                
                // Update the product row total when sides are added or removed
                updateRowTotal($sidesContainer.closest('tr'));
            }
            
            // Handle S/B Carving toggle
            $(document).on('change', '.sb-carving-toggle', function() {
                const $options = $(this).closest('.form-check').find('.sb-carving-options');
                $options.toggle(this.checked);
                if (!this.checked) {
                    $options.find('input[type="radio"]').prop('checked', false);
                }
            });

            // Handle ETCHING toggle
            $(document).on('change', '.etching-toggle', function() {
                const $options = $(this).closest('.form-check').find('.etching-options');
                $options.toggle(this.checked);
                if (!this.checked) {
                    $options.find('input[type="radio"]').prop('checked', false);
                }
            });

            // Handle DOMESTIC ADD ON toggle
            $(document).on('change', '.domestic-addon-toggle', function() {
                const $fields = $(this).closest('.form-check').find('.domestic-addon-fields');
                $fields.toggle(this.checked);
                if (!this.checked) {
                    $fields.find('input[type="text"]').val('');
                }
            });
            
            // Handle side-specific toggles
            
            // Side S/B CARVING toggle
            $(document).on('change', '.side-sb-toggle', function() {
                const $options = $(this).closest('.form-check').find('.side-sb-options');
                $options.toggle(this.checked);
                if (!this.checked) {
                    // Clear values when unchecked
                    $options.find('input[type="radio"]').prop('checked', false);
                    $options.find('.side-sb-charge').val('');
                }
                // Update totals when toggling
                updateRowTotal($(this).closest('tr'));
            });
            
            // Side ETCHING toggle - for the new ETCHING section
            $(document).on('change', '.side-etching-toggle', function() {
                const $options = $(this).closest('.form-check').find('.side-etching-options');
                $options.toggle(this.checked);
                if (!this.checked) {
                    // Clear values when unchecked
                    $options.find('input[type="radio"]').prop('checked', false);
                    $options.find('.side-etching-charge').val('');
                }
                // Update totals when toggling
                updateRowTotal($(this).closest('tr'));
            });
            
            // Side DEDO toggle
            $(document).on('change', '.side-dedo-toggle', function() {
                const $options = $(this).closest('.form-check').find('.side-dedo-charge');
                $options.toggle(this.checked);
                if (!this.checked) {
                    $options.find('input').val('');
                }
                // Update totals when toggling
                updateRowTotal($(this).closest('tr'));
            });
            
            // Side ETCHING toggle
            $(document).on('change', '.side-etching-toggle', function() {
                const $chargeField = $(this).closest('.form-check').find('.side-etching-charge');
                $chargeField.toggle(this.checked);
                if (!this.checked) {
                    $chargeField.find('input').val('');
                }
                updateRowTotal($(this).closest('tr'));
            });
            
            // Side DEDO toggle
            $(document).on('change', '.side-dedo-toggle', function() {
                const $chargeField = $(this).closest('.form-check').find('.side-dedo-charge');
                $chargeField.toggle(this.checked);
                if (!this.checked) {
                    $chargeField.find('input').val('');
                }
                updateRowTotal($(this).closest('tr'));
            });
            
            // Side DOMESTIC ADD ON toggle
            $(document).on('change', '.side-domestic-toggle', function() {
                const $fields = $(this).closest('.form-check').find('.side-domestic-fields');
                $fields.toggle(this.checked);
                if (!this.checked) {
                    $fields.find('input[type="text"]').val('');
                    $fields.find('.side-domestic-charge').val('');
                }
                updateRowTotal($(this).closest('tr'));
            });
            
            // Side DIGITIZATION toggle
            $(document).on('change', '.side-digitization-toggle', function() {
                const $fields = $(this).closest('.form-check').find('.side-digitization-fields');
                $fields.toggle(this.checked);
                if (!this.checked) {
                    $fields.find('input[type="text"]').val('');
                    $fields.find('.side-digitization-charge').val('');
                }
                updateRowTotal($(this).closest('tr'));
            });
            
            // Initialize side charge inputs to update totals on change
            $(document).on('input', '.side-charge', function() {
                updateRowTotal($(this).closest('tr'));
            });

            // Initialize toggles and calculate totals on page load
            $(document).ready(function() {
                // Force recalculate all totals on page load
                $('.product-row').each(function() {
                    updateRowTotal($(this));
                });
                calculateOrderTotals();
                
                // Set up special binding for etching charge inputs to ensure they update totals
                $(document).on('input change keyup', '.side-etching-charge', function() {
                    console.log('Etching charge updated to: ' + $(this).val());
                    updateRowTotal($(this).closest('tr'));
                });
                
                // File upload preview and validation
                $('#fileUploads').on('change', function(e) {
                    const files = e.target.files;
                    const $preview = $('#uploadPreview');
                    $preview.empty();
                    
                    // Check total file size (max 10MB)
                    let totalSize = 0;
                    let invalidFiles = [];
                    const allowedTypes = [
                        'image/jpeg', 'image/png', 'image/gif', 
                        'application/pdf', 
                        'application/vnd.ms-excel', 
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                    ];
                    
                    // Validate files
                    Array.from(files).forEach(file => {
                        totalSize += file.size;
                        if (!allowedTypes.includes(file.type)) {
                            invalidFiles.push(file.name);
                        }
                    });
                    
                    // Show warnings if needed
                    if (totalSize > 10 * 1024 * 1024) {
                        alert('Total file size exceeds 10MB limit. Please reduce the number or size of files.');
                        $(this).val(''); // Clear the input
                        return;
                    }
                    
                    if (invalidFiles.length > 0) {
                        alert('The following files have invalid types: ' + invalidFiles.join(', ') + '\nOnly JPG, PNG, GIF, PDF, XLS and XLSX files are allowed.');
                        $(this).val(''); // Clear the input
                        return;
                    }
                    
                    // Show previews for valid files
                    Array.from(files).forEach(file => {
                        const $item = $('<div class="border rounded p-2 text-center" style="width: 100px;"></div>');
                        
                        if (file.type.startsWith('image/')) {
                            const reader = new FileReader();
                            reader.onload = function(e) {
                                $item.append(`<img src="${e.target.result}" class="img-fluid mb-1" style="max-height: 60px;">`)
                                     .append(`<div class="small text-truncate">${file.name}</div>`);
                            };
                            reader.readAsDataURL(file);
                        } else {
                            let icon = 'bi-file-earmark';
                            if (file.type === 'application/pdf') icon = 'bi-file-earmark-pdf';
                            if (file.type.includes('excel') || file.type.includes('spreadsheet')) icon = 'bi-file-earmark-excel';
                            
                            $item.append(`<i class="bi ${icon} fs-2"></i>`)
                                 .append(`<div class="small text-truncate">${file.name}</div>`);
                        }
                        
                        $preview.append($item);
                    });
                });
            });
            
            // Initialize toggles on page load
            function initializeToggles() {
                // All product-level charge toggles have been removed in favor of side-level toggles
                
                // Initialize side level toggles
                $('.side-sb-toggle').each(function() {
                    if (this.checked) {
                        $(this).closest('.form-check').find('.side-sb-options').show();
                    }
                });
                
                $('.side-etching-toggle').each(function() {
                    if (this.checked) {
                        $(this).closest('.form-check').find('.side-etching-options').show();
                    }
                });
                
                // Force recalculation for any existing values
                $('.side-etching-charge').each(function() {
                    if($(this).val()) {
                        updateRowTotal($(this).closest('tr'));
                    }
                });
                
                $('.side-dedo-toggle').each(function() {
                    if (this.checked) {
                        $(this).closest('.form-check').find('.side-dedo-charge').show();
                    }
                });
                
                $('.side-digitization-toggle').each(function() {
                    if (this.checked) {
                        $(this).closest('.form-check').find('.side-digitization-charge').show();
                    }
                });
                
                $('.side-domestic-toggle').each(function() {
                    if (this.checked) {
                        $(this).closest('.form-check').find('.side-domestic-fields').show();
                    }
                });
                
                // Note: side-digitization-toggle is already handled above
                
                // Update all row totals on page load
                $('.product-row').each(function() {
                    updateRowTotal($(this));
                });
            }

            // Call initialize on document ready
            $(document).ready(initializeToggles);
            
            // Form submission handler
            $('#orderQuoteForm').on('submit', function(e) {
                e.preventDefault(); // Prevent default submission
                
                console.log('Form submission started');
                
                // Show loading state
                $('#submitBtnText').text('Submitting...');
                $('#submitSpinner').removeClass('d-none');
                
                // Get form data including files
                const formData = new FormData(this);
                
                // Log what's being submitted for debugging
                console.log('Form data being submitted:', formData);
                
                // Submit via AJAX
                $.ajax({
                    url: 'process_order_quote.php',
                    type: 'POST',
                    data: formData,
                    processData: false,  // Don't process the data
                    contentType: false,  // Don't set content type
                    success: function(response) {
                        console.log('Response received:', response);
                        
                        // Reset loading state
                        $('#submitBtnText').text('Submit');
                        $('#submitSpinner').addClass('d-none');
                        
                        if (response.status === 'success') {
                            // Show success message
                            alert(response.message);
                            
                            // Optionally reset form or redirect
                            // $('#orderQuoteForm')[0].reset();
                        } else {
                            // Show error message
                            alert('Error: ' + (response.message || 'An unknown error occurred'));
                            
                            // Log detailed debug info if available
                            if (response.debug_info) {
                                console.log('Debug info:', response.debug_info);
                            }
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', status, error);
                        $('#submitBtnText').text('Submit');
                        $('#submitSpinner').addClass('d-none');
                        alert('Error submitting form: ' + error);
                    }
                });
            });
        });
    </script>
</body>
</html>
