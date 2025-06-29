document.addEventListener('DOMContentLoaded', () => {
    // Get form elements
    const variationInputs = document.querySelectorAll('input[name="price_variation"]');
    const variationNameInput = document.getElementById('variation-name');
    const variationPriceInput = document.getElementById('variation-price');
    const submitButton = document.querySelector('#add-to-cart-button');

    // Exit if no add to cart button
    if (!submitButton) return;

    if (variationInputs.length) {
        // Helper function to update the button state and form values
        const updateButtonState = (selectedInput) => {
            const selectedPrice = selectedInput.value;
            const selectedName = selectedInput.dataset.name;
            const formattedPrice = selectedInput.dataset.formattedPrice;

            // Update hidden form values
            variationPriceInput.value = selectedPrice;
            variationNameInput.value = selectedName || '';

            // Update button state and text
            submitButton.innerHTML = `${digicommerceVars.i18n.purchase_for} <span class="button-price">${formattedPrice}</span>`;
            submitButton.classList.remove('button-disabled');
            submitButton.disabled = false;
        };

        // Check if a default radio button is selected
        const defaultCheckedInput = Array.from(variationInputs).find(input => input.checked);
        if (defaultCheckedInput) {
            // Initialize the button state for the default selection
            updateButtonState(defaultCheckedInput);
        } else {
            // Fallback: Disable the button and set initial state
            submitButton.innerHTML = digicommerceVars.i18n.select_option;
            submitButton.classList.add('button-disabled');
            submitButton.disabled = true;
        }

        // Add change event listeners to update the state when a user selects another option
        variationInputs.forEach(input => {
            input.addEventListener('change', (e) => updateButtonState(e.target));
        });
    }

    // Add to cart button
    const form = document.querySelector('.digicommerce-add-to-cart');
    if (form) {
        form.addEventListener('submit', async function (e) {
            e.preventDefault();

            const formData = new FormData(form);

            try {
                const response = await fetch(digicommerceVars.ajaxurl, {
                    method: 'POST',
                    body: new URLSearchParams({
                        action: 'digicommerce_add_to_cart',
                        product_id: formData.get('product_id'),
                        product_price: formData.get('product_price') || '',
                        variation_name: formData.get('variation_name') || '',
                        variation_price: formData.get('variation_price') || '',
                        nonce: formData.get('cart_nonce'),
                    }),
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                });

                const result = await response.json();

				// Helper function to determine if we should redirect to checkout
				const shouldRedirect = () => {
					// Case 1: No pro version - always redirect
					if (!digicommerceVars.proVersion) {
						return true;
					}

					// Case 2: Pro version but side cart disabled
					if (digicommerceVars.proVersion && !digicommerceVars.enableSideCart) {
						return true;
					}

					// Case 3: Pro version and side cart enabled but side_cart_trigger not active
					if (digicommerceVars.proVersion && digicommerceVars.enableSideCart && !digicommerceVars.autoOpen) {
						return true;
					}

					// If none of the above, don't redirect (show side cart)
					return false;
				};

                if (result.success) {
					// Update side cart if it exists
					if ( digicommerceVars.proVersion && digicommerceVars.enableSideCart ) {
						// After successful add to cart
						const cartUpdateEvent = new CustomEvent('digicommerce_cart_updated', {
							detail: {
								source: 'add_to_cart'
							}
						});
						document.dispatchEvent(cartUpdateEvent);
					}

					// Handle redirect logic
					if (shouldRedirect()) {
						if (result.data.redirect) {
							window.location.href = result.data.redirect;
						} else {
							alert('Product added to cart successfully!');
						}
					}
                } else {
                    alert(result.data.message || 'Failed to add product to cart.');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            }
        });
    }

    // Product share
    const shareLinks = document.querySelectorAll('.share-link');

    shareLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            
            const url = link.href;
            const screenWidth = window.innerWidth;
            const screenHeight = window.innerHeight;
            const width = Math.min(600, screenWidth * 0.9);
            const height = Math.min(400, screenHeight * 0.8);
            const left = (screenWidth / 2) - (width / 2);
            const top = (screenHeight / 2) - (height / 2);
            
            window.open(url, 'shareWindow', `width=${width},height=${height},top=${top},left=${left},resizable=yes,scrollbars=yes`);
        });
    });
});