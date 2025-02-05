// Modal functionality
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('terms-modal');
    if (!modal) return;

    const overlay = modal.querySelector('.modal-overlay');
    const container = modal.querySelector('.modal-container');

    // Show modal with animations
    const showModal = () => {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        
        // Fade in overlay
        setTimeout(() => {
            overlay.classList.remove('opacity-0');
            
            // Slide in modal after overlay starts appearing
            setTimeout(() => {
                container.classList.remove('opacity-0', 'translate-y-[-20px]');
            }, 150);
        }, 10);
    };

    // Hide modal with animations
    const hideModal = () => {
        overlay.classList.add('opacity-0');
        container.classList.add('opacity-0', 'translate-y-[-20px]');
        
        // Wait for animations to finish before hiding completely
        setTimeout(() => {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        }, 300);
    };

    // Show modal when clicking links with 'modal' class
    document.querySelectorAll('a.modal').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            showModal();
        });
    });

    // Close on button click
    modal.querySelectorAll('.close-modal').forEach(button => {
        button.addEventListener('click', hideModal);
    });

    // Close on overlay click
    overlay.addEventListener('click', hideModal);

    // Close on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            hideModal();
        }
    });
});