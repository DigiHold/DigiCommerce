(() => {
  // resources/js/admin/wizard.js
  document.addEventListener("DOMContentLoaded", function() {
    const wizard = document.querySelector(".digicommerce-setup-wizard");
    if (wizard) {
      document.body.style.overflow = "hidden";
    }
    const steps = document.querySelectorAll(".digicommerce-setup-content");
    const continueButtons = document.querySelectorAll(".continue");
    if (steps.length) {
      steps.forEach((step, index) => {
        if (index === 0) {
          step.classList.remove("hidden");
        } else {
          step.classList.add("hidden");
        }
      });
    }
    const businessForm = document.querySelector(".business-form");
    if (businessForm) {
      businessForm.addEventListener("submit", function(e) {
        e.preventDefault();
        const currentStep = this.closest(".digicommerce-setup-content");
        const currentIndex = Array.from(steps).indexOf(currentStep);
        const submitButton = this.querySelector('button[type="submit"]');
        const originalText = submitButton.textContent;
        submitButton.textContent = digicommerceSetup.i18n.saving;
        submitButton.disabled = true;
        const formData = new FormData(this);
        formData.append("action", "digicommerce_setup_wizard_save");
        formData.append("nonce", digicommerceSetup.nonce);
        formData.append("subscribe_newsletter", document.getElementById("subscribe_newsletter").checked);
        fetch(digicommerceSetup.ajaxurl, {
          method: "POST",
          body: formData,
          credentials: "same-origin",
          headers: {
            "Accept": "application/json"
          }
        }).then((response) => {
          if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
          }
          return response.text();
        }).then((data) => {
          try {
            const jsonData = JSON.parse(data);
            if (jsonData.success) {
              currentStep.classList.add("hidden");
              if (steps[currentIndex + 1]) {
                steps[currentIndex + 1].classList.remove("hidden");
              }
            } else {
              console.error("Server returned error:", jsonData);
              alert(digicommerceSetup.i18n.error);
            }
          } catch (e2) {
            console.error("JSON parse error:", e2);
            alert(digicommerceSetup.i18n.error);
          }
          submitButton.textContent = originalText;
          submitButton.disabled = false;
        }).catch((error) => {
          console.error("Fetch error:", error);
          alert(digicommerceSetup.i18n.error);
          submitButton.textContent = originalText;
          submitButton.disabled = false;
        });
      });
    }
    continueButtons.forEach((button) => {
      button.addEventListener("click", function(e) {
        e.preventDefault();
        const currentStep = this.closest(".digicommerce-setup-content");
        const currentIndex = Array.from(steps).indexOf(currentStep);
        currentStep.classList.add("hidden");
        if (steps[currentIndex + 1]) {
          steps[currentIndex + 1].classList.remove("hidden");
        }
      });
    });
    const searchSelects = document.querySelectorAll(".digicommerce__search");
    searchSelects.forEach((select) => {
      new Choices(select, {
        searchEnabled: true,
        searchPlaceholderValue: select.dataset.placeholder || digicommerceSetup.i18n.select,
        searchResultLimit: -1
      });
    });
    const skipSetupButton = document.querySelector(".skip");
    if (skipSetupButton) {
      skipSetupButton.addEventListener("click", async (e) => {
        e.preventDefault();
        try {
          const response = await fetch(digicommerceSetup.ajaxurl, {
            method: "POST",
            headers: {
              "Content-Type": "application/x-www-form-urlencoded"
            },
            body: new URLSearchParams({
              action: "digicommerce_skip_setup",
              nonce: digicommerceSetup.nonce
            })
          });
          const data = await response.json();
          if (data.success && data.data.redirect) {
            window.location.href = data.data.redirect;
          } else {
            throw new Error(digicommerceSetup.i18n.error);
          }
        } catch (error) {
          alert(error.message || digicommerceSetup.i18n.error);
        }
      });
    }
  });
})();
