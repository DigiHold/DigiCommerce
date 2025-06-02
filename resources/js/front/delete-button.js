document.addEventListener('DOMContentLoaded', function () {
	// Listen for cart updates from DigiBlocks mini cart
    document.addEventListener('digicommerce_cart_updated', function(e) {
        // Only refresh if the event is coming from outside the checkout page
        if (e.detail && e.detail.source !== 'checkout_page') {
            // Simple approach: reload the page to refresh cart data
            setTimeout(() => {
                window.location.reload();
            }, 200);
        }
    });
	
    const removeButtons = document.querySelectorAll('.remove-item-btn');

    if (removeButtons) {
        removeButtons.forEach(button => {
            button.addEventListener('click', async function (e) {
                e.preventDefault();
                const index = this.dataset.index;

                try {
                    const response = await fetch(digicommerceVars.ajaxurl, {
                        method: 'POST',
                        body: new URLSearchParams({
                            action: 'digicommerce_remove_cart_item',
                            index: index,
                            nonce: digicommerceVars.order_nonce,
                        }),
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                    });

                    const result = await response.json();

                    if (result.success && result.data) {
						// Remove the cart item from DOM
						const parentElement = this.closest('.cart-item');
						parentElement.remove();
					
						// Re-index remaining buttons
						const remainingButtons = document.querySelectorAll('.remove-item-btn');
						remainingButtons.forEach((button, newIndex) => {
							button.dataset.index = newIndex;
						});
					
						// Check if cart is empty
						if (!document.querySelectorAll('.cart-item').length) {
							// Get the main checkout container
							const checkoutContainer = document.querySelector('.digicommerce-checkout');
							if (checkoutContainer && digicommerceVars.empty_cart_template) {
								// Replace entire checkout content with empty cart template
								checkoutContainer.innerHTML = digicommerceVars.empty_cart_template;
							}
						} else {
							// Update prices if items still exist
							const subtotalEl = document.getElementById('cart-subtotal');
							if (subtotalEl) {
								subtotalEl.innerHTML = result.data.formatted_prices.subtotal;
							}
					
							if (window.vatCalculator) {
								window.vatCalculator.updateFromSubtotal();
							}
						}
					
						// Dispatch cart updated event with detailed information
						const cartUpdateEvent = new CustomEvent('digicommerce_cart_updated', {
							detail: {
								source: 'checkout_page',
								action: 'remove',
								itemIndex: index,
								data: result.data
							}
						});
						document.dispatchEvent(cartUpdateEvent);
					
						// Dispatch specific remove event
						const removeEvent = new CustomEvent('digicommerce_removed_from_cart', {
							detail: {
								source: 'checkout_page',
								itemIndex: index,
								data: result.data
							}
						});
						document.dispatchEvent(removeEvent);
					
						// Update side cart if it exists
						if (digicommerceVars.proVersion && digicommerceVars.enableSideCart) {
							// This can stay as is since it's for DigiCommerce Pro's side cart
							const sideCartUpdateEvent = new CustomEvent('digicommerce_cart_updated');
							document.dispatchEvent(sideCartUpdateEvent);
						}
					} else {
                        console.error('Remove cart item failed:', result);
                        alert(result.data?.message || 'Failed to remove item.');
                    }
                } catch (error) {
                    console.error('Cart removal error:', error);
                    alert('An error occurred. Please try again.');
                }
            });
        });
    }
});