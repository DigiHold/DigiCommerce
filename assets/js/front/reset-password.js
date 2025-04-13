(() => {
  // resources/js/front/reset-password.js
  document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("digicommerce-reset-password-form");
    if (!form)
      return;
    const messageEl = document.getElementById("reset-password-message");
    const passwordInput = document.getElementById("password");
    const strengthMeter = document.querySelector(".password-strength-meter-bar");
    const strengthText = document.querySelector(".password-strength-text");
    const requirements = {
      length: {
        regex: /.{8,}/,
        element: document.querySelector('[data-requirement="length"]')
      },
      uppercase: {
        regex: /[A-Z]/,
        element: document.querySelector('[data-requirement="uppercase"]')
      },
      lowercase: {
        regex: /[a-z]/,
        element: document.querySelector('[data-requirement="lowercase"]')
      },
      number: {
        regex: /[0-9]/,
        element: document.querySelector('[data-requirement="number"]')
      },
      special: {
        regex: /[^A-Za-z0-9]/,
        element: document.querySelector('[data-requirement="special"]')
      }
    };
    const checkPasswordStrength = (password) => {
      let valid = true;
      let validRequirements = 0;
      let totalRequirements = Object.keys(requirements).length;
      Object.entries(requirements).forEach(([key, { regex, element }]) => {
        const isValid = regex.test(password);
        valid = valid && isValid;
        if (isValid)
          validRequirements++;
        if (element) {
          const icon = element.querySelector("svg");
          if (isValid) {
            element.classList.remove("text-gray-500");
            element.classList.add("text-green-500");
            icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />';
          } else {
            element.classList.remove("text-green-500");
            element.classList.add("text-gray-500");
            icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />';
          }
        }
      });
      const strengthContainer = document.querySelector(".password-strength");
      strengthContainer.classList.remove("weak", "medium", "strong");
      if (validRequirements < 2) {
        strengthContainer.classList.add("weak");
        strengthText.textContent = "Faible";
        strengthMeter.style.width = "33%";
      } else if (!valid) {
        strengthContainer.classList.add("medium");
        strengthText.textContent = "Moyen";
        strengthMeter.style.width = "66%";
      } else {
        strengthContainer.classList.add("strong");
        strengthText.textContent = "Fort";
        strengthMeter.style.width = "100%";
      }
      return valid;
    };
    passwordInput.addEventListener("input", () => {
      checkPasswordStrength(passwordInput.value);
    });
    const showMessage = (message, isError = true) => {
      messageEl.textContent = message;
      messageEl.classList.remove("hidden", "bg-green-500", "bg-red-500");
      messageEl.classList.add(isError ? "bg-red-500" : "bg-green-500");
    };
    form.addEventListener("submit", async (e) => {
      e.preventDefault();
      if (!checkPasswordStrength(passwordInput.value)) {
        showMessage(digicommerceVars.i18n.password_requirements);
        return;
      }
      const submitBtn = form.querySelector(".digi__button");
      const submitText = submitBtn.querySelector(".text");
      const originalText = submitText.textContent;
      try {
        submitBtn.disabled = true;
        submitText.textContent = digicommerceVars.i18n.resetting;
        const formData = new FormData(form);
        formData.append("action", "digicommerce_reset_password");
        formData.append("nonce", form.querySelector("#digicommerce_reset_password_nonce").value);
        const response = await fetch(digicommerceVars.ajaxurl, {
          method: "POST",
          credentials: "same-origin",
          body: formData
        });
        if (!response.ok) {
          throw new Error(digicommerceVars.i18n.server_error);
        }
        const data = await response.json();
        if (data.success) {
          showMessage(data.data.message, false);
          if (data.data.redirect_url) {
            setTimeout(() => {
              window.location.href = data.data.redirect_url;
            }, 2e3);
          }
        } else {
          throw new Error(data.data.message || digicommerceVars.i18n.unknown_error);
        }
      } catch (error) {
        showMessage(error.message);
        submitBtn.disabled = false;
        submitText.textContent = originalText;
      }
    });
  });
})();
