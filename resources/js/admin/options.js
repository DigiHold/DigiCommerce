document.addEventListener('DOMContentLoaded', function() {
    // Tab handling
    const tabs = document.querySelectorAll('.digicommerce-tab');
    const tabContents = document.querySelectorAll('.digicommerce-tab-content');
    const activeTabInput = document.querySelector('input[name="active_tab"]');

    function updateTab(tabId) {
        // Update URL without reloading page
        const newUrl = new URL(window.location);
        newUrl.searchParams.set('tab', tabId);
        window.history.pushState({}, '', newUrl);
        
        // Remove active class from all tabs and contents
        tabs.forEach(t => t.classList.remove('active'));
        tabContents.forEach(content => content.classList.remove('active'));
        
        // Add active class to clicked tab and corresponding content
        const selectedTab = document.querySelector(`[data-tab="${tabId}"]`);
        if (selectedTab) {
            selectedTab.classList.add('active');
        }
        
        const targetContent = document.querySelector(`#${tabId}`);
        if (targetContent) {
            targetContent.classList.add('active');
        }

        // Update hidden input field
        if (activeTabInput) {
            activeTabInput.value = tabId;
        }
    }

    tabs.forEach(tab => {
        tab.addEventListener('click', (e) => {
            e.preventDefault();
            const tabId = tab.getAttribute('data-tab');
            updateTab(tabId);
        });
    });

    // Stripe mode toggle
    const stripeModeInputs = document.querySelectorAll('input[name="stripe_mode"]');
    const testModeKeys = document.querySelector('.test-mode-keys');
    const liveModeKeys = document.querySelector('.live-mode-keys');

    stripeModeInputs.forEach(input => {
        input.addEventListener('change', function() {
            if (this.value === 'test') {
                testModeKeys.style.display = 'flex';
                liveModeKeys.style.display = 'none';
            } else {
                testModeKeys.style.display = 'none';
                liveModeKeys.style.display = 'flex';
            }
        });
    });

    // Handle popstate event (browser back/forward)
    window.addEventListener('popstate', function(e) {
        const urlParams = new URLSearchParams(window.location.search);
        const currentTab = urlParams.get('tab') || 'general';
        updateTab(currentTab);
    });

    // Check initial URL for tab
    const urlParams = new URLSearchParams(window.location.search);
    const initialTab = urlParams.get('tab') || 'general';
    updateTab(initialTab);

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

    // Media Uploader
    let frame;
    const uploadButtons = document.querySelectorAll('.upload-logo');

    uploadButtons.forEach(uploadButton => {
        const container = uploadButton.closest('.image-wrap');
        const logoInput = container.querySelector('input[type="hidden"]');
        const previewContainer = container.querySelector('[class*="image-preview"]');

        if (uploadButton) {
            uploadButton.addEventListener('click', function(e) {
                e.preventDefault();

                if (frame) {
                    frame.open();
                    return;
                }

                frame = wp.media({
                    title: digiCommerceAdmin.mediaUploader.title,
                    button: {
                        text: digiCommerceAdmin.mediaUploader.buttonText
                    },
                    multiple: false
                });

                frame.on('select', function() {
                    const attachment = frame.state().get('selection').first().toJSON();
                    
                    if (logoInput) {
                        logoInput.value = attachment.id;
                    }

                    if (previewContainer) {
                        const img = document.createElement('img');
                        img.src = attachment.url;
                        img.className = 'max-w-64';
                        
                        // Only show preview once image is loaded
                        img.onload = function() {
                            previewContainer.classList.remove('hidden');
                            previewContainer.classList.add('flex');
                            previewContainer.innerHTML = '';
                            previewContainer.appendChild(img);
                        };

                        // Handle image load error
                        img.onerror = function() {
                            console.error('Error loading image preview');
                        };
                    }

                    const buttonContainer = uploadButton.parentElement;
                    if (!buttonContainer.querySelector('.remove-logo')) {
                        const removeButton = document.createElement('button');
                        removeButton.type = 'button';
                        removeButton.className = 'remove-logo flex items-center justify-center gap-2 bg-red-600 hover:bg-red-400 text-white hover:text-white py-2 px-4 rounded default-transition';
                        removeButton.innerHTML = `<span class="text">${digiCommerceAdmin.mediaUploader.removeText}</span>`;
                        uploadButton.insertAdjacentElement('afterend', removeButton);
                        
                        // Add remove button event listener
                        removeButton.addEventListener('click', function() {
                            if (logoInput) {
                                logoInput.value = '';
                            }
                            if (previewContainer) {
                                previewContainer.innerHTML = '';
                                previewContainer.classList.add('hidden');
                                previewContainer.classList.remove('flex');
                            }
                            removeButton.remove();
                        });
                    }
                });

                frame.open();
            });
        }
    });

    // Add event listeners to existing remove buttons
    document.querySelectorAll('.remove-logo').forEach(removeButton => {
        removeButton.addEventListener('click', function() {
            const container = removeButton.closest('.image-wrap');
            const logoInput = container.querySelector('input[type="hidden"]');
            const previewContainer = container.querySelector('[class*="image-preview"]');
            
            if (logoInput) {
                logoInput.value = '';
            }
            if (previewContainer) {
                previewContainer.innerHTML = '';
                previewContainer.classList.add('hidden');
                previewContainer.classList.remove('flex');
            }
            removeButton.remove();
        });
    });

    // Social medias
    const socialLinksContainer = document.querySelector('.social-links-container');
    const addButton = document.querySelector('.add-social-link');

    // Get existing social links from PHP if any
    let socialLinks = window.digicommerce?.socialLinks || [];

    // Initialize existing links if any
    if (socialLinks.length > 0) {
        socialLinks.forEach(link => addSocialLinkRow(link));
    }

    function createSocialRow(existingData = { platform: '', url: '' }) {
        const row = document.createElement('div');
        row.className = 'social-link-row flex flex-col mdl:flex-row mdl:items-center gap-4 border rounded p-2.5 border-solid border-[#ddd] cursor-move';
        row.draggable = true;

        const platforms = [
            { value: 'facebook', label: 'Facebook' },
            { value: 'twitter', label: 'X' },
            { value: 'instagram', label: 'Instagram' },
            { value: 'linkedin', label: 'LinkedIn' },
            { value: 'youtube', label: 'YouTube' },
            { value: 'pinterest', label: 'Pinterest' },
            { value: 'tiktok', label: 'TikTok' }
        ];

        const index = document.querySelectorAll('.social-link-row').length;

        row.innerHTML = `
            <div class="flex gap-2 w-full">
                <div class="drag-handle flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="7" r="1"/>
                        <circle cx="12" cy="12" r="1"/>
                        <circle cx="12" cy="17" r="1"/>
                        <circle cx="7" cy="7" r="1"/>
                        <circle cx="7" cy="12" r="1"/>
                        <circle cx="7" cy="17" r="1"/>
                    </svg>
                </div>
                <select name="social_links[${index}][platform]" 
                        id="social_platform_${index}"
                        class="regular-text"
                        style="max-width:100%">
                    ${platforms.map(platform => `
                        <option value="${platform.value}" 
                            ${existingData.platform === platform.value ? 'selected' : ''}>
                            ${platform.label}
                        </option>
                    `).join('')}
                </select>
            </div>
            <div class="flex flex-col gap-2 w-full">
                <input type="url" 
                        id="social_url_${index}" 
                        name="social_links[${index}][url]" 
                        value="${existingData.url}"
                        class="regular-text"
                        placeholder="https://${digiCommerceAdmin.socialMedia.placeholder}.com/">
            </div>
            <button type="button" class="remove-social-link flex items-center justify-center gap-2 bg-red-600 hover:bg-red-400 text-white hover:text-white py-2 px-4 rounded default-transition">
                ${digiCommerceAdmin.socialMedia.remove}
            </button>
        `;

        // Add remove button event listener
        const removeButton = row.querySelector('.remove-social-link');
        removeButton.addEventListener('click', () => {
            row.remove();
            updateIndexes();
        });

        // Add drag and drop event listeners to the new row
        row.addEventListener('dragstart', handleDragStart);
        row.addEventListener('dragend', handleDragEnd);
        row.addEventListener('dragover', handleDragOver);
        row.addEventListener('dragenter', handleDragEnter);
        row.addEventListener('dragleave', handleDragLeave); 
        row.addEventListener('drop', handleDrop);

        return row;
    }

    function addSocialLinkRow(existingData = { platform: '', url: '' }) {
        const newRow = createSocialRow(existingData);
        socialLinksContainer.appendChild(newRow);

        // Add remove button event listener to the newly added row
        const removeButton = newRow.querySelector('.remove-social-link');
        removeButton.addEventListener('click', () => {
            newRow.remove();
            updateIndexes();
        });
    }

    function updateIndexes() {
        const rows = document.querySelectorAll('.social-link-row');
        rows.forEach((row, index) => {
            const select = row.querySelector('select');
            const input = row.querySelector('input');
            const selectLabel = row.querySelector('label[for^="social_platform_"]');
            const inputLabel = row.querySelector('label[for^="social_url_"]');

            select.name = `social_links[${index}][platform]`;
            select.id = `social_platform_${index}`;
            input.name = `social_links[${index}][url]`;
            input.id = `social_url_${index}`;
            selectLabel.setAttribute('for', `social_platform_${index}`);
            inputLabel.setAttribute('for', `social_url_${index}`);
        });
    }

    // Drag and drop functionality
    let draggedItem = null;

    function handleDragStart(e) {
        draggedItem = this;
        this.style.opacity = '0.4';
        
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/html', this.innerHTML);
    }

    function handleDragEnd(e) {
        this.style.opacity = '1';
        
        const rows = document.querySelectorAll('.social-link-row');
        rows.forEach(row => {
            row.classList.remove('drag-over');
        });
    }

    function handleDragOver(e) {
        if (e.preventDefault) {
            e.preventDefault();
        }
        e.dataTransfer.dropEffect = 'move';
        return false;
    }

    function handleDragEnter(e) {
        this.classList.add('drag-over');
    }

    function handleDragLeave(e) {
        this.classList.remove('drag-over');
    }

    function handleDrop(e) {
        if (e.stopPropagation) {
            e.stopPropagation();
        }
        
        if (draggedItem !== this) {
            const dropIndex = Array.from(socialLinksContainer.children).indexOf(this);
            const draggedIndex = Array.from(socialLinksContainer.children).indexOf(draggedItem);

            if (dropIndex > draggedIndex) {
                socialLinksContainer.insertBefore(draggedItem, this.nextSibling);
            } else {
                socialLinksContainer.insertBefore(draggedItem, this);
            }
        }
        
        this.classList.remove('drag-over');
        updateIndexes();
        
        return false;
    }

    // Add button click handler
    addButton.addEventListener('click', () => addSocialLinkRow());

    // Add remove button event listener to existing rows
    const existingRemoveButtons = document.querySelectorAll('.social-link-row .remove-social-link');
    existingRemoveButtons.forEach(removeButton => {
        removeButton.addEventListener('click', () => {
            const row = removeButton.closest('.social-link-row');
            row.remove();
            updateIndexes();
        });
    });

    // Add drag and drop event listeners to existing rows
    const existingRows = document.querySelectorAll('.social-link-row');
    existingRows.forEach(row => {
        row.addEventListener('dragstart', handleDragStart);
        row.addEventListener('dragend', handleDragEnd);
        row.addEventListener('dragover', handleDragOver);
        row.addEventListener('dragenter', handleDragEnter);
        row.addEventListener('dragleave', handleDragLeave);
        row.addEventListener('drop', handleDrop);
    });

	// Initialize color pickers
	const colorInputs = document.querySelectorAll('input[type="color"]');
	colorInputs.forEach(input => {
		const colorId = input.id;
		const defaultColor = digiCommerceAdmin.colors[colorId].default;
		
		// Initialize with default if no value
		if (!input.value) {
			input.value = defaultColor;
		}
	});
});