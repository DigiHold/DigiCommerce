// Wrap everything in an IIFE to avoid global scope pollution
(function() {
    const { registerPlugin } = wp.plugins;
    const { PluginSidebar, PluginSidebarMoreMenuItem } = wp.editor;
    const { MediaUpload, MediaUploadCheck } = wp.blockEditor;
    const { 
        PanelBody, 
        TextControl, 
        Button, 
        Card, 
        CardBody,
        ButtonGroup, 
        TextareaControl, 
        CheckboxControl, 
        SelectControl, 
        Slot,
        Modal
    } = wp.components;
    const { useSelect, useDispatch } = wp.data;
    const { useState, useEffect } = wp.element;
    const { __ } = wp.i18n;

    // Shared utility functions
    const formatFileName = (fileName) => {
        // Remove file extension
        const nameWithoutExt = fileName.replace(/\.[^/.]+$/, "");
        // Replace hyphens with spaces
        return nameWithoutExt.replace(/-/g, " ");
    };

    // Helper function to format file sizes
    const formatFileSize = (bytes) => {
        if (bytes === 0) return '0 Bytes';
        
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    };

    // Shared upload functions
    const createFileUploader = () => {
        // Enhanced file upload with better S3 integration and error handling
        const initFileUpload = async (onSuccess) => {
            const fileInput = document.createElement('input');
            fileInput.type = 'file';
            fileInput.multiple = false;
            
            fileInput.addEventListener('change', async (e) => {
                const file = e.target.files[0];
                if (!file) return;

                // Validate file size (max 100MB)
                const maxSize = 100 * 1024 * 1024; // 100MB
                if (file.size > maxSize) {
                    wp.data.dispatch('core/notices').createNotice(
                        'error',
                        __('File size too large. Maximum size is 100MB.', 'digicommerce'),
                        { type: 'snackbar' }
                    );
                    return;
                }

                // Validate file type
                const allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'zip', 'rar', '7z', 'jpg', 'jpeg', 'png', 'gif', 'svg', 'mp4', 'mp3', 'wav'];
                const fileExtension = file.name.split('.').pop().toLowerCase();
                
                if (!allowedExtensions.includes(fileExtension)) {
                    wp.data.dispatch('core/notices').createNotice(
                        'error',
                        __('Invalid file type. Please upload a supported file format.', 'digicommerce'),
                        { type: 'snackbar' }
                    );
                    return;
                }

                try {
                    const uploadedFile = await handleFileUpload(file);
                    if (uploadedFile && onSuccess) {
                        onSuccess(uploadedFile);
                    }
                } catch (error) {
                    // Error is already handled in handleFileUpload
                    console.error('Upload failed:', error);
                }
            });

            fileInput.click();
        };

        // Enhanced file upload handler with S3 optimization
        const handleFileUpload = async (file) => {
            const formData = new FormData();
            formData.append('action', 'digicommerce_upload_file');
            formData.append('file', file);
            formData.append('upload_nonce', digicommerceVars.upload_nonce);

            // Create a unique notice ID for this upload
            const noticeId = 'upload_' + Date.now();
            
            try {
                // Show initial upload notice based on S3 status
                const uploadMessage = digicommerceVars.s3_enabled 
                    ? digicommerceVars.i18n.s3_uploading 
                    : __('Uploading file...', 'digicommerce');
                    
                wp.data.dispatch('core/notices').createNotice(
                    'info',
                    uploadMessage,
                    { 
                        type: 'snackbar', 
                        isDismissible: false,
                        id: noticeId
                    }
                );

                // Create a timeout for long uploads
                const uploadTimeout = setTimeout(() => {
                    wp.data.dispatch('core/notices').removeNotice(noticeId);
                    wp.data.dispatch('core/notices').createNotice(
                        'warning',
                        __('Upload is taking longer than expected. Please wait...', 'digicommerce'),
                        { type: 'snackbar', id: noticeId + '_timeout' }
                    );
                }, 30000); // 30 seconds

                const response = await fetch(digicommerceVars.ajaxurl, {
                    method: 'POST',
                    body: formData,
                });

                // Clear the timeout
                clearTimeout(uploadTimeout);
                
                // Remove upload notice
                wp.data.dispatch('core/notices').removeNotice(noticeId);
                wp.data.dispatch('core/notices').removeNotice(noticeId + '_timeout');

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();
                
                if (data.success) {
                    const newFile = {
                        name: data.data.name,
                        file: data.data.file,
                        id: data.data.id,
                        type: data.data.type,
                        size: data.data.size,
                        itemName: formatFileName(data.data.name),
                        s3: data.data.s3 || false
                    };

                    // Success message based on storage type
                    const successMessage = digicommerceVars.s3_enabled 
                        ? __('File successfully uploaded to Amazon S3', 'digicommerce')
                        : __('File uploaded successfully', 'digicommerce');
                        
                    wp.data.dispatch('core/notices').createNotice(
                        'success',
                        successMessage,
                        { type: 'snackbar' }
                    );
                    
                    return newFile;
                    
                } else {
                    // Handle specific S3 errors
                    let errorMessage = data.data || __('Upload failed. Please try again.', 'digicommerce');
                    
                    if (data.data && data.data.includes('S3')) {
                        errorMessage = digicommerceVars.i18n.s3_upload_failed;
                    } else if (data.data && data.data.includes('timeout')) {
                        errorMessage = __('Upload timed out. Please try again with a smaller file.', 'digicommerce');
                    } else if (data.data && data.data.includes('size')) {
                        errorMessage = __('File too large. Please choose a smaller file.', 'digicommerce');
                    }
                    
                    throw new Error(errorMessage);
                }
                
            } catch (error) {
                // Clear any existing notices
                wp.data.dispatch('core/notices').removeNotice(noticeId);
                wp.data.dispatch('core/notices').removeNotice(noticeId + '_timeout');
                
                console.error('Upload error:', error);
                
                // Show specific error message
                let errorMessage = error.message;
                
                if (error.message.includes('NetworkError') || error.message.includes('Failed to fetch')) {
                    errorMessage = __('Network error. Please check your connection and try again.', 'digicommerce');
                } else if (error.message.includes('413') || error.message.includes('payload too large')) {
                    errorMessage = __('File too large for upload. Please try a smaller file.', 'digicommerce');
                } else if (error.message.includes('timeout')) {
                    errorMessage = __('Upload timed out. Please try again.', 'digicommerce');
                }
                
                wp.data.dispatch('core/notices').createNotice(
                    'error',
                    errorMessage,
                    { type: 'snackbar' }
                );
                
                throw error; // Re-throw for caller to handle if needed
            }
        };

        // Enhanced file removal with S3 support
        const removeFile = async (fileToRemove, onSuccess) => {
            if (!fileToRemove) {
                wp.data.dispatch('core/notices').createNotice(
                    'error',
                    __('File not found for removal.', 'digicommerce'),
                    { type: 'snackbar' }
                );
                return false;
            }

            try {
                const response = await wp.apiFetch({
                    path: '/wp/v2/digicommerce/delete-file',
                    method: 'POST',
                    data: { 
                        file: fileToRemove,
                        is_s3: fileToRemove.s3 || digicommerceVars.s3_enabled
                    }
                });

                if (response.success) {
                    let noticeMessage = response.message;
                    
                    // Customize message based on status
                    if (response.status === 'not_found') {
                        noticeMessage = digicommerceVars.s3_enabled 
                            ? __('File removed from product (was already deleted from S3)', 'digicommerce')
                            : __('File removed from product (was already deleted from server)', 'digicommerce');
                    } else if (digicommerceVars.s3_enabled) {
                        noticeMessage = __('File successfully removed from S3', 'digicommerce');
                    }
                    
                    wp.data.dispatch('core/notices').createNotice(
                        'success',
                        noticeMessage,
                        { type: 'snackbar' }
                    );
                    
                    if (onSuccess) {
                        onSuccess();
                    }
                    
                    return true;
                }
                
            } catch (error) {
                console.error('Error deleting file:', error);
                
                let errorMessage = error.message || __('Failed to delete file. Please try again.', 'digicommerce');
                
                if (digicommerceVars.s3_enabled && error.message.includes('S3')) {
                    errorMessage = __('Failed to delete file from S3. Please try again.', 'digicommerce');
                }
                
                wp.data.dispatch('core/notices').createNotice(
                    'error',
                    errorMessage,
                    { type: 'snackbar' }
                );
                
                return false;
            }
        };

        return { initFileUpload, handleFileUpload, removeFile };
    };

    // Create uploader instance
    const fileUploader = createFileUploader();

	// Version Modal Component
	const VersionModal = ({ isOpen, onClose, onSave, initialVersion = '', initialChangelog = '' }) => {
		const [version, setVersion] = useState(initialVersion);
		const [changelog, setChangelog] = useState(initialChangelog);

		// Reset form when modal opens/closes
		useEffect(() => {
			if (isOpen) {
				setVersion(initialVersion);
				setChangelog(initialChangelog);
			}
		}, [isOpen, initialVersion, initialChangelog]);

		const handleSave = () => {
			if (!version.trim()) {
				wp.data.dispatch('core/notices').createNotice(
					'error',
					__('Version number is required.', 'digicommerce'),
					{ type: 'snackbar' }
				);
				return;
			}

			// Basic version number validation (e.g., 1.0.5)
			const versionRegex = /^\d+\.\d+\.\d+$/;
			if (!versionRegex.test(version.trim())) {
				wp.data.dispatch('core/notices').createNotice(
					'error',
					__('Please use semantic versioning (e.g., 1.0.5)', 'digicommerce'),
					{ type: 'snackbar' }
				);
				return;
			}

			onSave({ 
				version: version.trim(), 
				changelog: changelog.trim(),
				release_date: new Date().toISOString()
			});
			onClose();
		};

		if (!isOpen) return null;

		return (
			<Modal
				title={initialVersion ? __('Edit Version', 'digicommerce') : __('Add Version', 'digicommerce')}
				onRequestClose={onClose}
				className="digi-version-modal"
			>
				<div className="digi-version-modal-content">
					<TextControl
						label={__('Version Number', 'digicommerce')}
						value={version}
						onChange={setVersion}
						placeholder="1.0.0"
						__nextHasNoMarginBottom={true}
					/>
					<TextareaControl
						label={__('Changelog', 'digicommerce')}
						value={changelog}
						onChange={setChangelog}
						rows={4}
						__nextHasNoMarginBottom={true}
					/>
					<div className="digi-version-modal-footer">
						<Button
							variant="secondary"
							isDestructive={true}
							onClick={onClose}
						>
							{__('Cancel', 'digicommerce')}
						</Button>
						<Button
							variant="primary"
							onClick={handleSave}
						>
							{__('Save', 'digicommerce')}
						</Button>
					</div>
				</div>
			</Modal>
		);
	};

	// Version List Component
	const VersionList = ({ versions, onDeleteVersion, onEditVersion }) => {
		return (
			<div className="digi-version-list">
				{versions.map((ver, index) => (
					<Card key={index} className="digi-version-item">
						<div className="digi-version-list-header">
							<div className="digi-version-list-title">
								{__('Version', 'digicommerce')} {ver.version}
								<div className="digi-version-actions">
									<Button
										variant="secondary"
										onClick={() => onEditVersion(index)}
										className="digi-edit-version"
									>
										<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="12" height="12" ><path d="M362.7 19.3L314.3 67.7 444.3 197.7l48.4-48.4c25-25 25-65.5 0-90.5L453.3 19.3c-25-25-65.5-25-90.5 0zm-71 71L58.6 323.5c-10.4 10.4-18 23.3-22.2 37.4L1 481.2C-1.5 489.7 .8 498.8 7 505s15.3 8.5 23.7 6.1l120.3-35.4c14.1-4.2 27-11.8 37.4-22.2L421.7 220.3 291.7 90.3z"/></svg>
									</Button>
									<Button
										variant="secondary"
										isDestructive={true}
										onClick={() => onDeleteVersion(index)}
										className="digi-delete-version"
									>
										<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" width="12" height="12"><path d="M135.2 17.7L128 32 32 32C14.3 32 0 46.3 0 64S14.3 96 32 96l384 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l-96 0-7.2-14.3C307.4 6.8 296.3 0 284.2 0L163.8 0c-12.1 0-23.2 6.8-28.6 17.7zM416 128L32 128 53.2 467c1.6 25.3 22.6 45 47.9 45l245.8 0c25.3 0 46.3-19.7 47.9-45L416 128z"/></svg>
									</Button>
								</div>
							</div>
						</div>
					</Card>
				))}
			</div>
		);
	};

	// Version Manager Component
	const VersionManager = ({ versions = [], onUpdateVersions }) => {
		const [isModalOpen, setIsModalOpen] = useState(false);
		const [editingIndex, setEditingIndex] = useState(null);
	
		const handleAddVersion = (newVersion) => {
			if (editingIndex !== null) {
				// Editing existing version
				const updatedVersions = [...versions];
				updatedVersions[editingIndex] = newVersion;
				onUpdateVersions(updatedVersions);
				setEditingIndex(null);
			} else {
				// Adding new version
				const updatedVersions = [...versions, newVersion];
				onUpdateVersions(updatedVersions);
			}
		};
	
		const handleEditVersion = (index) => {
			setEditingIndex(index);
			setIsModalOpen(true);
		};
	
		const handleCloseModal = () => {
			setIsModalOpen(false);
			setEditingIndex(null);
		};
	
		const handleDeleteVersion = (index) => {
			const updatedVersions = versions.filter((_, i) => i !== index);
			onUpdateVersions(updatedVersions);
		};
	
		return (
			<div className="digi-version-manager">
				<div className="digi-version-header">
					<h3>{__('Versions', 'digicommerce')}</h3>
					<Button
						variant="secondary"
						onClick={() => setIsModalOpen(true)}
						className="digi-add-version"
					>
						{__('Add', 'digicommerce')}
					</Button>
				</div>
	
				<VersionList
					versions={versions}
					onDeleteVersion={handleDeleteVersion}
					onEditVersion={handleEditVersion}
				/>
	
				{isModalOpen && (
					<VersionModal
						isOpen={isModalOpen}
						onClose={handleCloseModal}
						onSave={handleAddVersion}
						initialVersion={editingIndex !== null ? versions[editingIndex].version : ''}
						initialChangelog={editingIndex !== null ? versions[editingIndex].changelog : ''}
					/>
				)}
			</div>
		);
	};

    // Custom URL Field Component
    const CustomURLField = ({ url }) => {
        const [tooltipText, setTooltipText] = useState(__('Click to copy', 'digicommerce'));
        const [showTooltip, setShowTooltip] = useState(false);

        const handleCopy = async () => {
            try {
                await navigator.clipboard.writeText(url);
                setTooltipText(__('Link copied', 'digicommerce'));
                setTimeout(() => {
                    setTooltipText(__('Click to copy', 'digicommerce'));
                }, 2000);
            } catch (err) {
                console.error('Failed to copy:', err);
            }
        };

        return (
            <div 
                className="digi-url-field" 
                onMouseEnter={() => setShowTooltip(true)}
                onMouseLeave={() => setShowTooltip(false)}
            >
                <TextControl
                    label={__("Direct Purchase URL", "digicommerce")}
                    value={url}
                    onClick={handleCopy}
                    style={{ cursor: 'pointer' }}
                    readOnly={true}
                    __nextHasNoMarginBottom={true}
                />
                {showTooltip && (
                    <div 
                        style={{
                            position: 'absolute',
                            top: '100%',
                            left: '50%',
                            transform: 'translateX(-50%)',
                            backgroundColor: '#1e1e1e',
                            color: 'white',
                            padding: '6px 12px',
                            borderRadius: '4px',
                            fontSize: '12px',
                            marginTop: '4px',
                            zIndex: 1000,
                            pointerEvents: 'none',
                            whiteSpace: 'nowrap'
                        }}
                    >
                        {tooltipText}
                        <div 
                            style={{
                                position: 'absolute',
                                bottom: '100%',
                                left: '50%',
                                transform: 'translateX(-50%)',
                                borderLeft: '6px solid transparent',
                                borderRight: '6px solid transparent',
                                borderBottom: '6px solid #1e1e1e'
                            }}
                        />
                    </div>
                )}
            </div>
        );
    };

    // Price Variation Row Component
    const PriceVariationRow = ({ variation, index, onUpdate, onRemove, onDragStart, onDragOver, onDrop, onDragLeave, onDragEnd }) => {
        const addFileToVariation = async (newFile) => {
            const updatedFiles = [...(variation.files || []), newFile];
            onUpdate(index, { ...variation, files: updatedFiles });
        };

        const removeFileFromVariation = async (fileIndex) => {
            const fileToRemove = variation.files[fileIndex];
            
            // Optimistically remove the file from state
            const updatedFiles = variation.files.filter((_, i) => i !== fileIndex);
            onUpdate(index, { ...variation, files: updatedFiles });

            // Attempt to delete the file
            const success = await fileUploader.removeFile(fileToRemove, () => {
                // File successfully removed - state already updated optimistically
            });

            // If deletion failed, revert the state
            if (!success) {
                onUpdate(index, { ...variation, files: [...variation.files] });
            }
        };

        const postId = useSelect(select => select('core/editor').getCurrentPostId());
        const checkoutPageId = digicommerceVars.checkout_page_id || '';

        // Function to get checkout page URL
        const getCheckoutUrl = () => {
            if (!checkoutPageId) return '';
            return `${wp.url.addQueryArgs(digicommerceVars.checkout_url, {})}`;
        };

        const directUrl = wp.url.addQueryArgs(getCheckoutUrl(), {
            id: postId,
            variation: index + 1,
        });

		const handleFileVersionUpdate = (fileIndex, versions) => {
			const updatedFiles = [...variation.files];
			updatedFiles[fileIndex] = { 
				...updatedFiles[fileIndex],
				versions 
			};
			onUpdate(index, { ...variation, files: updatedFiles });
		};

        return (
            <Card
                className="digi-variation-row digi-row"
                draggable={true}
                onDragStart={(e) => onDragStart(e, index)}
                onDragOver={(e) => onDragOver(e)}
                onDrop={(e) => onDrop(e, index)}
                onDragLeave={(e) => onDragLeave(e)}
                onDragEnd={(e) => onDragEnd(e)}
            >
                <CardBody>
                    <div className="digi-inputs">
                        <TextControl
                            label={__("Name", "digicommerce")}
                            value={variation.name}
                            onChange={(name) => onUpdate(index, { ...variation, name })}
                            placeholder={__("e.g., Single Site License", "digicommerce")}
                            __nextHasNoMarginBottom={true}
                        />
                        <TextControl
                            label={__("Regular Price", "digicommerce")}
                            value={variation.price}
                            onChange={(value) => {
                                if (value === "") {
                                    onUpdate(index, { ...variation, price: "" });
                                    return;
                                }
                                const numValue = parseFloat(value);
                                if (!isNaN(numValue)) {
                                    // Reset sale price if it's higher than regular price
                                    if (variation.salePrice && parseFloat(variation.salePrice) >= numValue) {
                                        onUpdate(index, { ...variation, price: numValue, salePrice: "" });
                                    } else {
                                        onUpdate(index, { ...variation, price: numValue });
                                    }
                                }
                            }}
                            type="number"
                            step="1"
                            min="0"
                            inputMode="decimal"
							__nextHasNoMarginBottom={true}
                        />
                        <TextControl
                            label={__("Sale Price", "digicommerce")}
                            value={variation.salePrice || ""}
                            onChange={(value) => {
                                if (value === "") {
                                    onUpdate(index, { ...variation, salePrice: "" });
                                    return;
                                }
                                const numValue = parseFloat(value);
                                if (!isNaN(numValue)) {
                                    onUpdate(index, { ...variation, salePrice: numValue });
                                }
                            }}
                            onBlur={(e) => {
                                const salePriceValue = parseFloat(e.target.value);
                                const regularPrice = parseFloat(variation.price);
                                
                                if (salePriceValue && regularPrice && salePriceValue >= regularPrice) {
                                    wp.data.dispatch('core/notices').createNotice(
                                        'error',
                                        __('Sale price must be less than regular price', 'digicommerce'),
                                        { type: 'snackbar' }
                                    );
                                    onUpdate(index, { ...variation, salePrice: "" });
                                }
                            }}
                            type="number"
                            step="1"
                            min="0"
                            inputMode="decimal"
                            __nextHasNoMarginBottom={true}
                        />
                        <CheckboxControl
                            label={__("Selected by default", "digicommerce")}
                            checked={variation.isDefault || false}
                            onChange={(isChecked) => onUpdate(index, { ...variation, isDefault: isChecked })}
                            __nextHasNoMarginBottom={true}
                        />
                        <CustomURLField url={directUrl} />
                    </div>

                    <div className="digi-variation-files">
                        {variation.files && variation.files.length > 0 && (
                            <p>{__("Download File:", "digicommerce")}</p>
                        )}
                        {variation.files && variation.files.map((file, fileIndex) => (
                            <Card key={fileIndex} className="digi-card">
                                <CardBody className="digi-card-body">
                                    <div className="digi-inputs">
                                        <TextControl
                                            label={__("File Name", "digicommerce")}
                                            value={file.name}
                                            onChange={(name) => {
                                                const updatedFiles = [...variation.files];
                                                updatedFiles[fileIndex] = { ...file, name };
                                                onUpdate(index, { ...variation, files: updatedFiles });
                                            }}
                                            __nextHasNoMarginBottom={true}
                                        />
                                        <TextControl
                                            label={__("File Path", "digicommerce")}
                                            value={file.file}
                                            disabled={true}
                                            __nextHasNoMarginBottom={true}
                                        />
										<TextControl
											label={__("Item Name", "digicommerce")}
											value={file.itemName || ""}
											onChange={(itemName) => {
												const updatedFiles = [...variation.files];
												updatedFiles[fileIndex] = { ...file, itemName };
												onUpdate(index, { ...variation, files: updatedFiles });
											}}
											placeholder={__("Enter item name", "digicommerce")}
											__nextHasNoMarginBottom={true}
										/>
                                    </div>
									{digicommerceVars.license_enabled && (
                                        <div className="digi-version-section">
                                            <VersionManager
                                                versions={file.versions || []}
                                                onUpdateVersions={(versions) => handleFileVersionUpdate(fileIndex, versions)}
                                            />
                                        </div>
                                    )}
                                    <div className="digi-file-actions">
                                        <Button
                                            variant="secondary"
                                            isDestructive={true}
                                            onClick={() => removeFileFromVariation(fileIndex)}
                                        >
                                            {__("Remove File", "digicommerce")}
                                        </Button>
                                    </div>
                                </CardBody>
                            </Card>
                        ))}
                        <Button
                            variant="secondary"
                            onClick={() => fileUploader.initFileUpload(addFileToVariation)}
                            className="digi-add-button"
                        >
                            {__("Add Download File", "digicommerce")}
                        </Button>
                    </div>

                    <div className="digi-variation-slots">
                        <Slot 
                            name={`DigiCommerceVariablePriceAfter-${index}`}
                            fillProps={{
                                variation: variation,
                                index: index,
                                onUpdate: onUpdate
                            }}
                        />
                    </div>

                    <div className="digi-actions">
                        <Button
                            variant="secondary"
                            isDestructive={true}
                            onClick={() => onRemove(index)}
                            className="digi-remove-button"
                        >
                            {__("Remove Variation", "digicommerce")}
                        </Button>
                    </div>
                </CardBody>
            </Card>
        );
    };

    // FileRow Component
    const FileRow = ({ file, index, onUpdate, onRemove, onDragStart, onDragOver, onDrop, onDragLeave, onDragEnd }) => {
		const handleVersionUpdate = (versions) => {
			onUpdate(index, { ...file, versions });
		};

        return (
            <Card
                className="digi-file-row digi-row"
                draggable={true}
                onDragStart={(e) => onDragStart(e, index)}
                onDragOver={(e) => onDragOver(e)}
                onDrop={(e) => onDrop(e, index)}
                onDragLeave={(e) => onDragLeave(e)}
                onDragEnd={(e) => onDragEnd(e)}
            >
                <CardBody>
                    <div className="digi-inputs">
                        <TextControl
                            label={__("File Name", "digicommerce")}
                            value={file.name}
                            onChange={(name) => onUpdate(index, { ...file, name })}
                            __nextHasNoMarginBottom={true}
                        />
                        <TextControl
                            label={__("File Path", "digicommerce")}
                            value={file.file}
                            onChange={(url) => onUpdate(index, { ...file, file: url })}
                            disabled={true}
                            __nextHasNoMarginBottom={true}
                        />
						<TextControl
							label={__("Item Name", "digicommerce")}
							value={file.itemName || ""}
							onChange={(itemName) => {
								const updatedFile = { ...file, itemName };
								onUpdate(index, updatedFile);
							}}
							placeholder={__("Enter item name", "digicommerce")}
							__nextHasNoMarginBottom={true}
						/>
                    </div>
					{digicommerceVars.license_enabled && (
						<div className="digi-version-section">
							<VersionManager
								versions={file.versions || []}
								onUpdateVersions={handleVersionUpdate}
							/>
						</div>
					)}
                    <div className="digi-actions">
                        <Button
                            variant="secondary"
                            isDestructive={true}
                            onClick={() => onRemove(index)}
                        >
                            {__("Remove File", "digicommerce")}
                        </Button>
                    </div>
                </CardBody>
            </Card>
        );
    };

    // Features Component
    const FeaturesRow = ({ feature, index, onUpdate, onRemove, onDragStart, onDragOver, onDrop, onDragLeave, onDragEnd }) => {
        return (
            <Card
                className="digi-feature-row digi-row"
                draggable={true}
                onDragStart={(e) => onDragStart(e, index)}
                onDragOver={(e) => onDragOver(e)}
                onDrop={(e) => onDrop(e, index)}
                onDragLeave={(e) => onDragLeave(e)}
                onDragEnd={(e) => onDragEnd(e)}
            >
                <CardBody>
                    <div className="digi-inputs">
                        <TextControl
                            label={__("Name", "digicommerce")}
                            value={feature.name}
                            onChange={(name) => onUpdate(index, { ...feature, name })}
                            placeholder={__("Name", "digicommerce")}
                            __nextHasNoMarginBottom={true}
                        />
                        <TextControl
                            label={__("Text", "digicommerce")}
                            value={feature.text}
                            onChange={(text) => onUpdate(index, { ...feature, text })}
                            placeholder={__("Text", "digicommerce")}
                            __nextHasNoMarginBottom={true}
                        />
                    </div>

                    <div className="digi-actions">
                        <Button
                            variant="secondary"
                            isDestructive={true}
                            onClick={() => onRemove(index)}
                            className="digi-remove-button"
                        >
                            {__("Remove Feature", "digicommerce")}
                        </Button>
                    </div>
                </CardBody>
            </Card>
        );
    };

	// Upgrade Path Panel Component 
	const UpgradePathPanel = () => {
		const [upgradePaths, setUpgradePaths] = useState([]);
		const [products, setProducts] = useState([]);
		const currentPostId = useSelect(select => select('core/editor').getCurrentPostId());
		const { editPost } = useDispatch("core/editor");
		const postMeta = useSelect((select) => {
			return select("core/editor").getEditedPostAttribute("meta");
		});
	
		// Load products on mount
		useEffect(() => {
			wp.apiFetch({
				path: '/wp/v2/digi_product?per_page=-1',
				_fields: 'id,title,meta'
			}).then(fetchedProducts => {
				const licensedProducts = fetchedProducts.filter(product => {
					return product.meta?.digi_license_enabled === true || 
						(product.meta?.digi_price_variations && 
							product.meta.digi_price_variations.some(variation => variation.license_enabled));
				});
				setProducts(licensedProducts);
			});
		}, []);
	
		// Load current upgrade paths if they exist
		useEffect(() => {
			if (postMeta?.digi_upgrade_paths) {
				setUpgradePaths(postMeta.digi_upgrade_paths);
			}
		}, [postMeta?.digi_upgrade_paths]);
	
		const addPath = () => {
			const newPath = {
				product_id: '',
				variation_id: '',
				prorate: false,
				include_coupon: false,
				discount_type: 'fixed',
				discount_amount: ''
			};
			const updatedPaths = [...upgradePaths, newPath];
			setUpgradePaths(updatedPaths);
			editPost({ meta: { digi_upgrade_paths: updatedPaths } });
		};
	
		const updatePath = (index, field, value) => {
			const updatedPaths = [...upgradePaths];
			updatedPaths[index] = {
				...updatedPaths[index],
				[field]: value
			};
			setUpgradePaths(updatedPaths);
			editPost({ meta: { digi_upgrade_paths: updatedPaths } });
		};
	
		const removePath = (index) => {
			const updatedPaths = upgradePaths.filter((_, i) => i !== index);
			setUpgradePaths(updatedPaths);
			editPost({ meta: { digi_upgrade_paths: updatedPaths } });
		};
	
		// Only render if Pro is active and license is enabled
		if (!digicommerceVars.pro_active || !digicommerceVars.license_enabled) {
			return null;
		}
	
		const currentProductEnabled = postMeta?.digi_license_enabled || 
			(postMeta?.digi_price_variations && 
			 postMeta.digi_price_variations.some(variation => variation.license_enabled));
			 
		if (!currentProductEnabled) {
			return null;
		}
	
		return (
			<PanelBody title={__("Upgrade Paths", "digicommerce")} initialOpen={false}>
				<div className="digi-container">
					{upgradePaths.map((path, index) => (
						<Card key={index} className="digi-upgrade-path-card">
							<CardBody className="digi-inputs">
								<SelectControl
									label={__("Target Product", "digicommerce")}
									value={path.product_id}
									options={[
										{ label: __("Select a product...", "digicommerce"), value: '' },
										...products.map(product => ({
											label: product.title.rendered,
											value: product.id.toString()
										}))
									]}
									onChange={(value) => updatePath(index, 'product_id', value)}
									__nextHasNoMarginBottom={true}
								/>
	
								{path.product_id && products.find(p => p.id === parseInt(path.product_id))?.meta?.digi_price_mode === 'variations' && (
									<SelectControl
										label={__("Target Variation", "digicommerce")}
										value={path.variation_id}
										options={[
											{ label: __("Select a variation...", "digicommerce"), value: '' },
											...products
												.find(p => p.id === parseInt(path.product_id))
												.meta.digi_price_variations
												.filter(v => v.license_enabled)
												.map(variation => ({
													label: variation.name,
													value: variation.id
												}))
										]}
										onChange={(value) => updatePath(index, 'variation_id', value)}
										__nextHasNoMarginBottom={true}
									/>
								)}
	
								<CheckboxControl
									label={__("Prorate", "digicommerce")}
									checked={path.prorate}
									onChange={(value) => updatePath(index, 'prorate', value)}
									__nextHasNoMarginBottom={true}
								/>
	
								<CheckboxControl
									label={__("Include Coupon", "digicommerce")}
									checked={path.include_coupon}
									onChange={(value) => updatePath(index, 'include_coupon', value)}
									__nextHasNoMarginBottom={true}
								/>
	
								{path.include_coupon && (
									<>
										<SelectControl
											label={__("Discount Type", "digicommerce")}
											value={path.discount_type}
											options={[
												{ label: __("Fixed Amount", "digicommerce"), value: 'fixed' },
												{ label: __("Percentage", "digicommerce"), value: 'percentage' }
											]}
											onChange={(value) => updatePath(index, 'discount_type', value)}
											__nextHasNoMarginBottom={true}
										/>
	
										<TextControl
											label={__("Amount", "digicommerce")}
											type="number"
											value={path.discount_amount}
											onChange={(value) => updatePath(index, 'discount_amount', value)}
											min="0"
											step={path.discount_type === 'percentage' ? "1" : "0.01"}
											__nextHasNoMarginBottom={true}
										/>
									</>
								)}
	
								<Button
									variant="secondary"
									isDestructive={true}
									onClick={() => removePath(index)}
									className="digi-remove-button"
								>
									{__("Remove Path", "digicommerce")}
								</Button>
							</CardBody>
						</Card>
					))}
	
					<Button
						variant="primary"
						onClick={addPath}
						className="digi-add-button"
					>
						{__("Add Upgrade Path", "digicommerce")}
					</Button>
				</div>
			</PanelBody>
		);
	};

	// API Data Modal Component
	const ApiDataModal = ({ isOpen, onClose, initialData = {}, onSave }) => {
		const [formData, setFormData] = useState({
			homepage: '',
			author: '',
			requires: '',
			requires_php: '',
			tested: '',
			description: '',
			installation: '',
			upgrade_notice: '',
			icons: {
				default: ''
			},
			banners: {
				low: '',
				high: ''
			},
			contributors: [],
			...initialData
		});

		// Reset form when modal opens/closes
		useEffect(() => {
			if (isOpen) {
				setFormData({
					homepage: '',
					author: '',
					requires: '',
					requires_php: '',
					tested: '',
					description: '',
					installation: '',
					upgrade_notice: '',
					icons: {
						default: ''
					},
					banners: {
						low: '',
						high: ''
					},
					contributors: [],
					...initialData
				});
			}
		}, [isOpen, initialData]);

		const addContributor = () => {
			setFormData({
				...formData,
				contributors: [...formData.contributors, {
					username: '',
					avatar: '',
					name: ''
				}]
			});
		};

		const removeContributor = (index) => {
			const newContributors = [...formData.contributors];
			newContributors.splice(index, 1);
			setFormData({
				...formData,
				contributors: newContributors
			});
		};

		const updateContributor = (index, value) => {
			const newContributors = [...formData.contributors];
			newContributors[index] = value;
			setFormData({
				...formData,
				contributors: newContributors
			});
		};

		if (!isOpen) return null;

		return (
			<Modal
				title={__("API Data", "digicommerce")}
				onRequestClose={onClose}
				className="digi-api-modal"
			>
				<div className="digi-api-modal-content">
					<div className="digi-api-section">
						<h3>{__("Basic Information", "digicommerce")}</h3>
						<TextControl
							label={__("Homepage", "digicommerce")}
							type="url"
							value={formData.homepage}
							onChange={(value) => setFormData({...formData, homepage: value})}
							help={__("Plugin homepage URL.", "digicommerce")}
							__nextHasNoMarginBottom={true}
						/>
						<TextControl
							label={__("Author", "digicommerce")}
							value={formData.author}
							onChange={(value) => setFormData({...formData, author: value})}
							help={__("Author information with optional link.", "digicommerce")}
							__nextHasNoMarginBottom={true}
						/>
					</div>

					<div className="digi-api-section">
						<h3>{__("Requirements", "digicommerce")}</h3>
						<TextControl
							label={__("Requires WordPress Version", "digicommerce")}
							value={formData.requires}
							onChange={(value) => setFormData({...formData, requires: value})}
							help={__("Minimum required WordPress version.", "digicommerce")}
							__nextHasNoMarginBottom={true}
						/>
						<TextControl
							label={__("Requires PHP Version", "digicommerce")}
							value={formData.requires_php}
							onChange={(value) => setFormData({...formData, requires_php: value})}
							help={__("Minimum required PHP version.", "digicommerce")}
							__nextHasNoMarginBottom={true}
						/>
						<TextControl
							label={__("Tested up to", "digicommerce")}
							value={formData.tested}
							onChange={(value) => setFormData({...formData, tested: value})}
							help={__("WordPress version the plugin has been tested up to.", "digicommerce")}
							__nextHasNoMarginBottom={true}
						/>
					</div>

					<div className="digi-api-section">
						<h3>{__("Description & Installation", "digicommerce")}</h3>
						<TextareaControl
							label={__("Description", "digicommerce")}
							value={formData.description}
							onChange={(value) => setFormData({...formData, description: value})}
							help={__("Full description of the plugin (HTML allowed).", "digicommerce")}
							rows={4}
							__nextHasNoMarginBottom={true}
						/>
						<TextareaControl
							label={__("Installation", "digicommerce")}
							value={formData.installation}
							onChange={(value) => setFormData({...formData, installation: value})}
							help={__("Installation instructions (HTML allowed).", "digicommerce")}
							rows={4}
							__nextHasNoMarginBottom={true}
						/>
						<TextareaControl
							label={__("Upgrade Notice", "digicommerce")}
							value={formData.upgrade_notice}
							onChange={(value) => setFormData({...formData, upgrade_notice: value})}
							help={__("Upgrade notices for your users.", "digicommerce")}
							rows={2}
							__nextHasNoMarginBottom={true}
						/>
					</div>

					<div className="digi-api-section">
						<h3>{__("Assets", "digicommerce")}</h3>
						<TextControl
							label={__("Plugin Icon URL", "digicommerce")}
							type="url"
							value={formData.icons.default}
							onChange={(value) => setFormData({
								...formData,
								icons: { default: value }
							})}
							help={__("URL to your plugin's icon (256x256px).", "digicommerce")}
							__nextHasNoMarginBottom={true}
						/>
						<TextControl
							label={__("Banner Low Resolution URL", "digicommerce")}
							type="url"
							value={formData.banners.low}
							onChange={(value) => setFormData({
								...formData,
								banners: { ...formData.banners, low: value }
							})}
							help={__("URL to your plugin's low resolution banner (772x250px).", "digicommerce")}
							__nextHasNoMarginBottom={true}
						/>
						<TextControl
							label={__("Banner High Resolution URL", "digicommerce")}
							type="url"
							value={formData.banners.high}
							onChange={(value) => setFormData({
								...formData,
								banners: { ...formData.banners, high: value }
							})}
							help={__("URL to your plugin's high resolution banner (1544x500px).", "digicommerce")}
							__nextHasNoMarginBottom={true}
						/>
					</div>

					<div className="digi-api-section">
						<h3>{__("Contributors", "digicommerce")}</h3>
						<div className="digi-contributor-wrap">
							{formData.contributors.map((contributor, index) => (
								<div key={index} className="digi-contributor-row">
									<div className="digi-contributor-fields">
										<TextControl
											value={contributor.username || ''}
											onChange={(value) => updateContributor(index, {
												...contributor,
												username: value
											})}
											placeholder={__("WordPress.org username", "digicommerce")}
											__nextHasNoMarginBottom={true}
										/>
										<TextControl
											value={contributor.name || ''}
											onChange={(value) => updateContributor(index, {
												...contributor,
												name: value
											})}
											placeholder={__("Display Name", "digicommerce")}
											__nextHasNoMarginBottom={true}
										/>
										<TextControl
											value={contributor.avatar || ''}
											onChange={(value) => updateContributor(index, {
												...contributor,
												avatar: value
											})}
											type="url"
											placeholder={__("Avatar URL", "digicommerce")}
											__nextHasNoMarginBottom={true}
										/>
									</div>
									<Button
										isDestructive
										variant="secondary"
										onClick={() => removeContributor(index)}
										icon={
											<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24">
												<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm5 11H7v-2h10v2z"/>
											</svg>
										}
									/>
								</div>
							))}
							<Button
								variant="secondary"
								onClick={() => addContributor()}
								className="digi-add-contributor"
							>
								{__("Add Contributor", "digicommerce")}
							</Button>
						</div>
					</div>

					<div className="digi-api-modal-footer">
						<Button
							variant="secondary"
							isDestructive={true}
							onClick={onClose}
						>
							{__("Cancel", "digicommerce")}
						</Button>
						<Button
							variant="primary"
							onClick={() => onSave(formData)}
						>
							{__("Save", "digicommerce")}
						</Button>
					</div>
				</div>
			</Modal>
		);
	};

	// Main API Data Panel Component
	const ApiDataPanel = () => {
		const [isApiModalOpen, setIsApiModalOpen] = useState(false);
		const { editPost } = useDispatch("core/editor");
		const postMeta = useSelect((select) => {
			return select("core/editor").getEditedPostAttribute("meta");
		});

		// Only render if Pro is active and license is enabled
		if (!digicommerceVars.pro_active || !digicommerceVars.license_enabled) {
			return null;
		}

		return (
			<PanelBody title={__("API Data", "digicommerce")} initialOpen={false}>
				{(!postMeta?.digi_api_data || Object.keys(postMeta.digi_api_data).length === 0) ? (
					<Button
						variant="primary"
						onClick={() => setIsApiModalOpen(true)}
						className="digi-add-button"
					>
						{__("Add API Data", "digicommerce")}
					</Button>
				) : (
					<div className="digi-api-data-preview">
						<Button
							variant="primary"
							onClick={() => setIsApiModalOpen(true)}
						>
							{__("Edit API Data", "digicommerce")}
						</Button>
						<div className="digi-api-data-info">
							<span><strong>{__("Requires:", "digicommerce")}</strong> WordPress {postMeta.digi_api_data.requires}</span>
							<span><strong>{__("Tested up to:", "digicommerce")}</strong> {postMeta.digi_api_data.tested}</span>
						</div>
					</div>
				)}

				{isApiModalOpen && (
					<ApiDataModal
						isOpen={isApiModalOpen}
						onClose={() => setIsApiModalOpen(false)}
						initialData={postMeta?.digi_api_data}
						onSave={(data) => {
							editPost({ meta: { digi_api_data: data } });
							setIsApiModalOpen(false);
						}}
					/>
				)}
			</PanelBody>
		);
	};

	// Bundle Panel Component
	const BundlePanel = () => {
		const [bundleProducts, setBundleProducts] = useState([]);
		const [products, setProducts] = useState([]);
		const [selectedProductsInfo, setSelectedProductsInfo] = useState([]);
		const { editPost } = useDispatch("core/editor");
		const postMeta = useSelect((select) => {
			return select("core/editor").getEditedPostAttribute("meta");
		});
		
		// Get current post ID at component level
		const currentPostId = useSelect(select => select('core/editor').getCurrentPostId());

		// Load products on mount
		useEffect(() => {
			wp.apiFetch({
				path: '/wp/v2/digi_product?per_page=-1&status=publish',
				_fields: 'id,title,meta'
			}).then(fetchedProducts => {
				// Filter out current product
				const filteredProducts = fetchedProducts.filter(product => product.id !== currentPostId);
				setProducts(filteredProducts);
			}).catch(error => {
				console.error('Error fetching products:', error);
			});
		}, [currentPostId]);

		// Load current bundle data
		useEffect(() => {
			const metaBundleProducts = postMeta?.digi_bundle_products;
			// Ensure we always have an array, even if meta is null/undefined
			const bundleProductsArray = Array.isArray(metaBundleProducts) ? metaBundleProducts : [];
			setBundleProducts(bundleProductsArray);
		}, [postMeta?.digi_bundle_products]);

		// Update selected products info when bundle products change
		useEffect(() => {
			if (bundleProducts.length > 0 && products.length > 0) {
				const selectedInfo = bundleProducts
					.filter(productId => productId && productId !== '')
					.map(productId => {
						const product = products.find(p => p.id === parseInt(productId));
						if (product) {
							// Get file count
							const files = product.meta?.digi_files || [];
							const fileCount = Array.isArray(files) ? files.length : 0;
							
							return {
								id: product.id,
								name: product.title.rendered,
								fileCount: fileCount
							};
						}
						return null;
					})
					.filter(Boolean);
				
				setSelectedProductsInfo(selectedInfo);
			} else {
				setSelectedProductsInfo([]);
			}
		}, [bundleProducts, products]);

		const addProduct = () => {
			const newProducts = [...bundleProducts, ''];
			setBundleProducts(newProducts);
			editPost({ meta: { digi_bundle_products: newProducts } });
		};

		const updateProduct = (index, productId) => {
			const updatedProducts = [...bundleProducts];
			updatedProducts[index] = productId;
			setBundleProducts(updatedProducts);
			editPost({ meta: { digi_bundle_products: updatedProducts } });
		};

		const removeProduct = (index) => {
			const updatedProducts = bundleProducts.filter((_, i) => i !== index);
			setBundleProducts(updatedProducts);
			// FIXED: Always save as array, never null or undefined
			editPost({ meta: { digi_bundle_products: updatedProducts } });
		};

		return (
			<PanelBody title={__("Bundle Products", "digicommerce")} initialOpen={false}>
				<div className="digi-container">
					<div className="digi-bundle-info">
						<p>{__("Select products to include in this bundle. Customer will receive downloads for all selected products.", "digicommerce")}</p>
						{digicommerceVars.license_enabled && (
							<p className="text-sm text-gray-600 italic">
								{__("If this bundle product has license system enabled, customers will get one master license that works for all bundled products.", "digicommerce")}
							</p>
						)}
					</div>

					{bundleProducts.map((productId, index) => (
						<Card key={index} className="digi-bundle-product-card">
							<CardBody className="digi-inputs">
								<SelectControl
									label={__("Product", "digicommerce")}
									value={productId}
									options={[
										{ label: __("Select a product...", "digicommerce"), value: '' },
										...products.map(product => ({
											label: product.title.rendered,
											value: product.id.toString()
										}))
									]}
									onChange={(value) => updateProduct(index, value)}
									__nextHasNoMarginBottom={true}
								/>

								<Button
									variant="secondary"
									isDestructive={true}
									onClick={() => removeProduct(index)}
									className="digi-remove-button"
								>
									{__("Remove Product", "digicommerce")}
								</Button>
							</CardBody>
						</Card>
					))}

					<Button
						variant="primary"
						onClick={addProduct}
						className="digi-add-button"
					>
						{__("Add Product", "digicommerce")}
					</Button>

					{/* Bundle Preview */}
					{selectedProductsInfo.length > 0 && (
						<Card className="digi-bundle-preview" style={{ marginTop: '20px', backgroundColor: '#f8f9fa' }}>
							<CardBody>
								<h4 style={{ margin: '0 0 10px 0', fontSize: '14px', fontWeight: '600' }}>
									{__("Bundle Preview", "digicommerce")}
								</h4>
								<div style={{ fontSize: '13px', color: '#666' }}>
									<p style={{ margin: '0 0 8px 0' }}>
										{sprintf(
											__("This bundle includes %d products:", "digicommerce"), 
											selectedProductsInfo.length
										)}
									</p>
									<ul style={{ margin: '0' }}>
										{selectedProductsInfo.map(product => (
											<li key={product.id} style={{ marginBottom: '4px' }}>
												<strong>{product.name}</strong>
												{product.fileCount > 0 && (
													<span style={{ color: '#888', fontSize: '12px' }}>
														{' '}({sprintf(__("%d files", "digicommerce"), product.fileCount)})
													</span>
												)}
											</li>
										))}
									</ul>
									<p style={{ margin: '8px 0 0 0', fontSize: '12px', fontStyle: 'italic' }}>
										{__("Customers will get one master license that works for all bundled products.", "digicommerce")}
									</p>
								</div>
							</CardBody>
						</Card>
					)}
				</div>
			</PanelBody>
		);
	};

    // Main Product Sidebar Component
    const ProductSidebar = () => {
        const [price, setPrice] = useState(0);
        const [salePrice, setSalePrice] = useState("");
        const [files, setFiles] = useState([]);
        const [priceVariations, setPriceVariations] = useState([]);
        const [priceMode, setPriceMode] = useState('single');
        const [productDescription, setProductDescription] = useState("");
        const [features, setFeatures] = useState([]);
        const [instructions, setInstructions] = useState("");
        const { editPost } = useDispatch("core/editor");
        const postId = useSelect(select => select('core/editor').getCurrentPostId());
        const checkoutPageId = digicommerceVars.checkout_page_id || '';
        
        // Function to get checkout page URL
        const getCheckoutUrl = () => {
            if (!checkoutPageId) return '';
            return `${wp.url.addQueryArgs(digicommerceVars.checkout_url, {})}`;
        };
        
        const postMeta = useSelect((select) => {
            return select("core/editor").getEditedPostAttribute("meta");
        });

        useEffect(() => {
            if (postMeta) {
                setPrice(postMeta.digi_price || 0);
                setSalePrice(postMeta.digi_sale_price || "");
                setFiles(postMeta.digi_files || []);
                setPriceVariations(postMeta.digi_price_variations || []);
                setPriceMode(postMeta.digi_price_mode || 'single');
                setProductDescription(postMeta.digi_product_description || "");
                setFeatures(postMeta.digi_features || []);
                setInstructions(postMeta.digi_instructions || "");
            }
        }, [postMeta]);

        // File handlers for main product files
        const addFileToProduct = async (newFile) => {
            const updatedFiles = [...files, newFile];
            setFiles(updatedFiles);
            editPost({ meta: { digi_files: updatedFiles } });
        };

        const updateFile = (index, updatedFile) => {
            const updatedFiles = [...files];
            updatedFiles[index] = updatedFile;
            setFiles(updatedFiles);
            editPost({ meta: { digi_files: updatedFiles } });
        };

        const removeFileFromProduct = async (index) => {
            const fileToRemove = files[index];
            
            // Optimistically remove the file from state
            const updatedFiles = files.filter((_, i) => i !== index);
            setFiles(updatedFiles);
            editPost({ meta: { digi_files: updatedFiles } });

            // Attempt to delete the file
            const success = await fileUploader.removeFile(fileToRemove, () => {
                // File successfully removed - state already updated optimistically
            });

            // If deletion failed, revert the state
            if (!success) {
                setFiles([...files]);
                editPost({ meta: { digi_files: [...files] } });
            }
        };

        // Price Mode Toggle Handler
        const handlePriceModeChange = (mode) => {
            setPriceMode(mode);
            editPost({ meta: { digi_price_mode: mode } });
        };

        // Price Variation Handlers
        const addPriceVariation = () => {
            const uniqueId = Date.now().toString() + Math.random().toString(36).substr(2, 5);
            const newVariation = {
                id: uniqueId,
                name: '',
                price: 0,
                salePrice: null,
                files: [],
                subscription_enabled: false,
                subscription_period: 'month',
                subscription_free_trial: { duration: 0, period: 'days' },
                subscription_signup_fee: 0
            };
            
            const updatedVariations = [...priceVariations, newVariation];
            setPriceVariations(updatedVariations);
            editPost({ meta: { digi_price_variations: updatedVariations } });
        };

        const updatePriceVariation = (index, updatedVariation) => {
            const updatedVariations = [...priceVariations];
            updatedVariations[index] = updatedVariation;
            setPriceVariations(updatedVariations);
            editPost({ meta: { digi_price_variations: updatedVariations } });
        };

        const removePriceVariation = async (index) => {
            const variationToRemove = priceVariations[index];
            
            // First, handle file deletions if the variation has files
            if (variationToRemove.files && variationToRemove.files.length > 0) {
                // Delete each file associated with the variation
                for (const file of variationToRemove.files) {
                    try {
                        await fileUploader.removeFile(file);
                    } catch (error) {
                        console.error('Error deleting variation file:', error);
                        // Show error notification but continue with variation removal
                        wp.data.dispatch('core/notices').createNotice(
                            'error',
                            __('Error deleting some files, but variation was removed', 'digicommerce'),
                            { type: 'snackbar' }
                        );
                    }
                }
            }
        
            // Remove the variation from state
            const updatedVariations = priceVariations.filter((_, i) => i !== index);
            setPriceVariations(updatedVariations);
            editPost({ meta: { digi_price_variations: updatedVariations } });
        
            // Show success notification
            wp.data.dispatch('core/notices').createNotice(
                'success',
                digicommerceVars.s3_enabled ?
                    __('Variation and associated S3 files removed successfully', 'digicommerce') :
                    __('Variation removed successfully', 'digicommerce'),
                { type: 'snackbar' }
            );
        };

        // Features handler
        const addFeature = () => {
            const newFeature = { name: "", text: "" };
            const updatedFeatures = [...features, newFeature];
            setFeatures(updatedFeatures);
            editPost({ meta: { digi_features: updatedFeatures } });
        };

        const updateFeature = (index, updatedFeature) => {
            const updatedFeatures = [...features];
            updatedFeatures[index] = updatedFeature;
            setFeatures(updatedFeatures);
            editPost({ meta: { digi_features: updatedFeatures } });
        };

        const removeFeature = (index) => {
            const updatedFeatures = features.filter((_, i) => i !== index);
            setFeatures(updatedFeatures);
            editPost({ meta: { digi_features: updatedFeatures } });
        };

        // Drag and Drop Handlers
        const handleDragStart = (e, index) => {
            e.dataTransfer.setData("text/plain", index);
            e.currentTarget.classList.add("is-dragging");
        };

        const handleDragOver = (e) => {
            e.preventDefault();
            e.currentTarget.classList.add("is-drag-over");
        };

        const handleDragLeave = (e) => {
            e.currentTarget.classList.remove("is-drag-over");
            e.currentTarget.classList.remove("is-dragging");
        };

        const handleDragEnd = (e) => {
            e.currentTarget.classList.remove("is-dragging");
            e.currentTarget.classList.remove("is-drag-over");
            document.querySelectorAll(".digi-file-row, .digi-variation-row, .digi-feature-row").forEach((row) => {
                row.classList.remove("is-drag-over");
                row.classList.remove("is-dragging");
            });
        };

        const handleDrop = (e, dropIndex, items, setItems, metaKey) => {
            e.preventDefault();
            e.currentTarget.classList.remove("is-drag-over");
            e.currentTarget.classList.remove("is-dragging");
            
            const dragIndex = parseInt(e.dataTransfer.getData("text/plain"));
            if (dragIndex === dropIndex) return;

            const updatedItems = [...items];
            const [draggedItem] = updatedItems.splice(dragIndex, 1);
            updatedItems.splice(dropIndex, 0, draggedItem);
            setItems(updatedItems);
            editPost({ meta: { [metaKey]: updatedItems } });

            document.querySelectorAll(".digi-file-row, .digi-variation-row, .digi-feature-row").forEach((row) => {
                row.classList.remove("is-drag-over");
                row.classList.remove("is-dragging");
            });
        };

        const handleFileDrop = (e, dropIndex) => handleDrop(e, dropIndex, files, setFiles, 'digi_files');
        const handleVariationDrop = (e, dropIndex) => handleDrop(e, dropIndex, priceVariations, setPriceVariations, 'digi_price_variations');
        const handleFeaturesDrop = (e, dropIndex) => handleDrop(e, dropIndex, features, setFeatures, 'digi_features');

        return (
            <>
                <PluginSidebarMoreMenuItem target="product-details">
                    {__("Product Details", "digicommerce")}
                </PluginSidebarMoreMenuItem>
                <PluginSidebar
                    name="product-details"
                    title={__("Product Details", "digicommerce")}
                    className="digi-product-sidebar"
                >
                    <PanelBody title={__("Pricing", "digicommerce")} initialOpen={true}>
                        <div className="digi-price-mode-toggle">
                            <ButtonGroup className="digi-price-mode-buttons">
                                <Button 
                                    variant={priceMode === 'single' ? 'primary' : 'secondary'}
                                    onClick={() => handlePriceModeChange('single')}
                                    className="digi-price-mode-button"
                                >
                                    {__("Single Price", "digicommerce")}
                                </Button>
                                <Button 
                                    variant={priceMode === 'variations' ? 'primary' : 'secondary'}
                                    onClick={() => handlePriceModeChange('variations')}
                                    className="digi-price-mode-button"
                                >
                                    {__("Price Variations", "digicommerce")}
                                </Button>
                            </ButtonGroup>
                        </div>

                        {priceMode === 'single' ? (
                            <div className="digi-inputs">
                                <TextControl
                                    label={__("Regular Price", "digicommerce")}
                                    value={price}
                                    onChange={(value) => {
                                        if (value === "") {
                                            setPrice("");
                                            return;
                                        }
                                        const numValue = parseFloat(value);
                                        if (!isNaN(numValue)) {
                                            setPrice(numValue);
                                            editPost({ meta: { digi_price: numValue } });
                                            // Reset sale price if regular price is lower
                                            if (salePrice && parseFloat(salePrice) >= numValue) {
                                                setSalePrice("");
                                                editPost({ meta: { digi_sale_price: "" } });
                                            }
                                        }
                                    }}
                                    onBlur={() => {
                                        const finalValue = parseFloat(price) || 0;
                                        setPrice(finalValue);
                                        editPost({ meta: { digi_price: finalValue } });
                                    }}
                                    type="number"
                                    step="1"
                                    min="0"
                                    inputMode="decimal"
									__nextHasNoMarginBottom={true}
                                />
                                <TextControl
                                    label={__("Sale Price", "digicommerce")}
                                    value={salePrice}
                                    onChange={(value) => {
                                        if (value === "") {
                                            setSalePrice("");
                                            editPost({ meta: { digi_sale_price: "" } });
                                            return;
                                        }
                                        const numValue = parseFloat(value);
                                        if (!isNaN(numValue)) {
                                            setSalePrice(numValue);
                                            editPost({ meta: { digi_sale_price: numValue } });
                                        }
                                    }}
                                    onBlur={(e) => {
                                        const salePriceValue = parseFloat(e.target.value);
                                        const regularPrice = parseFloat(price);
                                        
                                        if (salePriceValue && regularPrice && salePriceValue >= regularPrice) {
                                            wp.data.dispatch('core/notices').createNotice(
                                                'error',
                                                __('Sale price must be less than regular price', 'digicommerce'),
                                                { type: 'snackbar' }
                                            );
                                            setSalePrice("");
                                            editPost({ meta: { digi_sale_price: "" } });
                                        }
                                    }}
                                    type="number"
                                    step="1"
                                    min="0"
                                    inputMode="decimal"
                                    __nextHasNoMarginBottom={true}
                                />
                                <CustomURLField 
                                    url={wp.url.addQueryArgs(getCheckoutUrl(), { id: postId })}
                                />
                                <div className="digi-slot-container">
                                    <Slot name="DigiCommerceSinglePriceAfter" />
                                </div>
                            </div>
                        ) : (
                            <div className="digi-variations-section">
                                <div className="digi-container">
                                    {priceVariations.map((variation, index) => (
                                        <PriceVariationRow
                                            key={index}
                                            variation={variation}
                                            index={index}
                                            onUpdate={updatePriceVariation}
                                            onRemove={removePriceVariation}
                                            onDragStart={handleDragStart}
                                            onDragOver={handleDragOver}
                                            onDrop={handleVariationDrop}
                                            onDragLeave={handleDragLeave}
                                            onDragEnd={handleDragEnd}
                                        />
                                    ))}
                                </div>
                                <Button
                                    variant="primary"
                                    onClick={addPriceVariation}
                                    className="digi-add-button"
                                >
                                    {__("Add Price Variation", "digicommerce")}
                                </Button>
                            </div>
                        )}
                    </PanelBody>

                    <PanelBody title={__("Downloadable Files", "digicommerce")} initialOpen={false}>
                        {files.length > 0 && (
                            <div
                                style={{
                                    display: 'flex',
                                    backgroundColor: '#f6f7f9',
                                    borderRadius: '0.75rem',
                                    fontSize: '0.7rem',
                                    marginBottom: '1.5rem',
                                    padding: '1rem',
                                    alignItems: 'center',
                                }}
                            >
                                {digicommerceVars.s3_enabled ? __("NOTE: When a file is removed, it is completely removed from your S3 bucket.", "digicommerce") : __("NOTE: When a file is removed, it is completely removed from your server.", "digicommerce")}
                            </div>
                        )}
                        <div className="digi-container">
                            {files.map((file, index) => (
                                <FileRow
                                    key={index}
                                    file={file}
                                    index={index}
                                    onUpdate={updateFile}
                                    onRemove={removeFileFromProduct}
                                    onDragStart={handleDragStart}
                                    onDragOver={handleDragOver}
                                    onDrop={handleFileDrop}
                                    onDragLeave={handleDragLeave}
                                    onDragEnd={handleDragEnd}
                                />
                            ))}
                        </div>
                        <Button
                            variant="primary"
                            onClick={() => fileUploader.initFileUpload(addFileToProduct)}
                            className="digi-add-button"
                        >
                            {__("Add New File", "digicommerce")}
                        </Button>
                    </PanelBody>
                    
                    <PanelBody title={__("Description", "digicommerce")} initialOpen={false}>
                        <TextareaControl
                            help={__("Add a detailed description for your product.", "digicommerce")}
                            value={productDescription}
                            onChange={(value) => {
                                setProductDescription(value);
                                editPost({ meta: { digi_product_description: value } });
                            }}
                            rows={4}
                            __nextHasNoMarginBottom={true}
                        />
                    </PanelBody>

                    <PanelBody title={__("Gallery", "digicommerce")} initialOpen={false}>
                        <MediaUploadCheck>
                            <MediaUpload
                                onSelect={(media) => {
                                    const galleryImages = media.map(image => ({
                                        id: image.id,
                                        url: image.sizes?.medium?.url || image.url,
                                        alt: image.alt || ''
                                    }));
                                    editPost({ meta: { digi_gallery: galleryImages } });
                                }}
                                allowedTypes={['image']}
                                multiple={true}
                                gallery={true}
                                value={postMeta?.digi_gallery?.map(img => img.id) || []}
                                render={({ open }) => (
                                    <div>
                                        <div className="digi-gallery-grid">
                                            {(postMeta?.digi_gallery || []).map((img, index) => (
                                                <div 
                                                    key={index} 
                                                    className="digi-gallery-item"
                                                    onClick={open}
                                                    role="button"
                                                    tabIndex={0}
                                                    onKeyDown={(e) => {
                                                        if (e.key === 'Enter' || e.key === ' ') {
                                                            open();
                                                        }
                                                    }}
                                                >
                                                    <img src={img.url} alt={img.alt} className="digi-gallery-image" />
                                                    <button
                                                        type="button"
                                                        onClick={(e) => {
                                                            e.stopPropagation();
                                                            const newGallery = [...(postMeta.digi_gallery || [])];
                                                            newGallery.splice(index, 1);
                                                            editPost({ meta: { digi_gallery: newGallery } });
                                                        }}
                                                        className="digi-remove-gallery-image"
                                                    >
                                                        <span className="sr-only">{__('Remove image', 'digicommerce')}</span>
                                                        <svg 
                                                            xmlns="http://www.w3.org/2000/svg" 
                                                            viewBox="0 0 24 24" 
                                                            width="20" 
                                                            height="20" 
                                                            fill="none" 
                                                            stroke="currentColor" 
                                                            strokeWidth="2"
                                                        >
                                                            <path d="M18 6L6 18M6 6l12 12" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            ))}
                                        </div>
                                        <Button
                                            variant="primary"
                                            onClick={open}
                                            className="digi-add-button"
                                        >
                                            {!postMeta?.digi_gallery?.length
                                                ? __('Add Gallery Images', 'digicommerce')
                                                : __('Edit Gallery', 'digicommerce')
                                            }
                                        </Button>
                                    </div>
                                )}
                            />
                        </MediaUploadCheck>
                    </PanelBody>

                    <PanelBody title={__("Features", "digicommerce")} initialOpen={false}>
                        <div className="digi-container">
                            {features.map((feature, index) => (
                                <FeaturesRow
                                    key={index}
                                    feature={feature}
                                    index={index}
                                    onUpdate={updateFeature}
                                    onRemove={removeFeature}
                                    onDragStart={handleDragStart}
                                    onDragOver={handleDragOver}
                                    onDrop={handleFeaturesDrop}
                                    onDragLeave={handleDragLeave}
                                    onDragEnd={handleDragEnd}
                                />
                            ))}
                        </div>
                        <Button
                            variant="primary"
                            onClick={addFeature}
                            className="digi-add-button"
                        >
                            {__("Add Feature", "digicommerce")}
                        </Button>
                    </PanelBody>

                    <PanelBody title={__("Download Instructions", "digicommerce")} initialOpen={false}>
                        <TextareaControl
                            label={__("Instructions for customers", "digicommerce")}
                            help={__("These instructions will be shown to customers after purchase", "digicommerce")}
                            value={instructions}
                            onChange={(value) => {
                                setInstructions(value);
                                editPost({ meta: { digi_instructions: value } });
                            }}
                            rows={4}
                            __nextHasNoMarginBottom={true}
                        />
                    </PanelBody>

					<UpgradePathPanel />

					<ApiDataPanel />

					<BundlePanel />
                </PluginSidebar>
            </>
        );
    };

    registerPlugin('digi-product-sidebar', {
        render: ProductSidebar,
        icon: (
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="24" height="24" fill="currentColor" className="digi__icon">
				<circle cx="256" cy="256" r="256"/>
				<path d="M361.4858,348.7728c4.6805,0,8.9099,1.8997,11.9904,4.96,3.1729,3.177,4.952,7.4854,4.9451,11.9755,0,4.672-1.8912,8.9099-4.9451,11.9701-3.1801,3.1788-7.494,4.9621-11.9904,4.9568-4.4924.0071-8.8023-1.7768-11.9755-4.9568-3.1781-3.1723-4.9618-7.4797-4.9568-11.9701,0-4.6805,1.8965-8.9099,4.9568-11.9755,3.1739-3.1794,7.483-4.9641,11.9755-4.96h0ZM199.2159,348.7728c4.6795,0,8.9152,1.8997,11.9755,4.96,3.1815,3.1724,4.9663,7.4826,4.9589,11.9755,0,4.672-1.8933,8.9099-4.9589,11.9701-3.1722,3.1815-7.4827,4.9657-11.9755,4.9568-4.491.0081-8.7996-1.7761-11.9701-4.9568-3.1808-3.1707-4.9656-7.479-4.9589-11.9701,0-4.6805,1.8933-8.9099,4.9589-11.9755,3.1712-3.1801,7.4791-4.9652,11.9701-4.96h0ZM145.0057,129.3637l8.0203,33.6693h-43.2928c-3.9738,0-7.1952,3.2214-7.1952,7.1952s3.2214,7.1952,7.1952,7.1952h100.7712c3.9729,0,7.1936,3.2207,7.1936,7.1936s-3.2207,7.1936-7.1936,7.1936h-50.6219l2.4341,10.2304h-9.0208c-3.9738,0-7.1952,3.2214-7.1952,7.1952s3.2214,7.1952,7.1952,7.1952h64.6784c3.9738.0484,7.1559,3.3091,7.1075,7.2829-.0476,3.9055-3.202,7.0599-7.1075,7.1075h-48.8075l2.528,10.6197h-57.4848c-3.9712,0-7.1904,3.2203-7.1904,7.1936s3.2203,7.1936,7.1904,7.1936h113.7248c3.9738.0481,7.1562,3.3084,7.1082,7.2822-.0472,3.906-3.2022,7.0609-7.1082,7.1082h-49.3802l2.6699,11.2192c-6.3669.7413-12.0949,3.6533-16.4149,7.9669-5.0325,5.0379-8.1557,11.9872-8.1557,19.6373s3.1243,14.6027,8.1557,19.6352c5.0379,5.0411,11.9872,8.1621,19.6437,8.1621h2.5835c-3.7221,1.5774-7.1056,3.8568-9.9659,6.7136-5.8861,5.8685-9.1892,13.8418-9.1776,22.1536,0,8.6475,3.5051,16.4757,9.1776,22.1451,5.6693,5.6693,13.5029,9.1744,22.1451,9.1744,8.6475,0,16.4843-3.5051,22.1536-9.1744,5.6693-5.6693,9.1744-13.4976,9.1744-22.1451.0113-8.3111-3.2904-16.2839-9.1744-22.1536-2.8615-2.8568-6.2461-5.1361-9.9691-6.7136h137.8997c-3.7203,1.5773-7.1018,3.8567-9.9595,6.7136-5.6693,5.6693-9.1776,13.5029-9.1776,22.1536s3.5083,16.4757,9.1776,22.1451c5.6693,5.6693,13.4965,9.1744,22.1451,9.1744s16.4693-3.5051,22.1419-9.1744c5.6725-5.6693,9.1915-13.4976,9.1915-22.1451s-3.52-16.4843-9.1915-22.1536c-2.8512-2.8593-6.2294-5.1392-9.9477-6.7136h10.2677c3.9563,0,7.1851-3.2203,7.1851-7.1968s-3.2288-7.1968-7.1851-7.1968h-199.4944c-3.68,0-7.0304-1.5093-9.4688-3.9381-2.4288-2.4352-3.9445-5.7803-3.9445-9.4656,0-3.68,1.5157-7.0251,3.9445-9.4592,2.4373-2.4288,5.7888-3.9445,9.4688-3.9445h175.072c5.8261,0,11.2224-1.9488,15.5211-5.3291,4.2763-3.3653,7.4464-8.1472,8.8427-13.8368l25.3365-103.9563c.2353-.739.353-1.5104.3488-2.2859,0-3.9733-3.2-7.1968-7.1851-7.1968h-234.5749l-10.0736-42.2912c-.6792-3.3563-3.6295-5.7691-7.0539-5.7685h-30.1205c-3.9735-.0012-7.1956,3.219-7.1968,7.1925v.0043c0,3.9729,3.2207,7.1936,7.1936,7.1936h24.4427v-.0011Z" fill="#fff"/>
			</svg>
		),
    });
})();