const Utilities = {
    showAlert(type, message) {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        
        $('.alert').remove();
        $('.container').prepend(alertHtml);
        
        setTimeout(() => {
            $('.alert').alert('close');
        }, 3000);
    }
};
