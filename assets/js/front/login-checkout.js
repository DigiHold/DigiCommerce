(() => {
  // resources/js/front/login-checkout.js
  document.addEventListener("DOMContentLoaded", function() {
    const loginForm = document.getElementById("digicommerce-login-checkout");
    const loginCheckoutLink = document.querySelector(".login-checkout-link");
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
    const showMessage = (element, message, isError = true) => {
      element.textContent = message;
      element.classList.remove("hidden");
      element.classList.remove("bg-green-500", "bg-red-500");
      element.classList.add(isError ? "bg-red-500" : "bg-green-500");
    };
    if (loginForm && loginCheckoutLink) {
      loginForm.style.cssText = `
            transition: max-height 0.3s ease-in-out, opacity 0.3s ease-in-out;
            max-height: 0;
            opacity: 0;
            overflow: hidden;
        `;
      let isFormVisible = false;
      loginCheckoutLink.addEventListener("click", function(e) {
        e.preventDefault();
        if (isFormVisible) {
          const height = loginForm.scrollHeight;
          loginForm.style.maxHeight = `${height}px`;
          loginForm.style.opacity = "1";
          requestAnimationFrame(() => {
            loginForm.style.maxHeight = "0";
            loginForm.style.opacity = "0";
          });
          setTimeout(() => {
            loginForm.classList.remove("flex");
            loginForm.classList.add("hidden");
          }, 300);
        } else {
          loginForm.classList.remove("hidden");
          loginForm.classList.add("flex");
          const height = loginForm.scrollHeight;
          loginForm.style.maxHeight = `${height}px`;
          loginForm.style.opacity = "1";
          setTimeout(() => {
            loginForm.style.maxHeight = "none";
          }, 300);
        }
        isFormVisible = !isFormVisible;
      });
    }
    if (loginForm) {
      loginForm.addEventListener("submit", async (e) => {
        e.preventDefault();
        const submitBtn = loginForm.querySelector('button[type="submit"]');
        const submitText = submitBtn.querySelector(".text");
        const originalText = submitText.textContent;
        const loginMessage = document.getElementById("login-message");
        try {
          submitBtn.disabled = true;
          submitText.textContent = digicommerceVars.i18n.logging_in;
          const formData = new FormData(loginForm);
          formData.append("action", "digicommerce_login_checkout");
          const nonceInput = loginForm.querySelector(`input[name="digicommerce_login_checkout_nonce"]`);
          if (!nonceInput) {
            console.error("Nonce field not found: digicommerce_login_checkout_nonce");
            throw new Error("Security token missing");
          }
          formData.append("nonce", nonceInput.value);
          if (digicommerceVars.recaptcha_site_key) {
            try {
              const token = await grecaptcha.execute(digicommerceVars.recaptcha_site_key, {
                action: "login"
              });
              formData.append("recaptcha_token", token);
            } catch (error) {
              console.error("reCAPTCHA error:", error);
            }
          }
          const response = await fetch(digicommerceVars.ajaxurl, {
            method: "POST",
            credentials: "same-origin",
            body: formData
          });
          const data = await response.json();
          if (data.success) {
            showMessage(loginMessage, data.data.message, false);
            if (data.data.redirect_url) {
              setTimeout(() => {
                window.location.href = data.data.redirect_url;
              }, 1e3);
            }
          } else {
            throw new Error(data.data ? data.data.message : digicommerceVars.i18n.error);
          }
        } catch (error) {
          showMessage(loginMessage, error.message);
          this.querySelector("#password").value = "";
        } finally {
          submitBtn.disabled = false;
          submitText.textContent = originalText;
        }
      });
    }
  });
})();
