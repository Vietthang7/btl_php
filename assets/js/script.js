// Performance optimizations
document.addEventListener('DOMContentLoaded', function() {
    // 1. Lazy loading images
    if ('loading' in HTMLImageElement.prototype) {
        const images = document.querySelectorAll("img.lazy");
        images.forEach(img => {
            img.src = img.dataset.src;
        });
    } else {
        // Fallback for browsers that don't support lazy loading
        let lazyLoadThrottleTimeout;
        
        function lazyLoad() {
            if (lazyLoadThrottleTimeout) {
                clearTimeout(lazyLoadThrottleTimeout);
            }
            
            lazyLoadThrottleTimeout = setTimeout(function() {
                const scrollTop = window.pageYOffset;
                const lazyImages = document.querySelectorAll("img.lazy");
                
                lazyImages.forEach(function(img) {
                    if (img.offsetTop < window.innerHeight + scrollTop) {
                        img.src = img.dataset.src;
                        img.classList.remove('lazy');
                    }
                });
                
                if (lazyImages.length == 0) {
                    document.removeEventListener("scroll", lazyLoad);
                    window.removeEventListener("resize", lazyLoad);
                    window.removeEventListener("orientationChange", lazyLoad);
                }
            }, 20);
        }
        
        document.addEventListener("scroll", lazyLoad);
        window.addEventListener("resize", lazyLoad);
        window.addEventListener("orientationChange", lazyLoad);
        lazyLoad();
    }
    
    // 2. Debounce function for search inputs
    function debounce(func, wait = 300) {
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
    
    // Apply debounce to search inputs
    const searchInputs = document.querySelectorAll('input[type="search"], .search-input');
    searchInputs.forEach(input => {
        input.addEventListener('input', debounce(function(e) {
            // Search functionality
            console.log('Searching for:', e.target.value);
        }, 500));
    });
    
    // 3. Use requestAnimationFrame for animations
    function animateElements() {
        const animatedElements = document.querySelectorAll('.animate-on-scroll');
        
        animatedElements.forEach(el => {
            const rect = el.getBoundingClientRect();
            const isVisible = (rect.top <= window.innerHeight * 0.8 && rect.bottom >= 0);
            
            if (isVisible) {
                el.classList.add('visible');
            }
        });
    }
    
    // Start animation only if elements exist
    if (document.querySelectorAll('.animate-on-scroll').length > 0) {
        window.addEventListener('scroll', debounce(animateElements, 100));
        // Initial call
        animateElements();
    }
    
    // 4. Event delegation for common actions
    document.addEventListener('click', function(e) {
        // Handle modal triggers
        if (e.target.matches('[data-bs-toggle="modal"]') || e.target.closest('[data-bs-toggle="modal"]')) {
            const triggerElement = e.target.matches('[data-bs-toggle="modal"]') ? 
                e.target : e.target.closest('[data-bs-toggle="modal"]');
            const targetModal = document.querySelector(triggerElement.dataset.bsTarget);
            
            if (targetModal && window.bootstrap && window.bootstrap.Modal) {
                const modal = new bootstrap.Modal(targetModal);
                modal.show();
            }
        }
        
        // Handle dropdown toggles without Bootstrap JS
        if (e.target.matches('.dropdown-toggle-custom') || e.target.closest('.dropdown-toggle-custom')) {
            const dropdown = e.target.matches('.dropdown-toggle-custom') ? 
                e.target.nextElementSibling : e.target.closest('.dropdown-toggle-custom').nextElementSibling;
            
            if (dropdown && dropdown.classList.contains('dropdown-menu')) {
                dropdown.classList.toggle('show');
                e.preventDefault();
            }
        }
    });
    
    // 5. Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.matches('.dropdown-toggle-custom') && 
            !e.target.closest('.dropdown-toggle-custom') && 
            !e.target.closest('.dropdown-menu')) {
            
            document.querySelectorAll('.dropdown-menu.show').forEach(dropdown => {
                dropdown.classList.remove('show');
            });
        }
    });
    
    // 6. Initialize any tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    if (window.bootstrap && window.bootstrap.Tooltip) {
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl, {
                boundary: document.body
            });
        });
    }
    
    // 7. Auto-dismiss alerts
    setTimeout(() => {
        document.querySelectorAll('.alert-auto-dismiss').forEach(alert => {
            if (window.bootstrap && window.bootstrap.Alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            } else {
                alert.style.opacity = '0';
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 500);
            }
        });
    }, 5000);
});