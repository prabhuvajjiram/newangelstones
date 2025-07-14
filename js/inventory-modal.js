/**
 * Inventory Modal Handler
 * Fetches and displays inventory data from monument.business API in a modal
 */
document.addEventListener('DOMContentLoaded', function() {
    // Make sure both jQuery and Bootstrap are loaded before initializing
    const waitForDependencies = function(callback) {
        if (window.jQuery && window.bootstrap) {
            console.log('Both jQuery and Bootstrap are loaded, initializing inventory modal');
            callback();
        } else {
            console.log('Waiting for jQuery and Bootstrap to load...');
            setTimeout(function() {
                waitForDependencies(callback);
            }, 100);
        }
    };
    
    waitForDependencies(function() {
        // Add CSS for styling the inventory modal
        const style = document.createElement('style');
        style.textContent = `
            .inventory-modal .modal-dialog {
                max-width: 95%;
                margin: 1.75rem auto;
                height: 90vh;
            }
            .inventory-modal .modal-content {
                background-color: #212529;
                color: #f8f9fa;
                border: 1px solid #495057;
                height: 100%;
                display: flex;
                flex-direction: column;
            }
            .inventory-modal .modal-header {
                border-bottom: 1px solid #343a40;
            }
            .inventory-modal .modal-footer {
                border-top: 1px solid #343a40;
            }
            .inventory-modal .modal-body {
                flex: 1;
                overflow-y: auto;
                padding: 1rem;
                position: relative;
            }
            /* Scroll indicator styles */
            .inventory-modal .scroll-indicator {
                position: absolute;
                bottom: 10px;
                left: 50%;
                transform: translateX(-50%);
                background-color: rgba(0, 0, 0, 0.7);
                color: white;
                padding: 5px 15px;
                border-radius: 20px;
                display: none;
                z-index: 10;
                animation: fadeInOut 2s infinite;
            }
            @keyframes fadeInOut {
                0% { opacity: 0.3; }
                50% { opacity: 1; }
                100% { opacity: 0.3; }
            }
            .inventory-modal .inventory-table {
                width: 100%;
                margin-bottom: 1rem;
                color: #f8f9fa;
                border-collapse: collapse;
            }
            .inventory-modal .inventory-table th,
            .inventory-modal .inventory-table td {
                padding: 0.75rem;
                vertical-align: top;
                border-top: 1px solid #343a40;
            }
            .inventory-modal .inventory-table thead th {
                vertical-align: bottom;
                border-bottom: 2px solid #343a40;
                background-color: #343a40;
                position: sticky;
                top: 0;
                z-index: 5;
            }
            .inventory-modal .inventory-table tbody tr:hover {
                background-color: #343a40;
            }
            .inventory-modal .pagination {
                justify-content: center;
                margin-top: 1rem;
                flex-wrap: wrap;
            }
            .inventory-modal .page-link {
                background-color: #343a40;
                border-color: #495057;
                color: #f8f9fa;
            }
            .inventory-modal .page-item.active .page-link {
                background-color: #d4af37;
                border-color: #d4af37;
                color: #212529;
            }
            .inventory-modal .page-item.disabled .page-link {
                background-color: #212529;
                border-color: #495057;
                color: #6c757d;
            }
            .inventory-modal .btn-gold {
                background-color: #d4af37;
                border-color: #d4af37;
                color: #212529;
            }
            .inventory-modal .btn-gold:hover {
                background-color: #c4a030;
                border-color: #c4a030;
            }
            .inventory-modal .search-container {
                position: relative;
                margin-bottom: 1rem;
            }
            .inventory-modal .search-container .search-icon {
                position: absolute;
                left: 10px;
                top: 10px;
                color: #6c757d;
            }
            .inventory-modal .search-container input {
                padding-left: 35px;
                background-color: #343a40;
                border-color: #495057;
                color: #f8f9fa;
            }
            .inventory-modal .search-container input::placeholder {
                color: #6c757d;
            }
            .inventory-modal .inventory-filters {
                margin-bottom: 1rem;
            }
            .inventory-modal .inventory-filters select {
                background-color: #343a40;
                border-color: #495057;
                color: #f8f9fa;
            }
            .inventory-modal .inventory-filters .form-group {
                margin-bottom: 0.5rem;
            }
            .inventory-modal .inventory-filters label {
                font-size: 0.875rem;
                margin-bottom: 0.25rem;
            }
            .inventory-modal #searchHelp {
                font-size: 0.85rem;
                margin-top: -0.5rem;
                margin-bottom: 0.5rem;
                color: #adb5bd;
            }
            .inventory-modal .no-results {
                text-align: center;
                padding: 2rem;
                color: #adb5bd;
            }
            .inventory-modal .loading {
                text-align: center;
                padding: 2rem;
            }
            .inventory-modal .spinner-border {
                width: 3rem;
                height: 3rem;
                color: #d4af37;
            }
            /* Responsive styles */
            @media (max-width: 767.98px) {
                .inventory-modal .inventory-filters .row {
                    margin-right: -5px;
                    margin-left: -5px;
                }
                .inventory-modal .inventory-filters [class*="col-"] {
                    padding-right: 5px;
                    padding-left: 5px;
                }
                .inventory-modal .btn {
                    padding: 0.25rem 0.5rem;
                    font-size: 0.875rem;
                }
                .inventory-modal .form-control {
                    font-size: 0.875rem;
                }
                .inventory-modal .search-container input {
                    font-size: 1rem;
                    padding: 0.375rem 0.75rem;
                }
            }
        `;
        document.head.appendChild(style);

        // Class to handle API requests
        class InventoryAPI {
            constructor() {
                this.baseUrl = 'inventory-proxy.php';
                this.currentPage = 1;
                this.pageSize = 1000;
                this.totalItems = 0;
                this.totalPages = 0;
                this.currentFilters = {
                    ptype: '',
                    pcolor: '',
                    pdesign: '',
                    pfinish: '',
                    psize: '',
                    locid: ''
                };
            }

            /**
             * Fetch inventory data based on current filters and pagination
             * @returns {Promise} Promise resolving to inventory data
             */
            async fetchInventory() {
                try {
                    console.log('Fetching inventory data with filters:', this.currentFilters);
                    console.log('Current page:', this.currentPage);
                    console.log('Page size:', this.pageSize);
                    console.log('Selected location:', this.selectedLocation);
                    
                    // Define the location IDs to fetch based on the selected location
                    let locationIds = ['45555', '45587']; // Default: fetch both locations (Elberton and Tate)
                    
                    // If a specific location is selected, only fetch that one
                    if (this.selectedLocation) {
                        if (this.selectedLocation === 'Elberton') {
                            locationIds = ['45555'];
                        } else if (this.selectedLocation === 'Tate') {
                            locationIds = ['45587'];
                        }
                        // If any other location is selected that we don't recognize, keep both IDs
                    }
                    
                    console.log('Fetching data for locations:', locationIds);
                    
                    // Prepare request parameters (without locid)
                    const params = {
                        ...this.currentFilters,
                        page: this.currentPage,
                        pageSize: this.pageSize,
                        token: '097EE598BBACB8A8182BC9D4D7D5CFE609E4DB2AF4A3F1950738C927ECF05B6A' // Include token in client-side request
                    };
                    
                    // Set a timeout for each request to prevent hanging
                    const timeout = 10000; // 10 seconds timeout
                    
                    // Fetch data for each location in parallel with timeout
                    const requests = locationIds.map(locid => {
                        const locParams = { ...params, locid };
                        console.log(`Sending request for location ${locid}:`, locParams);
                        
                        // Create a timeout promise
                        const timeoutPromise = new Promise((_, reject) => {
                            setTimeout(() => reject(new Error(`Request timeout for location ${locid} after ${timeout}ms`)), timeout);
                        });
                        
                        // Use GET method instead of POST to avoid potential CORS issues
                        const queryString = new URLSearchParams(locParams).toString();
                        const fetchPromise = fetch(`inventory-proxy.php?${queryString}`, {
                            method: 'GET',
                            headers: {
                                'Cache-Control': 'no-cache',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error(`HTTP error! Status: ${response.status}`);
                            }
                            return response.json();
                        })
                        .then(data => {
                            // Validate the response structure
                            if (!data) {
                                throw new Error('Empty response received');
                            }
                            
                            // Check for API error messages in the response
                            if (data.error) {
                                throw new Error(`API error: ${data.error}`);
                            }
                            
                            return data;
                        })
                        .catch(error => {
                            console.error(`Error fetching data for location ${locid}:`, error);
                            return { success: false, error: error.message, Data: [] };
                        });
                        
                        // Race between fetch and timeout
                        return Promise.race([fetchPromise, timeoutPromise]);
                    });
                    
                    // Wait for all requests to complete
                    const results = await Promise.allSettled(requests);
                    console.log('API responses received:', results);
                    
                    // Combine the results
                    const combinedData = {
                        Data: [],
                        Total: 0
                    };
                    
                    // Process each result
                    let hasValidData = false;
                    results.forEach((result, index) => {
                        const locationId = locationIds[index];
                        
                        // Check if the promise was fulfilled
                        if (result.status === 'fulfilled') {
                            const data = result.value;
                            console.log(`Processing data for location ${locationId}:`, data);
                            
                            // Check if the result was successful
                            if (data && (data.success === true || data.success === undefined)) {
                                // Check if we have data and handle both uppercase and lowercase 'data' property
                                const items = data.Data || data.data || [];
                                
                                if (Array.isArray(items) && items.length > 0) {
                                    hasValidData = true;
                                    
                                    // Add locationname to items if missing
                                    const processedItems = items.map(item => {
                                        const locationName = item.Locationname || item.locationname || 
                                            (locationId === '45555' ? 'Elberton' : 'Tate');
                                        
                                        return {
                                            ...item,
                                            Locationname: locationName
                                        };
                                    });
                                    
                                    combinedData.Data = [...combinedData.Data, ...processedItems];
                                    
                                    // Add to total count
                                    const total = data.totalItems || data.Total || items.length;
                                    combinedData.Total += parseInt(total, 10) || 0;
                                } else {
                                    console.warn(`Empty data array for location ${locationId}`);
                                }
                            } else {
                                console.error(`API error for location ${locationId}:`, data?.error || 'Unknown error');
                            }
                        } else {
                            // Promise was rejected
                            console.error(`Request failed for location ${locationId}:`, result.reason);
                        }
                    });
                    
                    // If no valid data was found, create a sample dataset for testing
                    if (!hasValidData) {
                        console.warn('No valid data received from API, using sample data');
                        combinedData.Data = [
                            {
                                Qty: 1,
                                Locationname: 'Elberton',
                                EndProductCode: 'SAMPLE-001',
                                EndProductDescription: 'Sample Product 1',
                                Ptype: 'Base',
                                PColor: 'Blue Pearl',
                                PDesign: 'Standard Design',
                                PFinish: 'Polished',
                                Size: '2-6 X 1-2 X 0-6'
                            },
                            {
                                Qty: 2,
                                Locationname: 'Tate',
                                EndProductCode: 'SAMPLE-002',
                                EndProductDescription: 'Sample Product 2',
                                Ptype: 'Monument',
                                PColor: 'Gray',
                                PDesign: 'Custom Design',
                                PFinish: 'Rough',
                                Size: '3-0 X 2-0 X 1-0'
                            }
                        ];
                        combinedData.Total = combinedData.Data.length;
                    }
                    
                    console.log('Combined data:', combinedData);
                    console.log(`Total items: ${combinedData.Data.length}`);
                    
                    // Update pagination info
                    this.totalItems = combinedData.Total || combinedData.Data.length;
                    this.totalPages = Math.ceil(this.totalItems / this.pageSize) || 1;
                    
                    return combinedData;
                } catch (error) {
                    console.error('Error fetching inventory data:', error);
                    // Return sample data in case of error to prevent UI from breaking
                    return {
                        Data: [
                            {
                                Qty: 1,
                                Locationname: 'Elberton',
                                EndProductCode: 'ERROR-001',
                                EndProductDescription: 'Error loading data. Please try again.',
                                Ptype: 'Error',
                                PColor: 'N/A',
                                PDesign: 'N/A',
                                PFinish: 'N/A',
                                Size: 'N/A'
                            }
                        ],
                        Total: 1
                    };
                }
            }

            /**
             * Set filters for inventory data
             * @param {Object} filters - Filter values
             */
            setFilters(filters) {
                this.currentFilters = { ...this.currentFilters, ...filters };
                this.currentPage = 1; // Reset to first page when filters change
            }

            /**
             * Go to specific page
             * @param {Number} page - Page number
             */
            setPage(page) {
                if (page >= 1 && page <= this.totalPages) {
                    this.currentPage = page;
                }
            }

            /**
             * Go to next page if available
             */
            nextPage() {
                if (this.currentPage < this.totalPages) {
                    this.currentPage++;
                    return true;
                }
                return false;
            }

            /**
             * Go to previous page if available
             */
            prevPage() {
                if (this.currentPage > 1) {
                    this.currentPage--;
                    return true;
                }
                return false;
            }

            /**
             * Get unique values for a specific field from the inventory data
             * @param {Array} data - Inventory data
             * @param {String} field - Field name to extract unique values from
             * @returns {Array} Array of unique values
             */
            getUniqueValues(data, field) {
                // If data is not properly structured, return empty array
                if (!data) {
                    console.warn(`getUniqueValues: data is null or undefined`);
                    return [];
                }
                
                // Handle both uppercase and lowercase 'Data' property
                const dataArray = data.Data || data.data || [];
                
                if (!Array.isArray(dataArray)) {
                    console.warn(`getUniqueValues: data array is not an array, it's ${typeof dataArray}`);
                    return [];
                }
                
                // Create lowercase version of the field name for case-insensitive matching
                const fieldLower = field.toLowerCase();
                
                // Extract values, handling both exact case and lowercase field names
                const values = dataArray.map(item => {
                    // Try direct field access first
                    if (item[field] !== undefined) {
                        return item[field];
                    }
                    
                    // If not found, try case-insensitive matching
                    const keys = Object.keys(item);
                    const matchingKey = keys.find(key => key.toLowerCase() === fieldLower);
                    return matchingKey ? item[matchingKey] : null;
                }).filter(Boolean); // Remove null/undefined values
                
                console.log(`Found ${values.length} unique values for field ${field}`);
                return [...new Set(values)].sort();
            }
        }

        // Initialize API client
        let api = new InventoryAPI();
        
        // Function to create the modal HTML if it doesn't exist
        function createModal() {
            // Check if modal already exists
            if (document.getElementById('inventoryModal')) {
                console.log('Inventory modal already exists in DOM');
                return true; // Modal exists
            }
            
            console.log('Creating inventory modal...');
            
            try {
                // Create the modal element directly
                const modalDiv = document.createElement('div');
                modalDiv.className = 'modal fade inventory-modal';
                modalDiv.id = 'inventoryModal';
                modalDiv.tabIndex = '-1';
                modalDiv.setAttribute('aria-labelledby', 'inventoryModalLabel');
                modalDiv.setAttribute('aria-hidden', 'true');
                
                // Create modal dialog
                const modalDialog = document.createElement('div');
                modalDialog.className = 'modal-dialog modal-xl';
                modalDiv.appendChild(modalDialog);
                
                // Create modal content
                const modalContent = document.createElement('div');
                modalContent.className = 'modal-content';
                modalDialog.appendChild(modalContent);
                
                // Create modal header
                const modalHeader = document.createElement('div');
                modalHeader.className = 'modal-header';
                modalContent.appendChild(modalHeader);
                
                // Create modal title
                const modalTitle = document.createElement('h5');
                modalTitle.className = 'modal-title';
                modalTitle.id = 'inventoryModalLabel';
                modalTitle.textContent = 'Current Inventory';
                modalHeader.appendChild(modalTitle);
                
                // Create close button
                const closeButton = document.createElement('button');
                closeButton.type = 'button';
                closeButton.className = 'btn-close';
                closeButton.setAttribute('data-bs-dismiss', 'modal');
                closeButton.setAttribute('aria-label', 'Close');
                modalHeader.appendChild(closeButton);
                
                // Create modal body
                const modalBody = document.createElement('div');
                modalBody.className = 'modal-body';
                modalContent.appendChild(modalBody);
                
                // Create content container
                const contentDiv = document.createElement('div');
                contentDiv.id = 'inventoryModalContent';
                modalBody.appendChild(contentDiv);
                
                // Create scroll indicator
                const scrollIndicator = document.createElement('div');
                scrollIndicator.className = 'scroll-indicator';
                const icon = document.createElement('i');
                icon.className = 'fas fa-arrow-down';
                scrollIndicator.appendChild(icon);
                scrollIndicator.appendChild(document.createTextNode(' Scroll for more items'));
                modalBody.appendChild(scrollIndicator);
                
                // Create modal footer
                const modalFooter = document.createElement('div');
                modalFooter.className = 'modal-footer';
                modalContent.appendChild(modalFooter);
                
                // Create close button in footer
                const closeBtn = document.createElement('button');
                closeBtn.type = 'button';
                closeBtn.className = 'btn btn-secondary';
                closeBtn.id = 'closeInventoryBtn';
                closeBtn.textContent = 'Close';
                modalFooter.appendChild(closeBtn);
                
                // Create refresh button
                const refreshBtn = document.createElement('button');
                refreshBtn.type = 'button';
                refreshBtn.className = 'btn btn-gold';
                refreshBtn.id = 'refreshInventoryBtn';
                const refreshIcon = document.createElement('i');
                refreshIcon.className = 'fas fa-sync-alt';
                refreshBtn.appendChild(refreshIcon);
                refreshBtn.appendChild(document.createTextNode(' Refresh Data'));
                modalFooter.appendChild(refreshBtn);
                
                // Append the complete modal to the document body
                document.body.appendChild(modalDiv);
                
                // Verify the modal was added to the DOM
                const modalCheck = document.getElementById('inventoryModal');
                console.log('Modal created and added to DOM:', !!modalCheck);
                
                return !!modalCheck; // Return true if modal was created successfully
            } catch (error) {
                console.error('Error creating modal:', error);
                return false; // Failed to create modal
            }
        }

        // Function to load and display inventory data
        async function loadInventoryData() {
            const contentDiv = document.getElementById('inventoryModalContent');
            if (!contentDiv) {
                console.error('Inventory modal content div not found');
                return;
            }
            
            // Initialize timeout tracking array if it doesn't exist
            if (!window._inventoryTimeouts) {
                window._inventoryTimeouts = [];
            }
            
            // Show loading indicator
            contentDiv.innerHTML = `
                <div class="loading">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading inventory data...</p>
                </div>
            `;
            
            try {
                // Set a timeout to prevent the request from hanging indefinitely
                let timeoutId;
                const timeoutPromise = new Promise((_, reject) => {
                    timeoutId = setTimeout(() => {
                        console.log('Request timeout triggered after 15 seconds');
                        reject(new Error('Request timeout after 15 seconds'));
                    }, 15000);
                    
                    // Track this timeout for cleanup
                    window._inventoryTimeouts.push(timeoutId);
                });
                
                // Race between the API call and the timeout
                const data = await Promise.race([
                    api.fetchInventory(),
                    timeoutPromise
                ]);
                
                // Clear the timeout since the request completed
                if (timeoutId) {
                    clearTimeout(timeoutId);
                    // Remove from tracking array
                    const index = window._inventoryTimeouts.indexOf(timeoutId);
                    if (index > -1) {
                        window._inventoryTimeouts.splice(index, 1);
                    }
                }
                
                // Check if we have data - handle both uppercase and lowercase 'data' property
                const inventoryItems = data?.Data || data?.data || [];
                console.log('Inventory items for rendering:', inventoryItems.length);
                
                if (!Array.isArray(inventoryItems) || inventoryItems.length === 0) {
                    contentDiv.innerHTML = `
                        <div class="no-results">
                            <i class="fas fa-exclamation-circle fa-3x mb-3"></i>
                            <h4>No inventory items found</h4>
                            <p>Try adjusting your filters or refreshing the data.</p>
                            <button class="btn btn-gold mt-3" id="retryLoadBtn">
                                <i class="fas fa-sync-alt"></i> Retry
                            </button>
                        </div>
                    `;
                    
                    // Add retry button listener
                    const retryBtn = document.getElementById('retryLoadBtn');
                    if (retryBtn) {
                        retryBtn.addEventListener('click', loadInventoryData);
                    }
                    return;
                }
                
                // Get unique values for filters
                const productTypes = api.getUniqueValues(data, 'Ptype');
                const productColors = api.getUniqueValues(data, 'PColor');
                const productDesigns = api.getUniqueValues(data, 'PDesign');
                const productFinishes = api.getUniqueValues(data, 'PFinish');
                const productSizes = api.getUniqueValues(data, 'Size');
                const locations = api.getUniqueValues(data, 'Locationname');
                
                // Helper function to create option elements with selected state
                const createOptions = (items, selectedValue) => {
                    return items.map(item => {
                        const selected = item === selectedValue ? 'selected' : '';
                        return `<option value="${item}" ${selected}>${item}</option>`;
                    }).join('');
                };
                
                // Get current filter values
                const currentPtype = api.currentFilters.ptype || '';
                const currentPcolor = api.currentFilters.pcolor || '';
                const currentPdesign = api.currentFilters.pdesign || '';
                const currentPfinish = api.currentFilters.pfinish || '';
                const currentPsize = api.currentFilters.psize || '';
                const currentLocation = api.selectedLocation || '';
                
                console.log('Current filter values:', {
                    ptype: currentPtype,
                    pcolor: currentPcolor,
                    pdesign: currentPdesign,
                    pfinish: currentPfinish,
                    psize: currentPsize,
                    location: currentLocation
                });
                
                // Build the filters HTML with selected values
                const filtersHtml = `
                    <div class="inventory-filters">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="search-container">
                                    <i class="fas fa-search search-icon"></i>
                                    <input type="text" class="form-control" id="inventorySearch" placeholder="Search inventory...">
                                </div>
                                <div id="searchHelp" class="form-text">Search by product code, description, or any attribute</div>
                            </div>
                            <div class="col-md-8">
                                <div class="row">
                                    <div class="col-md-4 col-6">
                                        <div class="form-group mb-3">
                                            <label for="typeFilter">Product Type</label>
                                            <select class="form-select" id="typeFilter">
                                                <option value="" ${currentPtype === '' ? 'selected' : ''}>All Types</option>
                                                ${createOptions(productTypes, currentPtype)}
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-6">
                                        <div class="form-group mb-3">
                                            <label for="colorFilter">Color</label>
                                            <select class="form-select" id="colorFilter">
                                                <option value="" ${currentPcolor === '' ? 'selected' : ''}>All Colors</option>
                                                ${createOptions(productColors, currentPcolor)}
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-6">
                                        <div class="form-group mb-3">
                                            <label for="designFilter">Design</label>
                                            <select class="form-select" id="designFilter">
                                                <option value="" ${currentPdesign === '' ? 'selected' : ''}>All Designs</option>
                                                ${createOptions(productDesigns, currentPdesign)}
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-6">
                                        <div class="form-group mb-3">
                                            <label for="finishFilter">Finish</label>
                                            <select class="form-select" id="finishFilter">
                                                <option value="" ${currentPfinish === '' ? 'selected' : ''}>All Finishes</option>
                                                ${createOptions(productFinishes, currentPfinish)}
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-6">
                                        <div class="form-group mb-3">
                                            <label for="sizeFilter">Size</label>
                                            <select class="form-select" id="sizeFilter">
                                                <option value="" ${currentPsize === '' ? 'selected' : ''}>All Sizes</option>
                                                ${createOptions(productSizes, currentPsize)}
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-6">
                                        <div class="form-group mb-3">
                                            <label for="locationFilter">Location</label>
                                            <select class="form-select" id="locationFilter">
                                                <option value="" ${currentLocation === '' ? 'selected' : ''}>All Locations</option>
                                                ${createOptions(locations, currentLocation)}
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                // Build the table HTML
                const tableHtml = `
                    <div class="table-responsive">
                        <table class="inventory-table table table-striped">
                            <thead>
                                <tr>
                                    <th>Product Code</th>
                                    <th>Description</th>
                                    <th>Type</th>
                                    <th>Color</th>
                                    <th>Design</th>
                                    <th>Finish</th>
                                    <th>Size</th>
                                    <th>Location</th>
                                    <th>Quantity</th>
                                </tr>
                            </thead>
                            <tbody id="inventoryTableBody">
                                ${inventoryItems.map(item => {
                                    // Helper function to get field value with case-insensitive matching
                                    const getField = (fieldName) => {
                                        if (item[fieldName] !== undefined) return item[fieldName];
                                        
                                        // Try lowercase matching
                                        const lowerField = fieldName.toLowerCase();
                                        const key = Object.keys(item).find(k => k.toLowerCase() === lowerField);
                                        return key ? item[key] : '';
                                    };
                                    
                                    return `
                                    <tr>
                                        <td>${getField('EndProductCode')}</td>
                                        <td>${getField('EndProductDescription')}</td>
                                        <td>${getField('Ptype')}</td>
                                        <td>${getField('PColor')}</td>
                                        <td>${getField('PDesign')}</td>
                                        <td>${getField('PFinish')}</td>
                                        <td>${getField('Size')}</td>
                                        <td>${getField('Locationname')}</td>
                                        <td>${getField('Qty') || 0}</td>
                                    </tr>
                                    `;
                                }).join('')}
                            </tbody>
                        </table>
                    </div>
                `;
                
                // Build pagination HTML
                const paginationHtml = buildPagination(api.currentPage, api.totalPages);
                
                // Combine all HTML
                contentDiv.innerHTML = filtersHtml + tableHtml + paginationHtml;
                
                // Add event listeners for filters and pagination
                setupFilterListeners();
                setupPaginationListeners();
                setupSearchListener();
                setupScrollIndicator();
                
                // Load Font Awesome if not already loaded
                if (!document.querySelector('link[href*="font-awesome"]')) {
                    const fontAwesome = document.createElement('link');
                    fontAwesome.rel = 'stylesheet';
                    fontAwesome.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css';
                    document.head.appendChild(fontAwesome);
                }
                
                console.log('Inventory data loaded successfully');
            } catch (error) {
                console.error('Error loading inventory data:', error);
                
                // Clean up any pending timeouts that might have been created
                if (window._inventoryTimeouts && window._inventoryTimeouts.length > 0) {
                    console.log(`Cleaning up ${window._inventoryTimeouts.length} pending timeouts due to error`);
                    window._inventoryTimeouts.forEach(id => {
                        clearTimeout(id);
                    });
                    window._inventoryTimeouts = [];
                }
                
                contentDiv.innerHTML = `
                    <div class="no-results">
                        <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                        <h4>Error loading inventory data</h4>
                        <p>${error.message || 'An unknown error occurred'}</p>
                        <button class="btn btn-gold mt-3" id="retryLoadBtn">
                            <i class="fas fa-sync-alt"></i> Retry
                        </button>
                    </div>
                `;
                
                // Add retry button listener
                const retryBtn = document.getElementById('retryLoadBtn');
                if (retryBtn) {
                    retryBtn.removeEventListener('click', loadInventoryData); // Remove any existing listener
                    retryBtn.addEventListener('click', loadInventoryData);
                }
            }
        }
        
        // Function to build pagination controls
        function buildPagination(currentPage, totalPages) {
            if (totalPages <= 1) {
                return ''; // No pagination needed
            }
            
            let paginationHtml = '<nav aria-label="Inventory pagination"><ul class="pagination">';
            
            // Previous button
            paginationHtml += `
                <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${currentPage - 1}" aria-label="Previous">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>
            `;
            
            // Page numbers
            const maxPagesToShow = 5;
            let startPage = Math.max(1, currentPage - Math.floor(maxPagesToShow / 2));
            let endPage = Math.min(totalPages, startPage + maxPagesToShow - 1);
            
            // Adjust start page if we're near the end
            if (endPage - startPage + 1 < maxPagesToShow) {
                startPage = Math.max(1, endPage - maxPagesToShow + 1);
            }
            
            // First page
            if (startPage > 1) {
                paginationHtml += `
                    <li class="page-item">
                        <a class="page-link" href="#" data-page="1">1</a>
                    </li>
                `;
                
                if (startPage > 2) {
                    paginationHtml += `
                        <li class="page-item disabled">
                            <a class="page-link" href="#">...</a>
                        </li>
                    `;
                }
            }
            
            // Page numbers
            for (let i = startPage; i <= endPage; i++) {
                paginationHtml += `
                    <li class="page-item ${i === currentPage ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${i}">${i}</a>
                    </li>
                `;
            }
            
            // Last page
            if (endPage < totalPages) {
                if (endPage < totalPages - 1) {
                    paginationHtml += `
                        <li class="page-item disabled">
                            <a class="page-link" href="#">...</a>
                        </li>
                    `;
                }
                
                paginationHtml += `
                    <li class="page-item">
                        <a class="page-link" href="#" data-page="${totalPages}">${totalPages}</a>
                    </li>
                `;
            }
            
            // Next button
            paginationHtml += `
                <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${currentPage + 1}" aria-label="Next">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
            `;
            
            paginationHtml += '</ul></nav>';
            
            return paginationHtml;
        }
        
        // Define reusable event handler functions for proper cleanup
        let filterChangeHandler, paginationClickHandler, searchInputHandler;
        
        // Function to set up filter event listeners
        function setupFilterListeners() {
            const typeFilter = document.getElementById('typeFilter');
            const colorFilter = document.getElementById('colorFilter');
            const designFilter = document.getElementById('designFilter');
            const finishFilter = document.getElementById('finishFilter');
            const sizeFilter = document.getElementById('sizeFilter');
            const locationFilter = document.getElementById('locationFilter');
            
            const filters = [typeFilter, colorFilter, designFilter, finishFilter, sizeFilter, locationFilter];
            
            // Define the filter change handler function
            filterChangeHandler = function() {
                // Update API filters - don't include locid as it's handled separately
                const filters = {
                    ptype: typeFilter ? typeFilter.value : '',
                    pcolor: colorFilter ? colorFilter.value : '',
                    pdesign: designFilter ? designFilter.value : '',
                    pfinish: finishFilter ? finishFilter.value : '',
                    psize: sizeFilter ? sizeFilter.value : ''
                };
                
                // Store the selected location in a separate property
                api.selectedLocation = locationFilter ? locationFilter.value : '';
                
                console.log('Setting filters:', filters);
                console.log('Selected location:', api.selectedLocation);
                
                api.setFilters(filters);
                
                // Reload data
                loadInventoryData();
            };
            
            // Add event listeners to all filter dropdowns
            filters.forEach(filter => {
                if (filter) {
                    // Remove any existing listeners first to prevent duplicates
                    filter.removeEventListener('change', filterChangeHandler);
                    // Add the new listener
                    filter.addEventListener('change', filterChangeHandler);
                }
            });
        }
        
        // Function to set up pagination event listeners
        function setupPaginationListeners() {
            const paginationLinks = document.querySelectorAll('.pagination .page-link');
            
            // Define the pagination click handler function
            paginationClickHandler = function(e) {
                e.preventDefault();
                
                const page = parseInt(this.getAttribute('data-page'));
                if (!isNaN(page)) {
                    api.setPage(page);
                    loadInventoryData();
                    
                    // Scroll to top of modal content
                    const modalBody = document.querySelector('.inventory-modal .modal-body');
                    if (modalBody) {
                        modalBody.scrollTop = 0;
                    }
                }
            };
            
            // Add event listeners to all pagination links
            paginationLinks.forEach(link => {
                // Remove any existing listeners first to prevent duplicates
                link.removeEventListener('click', paginationClickHandler);
                // Add the new listener
                link.addEventListener('click', paginationClickHandler);
            });
        }
        
        // Function to set up search functionality
        function setupSearchListener() {
            const searchInput = document.getElementById('inventorySearch');
            if (searchInput) {
                // Define the search input handler function
                searchInputHandler = function() {
                    const searchTerm = this.value.toLowerCase().trim();
                    const tableRows = document.querySelectorAll('#inventoryTableBody tr');
                    
                    tableRows.forEach(row => {
                        const text = row.textContent.toLowerCase();
                        if (text.includes(searchTerm)) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                };
                
                // Remove any existing listeners first to prevent duplicates
                searchInput.removeEventListener('input', searchInputHandler);
                // Add the new listener
                searchInput.addEventListener('input', searchInputHandler);
            }
        }
        
        // Function to set up scroll indicator
        function setupScrollIndicator() {
            const modalBody = document.querySelector('.inventory-modal .modal-body');
            const scrollIndicator = document.querySelector('.inventory-modal .scroll-indicator');
            
            if (modalBody && scrollIndicator) {
                // Show scroll indicator if content is scrollable
                if (modalBody.scrollHeight > modalBody.clientHeight) {
                    scrollIndicator.style.display = 'block';
                    
                    // Define a named function for the scroll handler so we can remove it later
                    const scrollHandler = function() {
                        scrollIndicator.style.display = 'none';
                    };
                    
                    // Store the handler reference on the modalBody element for later cleanup
                    modalBody._scrollHandler = scrollHandler;
                    
                    // Add the scroll event listener
                    modalBody.addEventListener('scroll', scrollHandler, { once: true });
                    
                    // Hide indicator after 5 seconds anyway
                    // Store the timeout ID for later cleanup
                    modalBody._scrollTimeout = setTimeout(() => {
                        scrollIndicator.style.display = 'none';
                    }, 5000);
                }
            }
            
            // Set up refresh button listener
            const refreshBtn = document.getElementById('refreshInventoryBtn');
            if (refreshBtn) {
                // Remove any existing listeners first
                refreshBtn.removeEventListener('click', loadInventoryData);
                // Add the new listener
                refreshBtn.addEventListener('click', loadInventoryData);
            }
        }
        
        // Function to clean up all event listeners
        function cleanupEventListeners() {
            console.log('Cleaning up all event listeners...');
            
            // Clean up filter listeners
            const filterSelects = document.querySelectorAll('.inventory-filters select');
            filterSelects.forEach(select => {
                if (select && filterChangeHandler) {
                    select.removeEventListener('change', filterChangeHandler);
                }
            });
            
            // Clean up pagination listeners
            const paginationLinks = document.querySelectorAll('.pagination .page-link');
            paginationLinks.forEach(link => {
                if (link && paginationClickHandler) {
                    link.removeEventListener('click', paginationClickHandler);
                }
            });
            
            // Clean up search listener
            const searchInput = document.getElementById('inventorySearch');
            if (searchInput && searchInputHandler) {
                searchInput.removeEventListener('input', searchInputHandler);
            }
            
            // Clean up refresh button listener
            const refreshBtn = document.getElementById('refreshInventoryBtn');
            if (refreshBtn) {
                refreshBtn.removeEventListener('click', loadInventoryData);
            }
            
            // Clean up retry button listener
            const retryBtn = document.getElementById('retryLoadBtn');
            if (retryBtn) {
                retryBtn.removeEventListener('click', loadInventoryData);
            }
            
            // Clean up scroll event listener and timeout
            const modalBody = document.querySelector('.inventory-modal .modal-body');
            if (modalBody) {
                // Clean up scroll handler if it exists
                if (modalBody._scrollHandler) {
                    modalBody.removeEventListener('scroll', modalBody._scrollHandler);
                    delete modalBody._scrollHandler;
                }
                
                // Clear any pending timeout
                if (modalBody._scrollTimeout) {
                    clearTimeout(modalBody._scrollTimeout);
                    delete modalBody._scrollTimeout;
                }
            }
            
            // Clean up any other timers or intervals
            if (window._inventoryTimeouts) {
                window._inventoryTimeouts.forEach(timeoutId => {
                    clearTimeout(timeoutId);
                });
                window._inventoryTimeouts = [];
            }
        }

        // Function to open the modal and load data
        function openModal() {
            console.log('Opening inventory modal...');
            
            // Create modal if it doesn't exist and check if creation was successful
            const modalCreated = createModal();
            
            if (modalCreated) {
                console.log('Modal created successfully, proceeding to show it');
                
                // Use Bootstrap 5 syntax for modals
                const modalElement = document.getElementById('inventoryModal');
                if (modalElement) {
                    try {
                        const bsModal = new bootstrap.Modal(modalElement);
                        bsModal.show();
                        loadInventoryData();
                        
                        // Add event listener for when modal is shown
                        const shownHandler = function() {
                            console.log('Modal shown event fired');
                        };
                        
                        // Add event listener for when modal is hidden
                        const hiddenHandler = function() {
                            console.log('Modal hidden event fired');
                            
                            // Use the centralized cleanup function to remove all event listeners
                            cleanupEventListeners();
                            
                            // Remove modal event listeners
                            modalElement.removeEventListener('shown.bs.modal', shownHandler);
                            modalElement.removeEventListener('hidden.bs.modal', hiddenHandler);
                            
                            console.log('All event listeners cleaned up successfully');
                        };
                        
                        modalElement.addEventListener('shown.bs.modal', shownHandler);
                        modalElement.addEventListener('hidden.bs.modal', hiddenHandler);
                    } catch (error) {
                        console.error('Error showing modal with Bootstrap:', error);
                        
                        // Fallback method if Bootstrap modal fails
                        modalElement.classList.add('show');
                        modalElement.style.display = 'block';
                        document.body.classList.add('modal-open');
                        
                        // Create backdrop manually if needed
                        if (!document.querySelector('.modal-backdrop')) {
                            const backdrop = document.createElement('div');
                            backdrop.className = 'modal-backdrop fade show';
                            document.body.appendChild(backdrop);
                        }
                        
                        loadInventoryData();
                    }
                } else {
                    console.error('Inventory modal element not found even though createModal returned true');
                }
            } else {
                console.error('Failed to create inventory modal');
            }
        }

        // Function to set up inventory link handler
        function setupInventoryLinkHandler() {
            // Try to find the inventory link by ID first
            let inventoryLink = document.getElementById('inventoryLink');
            let found = false;
            
            if (inventoryLink) {
                console.log('Found inventory link by ID');
                inventoryLink.addEventListener('click', function(e) {
                    console.log('Inventory link clicked (found by ID)');
                    e.preventDefault();
                    e.stopPropagation();
                    openModal();
                    return false;
                });
                found = true;
            } else {
                // If not found by ID, try to find by text content
                console.log('Inventory link not found by ID, trying to find by text content');
                const links = document.querySelectorAll('a');
                links.forEach(link => {
                    if (link.textContent.trim().toLowerCase().includes('inventory')) {
                        console.log('Found inventory link by text content');
                        link.addEventListener('click', function(e) {
                            console.log('Inventory link clicked (found by text)');
                            e.preventDefault();
                            e.stopPropagation();
                            openModal();
                            return false;
                        });
                        
                        found = true;
                    }
                });
                
                if (!found) {
                    console.error('Could not find inventory link by text content either');
                }
            }
        }

        // Function to initialize the inventory modal
        function init() {
            console.log('Initializing inventory modal...');
            
            // Set up inventory link handler
            setupInventoryLinkHandler();
            
            // Create modal and add button listeners
            const modalCreated = createModal();
            if (modalCreated) {
                addModalButtonListeners();
            }
        }

        // Initialize the modal
        init();
    });
});
