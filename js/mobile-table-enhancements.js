/**
 * Mobile Table Responsiveness Enhancements
 * Optimizes touch interactions and mobile table behavior
 */

(function() {
    'use strict';
    
    // Mobile detection
    const isMobile = window.innerWidth <= 768;
    const isSmallMobile = window.innerWidth <= 576;
    
    // Initialize mobile table enhancements
    function initMobileTableEnhancements() {
        if (!isMobile) return;
        
        const tables = document.querySelectorAll('.table-responsive .table');
        
        tables.forEach(table => {
            enhanceTableForMobile(table);
        });
        
        // Add touch event listeners
        addTouchEventListeners();
        
        // Optimize scroll performance
        optimizeScrollPerformance();
        
        // Add visual feedback for interactions
        addVisualFeedback();
    }
    
    // Enhance table structure for mobile
    function enhanceTableForMobile(table) {
        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach((row, index) => {
            // Add mobile-specific attributes
            row.setAttribute('data-mobile-card', 'true');
            row.setAttribute('data-product-index', index + 1);
            
            // Enhance touch targets
            const buttons = row.querySelectorAll('button');
            buttons.forEach(button => {
                if (button.offsetHeight < 44) {
                    button.style.minHeight = '44px';
                    button.style.minWidth = '44px';
                }
            });
            
            // Enhance form controls
            const formControls = row.querySelectorAll('.form-control, .form-select');
            formControls.forEach(control => {
                if (control.offsetHeight < 44) {
                    control.style.minHeight = '44px';
                }
                control.style.fontSize = '16px'; // Prevent zoom on iOS
            });
        });
    }
    
    // Add enhanced touch event listeners for better mobile interaction
    function addTouchEventListeners() {
        const tableRows = document.querySelectorAll('.table tbody tr');
        
        tableRows.forEach(row => {
            // Enhanced touch feedback with haptic-like response
            row.addEventListener('touchstart', function() {
                if (isSmallMobile) {
                    this.style.transform = 'translateY(-1px) scale(0.98)';
                    this.style.transition = 'transform 0.1s ease';
                    this.style.boxShadow = '0 8px 25px rgba(66, 153, 225, 0.15)';
                    this.classList.add('mobile-touch-active');
                }
            }, { passive: true });
            
            row.addEventListener('touchend', function() {
                if (isSmallMobile) {
                    setTimeout(() => {
                        this.style.transform = '';
                        this.style.transition = 'all 0.3s ease';
                        this.style.boxShadow = '';
                        this.classList.remove('mobile-touch-active');
                    }, 150);
                }
            }, { passive: true });
            
            // Add touch cancel handling
            row.addEventListener('touchcancel', function() {
                if (isSmallMobile) {
                    this.style.transform = '';
                    this.style.transition = 'all 0.3s ease';
                    this.style.boxShadow = '';
                    this.classList.remove('mobile-touch-active');
                }
            }, { passive: true });
            
            // Add enhanced focus management for card layout
            if (isSmallMobile) {
                row.addEventListener('focusin', function() {
                    this.classList.add('mobile-focus-within');
                    this.style.outline = '2px solid #4299e1';
                    this.style.outlineOffset = '2px';
                });
                
                row.addEventListener('focusout', function() {
                    // Delay to check if focus moved to another element within the row
                    setTimeout(() => {
                        if (!this.contains(document.activeElement)) {
                            this.classList.remove('mobile-focus-within');
                            this.style.outline = '';
                            this.style.outlineOffset = '';
                        }
                    }, 10);
                });
            }
        });
        
        // Enhanced remove button interactions with better feedback
        const removeButtons = document.querySelectorAll('.remove-product');
        removeButtons.forEach(button => {
            button.addEventListener('touchstart', function() {
                this.style.transform = 'scale(0.95)';
                this.style.transition = 'transform 0.1s ease';
                this.style.backgroundColor = 'rgba(229, 62, 62, 0.2)';
                
                // Add vibration feedback if supported
                if (navigator.vibrate) {
                    navigator.vibrate(50);
                }
            }, { passive: true });
            
            button.addEventListener('touchend', function() {
                setTimeout(() => {
                    this.style.transform = '';
                    this.style.transition = 'all 0.3s ease';
                    this.style.backgroundColor = '';
                }, 100);
            }, { passive: true });
            
            button.addEventListener('touchcancel', function() {
                this.style.transform = '';
                this.style.transition = 'all 0.3s ease';
                this.style.backgroundColor = '';
            }, { passive: true });
        });
        
        // Add enhanced form control touch interactions
        const formControls = document.querySelectorAll('.table .form-control, .table .form-select');
        formControls.forEach(control => {
            control.addEventListener('touchstart', function() {
                if (isMobile) {
                    this.style.borderColor = '#4299e1';
                    this.style.boxShadow = '0 0 0 2px rgba(66, 153, 225, 0.2)';
                    
                    // Add haptic feedback if available
                    if (navigator.vibrate) {
                        navigator.vibrate(10);
                    }
                }
            }, { passive: true });
            
            control.addEventListener('touchend', function() {
                if (isMobile && document.activeElement !== this) {
                    setTimeout(() => {
                        this.style.borderColor = '';
                        this.style.boxShadow = '';
                    }, 200);
                }
            }, { passive: true });
            
            // Add enhanced input validation feedback
            control.addEventListener('input', function() {
                if (isMobile) {
                    // Clear any previous validation states
                    this.classList.remove('is-invalid', 'is-valid');
                    
                    // Add subtle visual feedback for input
                    this.style.borderColor = '#4299e1';
                    this.style.transition = 'border-color 0.3s ease';
                    
                    setTimeout(() => {
                        this.style.borderColor = '';
                    }, 500);
                }
            });
            
            // Enhanced validation feedback
            control.addEventListener('invalid', function() {
                if (isMobile) {
                    this.classList.add('is-invalid');
                    this.style.animation = 'shake 0.5s ease-in-out';
                    
                    // Add stronger haptic feedback for errors
                    if (navigator.vibrate) {
                        navigator.vibrate([100, 50, 100]);
                    }
                    
                    setTimeout(() => {
                        this.style.animation = '';
                    }, 500);
                }
            });
            
            // Success validation feedback
            control.addEventListener('blur', function() {
                if (isMobile && this.checkValidity() && this.value.trim() !== '') {
                    this.classList.add('is-valid');
                    this.classList.remove('is-invalid');
                }
            });
        });
    }
    
    // Optimize scroll performance for horizontal tables
    function optimizeScrollPerformance() {
        const tableResponsive = document.querySelectorAll('.table-responsive');
        
        tableResponsive.forEach(container => {
            // Add momentum scrolling for iOS
            container.style.webkitOverflowScrolling = 'touch';
            container.style.overflowScrolling = 'touch';
            
            // Add scroll snap for better UX
            if (window.innerWidth > 576 && window.innerWidth <= 768) {
                container.style.scrollSnapType = 'x proximity';
                
                const cells = container.querySelectorAll('th, td');
                cells.forEach(cell => {
                    cell.style.scrollSnapAlign = 'start';
                });
            }
            
            // Add scroll indicators
            addScrollIndicators(container);
        });
    }
    
    // Add visual scroll indicators with enhanced functionality
    function addScrollIndicators(container) {
        if (window.innerWidth > 576 && window.innerWidth <= 768) {
            const table = container.querySelector('table');
            if (!table) return;
            
            // Check if content is scrollable
            const isScrollable = table.scrollWidth > container.clientWidth;
            
            if (isScrollable) {
                container.classList.add('has-horizontal-scroll');
                
                // Initial state check
                updateScrollIndicators(container);
                
                // Add scroll event listener to show/hide indicators
                container.addEventListener('scroll', function() {
                    updateScrollIndicators(this);
                }, { passive: true });
                
                // Add touch gesture support for better scrolling
                addTouchGestureSupport(container);
                
                // Add keyboard navigation support
                addKeyboardScrollSupport(container);
            } else {
                container.classList.remove('has-horizontal-scroll');
            }
        }
    }
    
    // Update scroll indicators based on current scroll position
    function updateScrollIndicators(container) {
        const scrollLeft = container.scrollLeft;
        const maxScroll = container.scrollWidth - container.clientWidth;
        
        // Update classes for CSS styling
        if (scrollLeft > 5) {
            container.classList.add('scrolled-left');
        } else {
            container.classList.remove('scrolled-left');
        }
        
        if (scrollLeft < maxScroll - 5) {
            container.classList.add('can-scroll-right');
        } else {
            container.classList.remove('can-scroll-right');
        }
    }
    
    // Add enhanced touch gesture support for horizontal scrolling
    function addTouchGestureSupport(container) {
        let startX = 0;
        let scrollStart = 0;
        let isScrolling = false;
        
        container.addEventListener('touchstart', function(e) {
            startX = e.touches[0].clientX;
            scrollStart = this.scrollLeft;
            isScrolling = false;
        }, { passive: true });
        
        container.addEventListener('touchmove', function(e) {
            if (!startX) return;
            
            const currentX = e.touches[0].clientX;
            const diffX = startX - currentX;
            
            // Detect horizontal scrolling intent
            if (Math.abs(diffX) > 10 && !isScrolling) {
                isScrolling = true;
                this.style.scrollBehavior = 'auto';
            }
            
            if (isScrolling) {
                this.scrollLeft = scrollStart + diffX;
                e.preventDefault();
            }
        }, { passive: false });
        
        container.addEventListener('touchend', function(e) {
            startX = 0;
            scrollStart = 0;
            isScrolling = false;
            this.style.scrollBehavior = 'smooth';
        }, { passive: true });
    }
    
    // Add keyboard navigation support for table scrolling
    function addKeyboardScrollSupport(container) {
        container.setAttribute('tabindex', '0');
        
        container.addEventListener('keydown', function(e) {
            const scrollAmount = 100;
            
            switch(e.key) {
                case 'ArrowLeft':
                    this.scrollLeft -= scrollAmount;
                    e.preventDefault();
                    break;
                case 'ArrowRight':
                    this.scrollLeft += scrollAmount;
                    e.preventDefault();
                    break;
                case 'Home':
                    this.scrollLeft = 0;
                    e.preventDefault();
                    break;
                case 'End':
                    this.scrollLeft = this.scrollWidth;
                    e.preventDefault();
                    break;
            }
        });
    }
    
    // Add visual feedback for form interactions
    function addVisualFeedback() {
        const formControls = document.querySelectorAll('.table .form-control, .table .form-select');
        
        formControls.forEach(control => {
            // Add focus feedback
            control.addEventListener('focus', function() {
                if (isMobile) {
                    this.parentElement.style.transform = 'scale(1.02)';
                    this.parentElement.style.zIndex = '10';
                    this.parentElement.style.position = 'relative';
                }
            });
            
            control.addEventListener('blur', function() {
                if (isMobile) {
                    this.parentElement.style.transform = '';
                    this.parentElement.style.zIndex = '';
                    this.parentElement.style.position = '';
                }
            });
            
            // Add input feedback for mobile
            if (isMobile) {
                control.addEventListener('input', function() {
                    // Add subtle animation on input
                    this.style.borderColor = '#4299e1';
                    setTimeout(() => {
                        this.style.borderColor = '';
                    }, 300);
                });
            }
        });
    }
    
    // Handle window resize
    function handleResize() {
        const newIsMobile = window.innerWidth <= 768;
        const newIsSmallMobile = window.innerWidth <= 576;
        
        if (newIsMobile !== isMobile || newIsSmallMobile !== isSmallMobile) {
            // Re-initialize if mobile state changed
            setTimeout(() => {
                location.reload(); // Simple approach for demo
            }, 100);
        }
    }
    
    // Debounce function for resize events
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
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMobileTableEnhancements);
    } else {
        initMobileTableEnhancements();
    }
    
    // Handle window resize with debouncing
    window.addEventListener('resize', debounce(handleResize, 250));
    
    // Enhance form submission on mobile
    function enhanceMobileFormSubmission() {
        if (!isMobile) return;
        
        const form = document.querySelector('form');
        if (!form) return;
        
        // Prevent double submissions
        form.addEventListener('submit', function(e) {
            const submitButton = this.querySelector('[type="submit"]');
            if (submitButton) {
                if (submitButton.classList.contains('submitting')) {
                    e.preventDefault();
                    return false;
                }
                submitButton.classList.add('submitting');
                submitButton.setAttribute('disabled', 'disabled');
                
                // Add loading indicator
                const originalText = submitButton.innerHTML;
                submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Submitting...';
                
                // Reset button after timeout (in case of network issues)
                setTimeout(() => {
                    submitButton.classList.remove('submitting');
                    submitButton.removeAttribute('disabled');
                    submitButton.innerHTML = originalText;
                }, 10000);
            }
        });
        
        // Add visual feedback for validation errors on mobile
        const requiredFields = form.querySelectorAll('[required]');
        requiredFields.forEach(field => {
            field.addEventListener('invalid', function() {
                if (isMobile) {
                    this.classList.add('shake-error');
                    setTimeout(() => {
                        this.classList.remove('shake-error');
                    }, 1000);
                }
            });
        });
    }
    
    // Add shake animation CSS for validation errors
    function addShakeAnimationCSS() {
        if (!document.getElementById('mobile-shake-css')) {
            const style = document.createElement('style');
            style.id = 'mobile-shake-css';
            style.textContent = `
                @keyframes shake {
                    0%, 100% { transform: translateX(0); }
                    10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
                    20%, 40%, 60%, 80% { transform: translateX(5px); }
                }
                .shake-error {
                    animation: shake 0.6s ease-in-out;
                    border-color: #e53e3e !important;
                    box-shadow: 0 0 0 2px rgba(229, 62, 62, 0.2) !important;
                }
            `;
            document.head.appendChild(style);
        }
    }
    
    // Enhanced mobile table accessibility with improved features
    function enhanceMobileAccessibility() {
        if (!isMobile) return;
        
        const tables = document.querySelectorAll('.table');
        tables.forEach(table => {
            // Add ARIA labels for mobile table navigation
            table.setAttribute('role', 'table');
            table.setAttribute('aria-label', 'Product information table - optimized for mobile');
            
            // Add live region for dynamic updates
            const liveRegion = document.createElement('div');
            liveRegion.setAttribute('aria-live', 'polite');
            liveRegion.setAttribute('aria-atomic', 'true');
            liveRegion.className = 'sr-only';
            liveRegion.id = 'mobile-table-announcements';
            table.parentNode.insertBefore(liveRegion, table);
            
            // Enhance table rows for screen readers
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach((row, index) => {
                row.setAttribute('role', 'row');
                row.setAttribute('aria-label', `Product ${index + 1} of ${rows.length}`);
                row.setAttribute('aria-describedby', 'mobile-table-announcements');
                
                // Add keyboard navigation support
                row.setAttribute('tabindex', '0');
                
                // Enhanced keyboard navigation with better feedback
                row.addEventListener('keydown', function(e) {
                    const currentRow = this;
                    const allRows = Array.from(table.querySelectorAll('tbody tr'));
                    const currentIndex = allRows.indexOf(currentRow);
                    
                    switch(e.key) {
                        case 'ArrowDown':
                            e.preventDefault();
                            if (currentIndex < allRows.length - 1) {
                                allRows[currentIndex + 1].focus();
                                announceNavigation(`Moved to product ${currentIndex + 2}`);
                            }
                            break;
                        case 'ArrowUp':
                            e.preventDefault();
                            if (currentIndex > 0) {
                                allRows[currentIndex - 1].focus();
                                announceNavigation(`Moved to product ${currentIndex}`);
                            }
                            break;
                        case 'Enter':
                        case ' ':
                            e.preventDefault();
                            // Focus on first input in the row
                            const firstInput = currentRow.querySelector('input, select, button');
                            if (firstInput) {
                                firstInput.focus();
                                announceNavigation(`Editing product ${currentIndex + 1}`);
                            }
                            break;
                        case 'Escape':
                            e.preventDefault();
                            currentRow.focus();
                            announceNavigation(`Returned to product ${currentIndex + 1} overview`);
                            break;
                    }
                });
                
                // Enhance cells for screen readers
                const cells = row.querySelectorAll('td');
                cells.forEach(cell => {
                    cell.setAttribute('role', 'cell');
                    
                    // Add better ARIA labels based on data-label
                    const dataLabel = cell.getAttribute('data-label');
                    if (dataLabel) {
                        cell.setAttribute('aria-label', `${dataLabel} for product ${index + 1}`);
                    }
                });
                
                // Add focus management for form elements within rows
                const formElements = row.querySelectorAll('input, select, button, textarea');
                formElements.forEach((element, elementIndex) => {
                    element.addEventListener('focus', function() {
                        if (isSmallMobile) {
                            // Scroll element into view with better positioning
                            this.scrollIntoView({
                                behavior: 'smooth',
                                block: 'center',
                                inline: 'nearest'
                            });
                        }
                    });
                    
                    // Add enhanced validation announcements
                    element.addEventListener('invalid', function() {
                        const fieldName = this.getAttribute('name') || this.getAttribute('aria-label') || 'Field';
                        announceNavigation(`${fieldName} is required and needs attention`);
                    });
                    
                    element.addEventListener('change', function() {
                        if (this.checkValidity()) {
                            const fieldName = this.getAttribute('name') || this.getAttribute('aria-label') || 'Field';
                            announceNavigation(`${fieldName} updated successfully`);
                        }
                    });
                });
            });
        });
        
        // Helper function to announce navigation changes
        function announceNavigation(message) {
            const liveRegion = document.getElementById('mobile-table-announcements');
            if (liveRegion) {
                liveRegion.textContent = message;
                // Clear after announcement
                setTimeout(() => {
                    liveRegion.textContent = '';
                }, 1000);
            }
        }
    }
    
    // Enhanced mobile performance optimizations
    function optimizeMobilePerformance() {
        if (!isMobile) return;
        
        // Use Intersection Observer for better performance
        const observerOptions = {
            root: null,
            rootMargin: '50px',
            threshold: 0.1
        };
        
        const tableObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    // Add performance optimizations when table is visible
                    entry.target.style.willChange = 'transform';
                } else {
                    // Remove optimizations when not visible
                    entry.target.style.willChange = 'auto';
                }
            });
        }, observerOptions);
        
        // Observe all table rows
        const tableRows = document.querySelectorAll('.table tbody tr');
        tableRows.forEach(row => {
            tableObserver.observe(row);
        });
        
        // Optimize touch event handling with passive listeners
        const optimizePassiveListeners = () => {
            const touchElements = document.querySelectorAll('.table tbody tr, .table .form-control, .table .form-select, .table .btn');
            
            touchElements.forEach(element => {
                // Remove existing listeners and add optimized ones
                element.addEventListener('touchstart', function() {
                    // Minimal touch feedback
                    this.style.opacity = '0.9';
                }, { passive: true, once: false });
                
                element.addEventListener('touchend', function() {
                    this.style.opacity = '';
                }, { passive: true, once: false });
            });
        };
        
        // Apply optimizations
        optimizePassiveListeners();
        
        // Memory cleanup on page unload
        window.addEventListener('beforeunload', () => {
            tableObserver.disconnect();
        });
    }
    
    // Enhanced mobile table state management
    function addMobileTableStateManagement() {
        if (!isMobile) return;
        
        const tables = document.querySelectorAll('.table');
        
        tables.forEach(table => {
            // Add loading state management
            const addLoadingState = (row) => {
                row.classList.add('loading');
                row.setAttribute('aria-busy', 'true');
                
                // Add loading indicator
                const loadingIndicator = document.createElement('div');
                loadingIndicator.className = 'mobile-loading-indicator';
                loadingIndicator.setAttribute('aria-hidden', 'true');
                row.appendChild(loadingIndicator);
            };
            
            const removeLoadingState = (row) => {
                row.classList.remove('loading');
                row.removeAttribute('aria-busy');
                
                const loadingIndicator = row.querySelector('.mobile-loading-indicator');
                if (loadingIndicator) {
                    loadingIndicator.remove();
                }
            };
            
            // Add error state management
            const addErrorState = (row, message) => {
                row.classList.add('error');
                row.setAttribute('aria-invalid', 'true');
                
                // Add error message for screen readers
                const errorMessage = document.createElement('div');
                errorMessage.className = 'sr-only';
                errorMessage.textContent = message || 'Error in this row';
                row.appendChild(errorMessage);
            };
            
            const removeErrorState = (row) => {
                row.classList.remove('error');
                row.removeAttribute('aria-invalid');
                
                const errorMessage = row.querySelector('.sr-only');
                if (errorMessage) {
                    errorMessage.remove();
                }
            };
            
            // Expose state management functions globally for form integration
            window.mobileTableStates = {
                addLoadingState,
                removeLoadingState,
                addErrorState,
                removeErrorState
            };
        });
    }
    
    // Add CSS classes for enhanced styling
    document.addEventListener('DOMContentLoaded', function() {
        if (isMobile) {
            document.body.classList.add('mobile-device');
        }
        if (isSmallMobile) {
            document.body.classList.add('small-mobile-device');
        }
        
        // Initialize all mobile enhancements
        enhanceMobileFormSubmission();
        addShakeAnimationCSS();
        enhanceMobileAccessibility();
        optimizeMobilePerformance();
        addMobileTableStateManagement();
        
        // Add mobile-specific meta tags if not present
        if (!document.querySelector('meta[name="viewport"]')) {
            const viewport = document.createElement('meta');
            viewport.name = 'viewport';
            viewport.content = 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no';
            document.head.appendChild(viewport);
        }
        
        // Add mobile-specific styles for better performance
        const mobileStyles = document.createElement('style');
        mobileStyles.textContent = `
            .sr-only {
                position: absolute !important;
                width: 1px !important;
                height: 1px !important;
                padding: 0 !important;
                margin: -1px !important;
                overflow: hidden !important;
                clip: rect(0, 0, 0, 0) !important;
                white-space: nowrap !important;
                border: 0 !important;
            }
            
            .mobile-loading-indicator {
                position: absolute;
                top: 50%;
                left: 50%;
                width: 20px;
                height: 20px;
                margin: -10px 0 0 -10px;
                border: 2px solid #4299e1;
                border-top: 2px solid transparent;
                border-radius: 50%;
                animation: spin 1s linear infinite;
                z-index: 100;
            }
            
            @media (max-width: 768px) {
                .mobile-device .table tbody tr:focus {
                    outline: 3px solid #4299e1;
                    outline-offset: 2px;
                    border-radius: 16px;
                }
                
                .mobile-device .table tbody tr[aria-busy="true"] {
                    pointer-events: none;
                    opacity: 0.7;
                }
                
                .mobile-device .table tbody tr[aria-invalid="true"] {
                    border-color: #e53e3e;
                    background: rgba(229, 62, 62, 0.05);
                }
            }
        `;
        document.head.appendChild(mobileStyles);
    });
    
})();