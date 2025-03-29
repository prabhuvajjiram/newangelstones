document.addEventListener('DOMContentLoaded', function() {
    // Factory image loading
    const factoryImage = document.querySelector('.factory-image');
    const factoryPicture = factoryImage.querySelector('picture');
    
    // Add loading class initially
    factoryImage.classList.add('loading');
    
    // Wait for the image to load
    const img = factoryPicture.querySelector('img');
    img.addEventListener('load', function() {
        // Remove loading class and add loaded class
        factoryImage.classList.remove('loading');
        factoryImage.classList.add('loaded');
    });

    // Handle image errors
    img.addEventListener('error', function() {
        console.error('Failed to load factory image');
        // Fallback to default image
        img.src = 'images/Factory-1280.webp';
    });

    // Add lazy loading support for older browsers
    if (!('loading' in HTMLImageElement.prototype)) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    const src = img.getAttribute('data-src');
                    if (src) {
                        img.src = src;
                        img.removeAttribute('data-src');
                        observer.unobserve(img);
                    }
                }
            });
        });

        const lazyImages = document.querySelectorAll('img[loading="lazy"]');
        lazyImages.forEach(img => {
            img.setAttribute('data-src', img.src);
            img.src = '';
            observer.observe(img);
        });
    }
});
