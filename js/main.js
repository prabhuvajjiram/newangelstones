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
    const video = document.getElementById('hero-video');
    const container = document.querySelector('.video-container');
    const posterImage = document.querySelector('.poster-image');
    
    if (!video || !container) return;

    // Function to start video
    function startVideo() {
        if (video.paused) {
            video.style.opacity = '1';
            const playPromise = video.play();
            
            if (playPromise !== undefined) {
                playPromise.catch(function(error) {
                    console.log("Video autoplay failed:", error);
                    // Show poster image if video fails to play
                    if (posterImage) {
                        posterImage.style.display = 'block';
                    }
                });
            }
        }
    }

    // Function to handle video loading
    function handleVideoLoad() {
        // Hide poster image when video starts playing
        if (posterImage) {
            posterImage.style.display = 'none';
        }
        
        // Start video playback
        startVideo();
    }

    // Add event listeners for video
    video.addEventListener('loadeddata', handleVideoLoad);
    video.addEventListener('canplay', handleVideoLoad);
    video.addEventListener('error', function(e) {
        console.error("Video loading error:", e);
        // Show poster image if video fails to load
        if (posterImage) {
            posterImage.style.display = 'block';
        }
    });

    // Try to start video immediately
    startVideo();

    // Fallback: If video hasn't started after 3 seconds, show poster
    setTimeout(() => {
        if (video.paused) {
            if (posterImage) {
                posterImage.style.display = 'block';
            }
        }
    }, 3000);

    // Factory image loading
    const factoryImage = document.querySelector('.factory-image');
    if (factoryImage) {
        const factoryPicture = factoryImage.querySelector('picture');
        
        // Only proceed if we found the picture element
        if (factoryPicture) {
            // Add loading class initially
            factoryImage.classList.add('loading');
            
            // Wait for the image to load
            const img = factoryPicture.querySelector('img');
            if (img) {
                img.addEventListener('load', function() {
                    // Remove loading class and add loaded class
                    factoryImage.classList.remove('loading');
                    factoryImage.classList.add('loaded');
                });
                
                // Handle image errors
                img.addEventListener('error', function() {
                    // Still remove loading class but add error class
                    factoryImage.classList.remove('loading');
                    factoryImage.classList.add('error');
                });
            }
        }
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

    // Load video after important content
    let videoLoadTimer;
    
    function loadVideo() {
        // Clear any existing timer
        if (videoLoadTimer) clearTimeout(videoLoadTimer);
        
        videoLoadTimer = setTimeout(() => {
            // Start loading the video
            video.preload = 'auto';
            
            // Wait for video to be loaded enough to play
            video.addEventListener('loadeddata', function onVideoLoad() {
                startVideo();
                video.removeEventListener('loadeddata', onVideoLoad);
            });
            
            // Fallback if video takes too long
            setTimeout(() => {
                if (video.readyState >= 3) {
                    startVideo();
                }
            }, 1000);
        }, 1000); // Delay video loading by 1s after page load
    }

    // Use Intersection Observer to load video when container is visible
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                loadVideo();
                observer.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.1
    });

    observer.observe(container);

    // Optimize playback
    video.addEventListener('loadeddata', function() {
        video.play().catch(function(error) {
            console.log("Auto-play prevented:", error);
        });
    }, { once: true });

    // Reduce quality on mobile
    function adjustVideoQuality() {
        if (window.innerWidth <= 768) {
            video.setAttribute('poster', 'images/video-poster-mobile.jpg');
        } else {
            video.setAttribute('poster', 'images/video-poster-optimized.jpg');
        }
    }

    // Listen for resize events
    window.addEventListener('resize', debounce(adjustVideoQuality, 250));
    adjustVideoQuality();

    // Handle hero video
    const heroVideo = document.getElementById('hero-video');
    if (heroVideo) {
        // Create a play button for manual interaction
        const playButton = document.getElementById('manual-play-btn');
        
        // Add event listener to play video on interaction (needed for autoplay policies)
        if (playButton) {
            playButton.addEventListener('click', function() {
                // Try to play the video
                const playPromise = heroVideo.play();
                
                // Handle play promise (to avoid AbortError)
                if (playPromise !== undefined) {
                    playPromise.then(_ => {
                        // Video playback started successfully
                        playButton.style.display = 'none';
                    }).catch(error => {
                        // Auto-play was prevented, show play button
                        playButton.style.display = 'flex';
                        console.log('Video autoplay not allowed by browser. User interaction required.');
                    });
                }
            });
        }
        
        // Try to play the video (will work on desktop, might be blocked on mobile)
        heroVideo.addEventListener('canplaythrough', function() {
            const playPromise = heroVideo.play();
            
            // Handle play promise to prevent AbortError
            if (playPromise !== undefined) {
                playPromise.catch(error => {
                    // Auto-play was prevented, show play button
                    if (playButton) {
                        playButton.style.display = 'flex';
                    }
                    console.log('Video autoplay not allowed by browser. User interaction required.');
                });
            }
        });
    }
});

// Simple function to initialize hero video
function initHeroVideo() {
    const video = document.getElementById('hero-video');
    const container = video.closest('.video-container');
    
    if (video && container) {
        // Check if video can play
        const canPlay = video.canPlayType('video/mp4');
        
        if (canPlay === '') {
            // Video format not supported
            container.classList.add('fallback');
            return;
        }
        
        // Video can play
        video.addEventListener('canplay', function() {
            container.classList.add('video-loaded');
        });
        
        // Handle video loading
        video.load();
        
        // Force play on mobile
        document.addEventListener('touchstart', function() {
            video.play().catch(function(error) {
                console.log("Mobile autoplay failed:", error);
            });
        }, { once: true });
    }
}

// Call the function when document is ready
document.addEventListener('DOMContentLoaded', initHeroVideo);
