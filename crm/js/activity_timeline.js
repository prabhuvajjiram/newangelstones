class ActivityTimeline {
    constructor(containerId, options = {}) {
        this.container = document.getElementById(containerId);
        this.options = {
            pageSize: options.pageSize || 50,
            customerId: options.customerId || null,
            companyId: options.companyId || null,
            ...options
        };
        this.currentPage = 1;
        this.filters = {};
        this.init();
    }

    init() {
        this.initializeFilters();
        this.loadCategories();
        this.loadActivities();
        this.setupEventListeners();
    }

    initializeFilters() {
        const filterHtml = `
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title">Activity Filters</h5>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Category</label>
                            <select class="form-select" id="categoryFilter">
                                <option value="">All Categories</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Importance</label>
                            <select class="form-select" id="importanceFilter">
                                <option value="">All</option>
                                <option value="high">High</option>
                                <option value="medium">Medium</option>
                                <option value="low">Low</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date From</label>
                            <input type="date" class="form-control" id="dateFromFilter">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date To</label>
                            <input type="date" class="form-control" id="dateToFilter">
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col">
                            <button class="btn btn-primary" id="applyFilters">Apply Filters</button>
                            <button class="btn btn-secondary" id="resetFilters">Reset</button>
                            <button class="btn btn-success float-end" id="exportActivities">
                                <i class="fas fa-download"></i> Export
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div id="timelineContainer"></div>
            <div id="paginationContainer" class="mt-3"></div>
        `;
        this.container.innerHTML = filterHtml;
    }

    async loadCategories() {
        try {
            const response = await fetch('ajax/get_activity_categories.php');
            const categories = await response.json();
            
            const select = document.getElementById('categoryFilter');
            categories.forEach(category => {
                const option = document.createElement('option');
                option.value = category.id;
                option.textContent = category.name;
                select.appendChild(option);
            });
        } catch (error) {
            console.error('Error loading categories:', error);
        }
    }

    async loadActivities() {
        try {
            const queryParams = new URLSearchParams({
                page: this.currentPage,
                ...this.filters,
                customer_id: this.options.customerId,
                company_id: this.options.companyId
            });

            const response = await fetch(`ajax/get_activities.php?${queryParams}`);
            const data = await response.json();
            
            this.renderTimeline(data.activities);
            this.renderPagination(data.total_pages);
            this.renderAnalytics(data.analytics);
        } catch (error) {
            console.error('Error loading activities:', error);
        }
    }

    renderTimeline(activities) {
        const timelineHtml = activities.map(activity => `
            <div class="activity-item mb-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <div class="activity-icon me-2" style="color: ${activity.category_color}">
                                <i class="fas fa-${activity.category_icon}"></i>
                            </div>
                            <h5 class="card-title mb-0">${activity.title}</h5>
                            <span class="badge bg-${this.getImportanceBadgeColor(activity.importance)} ms-2">
                                ${activity.importance}
                            </span>
                            <small class="text-muted ms-auto">
                                ${new Date(activity.activity_date).toLocaleString()}
                            </small>
                        </div>
                        <p class="card-text">${activity.description || ''}</p>
                        ${this.renderTags(activity.tags)}
                        ${activity.company_name ? `
                            <div class="mt-2">
                                <small class="text-muted">
                                    <i class="fas fa-building"></i> ${activity.company_name}
                                </small>
                            </div>
                        ` : ''}
                    </div>
                </div>
            </div>
        `).join('');

        document.getElementById('timelineContainer').innerHTML = timelineHtml;
    }

    renderPagination(totalPages) {
        const paginationHtml = `
            <nav>
                <ul class="pagination justify-content-center">
                    ${this.currentPage > 1 ? `
                        <li class="page-item">
                            <a class="page-link" href="#" data-page="${this.currentPage - 1}">Previous</a>
                        </li>
                    ` : ''}
                    ${Array.from({length: totalPages}, (_, i) => i + 1).map(page => `
                        <li class="page-item ${page === this.currentPage ? 'active' : ''}">
                            <a class="page-link" href="#" data-page="${page}">${page}</a>
                        </li>
                    `).join('')}
                    ${this.currentPage < totalPages ? `
                        <li class="page-item">
                            <a class="page-link" href="#" data-page="${this.currentPage + 1}">Next</a>
                        </li>
                    ` : ''}
                </ul>
            </nav>
        `;

        document.getElementById('paginationContainer').innerHTML = paginationHtml;
    }

    renderAnalytics(analytics) {
        // Implementation for analytics visualization can be added here
        // You might want to use a charting library like Chart.js
    }

    renderTags(tags) {
        if (!tags) return '';
        
        const tagArray = typeof tags === 'string' ? JSON.parse(tags) : tags;
        return `
            <div class="mt-2">
                ${tagArray.map(tag => `
                    <span class="badge bg-secondary me-1">${tag}</span>
                `).join('')}
            </div>
        `;
    }

    getImportanceBadgeColor(importance) {
        const colors = {
            high: 'danger',
            medium: 'warning',
            low: 'info'
        };
        return colors[importance] || 'secondary';
    }

    setupEventListeners() {
        document.getElementById('applyFilters').addEventListener('click', () => {
            this.filters = {
                category_id: document.getElementById('categoryFilter').value,
                importance: document.getElementById('importanceFilter').value,
                date_from: document.getElementById('dateFromFilter').value,
                date_to: document.getElementById('dateToFilter').value
            };
            this.currentPage = 1;
            this.loadActivities();
        });

        document.getElementById('resetFilters').addEventListener('click', () => {
            document.getElementById('categoryFilter').value = '';
            document.getElementById('importanceFilter').value = '';
            document.getElementById('dateFromFilter').value = '';
            document.getElementById('dateToFilter').value = '';
            this.filters = {};
            this.currentPage = 1;
            this.loadActivities();
        });

        document.getElementById('exportActivities').addEventListener('click', async () => {
            try {
                const response = await fetch('ajax/export_activities.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(this.filters)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    window.location.href = `downloads/${data.filename}`;
                } else {
                    alert('Export failed: ' + data.message);
                }
            } catch (error) {
                console.error('Error exporting activities:', error);
                alert('Export failed. Please try again.');
            }
        });

        document.getElementById('paginationContainer').addEventListener('click', (e) => {
            if (e.target.classList.contains('page-link')) {
                e.preventDefault();
                this.currentPage = parseInt(e.target.dataset.page);
                this.loadActivities();
            }
        });
    }
}
