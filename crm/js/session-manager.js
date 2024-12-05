let sessionTimer;
let warningTimer;
const SESSION_TIMEOUT = 30 * 60 * 1000; // 30 minutes
const WARNING_TIMEOUT = 60 * 1000; // 60 seconds warning before logout

function resetSessionTimer() {
    clearTimeout(sessionTimer);
    clearTimeout(warningTimer);
    
    // Set timer for showing warning
    warningTimer = setTimeout(() => {
        showSessionWarning();
    }, SESSION_TIMEOUT - WARNING_TIMEOUT);

    // Set timer for automatic logout
    sessionTimer = setTimeout(() => {
        window.location.href = 'logout.php';
    }, SESSION_TIMEOUT);
}

function showSessionWarning() {
    const warningModal = document.getElementById('sessionWarningModal');
    if (warningModal) {
        const modal = new bootstrap.Modal(warningModal);
        modal.show();
    }
}

function extendSession() {
    fetch('check_session.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ action: 'extend_session' })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const warningModal = document.getElementById('sessionWarningModal');
            if (warningModal) {
                const modal = bootstrap.Modal.getInstance(warningModal);
                if (modal) modal.hide();
            }
            resetSessionTimer();
        } else {
            window.location.href = 'logout.php';
        }
    })
    .catch(() => {
        window.location.href = 'logout.php';
    });
}

// Initialize session timer
document.addEventListener('DOMContentLoaded', () => {
    resetSessionTimer();

    // Reset timer on user activity
    ['click', 'mousemove', 'keypress', 'scroll', 'touchstart'].forEach(event => {
        document.addEventListener(event, resetSessionTimer, false);
    });
});
