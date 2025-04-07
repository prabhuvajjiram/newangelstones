// Add this at the beginning of main.js, before any other code
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Initialize video immediately when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Initialize hero video
    initHeroVideo();
    
    // Factory image loading
    const factoryImage = document.querySelector('.factory-image');
    if (factoryImage) {
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
    }

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

    const heroVideo = document.getElementById('hero-video');
    if (heroVideo) {
        // Optimize video loading
        heroVideo.style.opacity = '0';
        heroVideo.style.transition = 'opacity 0.3s ease';

        // Show video when poster is loaded
        const posterImage = new Image();
        posterImage.onload = function() {
            heroVideo.style.opacity = '1';
        };
        posterImage.src = heroVideo.poster;

        // Optimize playback
        heroVideo.addEventListener('loadeddata', function() {
            heroVideo.play().catch(function(error) {
                console.log("Auto-play prevented:", error);
            });
        }, { once: true });

        // Reduce quality on mobile
        function adjustVideoQuality() {
            if (window.innerWidth <= 768) {
                heroVideo.setAttribute('poster', 'images/video-poster-mobile.jpg');
            } else {
                heroVideo.setAttribute('poster', 'images/video-poster-optimized.jpg');
            }
        }

        // Listen for resize events
        window.addEventListener('resize', debounce(adjustVideoQuality, 250));
        adjustVideoQuality();
    }
});

// Simple function to initialize hero video
function initHeroVideo() {
    // Get hero video element
    const video = document.getElementById('hero-video');
    const playBtn = document.getElementById('manual-play-btn');
    
    if (!video) {
        console.error('Hero video element not found');
        return;
    }
    
    // Make sure video properties are set
    video.muted = true;
    video.playsInline = true;
    video.loop = true;
    
    // Load the video
    try {
        video.load();
        
        // Try to play the video
        const playPromise = video.play();
        
        if (playPromise !== undefined) {
            playPromise.then(() => {
            }).catch(error => {
                console.error('Autoplay prevented:', error);
                
                // Show play button if available
                if (playBtn) {
                    playBtn.style.display = 'block';
                    video.classList.add('needs-user-action');
                    
                    // Add click event to play button
                    playBtn.addEventListener('click', function() {
                        video.play()
                            .then(() => {
                                playBtn.style.display = 'none';
                                video.classList.remove('needs-user-action');
                            })
                            .catch(err => console.error('Still cannot play video:', err));
                    });
                }
            });
        }
    } catch (error) {
        console.error('Error setting up video:', error);
    }
}
