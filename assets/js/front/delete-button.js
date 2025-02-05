(() => {
  // resources/js/front/delete-button.js
  document.addEventListener("DOMContentLoaded", function() {
    const removeButtons = document.querySelectorAll(".remove-item-btn");
    if (removeButtons) {
      removeButtons.forEach((button) => {
        button.addEventListener("click", async function(e) {
          e.preventDefault();
          const index = this.dataset.index;
          try {
            const response = await fetch(digicommerceVars.ajaxurl, {
              method: "POST",
              body: new URLSearchParams({
                action: "digicommerce_remove_cart_item",
                index,
                nonce: digicommerceVars.order_nonce
              }),
              headers: {
                "Content-Type": "application/x-www-form-urlencoded"
              }
            });
            const result = await response.json();
            if (result.success && result.data) {
              const parentElement = this.closest(".cart-item");
              parentElement.remove();
              const remainingButtons = document.querySelectorAll(".remove-item-btn");
              remainingButtons.forEach((button2, newIndex) => {
                button2.dataset.index = newIndex;
              });
              if (!document.querySelectorAll(".cart-item").length) {
                const checkoutContainer = document.querySelector(".digicommerce-checkout");
                if (checkoutContainer && digicommerceVars.empty_cart_template) {
                  checkoutContainer.innerHTML = digicommerceVars.empty_cart_template;
                }
              } else {
                const subtotalEl = document.getElementById("cart-subtotal");
                if (subtotalEl) {
                  subtotalEl.innerHTML = result.data.formatted_prices.subtotal;
                }
                if (window.vatCalculator) {
                  window.vatCalculator.updateFromSubtotal();
                }
              }
              if (digicommerceVars.proVersion && digicommerceVars.enableSideCart) {
                const cartUpdateEvent = new CustomEvent("digicommerce_cart_updated");
                document.dispatchEvent(cartUpdateEvent);
              }
            } else {
              console.error("Remove cart item failed:", result);
              alert(result.data?.message || "Failed to remove item.");
            }
          } catch (error) {
            console.error("Cart removal error:", error);
            alert("An error occurred. Please try again.");
          }
        });
      });
    }
  });
})();
