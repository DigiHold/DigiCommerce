document.addEventListener('DOMContentLoaded', function() {
    // Handle review notice dismissal
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('digicommerce-dismiss-review-notice') || 
            e.target.closest('.digicommerce-dismiss-review-notice')) {
            e.preventDefault();
            
            const clickedButton = e.target.classList.contains('digicommerce-dismiss-review-notice') ? 
                e.target : e.target.closest('.digicommerce-dismiss-review-notice');
            
            const isPermanent = clickedButton.dataset.permanent === 'true';
            const notice = clickedButton.closest('.digicommerce-review-notice');
            
            const formData = new FormData();
            formData.append('action', 'digicommerce_dismiss_review_notice');
            formData.append('permanent', isPermanent ? 'true' : 'false');
            formData.append('nonce', digicommerceReviewVars.nonce);
            
            fetch(digicommerceReviewVars.ajaxurl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                notice.style.animation = 'fadeOut 0.5s';
                setTimeout(function() {
                    notice.style.display = 'none';
                }, 500);
            })
            .catch(error => console.error('Error:', error));
        }
    });
});