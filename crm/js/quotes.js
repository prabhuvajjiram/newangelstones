const QuotesManager = {
    // Helper method to create quote row HTML
    createQuoteRow(quote) {
        return `
            <tr>
                <td>
                    <a href="preview_quote.php?id=${quote.id}" class="text-decoration-none">
                        ${quote.quote_number}
                    </a>
                </td>
                <td>${quote.customer_name || 'N/A'}</td>
                <td class="text-center">${quote.item_count || 0}</td>
                <td class="text-end">$${parseFloat(quote.total_amount || 0).toFixed(2)}</td>
                <td class="text-end">${parseFloat(quote.total_cubic_feet || 0).toFixed(2)}</td>
                <td>${quote.created_by_name || 'System'}</td>
                <td>${new Date(quote.created_at).toLocaleDateString()}</td>
                <td>
                    <span class="badge bg-${this.getStatusBadgeClass(quote.status)}">
                        ${quote.status}
                    </span>
                </td>
                <td>
                    <div class="btn-group">
                        <a href="preview_quote.php?id=${quote.id}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye"></i>
                        </a>
                        <button class="btn btn-sm btn-outline-danger delete-quote" data-quote-id="${quote.id}">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    },

    // Helper method to get status badge class
    getStatusBadgeClass(status) {
        switch(status.toLowerCase()) {
            case 'pending': return 'warning';
            case 'sent': return 'info';
            case 'accepted': return 'success';
            case 'rejected': return 'danger';
            case 'converted': return 'primary';
            default: return 'secondary';
        }
    },

    // Initialize the module
    init() {
        this.bindEvents();
        this.loadQuotes();
    },

    // Bind event handlers
    bindEvents() {
        $(document).on('click', '.delete-quote', (e) => this.handleDelete(e));
        $('#searchForm').on('submit', (e) => this.handleSearch(e));
        $('#searchForm button[type="reset"]').on('click', () => this.handleReset());
    },

    // Handle quote deletion
    handleDelete(e) {
        e.preventDefault();
        const quoteId = $(e.currentTarget).data('quote-id');
        const row = $(e.currentTarget).closest('tr');
        
        if (confirm('Are you sure you want to delete this quote?')) {
            const button = $(e.currentTarget);
            button.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i>');

            $.ajax({
                url: 'ajax/delete_quote.php',
                method: 'POST',
                data: JSON.stringify({ quote_id: quoteId }),
                contentType: 'application/json',
                success: (response) => {
                    if (response.success) {
                        row.fadeOut(400, () => {
                            row.remove();
                            Utilities.showAlert('success', 'Quote deleted successfully');
                        });
                    } else {
                        Utilities.showAlert('danger', response.message || 'Error deleting quote');
                        button.prop('disabled', false).html('<i class="bi bi-trash"></i>');
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Error:', error);
                    Utilities.showAlert('danger', 'Error deleting quote. Please try again.');
                    button.prop('disabled', false).html('<i class="bi bi-trash"></i>');
                }
            });
        }
    },

    // Handle search form submission
    handleSearch(e) {
        e.preventDefault();
        const searchData = {
            customerName: $('#customerName').val(),
            quoteNumber: $('#quoteNumber').val(),
            dateFrom: $('#dateFrom').val(),
            dateTo: $('#dateTo').val()
        };

        $.ajax({
            url: 'ajax/search_quotes.php',
            method: 'POST',
            data: JSON.stringify(searchData),
            contentType: 'application/json',
            success: (response) => {
                if (response.success) {
                    this.updateTable(response.quotes);
                } else {
                    Utilities.showAlert('danger', response.message || 'Error searching quotes');
                }
            },
            error: (xhr, status, error) => {
                console.error('Error:', error);
                Utilities.showAlert('danger', 'Error searching quotes. Please try again.');
            }
        });
    },

    // Handle search form reset
    handleReset() {
        $('#searchForm')[0].reset();
        this.loadQuotes();
    },

    // Load all quotes
    loadQuotes() {
        $.ajax({
            url: 'ajax/get_quotes.php',
            method: 'GET',
            success: (response) => {
                if (response.success) {
                    this.updateTable(response.quotes);
                }
            }
        });
    },

    // Update quotes table
    updateTable(quotes) {
        const tbody = $('table tbody');
        tbody.empty();
        
        if (quotes.length === 0) {
            tbody.append(`
                <tr>
                    <td colspan="9" class="text-center">No quotes found</td>
                </tr>
            `);
            return;
        }

        quotes.forEach(quote => tbody.append(this.createQuoteRow(quote)));
    }
};

// Initialize when document is ready
$(document).ready(() => {
    QuotesManager.init();
});
