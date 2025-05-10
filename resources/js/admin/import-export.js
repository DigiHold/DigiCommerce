/**
 * DigiCommerce Import/Export JavaScript
 */
(function() {
    'use strict';
    
    // Wait for DOM ready
    document.addEventListener('DOMContentLoaded', function() {
        initImportExport();
    });

    /**
     * Initialize the Import/Export functionality
     */
    function initImportExport() {
        // Get import and export form elements
        const exportForm = document.getElementById('digicommerce-export-form');
        const importForm = document.getElementById('digicommerce-import-form');
        
        // Check if we're on the import/export page
        if (!exportForm && !importForm) {
            return;
        }

        // Initialize modals system
        initModals();

        // Initialize export form functionality
        if (exportForm) {
            initExportForm(exportForm);
        }

        // Initialize import form functionality
        if (importForm) {
            initImportForm(importForm);
        }
    }

    /**
     * Initialize Export Form functionality
     * 
     * @param {HTMLElement} exportForm The export form element
     */
    function initExportForm(exportForm) {
        const exportAll = document.getElementById('export-all');
        
        // Get all individual export checkboxes
        const exportCheckboxes = document.querySelectorAll('#digicommerce-export-form input[type="checkbox"]:not(#export-all)');
        const individualCheckboxes = Array.from(exportCheckboxes);

        // Handle "Export All" checkbox
        if (exportAll) {
            exportAll.addEventListener('change', function() {
                const isChecked = this.checked;
                
                // Set all other export checkboxes to match
                individualCheckboxes.forEach(function(checkbox) {
                    checkbox.checked = isChecked;
                    checkbox.disabled = isChecked;
                });
            });
        }
        
        // Add listener to individual checkboxes
        individualCheckboxes.forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                if (exportAll) {
                    // Check if all checkboxes are checked and update Export All checkbox
                    const allChecked = individualCheckboxes.every(function(box) {
                        return box.checked;
                    });
                    
                    exportAll.checked = allChecked;
                }
            });
        });
        
        // Handle the export form submission
        exportForm.addEventListener('submit', function(event) {
            // Check if at least one export option is selected
            const hasSelection = document.querySelectorAll('#digicommerce-export-form input[type="checkbox"]:checked').length > 0;
            
            if (!hasSelection) {
                event.preventDefault();
                showAlert(digiCommerceImportExport.selectOneOption || 'Please select at least one option to export.');
                return false;
            }
            
            // Continue with form submission
            return true;
        });
    }

    /**
     * Initialize Import Form functionality
     * 
     * @param {HTMLElement} importForm The import form element
     */
    function initImportForm(importForm) {
        const fileInput = document.getElementById('import-file');
        const dropzoneArea = document.getElementById('dropzone-area');
        const filePreview = document.getElementById('file-preview');
        const fileNameElement = filePreview.querySelector('.file-name');
        const fileSizeElement = filePreview.querySelector('.file-size');
        const fileRemove = filePreview.querySelector('.file-remove');
        
        // Function to format file size
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
        }
        
        // Show file preview
        function showFilePreview(file) {
            fileNameElement.textContent = file.name;
            fileSizeElement.textContent = formatFileSize(file.size);
            filePreview.classList.add('active');
        }
        
        // Handle file selection
        if (fileInput) {
            fileInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    showFilePreview(this.files[0]);
                }
            });
        }
        
        // Handle drag and drop
        if (dropzoneArea) {
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropzoneArea.addEventListener(eventName, preventDefaults, false);
            });
            
            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            ['dragenter', 'dragover'].forEach(eventName => {
                dropzoneArea.addEventListener(eventName, highlight, false);
            });
            
            ['dragleave', 'drop'].forEach(eventName => {
                dropzoneArea.addEventListener(eventName, unhighlight, false);
            });
            
            function highlight() {
                dropzoneArea.classList.add('dragover');
            }
            
            function unhighlight() {
                dropzoneArea.classList.remove('dragover');
            }
            
            dropzoneArea.addEventListener('drop', handleDrop, false);
            
            function handleDrop(e) {
                const dt = e.dataTransfer;
                const files = dt.files;
                if (files && files.length) {
                    fileInput.files = files;
                    showFilePreview(files[0]);
                }
            }
            
            // Make the entire dropzone area clickable to open file dialog
            dropzoneArea.addEventListener('click', function(e) {
                // Don't trigger if clicked on the label or input (let their default behavior work)
                if (e.target.closest('label') || e.target.closest('input')) {
                    return;
                }
                
                // Trigger click on file input
                fileInput.click();
            });
        }
        
        // Handle remove file
        if (fileRemove) {
            fileRemove.addEventListener('click', function() {
                fileInput.value = '';
                filePreview.classList.remove('active');
            });
        }
        
        // Handle form submission
        importForm.addEventListener('submit', function(event) {
            // Check if a file is selected
            if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                event.preventDefault();
                showAlert(digiCommerceImportExport.selectFile || 'Please select a file to import.');
                return false;
            }
            
            // Check file extension
            const fileName = fileInput.files[0].name;
            const fileExt = fileName.split('.').pop().toLowerCase();
            
            if (fileExt !== 'json') {
                event.preventDefault();
                showAlert(digiCommerceImportExport.jsonFileOnly || 'Please select a JSON file.');
                return false;
            }
            
            // Confirm import
            if (digiCommerceImportExport.confirmImport) {
                event.preventDefault();
                showConfirm(digiCommerceImportExport.confirmImport, function() {
                    // Submit the form programmatically after confirmation
                    importForm.submit();
                });
                return false;
            }
            
            // Continue with form submission
            return true;
        });
    }

    /**
     * Initialize custom modals system
     */
    function initModals() {
        // Create modal container if it doesn't exist
        if (!document.getElementById('digi-import-export-modals-container')) {
            const modalContainer = document.createElement('div');
            modalContainer.id = 'digi-import-export-modals-container';
            document.body.appendChild(modalContainer);
        }
    }

    // Track if a modal is currently active
    let modalActive = false;

    /**
     * Show a custom alert modal
     */
    function showAlert(message, onClose = null) {
        // Check if a modal is already active
        if (modalActive) {
            return;
        }
        
        // Set modal as active
        modalActive = true;
        
        const modalId = 'digi-import-export-alert-modal-' + Date.now();
        const modalHTML = `
            <div id="${modalId}" class="digi-modal">
                <div class="digi-modal-content" tabindex="-1">
                    <div class="digi-modal-header">
                        <span class="digi-modal-title">${digiCommerceImportExport.notice || 'Notice'}</span>
                        <button type="button" class="digi-modal-close">&times;</button>
                    </div>
                    <div class="digi-modal-body">
                        <p>${message}</p>
                    </div>
                    <div class="digi-modal-footer">
                        <button type="button" class="digi-btn digi-btn-primary digi-modal-ok">${digiCommerceImportExport.ok || 'OK'}</button>
                    </div>
                </div>
            </div>
        `;
        
        const container = document.getElementById('digi-import-export-modals-container');
        container.insertAdjacentHTML('beforeend', modalHTML);
        
        const modal = document.getElementById(modalId);
        const modalContent = modal.querySelector('.digi-modal-content');
        const closeBtn = modal.querySelector('.digi-modal-close');
        const okBtn = modal.querySelector('.digi-modal-ok');
        
        // Focus the modal content for keyboard navigation
        setTimeout(() => {
            modalContent.focus();
        }, 100);
        
        const closeModal = () => {
            if (!modal) return;
            
            modal.classList.add('closing');
            setTimeout(() => {
                if (modal.parentNode) {
                    modal.remove();
                }
                
                // Reset modal active state
                modalActive = false;
                
                if (typeof onClose === 'function') {
                    onClose();
                }
            }, 300);
        };
        
        closeBtn.addEventListener('click', closeModal);
        okBtn.addEventListener('click', closeModal);
        
        // Close when clicking outside
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal();
            }
        });
        
        // Handle keyboard events
        modal.addEventListener('keydown', (e) => {
            // Close on ESC key
            if (e.key === 'Escape') {
                e.preventDefault();
                closeModal();
            }
            // Confirm on Enter key
            if (e.key === 'Enter') {
                e.preventDefault();
                closeModal();
            }
            // Trap the focus inside modal
            if (e.key === 'Tab') {
                if (e.shiftKey && document.activeElement === closeBtn) {
                    e.preventDefault();
                    okBtn.focus();
                } else if (!e.shiftKey && document.activeElement === okBtn) {
                    e.preventDefault();
                    closeBtn.focus();
                }
            }
        });
        
        // Show modal with animation
        setTimeout(() => {
            modal.classList.add('active');
            // Focus the OK button by default
            okBtn.focus();
        }, 10);
    }

    /**
     * Show a custom confirm modal
     */
    function showConfirm(message, onConfirm, onCancel = null) {
        // Check if a modal is already active
        if (modalActive) {
            return;
        }
        
        // Set modal as active
        modalActive = true;
        
        const modalId = 'digi-import-export-confirm-modal-' + Date.now();
        const modalHTML = `
            <div id="${modalId}" class="digi-modal">
                <div class="digi-modal-content" tabindex="-1">
                    <div class="digi-modal-header">
                        <span class="digi-modal-title">${digiCommerceImportExport.confirmTitle || 'Confirm'}</span>
                        <button type="button" class="digi-modal-close">&times;</button>
                    </div>
                    <div class="digi-modal-body">
                        <p>${message}</p>
                    </div>
                    <div class="digi-modal-footer">
                        <button type="button" class="digi-btn digi-btn-secondary digi-modal-cancel">${digiCommerceImportExport.cancel || 'Cancel'}</button>
                        <button type="button" class="digi-btn digi-btn-primary digi-modal-confirm">${digiCommerceImportExport.confirm || 'Confirm'}</button>
                    </div>
                </div>
            </div>
        `;
        
        const container = document.getElementById('digi-import-export-modals-container');
        if (!container) {
            console.error('Modal container not found, creating it now');
            initModals();
            return showConfirm(message, onConfirm, onCancel); // Try again after creating container
        }
        
        container.insertAdjacentHTML('beforeend', modalHTML);
        
        const modal = document.getElementById(modalId);
        const modalContent = modal.querySelector('.digi-modal-content');
        const closeBtn = modal.querySelector('.digi-modal-close');
        const cancelBtn = modal.querySelector('.digi-modal-cancel');
        const confirmBtn = modal.querySelector('.digi-modal-confirm');
        
        // Focus the modal content for keyboard navigation
        setTimeout(() => {
            modalContent.focus();
        }, 100);
        
        const closeModal = (confirmed = false) => {
            if (!modal) return;
            
            modal.classList.add('closing');
            setTimeout(() => {
                if (modal.parentNode) {
                    modal.remove();
                }
                
                // Reset modal active state
                modalActive = false;
                
                if (confirmed && typeof onConfirm === 'function') {
                    onConfirm();
                } else if (!confirmed && typeof onCancel === 'function') {
                    onCancel();
                }
            }, 300);
        };
        
        closeBtn.addEventListener('click', () => closeModal(false));
        cancelBtn.addEventListener('click', () => closeModal(false));
        confirmBtn.addEventListener('click', () => closeModal(true));
        
        // Close when clicking outside
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal(false);
            }
        });
        
        // Handle keyboard events
        modal.addEventListener('keydown', (e) => {
            // Close on ESC key
            if (e.key === 'Escape') {
                e.preventDefault();
                closeModal(false);
            }
            // Confirm on Enter key
            if (e.key === 'Enter') {
                e.preventDefault();
                closeModal(true);
            }
            // Trap the focus inside modal
            if (e.key === 'Tab') {
                const focusableElements = [closeBtn, cancelBtn, confirmBtn];
                const firstElement = focusableElements[0];
                const lastElement = focusableElements[focusableElements.length - 1];
                
                if (e.shiftKey && document.activeElement === firstElement) {
                    e.preventDefault();
                    lastElement.focus();
                } else if (!e.shiftKey && document.activeElement === lastElement) {
                    e.preventDefault();
                    firstElement.focus();
                }
            }
        });
        
        // Show modal with animation
        setTimeout(() => {
            modal.classList.add('active');
            // Focus the confirm button by default
            confirmBtn.focus();
        }, 10);
    }
})();