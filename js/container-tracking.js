class ContainerTracker {
    constructor() {
        this.inventoryData = null;
        this.currentSearchTerm = '';
        this.currentPage = 1;
        this.itemsPerPage = 20;
        this.totalPages = 1;
        console.log('ContainerTracker initialized');
    }

    async init() {
        console.log('Starting initialization');
        try {
            await this.loadInventoryData();
            this.setupEventListeners();
            console.log('Initialization complete');
        } catch (error) {
            console.error('Error during initialization:', error);
            this.showError('Failed to initialize inventory tracking. Check console for details.');
        }
    }

    async loadInventoryData(searchTerm = '') {
        try {
            console.log('Fetching inventory data from API...');
            document.getElementById('container-view').innerHTML = '<div class="loading">Loading inventory data...</div>';
            
            // Store the current search term
            this.currentSearchTerm = searchTerm;
            
            // Build the API URL with search parameter if provided
            let apiUrl = '/api/container-data.php';
            if (searchTerm) {
                apiUrl += `?search=${encodeURIComponent(searchTerm)}`;
            }
            apiUrl += `&page=${this.currentPage}&limit=${this.itemsPerPage}`;
            
            const response = await fetch(apiUrl);
            console.log('API response status:', response.status);
            
            if (!response.ok) {
                throw new Error(`API returned ${response.status}: ${response.statusText}`);
            }
            
            // Get the response as text first
            const responseText = await response.text();
            
            // Log the first part of the response for debugging
            console.log('API response text (first 200 chars):', 
                responseText.length > 200 ? responseText.substring(0, 200) + '...' : responseText);
            
            // Before parsing, make sure we have valid JSON
            let trimmedText = responseText.trim();
            
            // Check if response is empty
            if (!trimmedText) {
                throw new Error('Empty response from API');
            }
            
            // Try to parse the JSON
            try {
                // Handle potential BOM character at the start of the response
                if (trimmedText.charCodeAt(0) === 0xFEFF) {
                    trimmedText = trimmedText.substring(1);
                }
                
                const data = JSON.parse(trimmedText);
                this.inventoryData = data.data;
                this.currentPage = data.pagination.page;
                this.totalPages = data.pagination.totalPages;
                this.itemsPerPage = data.pagination.itemsPerPage;
                console.log('Parsed data:', this.inventoryData);
                
                if (!this.inventoryData || !this.inventoryData.containers) {
                    if (this.inventoryData && this.inventoryData.error) {
                        throw new Error(`API Error: ${this.inventoryData.error}`);
                    } else {
                        throw new Error('API did not return valid inventory data');
                    }
                }
                
                this.renderInventoryData();
                this.updateStatistics();
                this.updateSearchInfo();
                this.renderPagination();
            } catch (jsonError) {
                console.error('JSON parsing error:', jsonError, 'Response text:', trimmedText);
                this.showError(`Failed to parse API response: ${jsonError.message}. Check the console for details.`);
            }
        } catch (error) {
            console.error('Error loading inventory data:', error);
            this.showError(`Failed to load inventory data: ${error.message}`);
        }
    }

    updateStatistics() {
        if (!this.inventoryData) return;
        
        // Update total containers
        const totalContainers = this.inventoryData.totalContainers || 0;
        document.getElementById('total-containers').textContent = totalContainers;
        
        // Update total items
        const totalItems = this.inventoryData.totalItems || 0;
        document.getElementById('total-items').textContent = totalItems;
        
        // Count in-transit and delivered containers
        let inTransitCount = 0;
        let deliveredCount = 0;
        
        this.inventoryData.containers.forEach(container => {
            if (container.status === 'In Transit') {
                inTransitCount++;
            } else if (container.status === 'Delivered') {
                deliveredCount++;
            }
        });
        
        document.getElementById('in-transit').textContent = inTransitCount;
        document.getElementById('delivered').textContent = deliveredCount;
    }
    
    updateSearchInfo() {
        const searchInfoElement = document.getElementById('search-info');
        
        if (!searchInfoElement) return;
        
        if (this.currentSearchTerm) {
            const totalItems = this.inventoryData.totalItems || 0;
            const totalContainers = this.inventoryData.totalContainers || 0;
            
            searchInfoElement.innerHTML = `
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    Search results for "<span class="search-highlight">${this.currentSearchTerm}</span>": 
                    Found ${totalItems} products in ${totalContainers} containers
                </div>
            `;
        } else {
            searchInfoElement.innerHTML = '';
        }
    }

    showError(message) {
        const containerView = document.getElementById('container-view');
        if (containerView) {
            containerView.innerHTML = `
                <div class="error-message">
                    <i class="bi bi-exclamation-triangle"></i>
                    <p>${message}</p>
                    <button id="retry-button" class="btn btn-primary">Retry</button>
                </div>
            `;
            
            // Add retry button listener
            const retryButton = document.getElementById('retry-button');
            if (retryButton) {
                retryButton.addEventListener('click', () => this.loadInventoryData(this.currentSearchTerm));
            }
        }
    }

    setupEventListeners() {
        console.log('Setting up event listeners');
        
        // Refresh button
        const refreshButton = document.getElementById('refresh-tracking');
        if (refreshButton) {
            refreshButton.addEventListener('click', () => this.loadInventoryData(this.currentSearchTerm));
            console.log('Refresh button listener added');
        }
        
        // Search functionality
        const searchButton = document.getElementById('search-button');
        const searchInput = document.getElementById('search-input');
        const clearSearchButton = document.getElementById('clear-search');
        
        if (searchButton && searchInput) {
            // Search button click
            searchButton.addEventListener('click', () => {
                const searchTerm = searchInput.value.trim();
                this.loadInventoryData(searchTerm);
            });
            
            // Enter key in search input
            searchInput.addEventListener('keyup', (e) => {
                if (e.key === 'Enter') {
                    const searchTerm = searchInput.value.trim();
                    this.loadInventoryData(searchTerm);
                }
            });
            
            console.log('Search listeners added');
        }
        
        // Clear search
        if (clearSearchButton) {
            clearSearchButton.addEventListener('click', () => {
                if (searchInput) {
                    searchInput.value = '';
                }
                this.loadInventoryData('');
            });
        }
        
        // Delegate container click events
        const containerView = document.getElementById('container-view');
        if (containerView) {
            containerView.addEventListener('click', (e) => {
                // Handle container header clicks for expand/collapse
                if (e.target.closest('.container-header')) {
                    const containerCard = e.target.closest('.container-card');
                    if (containerCard) {
                        this.toggleContainer(containerCard);
                    }
                }
                
                // Handle view toggle (grid/table)
                if (e.target.closest('.view-toggle-btn')) {
                    const button = e.target.closest('.view-toggle-btn');
                    const container = button.closest('.container-content');
                    const viewType = button.getAttribute('data-view');
                    
                    if (container && viewType) {
                        this.toggleViewType(container, viewType);
                    }
                }
                
                // Handle pagination
                if (e.target.closest('.pagination-link')) {
                    const link = e.target.closest('.pagination-link');
                    const page = parseInt(link.getAttribute('data-page'));
                    if (page) {
                        this.currentPage = page;
                        this.loadInventoryData(this.currentSearchTerm);
                    }
                }
            });
        }
    }
    
    toggleContainer(containerCard) {
        const content = containerCard.querySelector('.container-content');
        const icon = containerCard.querySelector('.toggle-icon');
        
        if (content.classList.contains('expanded')) {
            content.classList.remove('expanded');
            icon.classList.remove('expanded');
        } else {
            content.classList.add('expanded');
            icon.classList.add('expanded');
        }
    }
    
    toggleViewType(container, viewType) {
        // Update active button
        const buttons = container.querySelectorAll('.view-toggle-btn');
        buttons.forEach(btn => {
            btn.classList.remove('active');
            if (btn.getAttribute('data-view') === viewType) {
                btn.classList.add('active');
            }
        });
        
        // Toggle view content
        const gridView = container.querySelector('.items-grid');
        const tableView = container.querySelector('.items-table-container');
        
        if (viewType === 'grid') {
            gridView.style.display = 'grid';
            tableView.style.display = 'none';
        } else {
            gridView.style.display = 'none';
            tableView.style.display = 'block';
        }
    }

    renderInventoryData() {
        console.log('Rendering inventory data');
        const containerView = document.getElementById('container-view');
        if (!containerView) {
            console.error('Container view element not found');
            return;
        }
        
        if (!this.inventoryData || !this.inventoryData.containers || this.inventoryData.containers.length === 0) {
            containerView.innerHTML = '<div class="no-data">No inventory data available</div>';
            console.warn('No inventory data to display', this.inventoryData);
            return;
        }

        let html = '';
        for (const container of this.inventoryData.containers) {
            try {
                // Validate required container properties
                if (!container.containerId) {
                    console.warn('Container missing containerId', container);
                    continue;
                }
                
                const statusClass = (container.status || 'unknown').toLowerCase().replace(/\s+/g, '-');
                const hasItems = container.items && container.items.length > 0;
                
                // Default to first container expanded
                const isFirstContainer = this.inventoryData.containers.indexOf(container) === 0;
                const expandedClass = isFirstContainer ? 'expanded' : '';
                const iconClass = isFirstContainer ? 'expanded' : '';
                
                html += `
                    <div class="container-card" data-container="${container.containerId || 'unknown'}">
                        <div class="container-header">
                            <h3>
                                <i class="bi bi-chevron-right toggle-icon ${iconClass}"></i>
                                ${container.name || 'Unknown Container'}
                                <span class="item-count-badge">
                                    <i class="bi bi-box"></i> ${container.itemCount || 0} items
                                </span>
                            </h3>
                            <span class="status ${statusClass}">
                                ${container.status || 'Status Unknown'}
                            </span>
                        </div>
                        <div class="container-content ${expandedClass}">
                            ${this.renderItemsSection(container.items || [])}
                        </div>
                    </div>
                `;
            } catch (e) {
                console.error('Error rendering container', e, container);
            }
        }
        
        if (!html) {
            containerView.innerHTML = '<div class="no-data">Failed to render inventory data. Check console for details.</div>';
            return;
        }
        
        containerView.innerHTML = html;
        console.log('Inventory data rendered');
    }
    
    renderItemsSection(items) {
        if (!items || items.length === 0) {
            return `
                <div class="items-table-container">
                    <div class="no-data">No items available</div>
                </div>
            `;
        }
        
        return `
            <div class="view-toggle-options">
                <button class="view-toggle-btn active" data-view="grid">
                    <i class="bi bi-grid"></i> Grid View
                </button>
                <button class="view-toggle-btn" data-view="table">
                    <i class="bi bi-table"></i> Table View
                </button>
            </div>
            
            ${this.renderItemsGrid(items)}
            ${this.renderItemsTable(items)}
        `;
    }
    
    renderItemsGrid(items) {
        if (!items || items.length === 0) {
            return '<div class="no-data">No items available</div>';
        }
        
        let html = '<div class="items-grid">';
        
        for (const item of items) {
            try {
                html += `
                    <div class="item-card">
                        <div class="item-header">
                            <h4>${this.safeText(item.Product_Type) || 'Unknown Product'}</h4>
                        </div>
                        <div class="item-body">
                            <div class="item-details">
                                <div class="label">Color:</div>
                                <div class="value">${this.safeText(item.Color)}</div>
                                
                                <div class="label">Design:</div>
                                <div class="value">${this.safeText(item.Design)}</div>
                                
                                <div class="label">Finish:</div>
                                <div class="value">${this.safeText(item.Finish)}</div>
                                
                                <div class="label">Size:</div>
                                <div class="value">${this.safeText(item.Product_Size)}</div>
                                
                                <div class="label">Description:</div>
                                <div class="value">${this.safeText(item.Product_Description)}</div>
                                
                                <div class="label">Quantity:</div>
                                <div class="value">${this.safeText(item.Qty)}</div>
                                
                                <div class="label">Crate:</div>
                                <div class="value">${this.safeText(item.Crate_No)}</div>
                            </div>
                        </div>
                    </div>
                `;
            } catch (e) {
                console.error('Error rendering item card', e, item);
            }
        }
        
        html += '</div>';
        return html;
    }

    renderItemsTable(items) {
        if (!items || items.length === 0) {
            return `
                <div class="items-table-container" style="display: none;">
                    <div class="no-data">No items available</div>
                </div>
            `;
        }
        
        // Identify which columns we have data for
        const columns = {
            'Product_Type': 'Product Type',
            'Color': 'Color',
            'Design': 'Design',
            'Finish': 'Finish',
            'Product_Size': 'Size',
            'Product_Description': 'Description',
            'Status': 'Status',
            'Qty': 'Quantity',
            'Crate_No': 'Crate No',
            'Comments': 'Comments'
        };
        
        // Build table header
        let tableHtml = `
            <div class="items-table-container" style="display: none;">
                <table class="items-table">
                    <thead>
                        <tr>
        `;
        
        // Add columns that have data
        for (const [key, label] of Object.entries(columns)) {
            tableHtml += `<th>${label}</th>`;
        }
        
        tableHtml += `
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        // Build table body
        for (const item of items) {
            try {
                tableHtml += '<tr>';
                
                for (const key of Object.keys(columns)) {
                    tableHtml += `<td>${this.safeText(item[key])}</td>`;
                }
                
                tableHtml += '</tr>';
            } catch (e) {
                console.error('Error rendering item row', e, item);
            }
        }
        
        tableHtml += `
                    </tbody>
                </table>
            </div>
        `;
        
        return tableHtml;
    }
    
    safeText(value) {
        if (value === null || value === undefined) return '';
        return String(value);
    }

    renderPagination() {
        if (!this.paginationElement || this.totalPages <= 1) {
            this.paginationElement.innerHTML = '';
            return;
        }
        
        const paginationHtml = `
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <li class="page-item ${this.currentPage === 1 ? 'disabled' : ''}">
                        <a class="pagination-link" href="#" data-page="${this.currentPage - 1}" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                    ${this.generatePageNumbers()}
                    <li class="page-item ${this.currentPage === this.totalPages ? 'disabled' : ''}">
                        <a class="pagination-link" href="#" data-page="${this.currentPage + 1}" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                </ul>
            </nav>
        `;
        
        this.paginationElement.innerHTML = paginationHtml;
        
        // Add event listeners to pagination links
        const pageLinks = this.paginationElement.querySelectorAll('.pagination-link');
        pageLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const page = parseInt(e.currentTarget.dataset.page);
                if (page && page !== this.currentPage) {
                    this.currentPage = page;
                    this.loadInventoryData(this.currentSearchTerm);
                }
            });
        });
    }
    
    generatePageNumbers() {
        let pageNumbers = '';
        const maxVisiblePages = 5;
        let startPage = Math.max(1, this.currentPage - Math.floor(maxVisiblePages / 2));
        let endPage = Math.min(this.totalPages, startPage + maxVisiblePages - 1);
        
        // Adjust if we're near the end
        if (endPage - startPage + 1 < maxVisiblePages) {
            startPage = Math.max(1, endPage - maxVisiblePages + 1);
        }
        
        // First page
        if (startPage > 1) {
            pageNumbers += `
                <li class="page-item">
                    <a class="pagination-link" href="#" data-page="1">1</a>
                </li>
            `;
            if (startPage > 2) {
                pageNumbers += `
                    <li class="page-item disabled">
                        <a class="pagination-link" href="#">...</a>
                    </li>
                `;
            }
        }
        
        // Page numbers
        for (let i = startPage; i <= endPage; i++) {
            pageNumbers += `
                <li class="page-item ${i === this.currentPage ? 'active' : ''}">
                    <a class="pagination-link" href="#" data-page="${i}">${i}</a>
                </li>
            `;
        }
        
        // Last page
        if (endPage < this.totalPages) {
            if (endPage < this.totalPages - 1) {
                pageNumbers += `
                    <li class="page-item disabled">
                        <a class="pagination-link" href="#">...</a>
                    </li>
                `;
            }
            pageNumbers += `
                <li class="page-item">
                    <a class="pagination-link" href="#" data-page="${this.totalPages}">${this.totalPages}</a>
                </li>
            `;
        }
        
        return pageNumbers;
    }
}

// Initialize the tracker
document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM content loaded, initializing ContainerTracker');
    const tracker = new ContainerTracker();
    tracker.init();
});
