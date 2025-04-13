(() => {
  // resources/js/front/profile.js
  document.addEventListener("DOMContentLoaded", () => {
    const profileForm = document.getElementById("digicommerce-profile-form");
    const passwordForm = document.getElementById("digicommerce-password-form");
    const profileMessage = document.getElementById("profile-message");
    const passwordMessage = document.getElementById("password-message");
    const togglePasswordBtns = document.querySelectorAll(".pass__icon");
    if (togglePasswordBtns) {
      togglePasswordBtns.forEach((btn) => {
        const input = btn.parentElement.querySelector("input");
        const showIcon = btn.querySelector("[data-show]");
        const hideIcon = btn.querySelector("[data-hide]");
        btn.addEventListener("click", (e) => {
          e.preventDefault();
          if (input.type === "password") {
            input.type = "text";
            showIcon.classList.remove("hidden");
            hideIcon.classList.add("hidden");
          } else {
            input.type = "password";
            showIcon.classList.add("hidden");
            hideIcon.classList.remove("hidden");
          }
        });
      });
    }
    const passwordInput = document.getElementById("new_password");
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
    if (passwordInput) {
      passwordInput.addEventListener("input", () => {
        checkPasswordStrength(passwordInput.value);
      });
    }
    const showMessage = (element, message, isError = true) => {
      element.textContent = message;
      element.classList.remove("hidden", "bg-green-500", "bg-red-500");
      element.classList.add(isError ? "bg-red-500" : "bg-green-500", "text-white");
      const elementPosition = element.getBoundingClientRect().top;
      const offsetPosition = elementPosition + window.pageYOffset - 100;
      window.scrollTo({
        top: offsetPosition,
        behavior: "smooth"
      });
    };
    const handleSubmit = async (form, action, messageElement) => {
      const submitBtn = form.querySelector('button[type="submit"]');
      const submitText = submitBtn.querySelector(".text");
      const originalText = submitBtn.textContent;
      submitBtn.disabled = true;
      submitText.textContent = action === "update_profile" ? digicommerceVars.i18n.saving : digicommerceVars.i18n.updating;
      try {
        const formData = new FormData(form);
        formData.append("action", `digicommerce_${action}`);
        formData.append("nonce", form.querySelector(`#digicommerce_${action}_nonce`).value);
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
          showMessage(messageElement, data.data.message, false);
          if (action === "change_password") {
            form.querySelector("#current_password").value = "";
            form.querySelector("#new_password").value = "";
            const strengthContainer = document.querySelector(".password-strength");
            strengthContainer.classList.remove("weak", "medium", "strong");
            strengthText.textContent = "";
            strengthMeter.style.width = "0%";
            Object.values(requirements).forEach(({ element }) => {
              if (element) {
                const icon = element.querySelector("svg");
                element.classList.remove("text-green-500");
                element.classList.add("text-gray-500");
                icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />';
              }
            });
          }
          if (data.data.redirect_url) {
            setTimeout(() => {
              window.location.href = data.data.redirect_url;
            }, 1500);
          }
        } else {
          throw new Error(data.data.message || digicommerceVars.i18n.unknown_error);
        }
      } catch (error) {
        console.error("Form submission error:", error);
        showMessage(messageElement, error.message);
      } finally {
        submitBtn.disabled = false;
        submitText.textContent = originalText;
      }
    };
    if (profileForm) {
      profileForm?.addEventListener("submit", async (e) => {
        e.preventDefault();
        await handleSubmit(profileForm, "update_profile", profileMessage);
      });
    }
    if (passwordForm) {
      passwordForm?.addEventListener("submit", async (e) => {
        e.preventDefault();
        const newPassword = passwordForm.querySelector("#new_password").value;
        if (!checkPasswordStrength(newPassword)) {
          showMessage(passwordMessage, digicommerceVars.i18n.password_requirements);
          return;
        }
        await handleSubmit(passwordForm, "change_password", passwordMessage);
      });
    }
    const countrySelect = document.getElementById("billing_country");
    if (countrySelect) {
      const choices = new Choices(countrySelect, {
        searchEnabled: true,
        searchPlaceholderValue: countrySelect.dataset.placeholder,
        searchResultLimit: -1
      });
    }
    const inputs = document.querySelectorAll(".digi__form .field input");
    if (inputs) {
      inputs.forEach((input) => {
        const handleInputState = () => {
          if (input.value !== "") {
            input.classList.add("focused");
          } else {
            input.classList.remove("focused");
          }
        };
        handleInputState();
        input.addEventListener("blur", handleInputState);
        input.addEventListener("input", handleInputState);
      });
    }
  });
})();
