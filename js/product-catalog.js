class ProductCatalog {
    constructor() {
        this.products = [];
        this.filters = {
            type: [],
            color: [],
            price: { min: 0, max: 0 }
        };
        this.init();
    }

    async init() {
        console.log('Initializing product catalog...');
        await this.loadProducts();
        this.setupFilters();
        this.setupEventListeners();
        this.render();
    }

    async loadProducts() {
        try {
            const response = await fetch('api/get_products.php');
            const result = await response.json();
            
            if (result.status === 'success') {
                this.products = result.data;
                console.log(`Loaded ${this.products.length} products successfully`);
                
                // Set price range
                const prices = this.products.map(p => p.price);
                this.filters.price = {
                    min: Math.min(...prices),
                    max: Math.max(...prices)
                };
            } else {
                throw new Error(result.message || 'Failed to load products');
            }
        } catch (error) {
            console.error('Error loading products:', error);
            // Show error message to user
            const container = document.getElementById('product-grid');
            if (container) {
                container.innerHTML = `
                    <div class="alert alert-danger" role="alert">
                        <h4 class="alert-heading">Error Loading Products</h4>
                        <p>We're sorry, but there was an error loading the product catalog. Please try refreshing the page.</p>
                        <hr>
                        <p class="mb-0">If the problem persists, please contact support.</p>
                    </div>
                `;
            }
        }
    }

    setupFilters() {
        if (!this.products.length) return;
        
        // Extract unique values for filters
        this.filters.type = [...new Set(this.products.map(p => p.type))];
        this.filters.color = [...new Set(this.products.map(p => p.color))];
        
        // Create filter UI
        const filterContainer = document.getElementById('product-filters');
        if (!filterContainer) {
            console.error('Filter container not found');
            return;
        }

        filterContainer.innerHTML = `
            <div class="filter-section">
                <h4>Product Type</h4>
                <div class="type-filters">
                    ${this.filters.type.map(type => `
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="${type}" id="type-${type}">
                            <label class="form-check-label" for="type-${type}">${type}</label>
                        </div>
                    `).join('')}
                </div>
            </div>
            <div class="filter-section">
                <h4>Color</h4>
                <div class="color-filters">
                    ${this.filters.color.map(color => `
                        <div class="color-option" data-color="${color}">
                            <span class="color-swatch" style="background-color: ${color.toLowerCase()}"></span>
                            <span class="color-name">${color}</span>
                        </div>
                    `).join('')}
                </div>
            </div>
            <div class="filter-section">
                <h4>Price Range</h4>
                <div class="price-range">
                    <input type="range" class="form-range" id="price-range" 
                           min="${this.filters.price.min}" 
                           max="${this.filters.price.max}"
                           value="${this.filters.price.max}">
                    <div class="price-labels">
                        <span>$${this.filters.price.min}</span>
                        <span>$${this.filters.price.max}</span>
                    </div>
                </div>
            </div>
        `;
    }

    setupEventListeners() {
        // Filter change events
        document.querySelectorAll('.type-filters input').forEach(input => {
            input.addEventListener('change', () => this.applyFilters());
        });

        document.querySelectorAll('.color-option').forEach(option => {
            option.addEventListener('click', () => {
                option.classList.toggle('selected');
                this.applyFilters();
            });
        });

        const priceRange = document.getElementById('price-range');
        if (priceRange) {
            priceRange.addEventListener('input', () => this.applyFilters());
        }

        // Comparison tool
        document.querySelectorAll('.compare-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', (e) => this.handleComparison(e));
        });
    }

    applyFilters() {
        const selectedTypes = [...document.querySelectorAll('.type-filters input:checked')]
            .map(input => input.value);
        
        const selectedColors = [...document.querySelectorAll('.color-option.selected')]
            .map(option => option.dataset.color);
        
        const maxPrice = document.getElementById('price-range').value;

        const filteredProducts = this.products.filter(product => {
            const typeMatch = selectedTypes.length === 0 || selectedTypes.includes(product.type);
            const colorMatch = selectedColors.length === 0 || selectedColors.includes(product.color);
            const priceMatch = product.price <= maxPrice;
            return typeMatch && colorMatch && priceMatch;
        });

        this.renderProducts(filteredProducts);
    }

    render() {
        this.renderProducts(this.products);
    }

    renderProducts(products) {
        const container = document.getElementById('product-grid');
        if (!container) {
            console.error('Product grid container not found');
            return;
        }

        if (products.length === 0) {
            container.innerHTML = `
                <div class="no-products">
                    <p>No products match your filters.</p>
                </div>
            `;
            return;
        }

        container.innerHTML = products.map(product => `
            <div class="product-card">
                <div class="product-image">
                    <img src="${product.image}" alt="${product.name}" onerror="this.src='images/placeholder.jpg'">
                    <div class="product-actions">
                        <button class="btn btn-primary visualize-btn" data-product-id="${product.id}">
                            Visualize
                        </button>
                        <div class="form-check">
                            <input class="form-check-input compare-checkbox" 
                                   type="checkbox" 
                                   value="${product.id}" 
                                   id="compare-${product.id}">
                            <label class="form-check-label" for="compare-${product.id}">
                                Compare
                            </label>
                        </div>
                    </div>
                </div>
                <div class="product-info">
                    <h3>${product.name}</h3>
                    <p>${product.description}</p>
                    <div class="product-meta">
                        <span class="price">$${product.price}</span>
                        <span class="type">${product.type}</span>
                    </div>
                </div>
            </div>
        `).join('');

        this.setupProductEventListeners();
    }

    setupProductEventListeners() {
        // Visualize button clicks
        document.querySelectorAll('.visualize-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const productId = e.target.dataset.productId;
                this.openVisualizer(productId);
            });
        });
    }

    handleComparison(e) {
        const selectedProducts = [...document.querySelectorAll('.compare-checkbox:checked')]
            .map(cb => cb.value);
        
        if (selectedProducts.length > 3) {
            e.target.checked = false;
            alert('You can compare up to 3 products at a time');
            return;
        }

        if (selectedProducts.length >= 2) {
            this.showComparisonModal(selectedProducts);
        }
    }

    showComparisonModal(productIds) {
        const products = productIds.map(id => this.products.find(p => p.id === id));
        const modal = document.getElementById('comparison-modal');
        const modalContent = document.querySelector('#comparison-modal .modal-body');

        modalContent.innerHTML = `
            <div class="comparison-table">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Feature</th>
                            ${products.map(p => `<th>${p.name}</th>`).join('')}
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Type</td>
                            ${products.map(p => `<td>${p.type}</td>`).join('')}
                        </tr>
                        <tr>
                            <td>Color</td>
                            ${products.map(p => `<td>${p.color}</td>`).join('')}
                        </tr>
                        <tr>
                            <td>Price</td>
                            ${products.map(p => `<td>$${p.price}</td>`).join('')}
                        </tr>
                        <tr>
                            <td>Material</td>
                            ${products.map(p => `<td>${p.material}</td>`).join('')}
                        </tr>
                    </tbody>
                </table>
            </div>
        `;

        new bootstrap.Modal(modal).show();
    }

    openVisualizer(productId) {
        const product = this.products.find(p => p.id === productId);
        const visualizer = new StoneVisualizer(product);
        visualizer.show();
    }
}

// Initialize the catalog when the DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new ProductCatalog();
});
