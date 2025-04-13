(() => {
  // resources/js/front/account.js
  document.addEventListener("DOMContentLoaded", () => {
    const loginForm = document.getElementById("digicommerce-login-form");
    const registerForm = document.getElementById("digicommerce-register-form");
    const lostPasswordForm = document.getElementById("digicommerce-lost-password-form");
    const showLoginBtn = document.getElementById("show-login");
    const showRegisterBtn = document.getElementById("show-register");
    const showLostPasswordBtn = document.getElementById("show-lost-password");
    const backToLoginBtn = document.getElementById("back-to-login");
    const loginMessage = document.getElementById("login-message");
    const registerMessage = document.getElementById("register-message");
    const lostPasswordMessage = document.getElementById("lost-password-message");
    const urlParams = new URLSearchParams(window.location.search);
    const action = urlParams.get("action");
    if (action === "lostpassword" && lostPasswordForm) {
      loginForm?.classList.add("hidden");
      registerForm?.classList.add("hidden");
      lostPasswordForm.classList.remove("hidden");
    } else if (action === "register" && registerForm) {
      loginForm?.classList.add("hidden");
      lostPasswordForm?.classList.add("hidden");
      registerForm.classList.remove("hidden");
    }
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
    if (showRegisterBtn && registerForm) {
      showRegisterBtn?.addEventListener("click", (e) => {
        e.preventDefault();
        loginForm.classList.add("hidden");
        registerForm.classList.remove("hidden");
        loginMessage.classList.add("hidden");
        registerMessage.classList.add("hidden");
      });
    }
    if (showLoginBtn) {
      showLoginBtn?.addEventListener("click", (e) => {
        e.preventDefault();
        registerForm.classList.add("hidden");
        loginForm.classList.remove("hidden");
        loginMessage.classList.add("hidden");
        registerMessage.classList.add("hidden");
      });
    }
    if (showLostPasswordBtn) {
      showLostPasswordBtn?.addEventListener("click", (e) => {
        e.preventDefault();
        loginForm.classList.add("hidden");
        lostPasswordForm.classList.remove("hidden");
        loginMessage.classList.add("hidden");
        lostPasswordMessage.classList.add("hidden");
      });
    }
    if (backToLoginBtn) {
      backToLoginBtn?.addEventListener("click", (e) => {
        e.preventDefault();
        lostPasswordForm.classList.add("hidden");
        loginForm.classList.remove("hidden");
        loginMessage.classList.add("hidden");
        lostPasswordMessage.classList.add("hidden");
      });
    }
    const showMessage = (element, message, isError = true) => {
      element.textContent = message;
      element.classList.remove("hidden");
      element.classList.remove("bg-green-500", "bg-red-500");
      element.classList.add(isError ? "bg-red-500" : "bg-green-500");
    };
    const handleSubmit = async (form, action2, messageElement) => {
      const submitBtn = form.querySelector('button[type="submit"]');
      const submitText = submitBtn.querySelector(".text");
      const originalText = submitText.textContent;
      try {
        submitBtn.disabled = true;
        if (action2 === "login") {
          submitText.textContent = digicommerceVars.i18n.logging_in;
        } else if (action2 === "register") {
          submitText.textContent = digicommerceVars.i18n.registering_in;
        } else if (action2 === "lost_password") {
          submitText.textContent = digicommerceVars.i18n.sending_email;
        }
        const formData = new FormData(form);
        formData.append("action", `digicommerce_${action2}`);
        const nonceInput = form.querySelector(`input[name="digicommerce_${action2}_nonce"]`);
        if (!nonceInput) {
          console.error(`Nonce field not found: digicommerce_${action2}_nonce`);
          throw new Error("Security token missing");
        }
        formData.append("nonce", nonceInput.value);
        if (digicommerceVars.recaptcha_site_key) {
          try {
            const token = await grecaptcha.execute(digicommerceVars.recaptcha_site_key, {
              action: action2
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
          showMessage(messageElement, data.data.message, false);
          if (data.data.redirect_url) {
            setTimeout(() => {
              window.location.href = data.data.redirect_url;
            }, 1e3);
          }
        } else {
          throw new Error(data.data ? data.data.message : digicommerceVars.i18n.error);
        }
      } catch (error) {
        showMessage(messageElement, error.message);
        if (action2 === "login") {
          form.querySelector("#password").value = "";
        }
      } finally {
        submitBtn.disabled = false;
        submitText.textContent = originalText;
      }
    };
    if (loginForm) {
      loginForm?.addEventListener("submit", async (e) => {
        e.preventDefault();
        await handleSubmit(loginForm, "login", loginMessage);
      });
    }
    if (registerForm) {
      registerForm?.addEventListener("submit", async (e) => {
        e.preventDefault();
        await handleSubmit(registerForm, "register", registerMessage);
      });
    }
    if (lostPasswordForm) {
      lostPasswordForm?.addEventListener("submit", async (e) => {
        e.preventDefault();
        await handleSubmit(lostPasswordForm, "lost_password", lostPasswordMessage);
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
