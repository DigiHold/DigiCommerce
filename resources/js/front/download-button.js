/**
 * DigiCommerce Download Handler
 */
class DigiCommerceDownload {
    constructor() {
        this.init();
    }

    init() {
        document.addEventListener('DOMContentLoaded', () => {
            this.attachDownloadHandlers();
        });
    }

    attachDownloadHandlers() {
        const downloadButtons = document.querySelectorAll('.download-item');
        downloadButtons.forEach(button => {
            button.addEventListener('click', (e) => this.handleDownload(e));
        });
    }

    async handleDownload(event) {
        const button = event.currentTarget;
        let fileId = button.dataset.file;
        const orderId = button.dataset.order;
        const token = button.dataset.token;

		// Check if we have a select box next to the button
        const select = button.parentElement.querySelector('select');
        if (select) {
            fileId = select.value; // Get file ID from select if it exists
        }
        
        // Make sure we have a file ID
        if (!fileId) {
            this.handleDownloadError(button, {
                code: 'no_file',
                message: 'No file selected'
            });
            return;
        }
        
        this.updateButtonState(button, 'loading');

        try {
            // Get download URL
            const downloadUrl = await this.getDownloadUrl(fileId, orderId, token);
            
            // Create hidden iframe for download
            this.initiateDownload(downloadUrl);
            
            // Reset button after delay
            setTimeout(() => {
                this.updateButtonState(button, 'default');
            }, 2000);
            
        } catch (error) {
            console.error('Download error:', error);
            this.handleDownloadError(button, {
                code: 'unknown_error',
                message: error.message || digicommerceVars.i18n.download_failed
            });
        }
    }

    initiateDownload(url) {
        // Create hidden iframe for download
        const iframe = document.createElement('iframe');
        iframe.style.display = 'none';
        document.body.appendChild(iframe);
        
        // Use location change for iframe to trigger download
        iframe.contentWindow.location.href = url;
        
        // Remove iframe after a delay
        setTimeout(() => {
            document.body.removeChild(iframe);
        }, 5000);
    }

    async getDownloadUrl(fileId, orderId, token) {
        const formData = new FormData();
        formData.append('action', 'digicommerce_download_token');
        formData.append('file_id', fileId);
        formData.append('order_id', orderId);
        formData.append('nonce', digicommerceVars.download_nonce);

        if (token) {
            formData.append('order_token', token);
        }

        const response = await fetch(digicommerceVars.ajaxurl, {
            method: 'POST',
            body: formData,
            credentials: 'include'
        });

        if (!response.ok) {
            throw new Error(`Request failed: ${response.status}`);
        }

        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.data?.message || 'Failed to generate download URL');
        }

        return result.data.download_url;
    }

    updateButtonState(button, state, customText = null) {
        const textSpan = button.querySelector('.text');
        const originalText = button.dataset.originalText || textSpan.textContent;
        
        if (!button.dataset.originalText) {
            button.dataset.originalText = originalText;
        }

        switch (state) {
            case 'loading':
                button.classList.add('loading');
                button.classList.remove('error');
                textSpan.textContent = digicommerceVars.i18n.downloading;
                break;
            case 'error':
                button.classList.remove('loading');
                button.classList.add('error');
                textSpan.textContent = customText || digicommerceVars.i18n.download_failed;
                break;
            default:
                button.classList.remove('loading', 'error');
                textSpan.textContent = button.dataset.originalText;
                break;
        }
    }

    handleDownloadError(button, error) {
        this.updateButtonState(button, 'error', digicommerceVars.i18n.download_failed);
        
        if (window.DigiCommerceNotice) {
            window.DigiCommerceNotice.error(error.message);
        }
        
        setTimeout(() => {
            this.updateButtonState(button, 'default');
        }, 5000);
    }
}

// Initialize the download handler
window.digicommerceDownload = new DigiCommerceDownload();