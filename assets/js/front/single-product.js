(() => {
  // resources/js/front/single-product.js
  document.addEventListener("DOMContentLoaded", () => {
    const variationInputs = document.querySelectorAll('input[name="price_variation"]');
    const variationNameInput = document.getElementById("variation-name");
    const variationPriceInput = document.getElementById("variation-price");
    const submitButton = document.querySelector("#add-to-cart-button");
    if (!submitButton)
      return;
    if (variationInputs.length) {
      const updateButtonState = (selectedInput) => {
        const selectedPrice = selectedInput.value;
        const selectedName = selectedInput.dataset.name;
        const formattedPrice = selectedInput.dataset.formattedPrice;
        variationPriceInput.value = selectedPrice;
        variationNameInput.value = selectedName || "";
        submitButton.innerHTML = `${digicommerceVars.i18n.purchase_for} <span class="button-price">${formattedPrice}</span>`;
        submitButton.classList.remove("button-disabled");
        submitButton.disabled = false;
      };
      const defaultCheckedInput = Array.from(variationInputs).find((input) => input.checked);
      if (defaultCheckedInput) {
        updateButtonState(defaultCheckedInput);
      } else {
        submitButton.innerHTML = digicommerceVars.i18n.select_option;
        submitButton.classList.add("button-disabled");
        submitButton.disabled = true;
      }
      variationInputs.forEach((input) => {
        input.addEventListener("change", (e) => updateButtonState(e.target));
      });
    }
    const form = document.querySelector(".digicommerce-add-to-cart");
    if (form) {
      form.addEventListener("submit", async function(e) {
        e.preventDefault();
        const formData = new FormData(form);
        try {
          const response = await fetch(digicommerceVars.ajaxurl, {
            method: "POST",
            body: new URLSearchParams({
              action: "digicommerce_add_to_cart",
              product_id: formData.get("product_id"),
              product_price: formData.get("product_price") || "",
              variation_name: formData.get("variation_name") || "",
              variation_price: formData.get("variation_price") || "",
              nonce: formData.get("cart_nonce")
            }),
            headers: {
              "Content-Type": "application/x-www-form-urlencoded"
            }
          });
          const result = await response.json();
          const shouldRedirect = () => {
            if (!digicommerceVars.proVersion) {
              return true;
            }
            if (!digicommerceVars.enableSideCart) {
              return true;
            }
            if (!digicommerceVars.autoOpen) {
              return true;
            }
            return false;
          };
          if (result.success) {
            if (digicommerceVars.proVersion && digicommerceVars.enableSideCart) {
              const cartUpdateEvent = new CustomEvent("digicommerce_cart_updated", {
                detail: {
                  source: "add_to_cart"
                }
              });
              document.dispatchEvent(cartUpdateEvent);
            }
            if (shouldRedirect()) {
              if (result.data.redirect) {
                window.location.href = result.data.redirect;
              } else {
                alert("Product added to cart successfully!");
              }
            }
          } else {
            alert(result.data.message || "Failed to add product to cart.");
          }
        } catch (error) {
          console.error("Error:", error);
          alert("An error occurred. Please try again.");
        }
      });
    }
    const shareLinks = document.querySelectorAll(".share-link");
    shareLinks.forEach((link) => {
      link.addEventListener("click", (e) => {
        e.preventDefault();
        const url = link.href;
        const screenWidth = window.innerWidth;
        const screenHeight = window.innerHeight;
        const width = Math.min(600, screenWidth * 0.9);
        const height = Math.min(400, screenHeight * 0.8);
        const left = screenWidth / 2 - width / 2;
        const top = screenHeight / 2 - height / 2;
        window.open(url, "shareWindow", `width=${width},height=${height},top=${top},left=${left},resizable=yes,scrollbars=yes`);
      });
    });
  });
})();
