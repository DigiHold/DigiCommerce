/**
 * DigiCommerce Products Sorting Block Frontend Script
 */

(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        const sortingSelects = document.querySelectorAll('.digicommerce-products-sorting__select');
        
        sortingSelects.forEach(function(select) {
            select.addEventListener('change', function() {
                // Submit the form when selection changes
                const form = this.closest('form');
                if (form) {
                    form.submit();
                }
            });
        });
    });
})();