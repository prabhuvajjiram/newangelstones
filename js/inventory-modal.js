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
                max-width: 98%;
                margin: 1rem auto;
                height: 95vh;
            }
            .inventory-modal .modal-content {
                background-color: #212529;
                color: #f8f9fa;
                border: 1px solid #495057;
                height: 100%;
                display: flex;
                flex-direction: column;
                font-size: 1.1rem;
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
                position: fixed;
                bottom: 20px;
                right: 20px;
                background: linear-gradient(135deg, #d4af37 0%, #f4d03f 100%);
                color: #000000;
                padding: 12px 24px;
                border-radius: 25px;
                border: 3px solid #ffffff;
                display: none;
                z-index: 1000;
                font-size: 1.1rem;
                font-weight: bold;
                box-shadow: 0 6px 12px rgba(0,0,0,0.5), 0 0 0 2px rgba(212, 175, 55, 0.3);
                animation: pulse 2s infinite;
                text-shadow: 1px 1px 2px rgba(255,255,255,0.5);
            }
            @keyframes pulse {
                0% { transform: scale(1); }
                50% { transform: scale(1.05); }
                100% { transform: scale(1); }
            }
            /* Navigation buttons for better accessibility */
            .inventory-modal .nav-buttons {
                position: fixed;
                top: 50%;
                transform: translateY(-50%);
                z-index: 1000;
            }
            .inventory-modal .nav-button-left {
                left: 10px;
            }
            .inventory-modal .nav-button-right {
                right: 10px;
            }
            .inventory-modal .nav-button {
                background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%);
                color: #ffffff;
                border: 4px solid #ffffff;
                padding: 18px 22px;
                border-radius: 50%;
                font-size: 1.8rem;
                cursor: pointer;
                box-shadow: 0 8px 16px rgba(0,0,0,0.7), 0 0 0 3px rgba(255, 107, 53, 0.4), inset 0 2px 4px rgba(255,255,255,0.3);
                transition: all 0.3s ease;
                font-weight: 900;
                text-shadow: 2px 2px 4px rgba(0,0,0,0.8);
                position: relative;
                z-index: 1001;
            }
            .inventory-modal .nav-button:hover {
                background: linear-gradient(135deg, #ff8c42 0%, #ff6b35 100%);
                transform: scale(1.2);
                box-shadow: 0 10px 20px rgba(0,0,0,0.8), 0 0 0 4px rgba(255, 140, 66, 0.6), inset 0 2px 4px rgba(255,255,255,0.4);
                border-color: #ffffff;
            }
            .inventory-modal .nav-button:disabled {
                background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
                color: #ffffff;
                border-color: #adb5bd;
                cursor: not-allowed;
                transform: none;
                box-shadow: 0 2px 4px rgba(0,0,0,0.3);
                text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
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
                padding: 1rem;
                vertical-align: middle;
                border-top: 1px solid #dee2e6;
                font-size: 1.1rem;
                min-width: 120px;
            }
            .inventory-modal .inventory-table thead th {
                vertical-align: bottom;
                border-bottom: 2px solid #dee2e6;
                background-color: #f8f9fa;
                position: sticky;
                top: 0;
                z-index: 5;
                font-weight: bold;
                font-size: 1.2rem;
                color: #212529;
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
                padding: 0.75rem 0.75rem 0.75rem 35px;
                background-color: #343a40;
                border-color: #495057;
                color: #f8f9fa;
                font-size: 1.1rem;
                border-width: 2px;
            }
            .inventory-modal .search-container input::placeholder {
                color: #6c757d;
            }
            .inventory-modal .summary-count {
                color: #adb5bd;
                font-size: 0.85rem;
                font-weight: normal;
                background: transparent;
                padding: 0;
                margin: 0;
                opacity: 0.8;
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
                font-size: 1rem;
                padding: 0.5rem;
                border-width: 2px;
                min-width: 100px;
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
            /* Enhanced responsive styles for better accessibility */
            @media (max-width: 1200px) {
                .inventory-modal .modal-dialog {
                    max-width: 100%;
                    margin: 0.5rem;
                }
                .inventory-modal .modal-body {
                    padding: 0.75rem;
                }
                .inventory-modal .inventory-table th,
                .inventory-modal .inventory-table td {
                    font-size: 1rem;
                    padding: 0.75rem;
                }
            }
            @media (max-width: 767.98px) {
                .inventory-modal .modal-dialog {
                    max-width: 100%;
                    margin: 0.25rem;
                    height: 98vh;
                }
                .inventory-modal .modal-content {
                    font-size: 0.9rem;
                }
                .inventory-modal .sticky-filters {
                    position: static;
                    box-shadow: none;
                }
                .inventory-modal .nav-button {
                    width: 35px;
                    height: 35px;
                    font-size: 0.8rem;
                }
                .inventory-modal .nav-button-left {
                    left: 5px;
                }
                .inventory-modal .nav-button-right {
                    right: 5px;
                }
                .inventory-modal .inventory-table th,
                .inventory-modal .inventory-table td {
                    font-size: 0.8rem;
                    padding: 0.4rem;
                }
                .inventory-modal .search-container input {
                    font-size: 0.9rem;
                    padding: 0.4rem 0.8rem;
                }
                .inventory-modal .column-filter {
                    font-size: 0.8rem;
                    padding: 0.3rem;
                }
                .inventory-modal .summary-count {
                    font-size: 0.75rem;
                }
            }
            @media (max-width: 480px) {
                .inventory-modal .modal-dialog {
                    margin: 0.1rem;
                    height: 99vh;
                }
                .inventory-modal .modal-content {
                    font-size: 0.8rem;
                }
                .inventory-modal .nav-button {
                    width: 30px;
                    height: 30px;
                    font-size: 0.7rem;
                }
                .inventory-modal .inventory-table th,
                .inventory-modal .inventory-table td {
                    font-size: 0.7rem;
                    padding: 0.3rem;
                }
                .inventory-modal .search-container input {
                    font-size: 0.8rem;
                    padding: 0.3rem 0.6rem;
                }
                .inventory-modal .column-filter {
                    font-size: 0.7rem;
                    padding: 0.2rem;
                }
            }
            /* High contrast mode support */
            @media (prefers-contrast: high) {
                .inventory-modal .modal-content {
                    border-width: 3px;
                    border-color: #ffffff;
                }
                .inventory-modal .inventory-table th,
                .inventory-modal .inventory-table td {
                    border-width: 2px;
                }
                .inventory-modal .btn {
                    border-width: 3px;
                }
            }
            /* Reduced motion support */
            @media (prefers-reduced-motion: reduce) {
                .inventory-modal .scroll-indicator {
                    animation: none;
                }
                .inventory-modal .nav-button {
                    transition: none;
                }
            }
            /* Split Panel Styles */
            .inventory-modal .split-view {
                display: flex;
                gap: 0;
                height: 600px;
            }
            .inventory-modal .table-panel {
                flex: 1 1 auto;
                min-width: 0;
                overflow: hidden;
                display: flex;
                flex-direction: column;
                transition: none;
            }
            .inventory-modal .table-panel.with-details {
                flex: 0 0 55%;
                min-width: 400px;
            }
            
            /* Mobile Responsive Styles */
            @media (max-width: 768px) {
                .inventory-modal .split-view {
                    flex-direction: column;
                    height: auto;
                }
                .inventory-modal .table-panel {
                    flex: 1 1 auto;
                    max-height: 40vh;
                }
                .inventory-modal .table-panel.with-details {
                    flex: 1 1 auto;
                    max-height: 30vh;
                }
                .inventory-modal .details-panel {
                    flex: 1 1 auto;
                    max-height: 50vh;
                    border-left: none;
                    border-top: 3px solid #d4af37;
                }
                .inventory-modal .modal-dialog {
                    max-width: 95%;
                    margin: 0.5rem;
                }
                .inventory-modal .stone-card {
                    margin-bottom: 0.75rem;
                    padding: 0.75rem;
                }
                .inventory-modal .details-header {
                    padding: 1rem;
                }
                .inventory-modal .details-header h5 {
                    font-size: 1.1rem;
                }
                .inventory-modal .details-header small {
                    font-size: 0.85rem;
                }
                .inventory-table {
                    font-size: 0.85rem;
                }
                .inventory-table th,
                .inventory-table td {
                    padding: 0.4rem !important;
                }
            }
            
            .inventory-modal .details-panel {
                flex: 0 0 45%;
                background-color: #f8f9fa;
                border-left: 2px solid #d4af37;
                overflow-y: auto;
                overflow-x: hidden;
                display: none;
                padding: 0;
                position: relative;
            }
            .inventory-modal .details-panel.show {
                display: block;
            }
            .inventory-modal .details-panel .close-details {
                position: sticky;
                top: 1rem;
                float: right;
                margin: 1rem 1rem 0 0;
                background: #fff;
                border: 2px solid #d4af37;
                border-radius: 50%;
                width: 36px;
                height: 36px;
                font-size: 1.8rem;
                line-height: 1;
                color: #333;
                cursor: pointer;
                z-index: 1000;
                display: flex;
                align-items: center;
                justify-content: center;
                box-shadow: 0 2px 8px rgba(0,0,0,0.3);
                transition: all 0.2s ease;
            }
            .inventory-modal .details-panel .close-details:hover {
                background: #d4af37;
                color: #fff;
                transform: rotate(90deg) scale(1.1);
            }
            .inventory-modal .stone-card {
                background-color: #fff;
                border: 1px solid #dee2e6;
                border-radius: 8px;
                padding: 1rem;
                margin-bottom: 1rem;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            .inventory-modal .stone-card h6 {
                color: #d4af37;
                font-weight: bold;
                margin-bottom: 0.75rem;
                border-bottom: 2px solid #d4af37;
                padding-bottom: 0.5rem;
            }
            .inventory-modal .info-row {
                display: flex;
                justify-content: space-between;
                padding: 0.5rem 0;
                border-bottom: 1px solid #e9ecef;
            }
            .inventory-modal .info-row:last-child {
                border-bottom: none;
            }
            .inventory-modal .info-label {
                font-weight: bold;
                color: #495057;
            }
            .inventory-modal .info-value {
                color: #212529;
            }
            .inventory-modal .details-header {
                background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
                color: #fff;
                padding: 1.5rem;
                margin: -1.5rem -1.5rem 1.5rem -1.5rem;
                border-radius: 0;
            }
            .inventory-modal .details-header h5 {
                color: #d4af37;
                margin: 0 0 0.5rem 0;
            }
            .inventory-modal .details-loading {
                text-align: center;
                padding: 3rem 1rem;
                color: #666;
            }
            .inventory-table tbody tr {
                cursor: pointer;
                transition: background-color 0.2s ease;
            }
            .inventory-table tbody tr:hover {
                background-color: #e9ecef !important;
            }
            .inventory-table tbody tr.selected {
                background-color: #fff3cd !important;
                border-left: 3px solid #d4af37;
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
                this.cacheExpiryMinutes = 1440; // Cache for 24 hours (1440 minutes) - inventory doesn't change frequently
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
                    let locationIds = ['45555', '45587']; // Default: fetch both locations (Elberton 45555 and Barre 45587)
                    
                    // If a specific location is selected, only fetch that one
                    if (this.selectedLocation) {
                        if (this.selectedLocation === 'Elberton') {
                            locationIds = ['45555'];
                        } else if (this.selectedLocation === 'Barre') {
                            locationIds = ['45587'];
                        }
                        // If any other location is selected that we don't recognize, keep both IDs
                    }
                    
                    console.log('Fetching data for locations:', locationIds);
                    
                    // Set a timeout for each request to prevent hanging
                    const timeout = 15000; // 15 seconds timeout per request
                    
                    // Function to fetch all pages for a single location
                    const fetchAllPagesForLocation = async (locid) => {
                        console.log(`Fetching ALL pages for location ${locid}...`);
                        const allItems = [];
                        let currentPage = 1;
                        let hasMorePages = true;
                        let totalItems = 0;
                        
                        while (hasMorePages) {
                            try {
                                // Prepare request parameters for this page
                                const params = {
                                    ...this.currentFilters,
                                    page: currentPage,
                                    pageSize: this.pageSize,
                                    locid: locid,
                                    token: '097EE598BBACB8A8182BC9D4D7D5CFE609E4DB2AF4A3F1950738C927ECF05B6A',
                                    timestamp: Date.now() // Prevent caching
                                };
                                
                                console.log(`Fetching page ${currentPage} for location ${locid}...`);
                                
                                // Create abort controller for this request
                                const abortController = new AbortController();
                                if (!window._inventoryAbortControllers) {
                                    window._inventoryAbortControllers = [];
                                }
                                window._inventoryAbortControllers.push(abortController);
                                
                                // Create query string
                                const queryString = new URLSearchParams(params).toString();
                                
                                // Create timeout promise
                                const timeoutPromise = new Promise((_, reject) => {
                                    const timeoutId = setTimeout(() => {
                                        abortController.abort();
                                        reject(new Error(`Request timeout for location ${locid} page ${currentPage} after ${timeout}ms`));
                                    }, timeout);
                                    
                                    if (!window._inventoryTimeouts) {
                                        window._inventoryTimeouts = [];
                                    }
                                    window._inventoryTimeouts.push(timeoutId);
                                });
                                
                                // Fetch data with timeout
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
                                });
                                
                                const data = await Promise.race([fetchPromise, timeoutPromise]);
                                
                                // Validate response
                                if (!data || data.error) {
                                    console.error(`Error for location ${locid} page ${currentPage}:`, data?.error || 'Unknown error');
                                    hasMorePages = false;
                                    break;
                                }
                                
                                // Get items from response
                                const items = data.Data || data.data || [];
                                console.log(`Location ${locid} page ${currentPage}: Got ${items.length} items`);
                                
                                if (items.length > 0) {
                                    // Add location name to each item
                                    // Use actual location name from API if available, otherwise map by location ID
                                    const processedItems = items.map(item => ({
                                        ...item,
                                        Locationname: item.Locationname || item.locationname || 
                                            (locid === '45555' ? 'Elberton' : locid === '45587' ? 'Barre' : `Location ${locid}`)
                                    }));
                                    
                                    allItems.push(...processedItems);
                                    
                                    // Get total count from first page
                                    if (currentPage === 1) {
                                        totalItems = data.Total || data.total || data.totalItems || 0;
                                        console.log(`Location ${locid} total items from API: ${totalItems}`);
                                    }
                                }
                                
                                // Check if we have more pages - Multiple conditions:
                                // 1. If no items returned, we're done
                                // 2. If items.length < pageSize, we're on the last page
                                // 3. If we have totalItems and allItems.length >= totalItems, we have everything
                                if (items.length === 0) {
                                    hasMorePages = false;
                                    console.log(`Location ${locid}: No items on page ${currentPage}, stopping`);
                                } else if (items.length < this.pageSize) {
                                    hasMorePages = false;
                                    console.log(`Location ${locid}: Last page reached (got ${items.length} items, pageSize: ${this.pageSize})`);
                                } else if (totalItems > 0 && allItems.length >= totalItems) {
                                    hasMorePages = false;
                                    console.log(`Location ${locid}: All items fetched (${allItems.length} of ${totalItems})`);
                                } else {
                                    currentPage++;
                                    console.log(`Location ${locid}: Continuing to page ${currentPage} (have ${allItems.length} items so far)`);
                                }
                                
                            } catch (error) {
                                console.error(`Error fetching page ${currentPage} for location ${locid}:`, error);
                                hasMorePages = false;
                            }
                        }
                        
                        console.log(`Location ${locid}: Fetched total of ${allItems.length} items across ${currentPage} pages`);
                        return {
                            success: true,
                            Data: allItems,
                            Total: totalItems || allItems.length,
                            location: locid
                        };
                    };
                    
                    // Fetch all pages for each location in parallel
                    const requests = locationIds.map(locid => fetchAllPagesForLocation(locid));
                    
                    // Wait for all location requests to complete
                    const results = await Promise.allSettled(requests);
                    console.log('All location data fetched:', results);
                    
                    // Combine the results from all locations
                    const combinedData = {
                        Data: [],
                        Total: 0
                    };
                    
                    // Process each location result
                    let hasValidData = false;
                    results.forEach((result, index) => {
                        const locationId = locationIds[index];
                        
                        // Check if the promise was fulfilled
                        if (result.status === 'fulfilled') {
                            const data = result.value;
                            console.log(`Processing data for location ${locationId}:`, {
                                itemCount: data.Data?.length || 0,
                                total: data.Total
                            });
                            
                            // Check if we have data
                            if (data && data.success && Array.isArray(data.Data) && data.Data.length > 0) {
                                hasValidData = true;
                                combinedData.Data = [...combinedData.Data, ...data.Data];
                                combinedData.Total += parseInt(data.Total, 10) || data.Data.length;
                            } else {
                                console.warn(`No data for location ${locationId}`);
                            }
                        } else {
                            // Promise was rejected
                            console.error(`Request failed for location ${locationId}:`, result.reason);
                        }
                    });
                    
                    console.log(`Combined data: ${combinedData.Data.length} items total (expected: ${combinedData.Total})`);
                    
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
                                Locationname: 'Barre',
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
        
        // Function to close item details panel
        window.closeItemDetails = function() {
            const detailsPanel = document.getElementById('detailsPanel');
            const tablePanel = document.getElementById('tablePanel');
            const selectedRows = document.querySelectorAll('.inventory-table tbody tr.selected');
            
            if (detailsPanel) {
                detailsPanel.classList.remove('show');
                // Reset content to default state
                const content = document.getElementById('detailsPanelContent');
                if (content) {
                    content.innerHTML = `
                        <div class="details-loading">
                            <i class="fas fa-hand-pointer fa-3x mb-3" style="color: #d4af37;"></i>
                            <p>Click on any item to view details</p>
                        </div>
                    `;
                }
            }
            if (tablePanel) {
                tablePanel.classList.remove('with-details');
            }
            selectedRows.forEach(row => row.classList.remove('selected'));
        };
        
        // Function to fetch detailed stone records for an item
        async function fetchItemDetails(endProductCode) {
            try {
                const response = await fetch('inventory-proxy.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=getDetails&epcode=${encodeURIComponent(endProductCode)}`
                });
                
                if (!response.ok) {
                    throw new Error('Failed to fetch item details');
                }
                
                const data = await response.json();
                return data;
            } catch (error) {
                console.error('Error fetching item details:', error);
                throw error;
            }
        }
        
        // Function to show item details in split panel
        async function showItemDetails(endProductCode, basicItem, clickedRow) {
            const detailsPanel = document.getElementById('detailsPanel');
            const tablePanel = document.getElementById('tablePanel');
            const content = document.getElementById('detailsPanelContent');
            
            if (!detailsPanel || !content) {
                console.error('Details panel not found');
                return;
            }
            
            // Remove previous selection and add to clicked row
            document.querySelectorAll('.inventory-table tbody tr.selected').forEach(row => {
                row.classList.remove('selected');
            });
            if (clickedRow) {
                clickedRow.classList.add('selected');
            }
            
            // Show panels
            tablePanel.classList.add('with-details');
            detailsPanel.classList.add('show');
            
            // Show loading state
            content.innerHTML = `
                <div class="details-loading">
                    <div class="spinner-border" role="status" style="color: #d4af37;">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-3">Loading stone details...</p>
                </div>
            `;
            
            try {
                const data = await fetchItemDetails(endProductCode);
                const stones = data.stones || [];
                
                if (stones.length === 0) {
                    content.innerHTML = `
                        <div class="details-header">
                            <h5>Item Details</h5>
                            <small style="color: #adb5bd;">${basicItem.description}</small>
                        </div>
                        <div style="padding: 1rem;">
                            <div class="stone-card">
                                <h6>Product Information</h6>
                                <div class="info-row">
                                    <span class="info-label">Type:</span>
                                    <span class="info-value">${basicItem.type}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Color:</span>
                                    <span class="info-value">${basicItem.color}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Size:</span>
                                    <span class="info-value">${basicItem.size}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Total Quantity:</span>
                                    <span class="info-value">${basicItem.quantity}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Location:</span>
                                    <span class="info-value">${basicItem.location}</span>
                                </div>
                            </div>
                            <p class="text-muted mt-3"><i class="fas fa-info-circle"></i> Detailed stone records not available.</p>
                        </div>
                    `;
                    return;
                }
                
                // Show basic info and individual stones
                let html = `
                    <div class="details-header">
                        <h5>Item Details</h5>
                        <small style="color: #adb5bd;">${basicItem.description}</small>
                    </div>
                    <div style="padding: 0;">
                        <div class="stone-card">
                            <h6><i class="fas fa-info-circle"></i> Product Summary</h6>
                            <div class="info-row">
                                <span class="info-label">Type:</span>
                                <span class="info-value">${basicItem.type}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Color:</span>
                                <span class="info-value">${basicItem.color}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Size:</span>
                                <span class="info-value">${basicItem.size}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Total Available:</span>
                                <span class="info-value" style="color: #28a745; font-weight: bold;">${basicItem.quantity}</span>
                            </div>
                        </div>
                        
                        <h6 class="mt-3 mb-3" style="color: #495057; padding: 0 1rem;"><i class="fas fa-cubes"></i> Individual Stones (${stones.length})</h6>
                    </div>
                `;
                
                stones.forEach((stone, index) => {
                    html += `
                        <div class="stone-card">
                            <h6>Stone #${index + 1}</h6>
                            ${stone.Container ? `
                                <div class="info-row">
                                    <span class="info-label">Container:</span>
                                    <span class="info-value">${stone.Container}</span>
                                </div>
                            ` : ''}
                            ${stone.CrateNo ? `
                                <div class="info-row">
                                    <span class="info-label">Crate Number:</span>
                                    <span class="info-value">${stone.CrateNo}</span>
                                </div>
                            ` : ''}
                            ${stone.LocationName ? `
                                <div class="info-row">
                                    <span class="info-label">Location:</span>
                                    <span class="info-value">${stone.LocationName}</span>
                                </div>
                            ` : ''}
                            ${stone.SublocationName ? `
                                <div class="info-row">
                                    <span class="info-label">Sublocation:</span>
                                    <span class="info-value">${stone.SublocationName}</span>
                                </div>
                            ` : ''}
                            ${stone.Weight ? `
                                <div class="info-row">
                                    <span class="info-label">Weight:</span>
                                    <span class="info-value">${stone.Weight} lbs</span>
                                </div>
                            ` : ''}
                            ${stone.Status ? `
                                <div class="info-row">
                                    <span class="info-label">Status:</span>
                                    <span class="info-value">${stone.Status}</span>
                                </div>
                            ` : ''}
                            ${stone.StockId ? `
                                <div class="info-row">
                                    <span class="info-label">Stock ID:</span>
                                    <span class="info-value">${stone.StockId}</span>
                                </div>
                            ` : ''}
                            ${stone.Comments ? `
                                <div class="info-row">
                                    <span class="info-label">Notes:</span>
                                    <span class="info-value">${stone.Comments}</span>
                                </div>
                            ` : ''}
                        </div>
                    `;
                });
                
                content.innerHTML = html;
            } catch (error) {
                content.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Error loading details</strong>
                        <p>${error.message}</p>
                    </div>
                `;
            }
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

                // Summary count display
                const summaryDiv = document.createElement('div');
                summaryDiv.className = 'summary-count';
                summaryDiv.id = 'summaryCount';
                summaryDiv.textContent = 'Loading...';
                modalFooter.appendChild(summaryDiv);

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
                        <span id="activeBadge" class="badge bg-secondary" style="display:none;">Filters Active</span>
                    </div>
                `;
                
                // Build the table HTML with split-view structure
                const tableHtml = `
                    <div class="split-view">
                        <div class="table-panel" id="tablePanel">
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
                                            <th role="columnheader" tabindex="0">Quantity</th>
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
                                            <tr class="${highlight.trim()} clickable-row" data-code="${getField('EndProductCode')}" style="cursor: pointer;">
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
                        </div>
                        <div class="details-panel" id="detailsPanel">
                            <div style="position: sticky; top: 0; z-index: 100; background: #f8f9fa; padding: 0.5rem 0.5rem 0 0; text-align: right;">
                                <button class="close-details" onclick="window.closeItemDetails()">&times;</button>
                            </div>
                            <div id="detailsPanelContent" style="padding: 0 1.5rem 1.5rem 1.5rem;">
                                <div class="details-loading">
                                    <i class="fas fa-hand-pointer fa-3x mb-3" style="color: #d4af37;"></i>
                                    <p>Click on any item to view details</p>
                                </div>
                            </div>
                        </div>
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
                setupKeyboardNavigation();
                setupNavigationButtons();
                
                // Set up row click listeners AFTER table is fully rendered
                setupRowClickListeners(filteredItems);
                
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

            const summary = document.getElementById('summaryCount');
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

        // Function to set up navigation buttons for horizontal scrolling
        function setupNavigationButtons() {
            const leftBtn = document.querySelector('.nav-button-left');
            const rightBtn = document.querySelector('.nav-button-right');
            const container = document.getElementById('inventoryTableContainer');

            if (leftBtn && rightBtn && container) {
                leftBtn.addEventListener('click', () => {
                    container.scrollBy({ left: -200, behavior: 'smooth' });
                });

                rightBtn.addEventListener('click', () => {
                    container.scrollBy({ left: 200, behavior: 'smooth' });
                });

                // Update button states based on scroll position
                const updateNavButtons = () => {
                    const { scrollLeft, scrollWidth, clientWidth } = container;
                    leftBtn.disabled = scrollLeft <= 0;
                    rightBtn.disabled = scrollLeft >= scrollWidth - clientWidth - 1;
                };

                container.addEventListener('scroll', updateNavButtons);
                updateNavButtons(); // Initial state
            }
        }

        // Function to set up row click listeners for showing details
        function setupRowClickListeners(items) {
            const rows = document.querySelectorAll('#inventoryTable tbody tr.clickable-row');
            
            rows.forEach(row => {
                row.addEventListener('click', function(e) {
                    // Prevent event bubbling
                    e.stopPropagation();
                    
                    // Don't trigger if clicking on a dropdown, filter, or button
                    if (e.target.closest('.dropdown, select, button, input')) {
                        return;
                    }
                    
                    const code = this.getAttribute('data-code');
                    if (code) {
                        // Find the item data
                        const item = items.find(i => {
                            const itemCode = i.EndProductCode || i.endProductCode || i.code;
                            return itemCode === code;
                        });
                        
                        if (item) {
                            const basicItem = {
                                description: item.EndProductDescription || item.description || '',
                                type: item.Ptype || item.type || '',
                                color: item.PColor || item.color || '',
                                size: item.Size || item.size || '',
                                quantity: item.Qty || item.quantity || 0,
                                location: item.Locationname || item.location || ''
                            };
                            showItemDetails(code, basicItem, this);
                        }
                    }
                });
            });
        }
        
        // Function to set up keyboard navigation for accessibility
        function setupKeyboardNavigation() {
            const container = document.getElementById('inventoryTableContainer');
            const table = document.getElementById('inventoryTable');

            if (container && table) {
                // Make container focusable for keyboard navigation
                container.setAttribute('tabindex', '0');
                
                // Add keyboard navigation to table container
                container.addEventListener('keydown', (e) => {
                    switch (e.key) {
                        case 'ArrowLeft':
                            e.preventDefault();
                            container.scrollBy({ left: -100, behavior: 'smooth' });
                            break;
                        case 'ArrowRight':
                            e.preventDefault();
                            container.scrollBy({ left: 100, behavior: 'smooth' });
                            break;
                        case 'ArrowUp':
                            e.preventDefault();
                            container.scrollBy({ top: -50, behavior: 'smooth' });
                            break;
                        case 'ArrowDown':
                            e.preventDefault();
                            container.scrollBy({ top: 50, behavior: 'smooth' });
                            break;
                        case 'Home':
                            e.preventDefault();
                            container.scrollTo({ left: 0, top: 0, behavior: 'smooth' });
                            break;
                        case 'End':
                            e.preventDefault();
                            container.scrollTo({ 
                                left: container.scrollWidth, 
                                top: container.scrollHeight, 
                                behavior: 'smooth' 
                            });
                            break;
                    }
                });

                // Add focus management for table rows
                const rows = table.querySelectorAll('tbody tr');
                rows.forEach((row, index) => {
                    row.addEventListener('keydown', (e) => {
                        let targetRow = null;
                        switch (e.key) {
                            case 'ArrowUp':
                                e.preventDefault();
                                targetRow = rows[Math.max(0, index - 1)];
                                break;
                            case 'ArrowDown':
                                e.preventDefault();
                                targetRow = rows[Math.min(rows.length - 1, index + 1)];
                                break;
                        }
                        if (targetRow) {
                            targetRow.focus();
                        }
                    });
                });
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
