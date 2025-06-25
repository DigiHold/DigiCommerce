(function() {
    'use strict';

    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initProductMetaboxes);
    } else {
        initProductMetaboxes();
    }

    function initProductMetaboxes() {
        initPricingMode();
        initVariations();
        initFiles();
        initFeatures();
        initGallery();
        initBundleProducts();
        initUpgradePaths();
        initApiData();
		initDirectUrls();
    }

    // Pricing Mode Management
    function initPricingMode() {
        const priceModeInputs = document.querySelectorAll('input[name="digi_price_mode"]');
        const singlePricing = document.querySelector('.pricing-single');
        const variationsPricing = document.querySelector('.pricing-variations');

        if (!priceModeInputs.length || !singlePricing || !variationsPricing) {
            return;
        }

        priceModeInputs.forEach(input => {
            input.addEventListener('change', function() {
                if (this.value === 'single') {
                    singlePricing.style.display = 'block';
                    variationsPricing.style.display = 'none';
                } else {
                    singlePricing.style.display = 'none';
                    variationsPricing.style.display = 'block';
                }
            });
        });
    }

    // Price Variations Management
    function initVariations() {
        const addVariationBtn = document.querySelector('.add-variation');
        const variationsList = document.querySelector('.variations-list');
        const template = document.getElementById('variation-template');

        if (!addVariationBtn || !variationsList || !template) {
            return;
        }

        // Add variation
        addVariationBtn.addEventListener('click', function() {
            const existingVariations = variationsList.querySelectorAll('.variation-item');
            const newIndex = existingVariations.length;
            let templateHTML = template.innerHTML;

            // Replace placeholders
            templateHTML = templateHTML.replace(/\{\{INDEX\}\}/g, newIndex);
            templateHTML = templateHTML.replace(/\{\{NUMBER\}\}/g, newIndex + 1);

            variationsList.insertAdjacentHTML('beforeend', templateHTML);
            updateVariationNumbers();
			updateVariationUrls();
        });

        // Remove variation
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-variation')) {
                if (confirm(digicommerceVars.i18n.removeConfirm)) {
                    e.target.closest('.variation-item').remove();
                    updateVariationNumbers();
                }
            }
        });

		document.addEventListener('input', function(e) {
			if (e.target.classList.contains('variation-file-name') || 
				e.target.classList.contains('variation-file-item-name') ||
				e.target.classList.contains('version-number') ||
				e.target.classList.contains('version-changelog')) {
				updateVariationFilesData();
			}
		});

        // Default checkbox handling
        document.addEventListener('change', function(e) {
            if (e.target.type === 'checkbox' && e.target.name && e.target.name.includes('[isDefault]')) {
                if (e.target.checked) {
                    // Uncheck all other default checkboxes
                    const allDefaults = document.querySelectorAll('input[name*="[isDefault]"]');
                    allDefaults.forEach(checkbox => {
                        if (checkbox !== e.target) {
                            checkbox.checked = false;
                        }
                    });
                }
            }
        });

        // Add variation file upload handlers
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('add-variation-file-btn')) {
                handleVariationFileUpload(e);
            }
            if (e.target.classList.contains('remove-variation-file-btn')) {
                handleVariationFileRemoval(e);
            }
        });
    }

    function updateVariationNumbers() {
        const variations = document.querySelectorAll('.variation-item');
        variations.forEach((variation, index) => {
            variation.dataset.index = index;
            const numberSpan = variation.querySelector('.variation-number');
            if (numberSpan) {
                numberSpan.textContent = index + 1;
            }

            // Update input names
            const inputs = variation.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                if (input.name) {
                    input.name = input.name.replace(/\[\d+\]/, `[${index}]`);
                }
            });

            // Update data attributes
            const fileBtn = variation.querySelector('.add-variation-file-btn');
            if (fileBtn) {
                fileBtn.dataset.variationIndex = index;
            }
        });
    }

    // ==============================================
    // FILE MANAGEMENT WITH REST API
    // ==============================================
    
    // File Uploader Class
    class FileUploader {
        constructor() {
            this.isUploading = false;
        }

        async uploadFile(fileInput, onSuccess, onError) {
            const file = fileInput.files[0];
            if (!file) return;

            // Validate file size (max 100MB)
            const maxSize = 100 * 1024 * 1024;
            if (file.size > maxSize) {
                if (onError) onError(digicommerceVars.i18n.file_too_large);
                return;
            }

            // Validate file type
            const allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'zip', 'rar', '7z', 'jpg', 'jpeg', 'png', 'gif', 'svg', 'mp4', 'mp3', 'wav'];
            const fileExtension = file.name.split('.').pop().toLowerCase();
            
            if (!allowedExtensions.includes(fileExtension)) {
                if (onError) onError(digicommerceVars.i18n.invalid_file);
                return;
            }

            this.isUploading = true;
            
            try {
                const formData = new FormData();
                formData.append('file', file);
                formData.append('action', 'digicommerce_upload_file');
                formData.append('upload_nonce', digicommerceVars.upload_nonce);

                const response = await fetch(digicommerceVars.ajaxurl, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    const fileData = {
                        id: result.data.id,
                        name: this.formatFileName(file.name),
                        file: result.data.file,
                        type: file.type,
                        size: file.size,
                        itemName: this.formatFileName(file.name),
                        s3: digicommerceVars.s3_enabled,
                        versions: []
                    };

                    if (onSuccess) onSuccess(fileData);
                } else {
                    if (onError) onError(result.data || digicommerceVars.i18n.upload_failed);
                }
            } catch (error) {
                console.error('Upload error:', error);
                if (onError) onError(digicommerceVars.i18n.upload_failed);
            } finally {
                this.isUploading = false;
            }
        }

        async deleteFile(fileData, onSuccess, onError) {
			try {
				const response = await wp.apiFetch({
					path: '/wp/v2/digicommerce/delete-file',
					method: 'POST',
					data: { 
						file: fileData,
						is_s3: digicommerceVars.s3_enabled
					}
				});
		
				if (response.success) {
					let noticeMessage = response.message;
					
					// Customize message based on status
					if (response.status === 'not_found') {
						noticeMessage = digicommerceVars.s3_enabled 
							? digicommerceVars.i18n.file_removed_s3
							: digicommerceVars.i18n.file_removed_server;
					} else if (digicommerceVars.s3_enabled) {
						noticeMessage = digicommerceVars.i18n.file_deleted_s3;
					}
					
					if (onSuccess) onSuccess(response);
				} else {
					if (onError) onError(response.message || digicommerceVars.i18n.delete_failed);
				}
			} catch (error) {
				console.error('Delete error:', error);
				
				let errorMessage = error.message || digicommerceVars.i18n.delete_failed;
				
				if (digicommerceVars.s3_enabled && error.message && error.message.includes('S3')) {
					errorMessage = digicommerceVars.i18n.s3_delete_failed;
				}
				
				if (onError) onError(errorMessage);
			}
		}

        formatFileName(fileName) {
            // Remove file extension and replace hyphens with spaces
            const nameWithoutExt = fileName.replace(/\.[^/.]+$/, "");
            return nameWithoutExt.replace(/-/g, " ");
        }

        formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
    }

    const fileUploader = new FileUploader();

    function initFiles() {
        // Main file upload
        const uploadBtn = document.querySelector('.upload-file-btn');
        if (uploadBtn) {
            uploadBtn.addEventListener('click', handleMainFileUpload);
        }

        // File removal
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-file-btn')) {
                handleFileRemoval(e);
            }
        });

        // Version management
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('add-version-btn')) {
                handleAddVersion(e);
            }
            if (e.target.classList.contains('remove-version-btn')) {
                handleRemoveVersion(e);
            }
            if (e.target.classList.contains('upload-version-btn')) {
                handleVersionFileUpload(e);
            }
        });

        // File field updates
        document.addEventListener('input', function(e) {
            if (e.target.classList.contains('file-name-input') || 
                e.target.classList.contains('file-item-name-input') ||
                e.target.classList.contains('version-number')) {
                updateFilesInput();
            }
        });
    }

    function handleMainFileUpload() {
        const fileInput = document.createElement('input');
        fileInput.type = 'file';
        fileInput.accept = '.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip,.rar,.7z,.jpg,.jpeg,.png,.gif,.svg,.mp4,.mp3,.wav';
        
        fileInput.addEventListener('change', function() {
            if (this.files[0]) {
                showUploadProgress(digicommerceVars.s3_enabled ? digicommerceVars.i18n.s3_uploading : digicommerceVars.i18n.uploading);
                
                fileUploader.uploadFile(
                    this,
                    function(fileData) {
                        addFileToList(fileData);
                        updateFilesInput();
                        hideUploadProgress();
                        showNotice(digicommerceVars.i18n.saved, 'success');
                    },
                    function(error) {
                        hideUploadProgress();
                        showNotice(error, 'error');
                    }
                );
            }
        });
        
        fileInput.click();
    }

    function handleVariationFileUpload(e) {
        const variationIndex = e.target.dataset.variationIndex;
        const fileInput = document.createElement('input');
        fileInput.type = 'file';
        fileInput.accept = '.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip,.rar,.7z,.jpg,.jpeg,.png,.gif,.svg,.mp4,.mp3,.wav';
        
        fileInput.addEventListener('change', function() {
            if (this.files[0]) {
                showUploadProgress(digicommerceVars.s3_enabled ? digicommerceVars.i18n.s3_uploading : digicommerceVars.i18n.uploading);
                
                fileUploader.uploadFile(
                    this,
                    function(fileData) {
                        addFileToVariation(variationIndex, fileData);
                        hideUploadProgress();
                        showNotice(digicommerceVars.i18n.saved, 'success');
                    },
                    function(error) {
                        hideUploadProgress();
                        showNotice(error, 'error');
                    }
                );
            }
        });
        
        fileInput.click();
    }

    function handleVersionFileUpload(e) {
        const versionItem = e.target.closest('.version-item');
        const fileItem = e.target.closest('.file-item, .variation-file-item');
        
        const fileInput = document.createElement('input');
        fileInput.type = 'file';
        fileInput.accept = '.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip,.rar,.7z,.jpg,.jpeg,.png,.gif,.svg,.mp4,.mp3,.wav';
        
        fileInput.addEventListener('change', function() {
            if (this.files[0]) {
                showUploadProgress(digicommerceVars.s3_enabled ? digicommerceVars.i18n.s3_uploading : digicommerceVars.i18n.uploading);
                
                fileUploader.uploadFile(
                    this,
                    function(fileData) {
                        const versionFileInput = versionItem.querySelector('.version-file');
                        versionFileInput.value = fileData.file;
                        updateFilesInput();
                        hideUploadProgress();
                        showNotice(digicommerceVars.i18n.saved, 'success');
                    },
                    function(error) {
                        hideUploadProgress();
                        showNotice(error, 'error');
                    }
                );
            }
        });
        
        fileInput.click();
    }

    function handleFileRemoval(e) {
        if (!confirm(digicommerceVars.i18n.removeConfirm)) {
            return;
        }

        const fileItem = e.target.closest('.file-item');
        const fileData = getFileDataFromItem(fileItem);
        
        showUploadProgress(digicommerceVars.i18n.deleting);
        
        fileUploader.deleteFile(
            fileData,
            function(result) {
                fileItem.remove();
                updateFilesInput();
                hideUploadProgress();
                showNotice(result.message || digicommerceVars.i18n.remove, 'success');
            },
            function(error) {
                hideUploadProgress();
                showNotice(error, 'error');
            }
        );
    }

    function handleVariationFileRemoval(e) {
		if (!confirm(digicommerceVars.i18n.removeConfirm)) {
			return;
		}
	
		const variationFileItem = e.target.closest('.variation-file-item');
		const fileData = getFileDataFromVariationItem(variationFileItem);
		
		showUploadProgress(digicommerceVars.i18n.deleting);
		
		fileUploader.deleteFile(
			fileData,
			function(result) {
				variationFileItem.remove();
				updateVariationFilesData(); // Add this line
				hideUploadProgress();
				showNotice(result.message || digicommerceVars.i18n.remove, 'success');
			},
			function(error) {
				hideUploadProgress();
				showNotice(error, 'error');
			}
		);
	}

    function addFileToList(fileData) {
        const filesContainer = document.querySelector('.files-container');
        if (!filesContainer) return;
        
        // Remove "no files" message if exists
        const noFilesMsg = filesContainer.querySelector('p');
        if (noFilesMsg && noFilesMsg.textContent.includes('No files')) {
            noFilesMsg.remove();
        }
        
        const fileIndex = filesContainer.children.length;
        const fileItemHTML = createFileItemHTML(fileData, fileIndex);
        filesContainer.insertAdjacentHTML('beforeend', fileItemHTML);
    }

    function addFileToVariation(variationIndex, fileData) {
		const variationItem = document.querySelector(`.variation-item[data-index="${variationIndex}"]`);
		if (!variationItem) return;
		
		const filesContainer = variationItem.querySelector('.variation-files-container');
		if (!filesContainer) {
			// Create the files container if it doesn't exist
			const filesSection = `
				<div class="variation-files-section">
					<h5>${digicommerceVars.i18n.downloadFiles}</h5>
					<div class="variation-files-container"></div>
					<button type="button" class="button add-variation-file-btn" data-variation-index="${variationIndex}">
						${digicommerceVars.i18n.addDownloadFile}
					</button>
				</div>
			`;
			
			const basicFields = variationItem.querySelector('.variation-basic-fields');
			basicFields.insertAdjacentHTML('afterend', filesSection);
			return addFileToVariation(variationIndex, fileData); // Try again
		}
		
		const noFilesMsg = filesContainer.querySelector('.no-variation-files');
		if (noFilesMsg) {
			noFilesMsg.remove();
		}
		
		const fileIndex = filesContainer.children.length;
		const fileItemHTML = createVariationFileItemHTML(fileData, fileIndex);
		filesContainer.insertAdjacentHTML('beforeend', fileItemHTML);
		
		// Update variation files data
		updateVariationFilesData();
	}

	function updateVariationFilesData() {
		const variations = document.querySelectorAll('.variation-item');
		
		variations.forEach(function(variation, variationIndex) {
			const filesContainer = variation.querySelector('.variation-files-container');
			const files = [];
			
			if (filesContainer) {
				const fileItems = filesContainer.querySelectorAll('.variation-file-item');
				fileItems.forEach(function(fileItem) {
					const fileData = getFileDataFromVariationItem(fileItem);
					if (fileData.file) { // Only add if file path exists
						// Get versions if license is enabled
						if (digicommerceVars.license_enabled) {
							fileData.versions = getVersionsFromVariationFileItem(fileItem);
						}
						files.push(fileData);
					}
				});
			}
			
			// Create or update hidden input for this variation's files
			let hiddenInput = variation.querySelector('.variation-files-data');
			if (!hiddenInput) {
				hiddenInput = document.createElement('input');
				hiddenInput.type = 'hidden';
				hiddenInput.className = 'variation-files-data';
				hiddenInput.name = `variations[${variationIndex}][files]`;
				variation.appendChild(hiddenInput);
			} else {
				hiddenInput.name = `variations[${variationIndex}][files]`;
			}
			
			hiddenInput.value = JSON.stringify(files);
		});
	}

	function getVersionsFromVariationFileItem(fileItem) {
		const versions = [];
		const versionItems = fileItem.querySelectorAll('.version-item');
		
		versionItems.forEach(function(versionItem) {
			const version = versionItem.querySelector('.version-number').value;
			const changelog = versionItem.querySelector('.version-changelog')?.value || '';
			
			if (version.trim()) {
				const versionRegex = /^\d+\.\d+\.\d+$/;
				if (versionRegex.test(version.trim())) {
					versions.push({ 
						version: version.trim(), 
						changelog: changelog.trim(),
						release_date: new Date().toISOString()
					});
				}
			}
		});
		
		return versions;
	}

    function createFileItemHTML(file, index) {
		return `
			<div class="file-item" data-index="${index}">
				<div class="file-header">
					<h4>${digicommerceVars.i18n.downloadFiles} #${index + 1}</h4>
					<button type="button" class="button-link-delete remove-file-btn">
						${digicommerceVars.i18n.remove}
					</button>
				</div>
				
				<div class="file-details">
					<div class="field-group">
						<label>${digicommerceVars.i18n.fileName}</label>
						<input type="text" class="file-name-input" value="${file.name || ''}" placeholder="${digicommerceVars.i18n.fileName}" />
					</div>
					
					<div class="field-group">
						<label>${digicommerceVars.i18n.itemName}</label>
						<input type="text" class="file-item-name-input" value="${file.itemName || ''}" placeholder="${digicommerceVars.i18n.itemName}" />
					</div>
					
					<div class="field-group">
						<label>${digicommerceVars.i18n.filePath}</label>
						<input type="text" class="file-path-input" value="${file.file || ''}" readonly />
					</div>
					
					${file.size ? `
					<div class="field-group">
						<label>${digicommerceVars.i18n.fileSize}</label>
						<span class="file-size">${fileUploader.formatFileSize(file.size)}</span>
					</div>
					` : ''}
				</div>
				
				${digicommerceVars.license_enabled ? `
				<div class="file-versions">
					<h5>${digicommerceVars.i18n.versions}</h5>
					<div class="versions-container">
						<p class="no-versions">${digicommerceVars.i18n.noVersionsAdded}</p>
					</div>
					<button type="button" class="button add-version-btn" data-file-index="${index}">
						${digicommerceVars.i18n.addVersion}
					</button>
				</div>
				` : ''}
				
				<!-- Hidden data -->
				<input type="hidden" class="file-id" value="${file.id || ''}" />
				<input type="hidden" class="file-type" value="${file.type || ''}" />
			</div>
		`;
	}

    function createVariationFileItemHTML(file, index) {
		return `
			<div class="variation-file-item" data-file-index="${index}">
				<div class="variation-file-header">
					<span>${file.name || digicommerceVars.i18n.unnamedFile}</span>
					<button type="button" class="button-link-delete remove-variation-file-btn">
						${digicommerceVars.i18n.remove}
					</button>
				</div>
				<div class="variation-file-details">
					<p>
						<label>${digicommerceVars.i18n.fileName}</label>
						<input type="text" class="variation-file-name" value="${file.name || ''}" />
					</p>
					<p>
						<label>${digicommerceVars.i18n.itemName}</label>
						<input type="text" class="variation-file-item-name" value="${file.itemName || ''}" />
					</p>
					<p>
						<label>${digicommerceVars.i18n.filePath}</label>
						<input type="text" class="variation-file-path" value="${file.file || ''}" readonly />
					</p>
					
					${digicommerceVars.license_enabled ? `
					<div class="variation-file-versions">
						<h5>${digicommerceVars.i18n.versions}</h5>
						<div class="versions-container">
							<p class="no-versions">${digicommerceVars.i18n.noVersionsAdded}</p>
						</div>
						<button type="button" class="button add-version-btn" data-file-type="variation" data-file-index="${index}">
							${digicommerceVars.i18n.addVersion}
						</button>
					</div>
					` : ''}
					
					<!-- Hidden fields -->
					<input type="hidden" class="variation-file-id" value="${file.id || ''}" />
					<input type="hidden" class="variation-file-type" value="${file.type || ''}" />
				</div>
			</div>
		`;
	}

    function getFileDataFromItem(fileItem) {
        return {
            id: fileItem.querySelector('.file-id').value,
            name: fileItem.querySelector('.file-name-input').value,
            file: fileItem.querySelector('.file-path-input').value,
            type: fileItem.querySelector('.file-type').value,
            itemName: fileItem.querySelector('.file-item-name-input').value
        };
    }

    function getFileDataFromVariationItem(variationFileItem) {
        return {
            id: variationFileItem.querySelector('.variation-file-id').value,
            name: variationFileItem.querySelector('.variation-file-name').value,
            file: variationFileItem.querySelector('.variation-file-path').value,
            type: variationFileItem.querySelector('.variation-file-type').value,
            itemName: variationFileItem.querySelector('.variation-file-item-name').value
        };
    }

    function updateFilesInput() {
        const filesContainer = document.querySelector('.files-list');
        const hiddenInput = document.querySelector('#digi_files');
        
        if (!filesContainer || !hiddenInput) return;
        
        const files = [];
        const fileItems = filesContainer.querySelectorAll('.file-item');
        
        fileItems.forEach(function(item) {
            const fileData = getFileDataFromItem(item);
            
            // Get versions if license is enabled
            if (digicommerceVars.license_enabled) {
                fileData.versions = getVersionsFromFileItem(item);
            }
            
            files.push(fileData);
        });
        
        hiddenInput.value = JSON.stringify(files);
    }

    function getVersionsFromFileItem(fileItem) {
		const versions = [];
		const versionItems = fileItem.querySelectorAll('.version-item');
		
		versionItems.forEach(function(versionItem) {
			const version = versionItem.querySelector('.version-number').value;
			const changelog = versionItem.querySelector('.version-changelog').value;
			
			if (version.trim()) {
				// Basic version validation (semantic versioning)
				const versionRegex = /^\d+\.\d+\.\d+$/;
				if (versionRegex.test(version.trim())) {
					versions.push({ 
						version: version.trim(), 
						changelog: changelog.trim(),
						release_date: new Date().toISOString()
					});
				}
			}
		});
		
		return versions;
	}

    // Version Management
    function handleAddVersion(e) {
		const fileItem = e.target.closest('.file-item, .variation-file-item');
		const versionsContainer = fileItem.querySelector('.versions-container, .variation-file-versions .versions-container');
		const noVersionsMsg = versionsContainer.querySelector('.no-versions');
		
		if (noVersionsMsg) {
			noVersionsMsg.remove();
		}
		
		const versionIndex = versionsContainer.children.length;
		const versionHTML = createVersionItemHTML(versionIndex);
		versionsContainer.insertAdjacentHTML('beforeend', versionHTML);
		
		// Add validation on version number input
		const newVersionItem = versionsContainer.lastElementChild;
		const versionInput = newVersionItem.querySelector('.version-number');
		
		versionInput.addEventListener('blur', function() {
			const versionRegex = /^\d+\.\d+\.\d+$/;
			if (this.value && !versionRegex.test(this.value.trim())) {
				showNotice(digicommerceVars.i18n.semanticVersioning, 'error');
				this.focus();
			}
		});
		
		updateFilesInput();
	}

    function handleRemoveVersion(e) {
        if (!confirm(digicommerceVars.i18n.removeConfirm)) {
            return;
        }
        
        const versionItem = e.target.closest('.version-item');
        versionItem.remove();
        updateFilesInput();
    }

    function createVersionItemHTML(index) {
		return `
			<div class="version-item" data-version-index="${index}">
				<div class="version-header">
					<span class="version-label">${digicommerceVars.i18n.versions} ${index + 1}</span>
					<button type="button" class="button-link-delete remove-version-btn">
						${digicommerceVars.i18n.remove}
					</button>
				</div>
				<div class="version-fields">
					<p>
						<label>${digicommerceVars.i18n.versionNumber}</label>
						<input type="text" class="version-number" value="" placeholder="${digicommerceVars.i18n.versionPlaceholder}" />
					</p>
					<p>
						<label>${digicommerceVars.i18n.changelog}</label>
						<textarea class="version-changelog" rows="3" placeholder="${digicommerceVars.i18n.changelogPlaceholder}"></textarea>
					</p>
				</div>
			</div>
		`;
	}

    // ==============================================
    // EXISTING FUNCTIONALITY (UNCHANGED)
    // ==============================================

    // Features Management
    function initFeatures() {
        const addFeatureBtn = document.querySelector('.add-feature');
        if (!addFeatureBtn) return;

        addFeatureBtn.addEventListener('click', function() {
            const featuresList = document.querySelector('.features-list');
            const featuresCount = featuresList.children.length;
            
            const featureHTML = `
                <div class="feature-item">
                    <p>
                        <label>${digicommerceVars.i18n.featureName}</label>
                        <input type="text" name="features[${featuresCount}][name]" value="" />
                    </p>
                    <p>
                        <label>${digicommerceVars.i18n.featureDescription}</label>
                        <input type="text" name="features[${featuresCount}][text]" value="" />
                    </p>
                    <p>
                        <button type="button" class="button-link-delete remove-feature">${digicommerceVars.i18n.remove}</button>
                    </p>
                </div>
            `;
            
            featuresList.insertAdjacentHTML('beforeend', featureHTML);
        });

        // Remove feature
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-feature')) {
                if (confirm(digicommerceVars.i18n.removeConfirm)) {
                    e.target.closest('.feature-item').remove();
                }
            }
        });
    }

    // Gallery Management
    function initGallery() {
        const selectGalleryBtn = document.querySelector('.select-gallery');
        if (!selectGalleryBtn) return;

        selectGalleryBtn.addEventListener('click', function() {
            openMediaUploader('gallery', function(attachments) {
                updateGalleryDisplay(attachments);
            });
        });
    }

    function updateGalleryDisplay(attachments) {
        const preview = document.querySelector('.gallery-preview');
        const input = document.querySelector('#digi_gallery');

        const galleryData = attachments.map(attachment => ({
            id: attachment.id,
            url: attachment.sizes && attachment.sizes.thumbnail ? 
                 attachment.sizes.thumbnail.url : attachment.url,
            alt: attachment.alt || ''
        }));

        input.value = JSON.stringify(galleryData);

        // Update preview
        let previewHTML = '<div class="gallery-images">';
        galleryData.forEach(image => {
            previewHTML += `
                <div class="gallery-image">
                    <img src="${image.url}" alt="${image.alt}" style="max-width: 100px; height: auto;" />
                </div>
            `;
        });
        previewHTML += '</div>';

        preview.innerHTML = previewHTML;
    }

    // Bundle Products Management
    function initBundleProducts() {
		const addBundleBtn = document.querySelector('.add-bundle-product');
		const bundleList = document.querySelector('.bundle-products-list');
		
		if (!addBundleBtn || !bundleList) return;
	
		addBundleBtn.addEventListener('click', function() {
			const bundleCount = bundleList.children.length;
			
			// Build options HTML from available products
			let optionsHTML = `<option value="">${digicommerceVars.i18n.selectProduct}</option>`;
			
			if (digicommerceVars.available_products && digicommerceVars.available_products.length > 0) {
				digicommerceVars.available_products.forEach(function(product) {
					optionsHTML += `<option value="${product.id}">${escapeHtml(product.title)}</option>`;
				});
			}
			
			const bundleHTML = `
				<div class="bundle-product-item">
					<p>
						<label>${digicommerceVars.i18n.product}</label>
						<select name="bundle_products[${bundleCount}]">
							${optionsHTML}
						</select>
					</p>
					<p>
						<button type="button" class="button-link-delete remove-bundle-product">${digicommerceVars.i18n.remove}</button>
					</p>
				</div>
			`;
			
			bundleList.insertAdjacentHTML('beforeend', bundleHTML);
			
			// Update the "no products" message if it exists
			const noProductsMsg = bundleList.querySelector('p');
			if (noProductsMsg && noProductsMsg.textContent.includes('No products selected yet')) {
				noProductsMsg.remove();
			}
		});
	
		// Remove bundle product
		document.addEventListener('click', function(e) {
			if (e.target.classList.contains('remove-bundle-product')) {
				if (confirm(digicommerceVars.i18n.removeConfirm)) {
					const bundleItem = e.target.closest('.bundle-product-item');
					bundleItem.remove();
					
					// Show "no products" message if no items left
					const remainingItems = document.querySelectorAll('.bundle-product-item');
					if (remainingItems.length === 0) {
						bundleList.innerHTML = '<p>' + digicommerceVars.i18n.noProductsSelected + '</p>';
					}
				}
			}
		});
	}

	// Helper function to escape HTML
	function escapeHtml(text) {
		const map = {
			'&': '&amp;',
			'<': '&lt;',
			'>': '&gt;',
			'"': '&quot;',
			"'": '&#039;'
		};
		return text.replace(/[&<>"']/g, function(m) { return map[m]; });
	}

    // Upgrade Paths Management
    function initUpgradePaths() {
        const addBtn = document.querySelector('.add-upgrade-path');
        const listContainer = document.querySelector('.upgrade-paths-list');
        const template = document.getElementById('upgrade-path-template');

        if (!addBtn || !listContainer || !template) {
            return;
        }

        addBtn.addEventListener('click', function() {
            const existingItems = listContainer.querySelectorAll('.upgrade-path-item');
            const newIndex = existingItems.length;
            const newNumber = newIndex + 1;

            let templateHTML = template.innerHTML;
            templateHTML = templateHTML.replace(/\{\{INDEX\}\}/g, newIndex);
            templateHTML = templateHTML.replace(/\{\{NUMBER\}\}/g, newNumber);

            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = templateHTML;
            const newItem = tempDiv.firstElementChild;

            listContainer.appendChild(newItem);
            attachUpgradePathHandlers(newItem, true); // true = isNewItem
        });

        // Attach handlers to existing items WITHOUT modifying their content
        document.querySelectorAll('.upgrade-path-item').forEach(item => {
            attachUpgradePathHandlers(item, false); // false = isExistingItem
        });
    }

    function attachUpgradePathHandlers(item, isNewItem = false) {
        // Prevent double attachment
        if (item.dataset.handlersAttached === 'true') {
            return;
        }

        // Remove handler
        const removeBtn = item.querySelector('.remove-upgrade-path');
        if (removeBtn) {
            removeBtn.addEventListener('click', function() {
                if (confirm(digicommerceVars.i18n.removeConfirm)) {
                    item.remove();
                    updateUpgradePathNumbers();
                }
            });
        }

        // Coupon toggle
        const couponCheckbox = item.querySelector('.include-coupon-checkbox');
        const couponOptions = item.querySelector('.coupon-options');
        if (couponCheckbox && couponOptions) {
            couponCheckbox.addEventListener('change', function() {
                couponOptions.style.display = this.checked ? 'block' : 'none';
            });
        }

        // Product selection handler
        const productSelect = item.querySelector('.target-product-select');
        const variationSelect = item.querySelector('.target-variation-select');
        
        if (productSelect && variationSelect) {
            productSelect.addEventListener('change', function() {
                // Always update variations when user changes the product
                updateVariationOptions(this, variationSelect);
            });
            
            // For NEW items only, initialize the variation select
            if (isNewItem && productSelect.value) {
                updateVariationOptions(productSelect, variationSelect);
            }
            
            // For EXISTING items, do NOT call updateVariationOptions
            // This preserves the server-rendered content
        }

        // Mark as having handlers attached
        item.dataset.handlersAttached = 'true';
    }

    function updateVariationOptions(productSelect, variationSelect) {
        const productId = productSelect.value;
        
        // Clear existing options
		variationSelect.innerHTML = `<option value="">${digicommerceVars.i18n.selectVariation}</option>`;
        
        if (!productId) {
			variationSelect.innerHTML = `<option value="">${digicommerceVars.i18n.selectProductFirst}</option>`;
            variationSelect.disabled = true;
            return;
        }
        
        // Get variations from the selected option's data attribute
        const selectedOption = productSelect.querySelector(`option[value="${productId}"]`);
        if (!selectedOption) {
            variationSelect.disabled = true;
            return;
        }
        
        let variations = [];
        try {
            const variationsData = selectedOption.getAttribute('data-variations');
            if (variationsData) {
                const allVariations = JSON.parse(variationsData);
                // Filter for license-enabled variations
                variations = allVariations.filter(variation => variation.license_enabled);
            }
        } catch (e) {
            console.error('Error parsing variations data:', e);
			variationSelect.innerHTML = `<option value="">${digicommerceVars.i18n.errorLoadingVariations}</option>`;
            variationSelect.disabled = true;
            return;
        }
        
        // Also check global variations data if available
        if (variations.length === 0 && window.digicommerceProductVariations && window.digicommerceProductVariations[productId]) {
            variations = window.digicommerceProductVariations[productId];
        }
        
        if (variations.length === 0) {
			variationSelect.innerHTML = `<option value="">${digicommerceVars.i18n.noLicensedVariations}</option>`;
            variationSelect.disabled = true;
            return;
        }
        
        // Populate variations
        variationSelect.disabled = false;
        
        variations.forEach(variation => {
            const option = document.createElement('option');
            option.value = variation.id || '';
			option.textContent = variation.name || digicommerceVars.i18n.unnamedVariation;
            variationSelect.appendChild(option);
        });
    }

    function updateUpgradePathNumbers() {
        const items = document.querySelectorAll('.upgrade-path-item');
        items.forEach((item, index) => {
            const numberSpan = item.querySelector('.path-number');
            if (numberSpan) {
                numberSpan.textContent = index + 1;
            }

            // Update input names
            const inputs = item.querySelectorAll('input, select');
            inputs.forEach(input => {
                if (input.name) {
                    input.name = input.name.replace(/\[\d+\]/, '[' + index + ']');
                }
                // Update data-index for product selects
                if (input.classList.contains('target-product-select')) {
                    input.setAttribute('data-index', index);
                }
            });

            item.dataset.index = index;
        });
    }

    // API Data Contributors Management
    function initApiData() {
        const addBtn = document.querySelector('.add-contributor');
        const listContainer = document.querySelector('.contributors-list');
        const template = document.getElementById('contributor-template');

        if (!addBtn || !listContainer || !template) {
            return;
        }

        addBtn.addEventListener('click', function() {
            const existingItems = listContainer.querySelectorAll('.contributor-item');
            const newIndex = existingItems.length;

            let templateHTML = template.innerHTML;
            templateHTML = templateHTML.replace(/\{\{INDEX\}\}/g, newIndex);

            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = templateHTML;
            const newItem = tempDiv.firstElementChild;

            listContainer.appendChild(newItem);
            attachContributorRemoveHandler(newItem);
        });

        // Attach remove handlers to existing items
        document.querySelectorAll('.remove-contributor').forEach(btn => {
            btn.addEventListener('click', function() {
                if (confirm(digicommerceVars.i18n.removeConfirm)) {
                    this.closest('.contributor-item').remove();
                }
            });
        });
    }

	// Direct URL Management
	function initDirectUrls() {
		const postId = document.querySelector('#post_ID')?.value;
		const checkoutUrl = digicommerceVars.checkout_url;
		
		if (!postId || !checkoutUrl) return;
		
		// Single price URL
		const singleUrlField = document.querySelector('.digi-direct-url');
		if (singleUrlField) {
			const singleUrl = new URL(checkoutUrl);
			singleUrl.searchParams.set('id', postId);
			singleUrlField.value = singleUrl.toString();
			
			addUrlFieldHandlers(singleUrlField);
		}
		
		// Update variation URLs
		updateVariationUrls();
	}

	function updateVariationUrls() {
		const postId = document.querySelector('#post_ID')?.value;
		const checkoutUrl = digicommerceVars.checkout_url;
		
		if (!postId || !checkoutUrl) return;
		
		const variationUrlFields = document.querySelectorAll('.digi-direct-url-variation');
		variationUrlFields.forEach((field, index) => {
			const variationUrl = new URL(checkoutUrl);
			variationUrl.searchParams.set('id', postId);
			variationUrl.searchParams.set('variation', index + 1);
			field.value = variationUrl.toString();
			
			addUrlFieldHandlers(field);
		});
	}

	function addUrlFieldHandlers(field) {
		const wrapper = field.closest('.digi-url-field-wrapper');
		const tooltip = wrapper.querySelector('.digi-url-tooltip');
		
		// Click to copy with fallback
		field.addEventListener('click', async function() {
			try {
				// Check if modern Clipboard API is available
				if (navigator.clipboard && navigator.clipboard.writeText) {
					await navigator.clipboard.writeText(this.value);
				} else {
					// Fallback for older browsers or non-HTTPS
					this.select();
					this.setSelectionRange(0, 99999); // For mobile devices
					document.execCommand('copy');
				}
				
				tooltip.textContent = digicommerceVars.i18n.linkCopied || 'Link copied';
				setTimeout(() => {
					tooltip.textContent = digicommerceVars.i18n.clickToCopy || 'Click to copy';
				}, 2000);
			} catch (err) {
				console.error('Failed to copy:', err);
				// Show error message to user
				tooltip.textContent = 'Copy failed - please select and copy manually';
				setTimeout(() => {
					tooltip.textContent = digicommerceVars.i18n.clickToCopy || 'Click to copy';
				}, 3000);
			}
		});
		
		// Tooltip handlers
		field.addEventListener('mouseenter', function() {
			tooltip.style.display = 'block';
		});
		
		field.addEventListener('mouseleave', function() {
			tooltip.style.display = 'none';
		});
	}

    function attachContributorRemoveHandler(item) {
        const removeBtn = item.querySelector('.remove-contributor');
        if (removeBtn) {
            removeBtn.addEventListener('click', function() {
                if (confirm(digicommerceVars.i18n.removeConfirm)) {
                    item.remove();
                }
            });
        }
    }

    // Media Uploader Helper
    function openMediaUploader(type, callback) {
        if (typeof wp === 'undefined' || !wp.media) {
            console.error('WordPress media uploader not available');
            return;
        }

        let frame;

        if (type === 'gallery') {
            frame = wp.media({
                title: digicommerceVars.i18n.selectImages,
                button: {
                    text: digicommerceVars.i18n.useImages
                },
                multiple: true,
                library: {
                    type: 'image'
                }
            });
        } else {
            frame = wp.media({
                title: digicommerceVars.i18n.selectFile,
                button: {
                    text: digicommerceVars.i18n.useFile
                },
                multiple: false
            });
        }

        frame.on('select', function() {
            if (type === 'gallery') {
                const attachments = frame.state().get('selection').toJSON();
                callback(attachments);
            } else {
                const attachment = frame.state().get('selection').first().toJSON();
                callback(attachment);
            }
        });

        frame.open();
    }

    // Utility Functions
    function showUploadProgress(message) {
        let progress = document.querySelector('.digicommerce-upload-progress');
        if (!progress) {
            progress = document.createElement('div');
            progress.className = 'digicommerce-upload-progress';
            progress.style.cssText = `
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background: rgba(0, 0, 0, 0.8);
                color: white;
                padding: 20px;
                border-radius: 5px;
                z-index: 9999;
            `;
            document.body.appendChild(progress);
        }
        progress.textContent = message;
        progress.style.display = 'block';
    }

    function hideUploadProgress() {
        const progress = document.querySelector('.digicommerce-upload-progress');
        if (progress) {
            progress.style.display = 'none';
        }
    }

    function showNotice(message, type = 'info') {
        const notice = document.createElement('div');
        notice.className = `notice notice-${type} is-dismissible`;
        notice.innerHTML = `
            <p>${message}</p>
            <button type="button" class="notice-dismiss">
                <span class="screen-reader-text">${digicommerceVars.i18n.dismissNotice}</span>
            </button>
        `;

        const target = document.querySelector('.wrap') || document.querySelector('.postbox-container');
        if (target) {
            target.insertBefore(notice, target.firstChild);
        }

        setTimeout(function() {
            if (notice.parentNode) {
                notice.parentNode.removeChild(notice);
            }
        }, 5000);

        const dismissBtn = notice.querySelector('.notice-dismiss');
        if (dismissBtn) {
            dismissBtn.addEventListener('click', function() {
                notice.parentNode.removeChild(notice);
            });
        }
    }

})();