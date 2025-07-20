/**
 * Inventory Modal
 * 
 * This script creates a modal dialog that displays inventory data
 * from the monument.business API via a PHP proxy.
 * 
 * Version: 2.2.0 - Improved cache management (2025-07-14)
 * Version: 2.1.0 - Product codes hidden (2025-07-14)
 */
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
            .inventory-modal .modal-footer.sticky-bottom {
                position: sticky;
                bottom: 0;
                background-color: #212529;
                z-index: 9;
            }
            .inventory-modal .modal-body {
                flex: 1;
                overflow-y: auto;
                overflow-x: auto;
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
            .inventory-modal .table-responsive {
                overflow-x: auto;
                overflow-y: auto;
            }
            .inventory-modal .table-scroll-wrapper {
                position: relative;
            }
            .inventory-modal .table-scroll-wrapper .table-responsive {
                margin-right: 14px;
            }
            .inventory-modal .scrollbar-track {
                position: absolute;
                top: 0;
                right: 0;
                width: 12px;
                height: 100%;
                background: #343a40;
            }
            .inventory-modal .scrollbar-thumb {
                position: absolute;
                top: 0;
                right: 0;
                width: 100%;
                background-color: #6c757d;
                border-radius: 6px;
                cursor: pointer;
            }
            .inventory-modal .inventory-table {
                width: 100%;
                margin-bottom: 1rem;
                color: #212529;
                border-collapse: separate;
                border-spacing: 0;
                table-layout: auto;
                min-width: 100%;
            }
            .inventory-modal .inventory-table th,
            .inventory-modal .inventory-table td {
                padding: 0.75rem;
                vertical-align: middle;
                border-top: 1px solid #dee2e6;
            }
            .inventory-modal .inventory-table thead th {
                vertical-align: bottom;
                border-bottom: 2px solid #dee2e6;
                background-color: #f8f9fa;
                position: sticky;
                top: 0;
                z-index: 5;
            }
            .inventory-modal .inventory-table tbody tr:hover {
                background-color: #f1f1f1;
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
            .inventory-modal .summary-count {
                color: #f8f9fa;
                font-size: 0.9rem;
            }
            .inventory-modal .active-filter select,
            .inventory-modal .active-filter input {
                border-color: #d4af37;
                font-weight: bold;
            }
            .inventory-modal .search-highlight {
                background-color: #fff3cd !important;
            }
            .inventory-modal .inventory-filters {
                margin-bottom: 1rem;
            }
            .inventory-modal .sticky-filters {
                position: sticky;
                top: 0;
                z-index: 9;
                background-color: #212529;
                padding-top: 0.5rem;
                box-shadow: 0 2px 4px rgba(0,0,0,0.3);
            }
            .inventory-modal .column-filter {
                background-color: #343a40;
                border-color: #495057;
                color: #f8f9fa;
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
            .inventory-modal .modal-body::-webkit-scrollbar {
                width: 12px;
            }
            .inventory-modal .modal-body::-webkit-scrollbar-track {
                background: #343a40;
            }
            .inventory-modal .modal-body::-webkit-scrollbar-thumb {
                background-color: #6c757d;
                border-radius: 6px;
                border: 3px solid #343a40;
            }
            /* Responsive styles */
            @media (max-width: 767.98px) {
                .inventory-modal .sticky-filters {
                    position: static;
                    box-shadow: none;
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
                .inventory-modal .inventory-table thead tr {
                    display: flex;
                    flex-wrap: wrap;
                }
                .inventory-modal .inventory-table thead th {
                    flex: 1 0 50%;
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
                this.forceRefresh = false; // Flag to force refresh and bypass cache
                this.cacheExpiryMinutes = 1; // Reduce cache expiry to 1 minute for fresher data
                this.currentFilters = {
                    ptype: '',
                    pcolor: '',
                    pdesign: '',
                    pfinish: '',
                    psize: '',
                    locid: ''
                };
                
                // Cache configuration
                this.cacheKey = 'inventoryData';
                this.cacheExpiryKey = 'inventoryDataExpiry';
                this.expiryDuration = this.cacheExpiryMinutes * 60 * 1000; // Convert minutes to milliseconds
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
                    
                    // Check if we have any filters applied
                    const hasFilters = Object.values(this.currentFilters).some(val => val !== '');
                    const hasSearch = document.getElementById('inventorySearch')?.value?.trim() !== '';
                    
                    // Only use cache for unfiltered data and when not forcing refresh
                    if (!hasFilters && !hasSearch && !this.forceRefresh) {
                        // Check for cached data
                        const now = Date.now();
                        const cached = localStorage.getItem(this.cacheKey);
                        const expiry = localStorage.getItem(this.cacheExpiryKey);
                        
                        if (cached && expiry && now < parseInt(expiry)) {
                            console.log('Using cached inventory data (expires in', Math.round((parseInt(expiry) - now)/1000), 'seconds)');
                            return JSON.parse(cached);
                        } else {
                            console.log('Cache expired or not found, fetching fresh data');
                        }
                    } else if (this.forceRefresh) {
                        console.log('Force refresh requested, bypassing cache');
                    } else {
                        console.log('Filters or search applied, bypassing cache');
                    }
                    
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
                            const timeoutId = setTimeout(() => reject(new Error(`Request timeout for location ${locid} after ${timeout}ms`)), timeout);
                            
                            // Store timeout ID for cleanup
                            if (!window._inventoryTimeouts) {
                                window._inventoryTimeouts = [];
                            }
                            window._inventoryTimeouts.push(timeoutId);
                        });
                        
                        // Use GET method instead of POST to avoid potential CORS issues
                        // Add timestamp to prevent caching by Cloudflare
                        locParams.timestamp = Date.now();
                        const queryString = new URLSearchParams(locParams).toString();
                        
                        // Create abort controller for this request
                        const abortController = new AbortController();
                        if (!window._inventoryAbortControllers) {
                            window._inventoryAbortControllers = [];
                        }
                        window._inventoryAbortControllers.push(abortController);
                        
                        const fetchPromise = fetch(`inventory-proxy.php?${queryString}`, {
                            method: 'GET',
                            headers: {
                                'Cache-Control': 'no-cache',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            signal: abortController.signal
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
                    
                    // Cache the combined data if no filters are applied
                    // Reuse the hasFilters and hasSearch variables from earlier in the function
                    if (!hasFilters && !hasSearch && hasValidData) {
                        console.log(`Caching inventory data for ${this.cacheExpiryMinutes} minute(s)`);
                        const now = Date.now();
                        // Store the timestamp with the data for cache freshness checking
                        const dataWithTimestamp = {
                            ...combinedData,
                            _cachedAt: now
                        };
                        localStorage.setItem(this.cacheKey, JSON.stringify(dataWithTimestamp));
                        localStorage.setItem(this.cacheExpiryKey, now + this.expiryDuration);
                    }
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
             * Set force refresh flag to bypass cache
             * @param {Boolean} force - Whether to force refresh
             */
            setForceRefresh(force = true) {
                this.forceRefresh = force;
                return this;
            }
            
            /**
             * Reset force refresh flag after use
             */
            resetForceRefresh() {
                this.forceRefresh = false;
                return this;
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

        // Keep a reference to the Bootstrap modal instance
        let inventoryModalInstance = null;

        // Handler references for event cleanup
        let closeBtnHandler;
        let refreshDataHandler;
        let searchInputHandler;
        let filterChangeHandler;
        let paginationClickHandler;

        // Utility to decode HTML entities
        function decodeHtml(str) {
            const txt = document.createElement('textarea');
            txt.innerHTML = str;
            return txt.value;
        }
        
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
                closeButton.id = 'inventoryModalCloseBtn';
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


                
                // Create modal footer with sticky positioning
                const modalFooter = document.createElement('div');
                modalFooter.className = 'modal-footer sticky-bottom d-flex flex-wrap justify-content-between';
                modalContent.appendChild(modalFooter);

                // Reset filters button
                const resetBtn = document.createElement('button');
                resetBtn.type = 'button';
                resetBtn.className = 'btn btn-outline-secondary';
                resetBtn.id = 'resetFiltersBtn';
                resetBtn.textContent = 'Reset Filters';
                modalFooter.appendChild(resetBtn);

                // Refresh data button
                const refreshBtn = document.createElement('button');
                refreshBtn.type = 'button';
                refreshBtn.className = 'btn btn-gold';
                refreshBtn.id = 'refreshInventoryBtn';
                const refreshIcon = document.createElement('i');
                refreshIcon.className = 'fas fa-sync-alt';
                refreshBtn.appendChild(refreshIcon);
                refreshBtn.appendChild(document.createTextNode(' Refresh Data'));
                modalFooter.appendChild(refreshBtn);

                // Close button
                const closeBtn = document.createElement('button');
                closeBtn.type = 'button';
                closeBtn.className = 'btn btn-secondary';
                closeBtn.id = 'closeInventoryBtn';
                closeBtn.textContent = 'Close';
                modalFooter.appendChild(closeBtn);
                
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

        // Function to add listeners to modal buttons
        function addModalButtonListeners() {
            const footerBtn = document.getElementById('closeInventoryBtn');
            const headerBtn = document.getElementById('inventoryModalCloseBtn');
            const refreshBtn = document.getElementById('refreshInventoryBtn');
            const resetBtn = document.getElementById('resetFiltersBtn');

            // Close button handlers
            [footerBtn, headerBtn].forEach(btn => {
                if (btn) {
                    // Ensure button triggers the Bootstrap dismissal
                    btn.setAttribute('data-bs-dismiss', 'modal');
                    // Remove any existing listener to avoid duplicates
                    btn.removeEventListener('click', closeBtnHandler);
                }
            });

            closeBtnHandler = function() {
                if (inventoryModalInstance) {
                    inventoryModalInstance.hide();
                }
            };

            [footerBtn, headerBtn].forEach(btn => {
                if (btn) {
                    btn.addEventListener('click', closeBtnHandler);
                }
            });
            
            // Refresh button handler - force refresh data bypassing cache
            if (refreshBtn) {
                // Remove any existing listener to avoid duplicates
                refreshBtn.removeEventListener('click', refreshDataHandler);
                
                refreshDataHandler = function() {
                    console.log('Force refreshing inventory data...');
                    // Set force refresh flag to bypass cache
                    api.setForceRefresh(true);
                    loadInventoryData().then(() => {
                        // Reset the flag after use
                        api.resetForceRefresh();
                    });
                };
                
                refreshBtn.addEventListener('click', refreshDataHandler);
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

                // Apply column filters and search term client-side
                const searchVal = (document.getElementById('inventorySearch')?.value || '').toLowerCase().trim();
                const { ptype, pcolor, pdesign, pfinish, psize } = api.currentFilters;
                const locFilter = api.selectedLocation || '';

                const getFieldVal = (obj, field) => {
                    if (obj[field] !== undefined) return String(obj[field]);
                    const key = Object.keys(obj).find(k => k.toLowerCase() === field.toLowerCase());
                    return key ? String(obj[key]) : '';
                };

                let filteredItems = inventoryItems.filter(item => {
                    const typeMatch = !ptype || getFieldVal(item, 'Ptype').toLowerCase() === ptype.toLowerCase();
                    const colorMatch = !pcolor || getFieldVal(item, 'PColor').toLowerCase() === pcolor.toLowerCase();
                    const designMatch = !pdesign || getFieldVal(item, 'PDesign').toLowerCase() === pdesign.toLowerCase();
                    const finishMatch = !pfinish || getFieldVal(item, 'PFinish').toLowerCase() === pfinish.toLowerCase();
                    const sizeMatch = !psize || getFieldVal(item, 'Size').toLowerCase() === psize.toLowerCase();
                    const locationMatch = !locFilter || getFieldVal(item, 'Locationname').toLowerCase() === locFilter.toLowerCase();
                    const rowText = Object.values(item).join(' ').toLowerCase();
                    const searchMatch = !searchVal || rowText.includes(searchVal);
                    return typeMatch && colorMatch && designMatch && finishMatch && sizeMatch && locationMatch && searchMatch;
                });
                
                if (!Array.isArray(filteredItems) || filteredItems.length === 0) {
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
                const productTypes = api.getUniqueValues({ Data: inventoryItems }, 'Ptype');
                const productColors = api.getUniqueValues({ Data: inventoryItems }, 'PColor');
                const productDesigns = api.getUniqueValues({ Data: inventoryItems }, 'PDesign');
                const productFinishes = api.getUniqueValues({ Data: inventoryItems }, 'PFinish');
                const productSizes = api.getUniqueValues({ Data: inventoryItems }, 'Size');
                const locations = api.getUniqueValues({ Data: inventoryItems }, 'Locationname');
                
                // Helper function to create option elements with selected state
                const escapeHtml = (str) => {
                    return String(str)
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#39;');
                };

                const decodeHtml = (str) => {
                    const txt = document.createElement('textarea');
                    txt.innerHTML = str;
                    return txt.value;
                };
                const createOptions = (items, selectedValue) => {
                    return items.map(item => {
                        const selected = item === selectedValue ? 'selected' : '';
                        const escapedVal = escapeHtml(item);
                        const escapedText = escapeHtml(item);
                        return `<option value="${escapedVal}" ${selected}>${escapedText}</option>`;
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
                
                // Build the search HTML with selected values
                const searchHtml = `
                    <div class="inventory-filters sticky-filters">
                        <div class="search-container">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" class="form-control" id="inventorySearch" placeholder="Search by description, type, color, or any attribute..." value="${searchVal || ''}">
                        </div>
                        <div id="searchHelp" class="form-text">Search by description, type, color, or any attribute</div>
                    </div>
                    <div class="d-flex justify-content-end align-items-center mb-2">
                        <div class="summary-count me-2">Showing ${filteredItems.length} of ${api.totalItems}</div>
                        <span id="activeBadge" class="badge bg-secondary" style="display:none;">Filters Active</span>
                    </div>
                `;
                
                // Build the table HTML
                const tableHtml = `
                    <div class="table-scroll-wrapper" style="max-height: 65vh;">
                        <div id="inventoryTableContainer" class="table-responsive" style="max-height: 65vh; overflow-y: auto; overflow-x: auto;">
                            <table id="inventoryTable" class="inventory-table table table-striped table-sm table-hover table-bordered align-middle w-100">
                            <thead>
                                <tr>
                                    <!-- Product Code column hidden per client request -->
                                    <th>Description</th>
                                    <th>
                                        Type
                                        <select class="form-select form-select-sm column-filter mt-1" id="typeFilter" data-col-index="1">
                                            <option value="" ${currentPtype === '' ? 'selected' : ''}>All</option>
                                            ${createOptions(productTypes, currentPtype)}
                                        </select>
                                    </th>
                                    <th>
                                        Color
                                        <select class="form-select form-select-sm column-filter mt-1" id="colorFilter" data-col-index="2">
                                            <option value="" ${currentPcolor === '' ? 'selected' : ''}>All</option>
                                            ${createOptions(productColors, currentPcolor)}
                                        </select>
                                    </th>
                                    <th>
                                        Design
                                        <select class="form-select form-select-sm column-filter mt-1" id="designFilter" data-col-index="3">
                                            <option value="" ${currentPdesign === '' ? 'selected' : ''}>All</option>
                                            ${createOptions(productDesigns, currentPdesign)}
                                        </select>
                                    </th>
                                    <th>
                                        Finish
                                        <select class="form-select form-select-sm column-filter mt-1" id="finishFilter" data-col-index="4">
                                            <option value="" ${currentPfinish === '' ? 'selected' : ''}>All</option>
                                            ${createOptions(productFinishes, currentPfinish)}
                                        </select>
                                    </th>
                                    <th>
                                        Size
                                        <select class="form-select form-select-sm column-filter mt-1" id="sizeFilter" data-col-index="5">
                                            <option value="" ${currentPsize === '' ? 'selected' : ''}>All</option>
                                            ${createOptions(productSizes, currentPsize)}
                                        </select>
                                    </th>
                                    <th>
                                        Location
                                        <select class="form-select form-select-sm column-filter mt-1" id="locationFilter" data-col-index="6">
                                            <option value="" ${currentLocation === '' ? 'selected' : ''}>All</option>
                                            ${createOptions(locations, currentLocation)}
                                        </select>
                                    </th>
                                    <th>Quantity</th>
                                </tr>
                            </thead>
                            <tbody id="inventoryTableBody">
                                ${filteredItems.map(item => {
                                    // Helper function to get field value with case-insensitive matching
                                    const getField = (fieldName) => {
                                        if (item[fieldName] !== undefined) return item[fieldName];

                                        // Try lowercase matching
                                        const lowerField = fieldName.toLowerCase();
                                        const key = Object.keys(item).find(k => k.toLowerCase() === lowerField);
                                        return key ? item[key] : '';
                                    };

                                    const rowText = Object.values(item).join(' ').toLowerCase();
                                    const highlight = searchVal && rowText.includes(searchVal) ? ' search-highlight' : '';

                                    return `
                                    <tr class="${highlight.trim()}">
                                        <!-- Product Code column hidden per client request -->
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
                        <div class="scrollbar-track"><div class="scrollbar-thumb"></div></div>
                    </div>
                `;
                
                // Build pagination HTML
                const paginationHtml = buildPagination(api.currentPage, api.totalPages);

                // Combine all HTML
                contentDiv.innerHTML = searchHtml + tableHtml + paginationHtml;
                
                // Add event listeners for filters and pagination
                setupFilterListeners();
                setupPaginationListeners();
                setupSearchListener();
                setupScrollIndicator();
                filterTable();
                setupCustomScrollbar();
                
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
        
        // Event handlers are now defined at the top level for proper cleanup

        // Utility to enable/disable reset button
        function updateResetButtonState() {
            const resetBtn = document.getElementById('resetFiltersBtn');
            if (!resetBtn) return;
            const filterInputs = document.querySelectorAll('.column-filter, #inventorySearch');
            const anyActive = Array.from(filterInputs).some(el => el.value);
            resetBtn.disabled = !anyActive;
            if (anyActive) {
                resetBtn.classList.remove('disabled');
                const badge = document.getElementById('activeBadge');
                if (badge) badge.style.display = 'inline-block';
            } else {
                resetBtn.classList.add('disabled');
                const badge = document.getElementById('activeBadge');
                if (badge) badge.style.display = 'none';
            }
        }
        
        // Function to set up filter event listeners
        function setupFilterListeners() {
            const typeFilter = document.getElementById('typeFilter');
            const colorFilter = document.getElementById('colorFilter');
            const designFilter = document.getElementById('designFilter');
            const finishFilter = document.getElementById('finishFilter');
            const sizeFilter = document.getElementById('sizeFilter');
            const locationFilter = document.getElementById('locationFilter');
            const searchInput = document.getElementById('inventorySearch');

            const filterElements = [typeFilter, colorFilter, designFilter, finishFilter, sizeFilter, locationFilter];

            updateResetButtonState();
            
            // Define the filter change handler function
            filterChangeHandler = function() {
                // Update API filters - don't include locid as it's handled separately
                const filterValues = {
                    ptype: typeFilter ? decodeHtml(typeFilter.value) : '',
                    pcolor: colorFilter ? decodeHtml(colorFilter.value) : '',
                    pdesign: designFilter ? decodeHtml(designFilter.value) : '',
                    pfinish: finishFilter ? decodeHtml(finishFilter.value) : '',
                    psize: sizeFilter ? decodeHtml(sizeFilter.value) : ''
                };
                
                // Store the selected location in a separate property
                api.selectedLocation = locationFilter ? decodeHtml(locationFilter.value) : '';
                
                console.log('Setting filters:', filterValues);
                console.log('Selected location:', api.selectedLocation);

                filterElements.forEach(el => {
                    if (el && el.value) {
                        el.classList.add('active-filter');
                    } else if (el) {
                        el.classList.remove('active-filter');
                    }
                });

                api.setFilters(filterValues);
                filterTable();
                updateResetButtonState();
            };

            // Add event listeners to all filter dropdowns
            filterElements.forEach(filter => {
                if (filter) {
                    // Remove any existing listeners first to prevent duplicates
                    filter.removeEventListener('change', filterChangeHandler);
                    // Add the new listener
                    filter.addEventListener('change', filterChangeHandler);
                }
            });

            const resetBtn = document.getElementById('resetFiltersBtn');
            if (resetBtn) {
                resetBtn.addEventListener('click', () => {
                    filterElements.forEach(f => {
                        if (f) {
                            f.value = '';
                            f.classList.remove('active-filter');
                        }
                    });
                    if (searchInput) {
                        searchInput.value = '';
                        searchInput.parentElement.classList.remove('active-filter');
                    }
                    api.setFilters({ ptype:'', pcolor:'', pdesign:'', pfinish:'', psize:'' });
                    api.selectedLocation = '';
                    filterTable();
                    updateResetButtonState();
                });
                updateResetButtonState();
            }

            const refreshBtnInline = document.getElementById('refreshTableBtn');
            if (refreshBtnInline) {
                refreshBtnInline.addEventListener('click', loadInventoryData);
            }
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
                    if (searchInput.value) {
                        searchInput.parentElement.classList.add('active-filter');
                    } else {
                        searchInput.parentElement.classList.remove('active-filter');
                    }
                    filterTable();
                    updateResetButtonState();
                };

                // Remove any existing listeners first to prevent duplicates
                searchInput.removeEventListener('input', searchInputHandler);
                // Add the new listener
                searchInput.addEventListener('input', searchInputHandler);
            }
        }

        // Apply dropdown and text search filters to the table
        function filterTable() {
            const rows = document.querySelectorAll('#inventoryTable tbody tr');
            const searchVal = (document.getElementById('inventorySearch')?.value || '').toLowerCase();
            const selects = document.querySelectorAll('.column-filter');
            let visible = 0;

            rows.forEach(row => {
                row.style.display = 'table-row';
                let show = true;

                selects.forEach(select => {
                    const colIndex = parseInt(select.dataset.colIndex, 10);
                    const filterVal = decodeHtml(select.value).toLowerCase();
                    if (filterVal && !row.cells[colIndex].textContent.toLowerCase().includes(filterVal)) {
                        show = false;
                    }
                });

                if (show && searchVal) {
                    if (!row.textContent.toLowerCase().includes(searchVal)) {
                        show = false;
                    }
                }

                row.style.display = show ? 'table-row' : 'none';
                if (show) visible++;
            });

            const summary = document.querySelector('.summary-count');
            if (summary) {
                summary.textContent = `Showing ${visible} of ${rows.length}`;
            }
            updateResetButtonState();
            updateCustomScrollbar();
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

        // Set up custom scrollbar for the table
        function setupCustomScrollbar() {
            const container = document.getElementById('inventoryTableContainer');
            const track = document.querySelector('.inventory-modal .scrollbar-track');
            const thumb = document.querySelector('.inventory-modal .scrollbar-thumb');

            if (!container || !track || !thumb) return;

            const updateThumb = () => {
                const { scrollHeight, clientHeight, scrollTop } = container;
                const ratio = clientHeight / scrollHeight;
                const thumbHeight = Math.max(clientHeight * ratio, 20);
                thumb.style.height = thumbHeight + 'px';
                const maxTop = clientHeight - thumbHeight;
                const top = scrollTop / (scrollHeight - clientHeight) * maxTop;
                thumb.style.top = top + 'px';
            };

            const onScroll = () => updateThumb();
            container.addEventListener('scroll', onScroll);

            let startY = 0;
            let startTop = 0;

            const onMouseDown = (e) => {
                startY = e.clientY;
                startTop = parseFloat(thumb.style.top) || 0;
                document.addEventListener('mousemove', onMouseMove);
                document.addEventListener('mouseup', onMouseUp);
                e.preventDefault();
            };

            const onMouseMove = (e) => {
                const delta = e.clientY - startY;
                const maxTop = track.clientHeight - thumb.offsetHeight;
                let newTop = Math.min(Math.max(startTop + delta, 0), maxTop);
                const scrollRatio = newTop / maxTop;
                container.scrollTop = scrollRatio * (container.scrollHeight - container.clientHeight);
            };

            const onMouseUp = () => {
                document.removeEventListener('mousemove', onMouseMove);
                document.removeEventListener('mouseup', onMouseUp);
            };

            thumb.addEventListener('mousedown', onMouseDown);

            container._customScroll = { onScroll, onMouseDown, updateThumb };
            updateThumb();
        }

        function updateCustomScrollbar() {
            const container = document.getElementById('inventoryTableContainer');
            if (container && container._customScroll) {
                container._customScroll.updateThumb();
            }
        }

        // Function to clean up all event listeners
        function cleanupEventListeners() {
            console.log('Cleaning up all event listeners...');
            
            // Clean up filter listeners
            const filterSelects = document.querySelectorAll('.column-filter');
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
            if (refreshBtn && refreshDataHandler) {
                refreshBtn.removeEventListener('click', refreshDataHandler);
            }
            const refreshBtnInline = document.getElementById('refreshTableBtn');
            if (refreshBtnInline) {
                refreshBtnInline.removeEventListener('click', loadInventoryData);
            }

            // Clean up close button listeners
            const footerBtn = document.getElementById('closeInventoryBtn');
            const headerBtn = document.getElementById('inventoryModalCloseBtn');
            [footerBtn, headerBtn].forEach(btn => {
                if (btn && closeBtnHandler) {
                    btn.removeEventListener('click', closeBtnHandler);
                }
            });
            
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

            // Clean up custom scrollbar listeners
            const container = document.getElementById('inventoryTableContainer');
            if (container && container._customScroll) {
                container.removeEventListener('scroll', container._customScroll.onScroll);
                const thumb = document.querySelector('.inventory-modal .scrollbar-thumb');
                if (thumb) {
                    thumb.removeEventListener('mousedown', container._customScroll.onMouseDown);
                }
                delete container._customScroll;
            }
            
            // Clean up any other timers or intervals
            if (window._inventoryTimeouts) {
                window._inventoryTimeouts.forEach(timeoutId => {
                    clearTimeout(timeoutId);
                });
                window._inventoryTimeouts = [];
            }
            
            // Clean up any pending fetch requests
            if (window._inventoryAbortControllers) {
                window._inventoryAbortControllers.forEach(controller => {
                    try {
                        controller.abort();
                    } catch (e) {
                        console.error('Error aborting fetch request:', e);
                    }
                });
                window._inventoryAbortControllers = [];
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
                        inventoryModalInstance = new bootstrap.Modal(modalElement);
                        inventoryModalInstance.show();
                        loadInventoryData();
                        addModalButtonListeners();
                        
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
                            
                            // Reset handler references after cleanup
                            closeBtnHandler = null;
                            refreshDataHandler = null;
                            searchInputHandler = null;
                            filterChangeHandler = null;
                            paginationClickHandler = null;
                            
                            console.log('All event listeners cleaned up successfully');
                        };
                        
                        modalElement.addEventListener('shown.bs.modal', shownHandler);
                        modalElement.addEventListener('hidden.bs.modal', hiddenHandler);
                    } catch (error) {
                        console.error('Error showing modal with Bootstrap:', error);
                        
                        // Fallback method if Bootstrap modal fails
                        inventoryModalInstance = null;
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
                        addModalButtonListeners();
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
            // Try to find the inventory links by ID first
            let inventoryLink = document.getElementById('inventoryLink');
            let sideInventoryLink = document.getElementById('sideInventoryLink');
            let found = false;
            
            // Set up event listener for footer inventory link if it exists
            if (inventoryLink) {
                console.log('Found footer inventory link by ID');
                inventoryLink.addEventListener('click', function(e) {
                    console.log('Footer inventory link clicked');
                    e.preventDefault();
                    e.stopPropagation();
                    openModal();
                    return false;
                });
                found = true;
            }
            
            // Set up event listener for side menu inventory link if it exists
            if (sideInventoryLink) {
                console.log('Found side menu inventory link by ID');
                sideInventoryLink.addEventListener('click', function(e) {
                    console.log('Side menu inventory link clicked');
                    e.preventDefault();
                    e.stopPropagation();
                    openModal();
                    return false;
                });
                found = true;
            }
            
            // If neither link was found by ID, try to find by text content
            if (!found) {
                console.log('Inventory links not found by ID, trying to find by text content');
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
                    console.error('Could not find any inventory links by ID or text content');
                }
            }
        }

        // Function to initialize the inventory modal
        function init() {
            console.log('Initializing inventory modal...');
            
            // Initialize global arrays for tracking resources
            window._inventoryTimeouts = [];
            window._inventoryAbortControllers = [];
            
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
        
        // Expose the openModal function globally for deep linking
        window.openInventoryModal = openModal;
    });
});
