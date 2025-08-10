/**
 * Toastr Polyfill and Safe Wrapper
 * Ensures toastr is always available and handles errors gracefully
 */
(function() {
    'use strict';
    
    // Wait for DOM and dependencies
    function initializeToastrSafely() {
        // If toastr doesn't exist, create a minimal polyfill
        if (typeof window.toastr === 'undefined' || !window.toastr) {
            console.warn('Toastr not found, creating polyfill');
            window.toastr = createToastrPolyfill();
        }
        
        // Ensure toastr has options object
        if (!window.toastr.options) {
            window.toastr.options = {};
        }
        
        // Set default options
        Object.assign(window.toastr.options, {
            "closeButton": true,
            "debug": false,
            "newestOnTop": false,
            "progressBar": false,
            "positionClass": "toast-top-right",
            "preventDuplicates": false,
            "onclick": null,
            "showDuration": "300",
            "hideDuration": "1000",
            "timeOut": "5000",
            "extendedTimeOut": "1000",
            "showEasing": "swing",
            "hideEasing": "linear",
            "showMethod": "fadeIn",
            "hideMethod": "fadeOut"
        });
        
        console.log('Toastr initialized successfully');
    }
    
    // Create a minimal toastr polyfill that uses browser alerts as fallback
    function createToastrPolyfill() {
        return {
            options: {},
            success: function(message, title) {
                console.log('SUCCESS:', title, message);
                showFallbackNotification('✓ ' + (title || 'Success'), message, 'success');
            },
            error: function(message, title) {
                console.error('ERROR:', title, message);
                showFallbackNotification('✗ ' + (title || 'Error'), message, 'error');
            },
            info: function(message, title) {
                console.info('INFO:', title, message);
                showFallbackNotification('ℹ ' + (title || 'Info'), message, 'info');
            },
            warning: function(message, title) {
                console.warn('WARNING:', title, message);
                showFallbackNotification('⚠ ' + (title || 'Warning'), message, 'warning');
            },
            clear: function() {
                // Remove any existing fallback notifications
                const existing = document.querySelectorAll('.fallback-toast');
                existing.forEach(el => el.remove());
            }
        };
    }
    
    // Show fallback notification using DOM elements styled like toastr
    function showFallbackNotification(title, message, type) {
        const toast = document.createElement('div');
        toast.className = 'fallback-toast toast toast-' + type;
        toast.innerHTML = `
            <div class="toast-title">${title}</div>
            <div class="toast-message">${message}</div>
        `;
        
        // Style the toast
        Object.assign(toast.style, {
            position: 'fixed',
            top: '20px',
            right: '20px',
            zIndex: '999999',
            maxWidth: '300px',
            padding: '15px',
            borderRadius: '4px',
            color: 'white',
            fontSize: '14px',
            fontFamily: 'Arial, sans-serif',
            boxShadow: '0 2px 8px rgba(0,0,0,0.3)',
            cursor: 'pointer'
        });
        
        // Set background color based on type
        const colors = {
            success: '#51a351',
            error: '#bd362f',
            info: '#2f96b4',
            warning: '#f89406'
        };
        toast.style.backgroundColor = colors[type] || '#333';
        
        // Add to page
        document.body.appendChild(toast);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 5000);
        
        // Remove on click
        toast.addEventListener('click', () => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        });
    }
    
    // Override any existing problematic toastr calls
    function wrapToastrMethods() {
        if (window.toastr) {
            const originalMethods = ['success', 'error', 'info', 'warning'];
            originalMethods.forEach(method => {
                const original = window.toastr[method];
                window.toastr[method] = function(message, title, options) {
                    try {
                        if (original && typeof original === 'function') {
                            return original.call(this, message, title, options);
                        } else {
                            // Fallback if original method is missing
                            showFallbackNotification(title || method.toUpperCase(), message, method);
                        }
                    } catch (e) {
                        console.error('Toastr error caught:', e);
                        showFallbackNotification(title || 'Error', message, 'error');
                    }
                };
            });
        }
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(initializeToastrSafely, 100);
            setTimeout(wrapToastrMethods, 200);
        });
    } else {
        setTimeout(initializeToastrSafely, 100);
        setTimeout(wrapToastrMethods, 200);
    }
    
})();
