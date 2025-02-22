document.addEventListener('DOMContentLoaded', function() {
    // Create and insert enhanced header
    const enhancedHeader = document.createElement('div');
    enhancedHeader.className = 'enhanced-header';
    enhancedHeader.innerHTML = `
        <div class="container">
            <div class="logo">
                <a href="/">
                    <img src="images/logo02.png" alt="Angel Stones" width="200" height="50">
                </a>
            </div>
            <div class="quick-actions">
                <div class="contact-info">
                    <a href="tel:+19195357574" class="phone-link">
                        <i class="bi bi-telephone"></i>
                        <span>+1 919-535-7574</span>
                    </a>
                </div>
                <a href="#get-in-touch" class="quote-button">Get Quote</a>
            </div>
        </div>
    `;
    document.body.insertBefore(enhancedHeader, document.body.firstChild);

    // Handle scroll events
    let lastScroll = 0;
    const header = document.querySelector('.enhanced-header');

    window.addEventListener('scroll', () => {
        const currentScroll = window.pageYOffset;
        
        // Show/hide header based on scroll direction and position
        if (currentScroll <= 0) {
            header.classList.remove('visible');
        } else if (currentScroll > lastScroll && currentScroll > 100) {
            header.classList.remove('visible');
        } else {
            header.classList.add('visible');
        }
        
        lastScroll = currentScroll;
    });

    // Smooth scroll for all anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
                // Close mobile menu if open
                const nav = document.querySelector('#as-nav');
                if (nav.classList.contains('active')) {
                    nav.classList.remove('active');
                }
            }
        });
    });

    // Enhance existing mobile menu
    const existingToggle = document.querySelector('.as-nav-toggle');
    if (existingToggle) {
        existingToggle.addEventListener('click', function() {
            const nav = document.querySelector('#as-nav');
            if (nav) {
                nav.classList.toggle('active');
            }
        });

        // Close menu when clicking outside
        document.addEventListener('click', function(e) {
            const nav = document.querySelector('#as-nav');
            if (nav && nav.classList.contains('active') && 
                !nav.contains(e.target) && 
                !existingToggle.contains(e.target)) {
                nav.classList.remove('active');
            }
        });
    }
});
