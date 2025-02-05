document.addEventListener('DOMContentLoaded', function() {
    const updateBtn = document.getElementById('update-order-btn');
    if (updateBtn) {
        updateBtn.addEventListener('click', function(e) {
            const spinner = this.parentElement.querySelector('.spinner');
            spinner.classList.add('is-active');
        });
    }

    // Get all elements with digicommerce__search class
    const searchSelects = document.querySelectorAll('.digicommerce__search');
    
    // Initialize nice-select2 for each element
    searchSelects.forEach(select => {
        const choices = new Choices(select, {
            searchEnabled: true,
            searchPlaceholderValue: select.dataset.placeholder,
            searchResultLimit: -1,
        });
    });
});