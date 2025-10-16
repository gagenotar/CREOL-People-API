/**
 * CREOL People API Frontend JavaScript
 * 
 * Handles interactive features like card loading states
 */
(function() {
    'use strict';

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    function init() {
        // Add loading animation to cards as they appear
        addLoadingAnimations();
        
        // Setup card click handlers
        setupCardClickHandlers();
    }

    /**
     * Add fade-in animation to cards as they load
     */
    function addLoadingAnimations() {
        var cards = document.querySelectorAll('.creol-person-card');
        
        cards.forEach(function(card, index) {
            // Add slight delay for staggered animation effect
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
            
            setTimeout(function() {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 50); // 50ms delay between each card
        });
    }

    /**
     * Show loading spinner (for future AJAX implementations)
     */
    function showLoading(container) {
        if (!container) return;
        
        container.classList.add('creol-people-loading');
        container.setAttribute('aria-busy', 'true');
        
        var spinner = document.createElement('div');
        spinner.className = 'creol-loading-spinner';
        spinner.setAttribute('role', 'status');
        spinner.innerHTML = '<span class="screen-reader-text">Loading...</span>';
        
        container.appendChild(spinner);
    }

    /**
     * Hide loading spinner (for future AJAX implementations)
     */
    function hideLoading(container) {
        if (!container) return;
        
        container.classList.remove('creol-people-loading');
        container.removeAttribute('aria-busy');
        
        var spinner = container.querySelector('.creol-loading-spinner');
        if (spinner) {
            spinner.remove();
        }
    }

    // Expose utility functions for potential extensions
    window.creolPeopleAPI = {
        showLoading: showLoading,
        hideLoading: hideLoading
    };

})();
