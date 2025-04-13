document.addEventListener('DOMContentLoaded', () => {
    // DOM elements
    const loginForm = document.getElementById('digicommerce-login-form');
    const registerForm = document.getElementById('digicommerce-register-form');
    const lostPasswordForm = document.getElementById('digicommerce-lost-password-form');
    const showLoginBtn = document.getElementById('show-login');
    const showRegisterBtn = document.getElementById('show-register');
    const showLostPasswordBtn = document.getElementById('show-lost-password');
    const backToLoginBtn = document.getElementById('back-to-login');
    const loginMessage = document.getElementById('login-message');
    const registerMessage = document.getElementById('register-message');
    const lostPasswordMessage = document.getElementById('lost-password-message');

	// Check URL parameters to determine which form to show
    const urlParams = new URLSearchParams(window.location.search);
    const action = urlParams.get('action');
    
    // Show appropriate form based on URL parameter
    if (action === 'lostpassword' && lostPasswordForm) {
        loginForm?.classList.add('hidden');
        registerForm?.classList.add('hidden');
        lostPasswordForm.classList.remove('hidden');
    } else if (action === 'register' && registerForm) {
        loginForm?.classList.add('hidden');
        lostPasswordForm?.classList.add('hidden');
        registerForm.classList.remove('hidden');
    }

    // Toggle password visibility
    const togglePasswordBtns = document.querySelectorAll('.pass__icon');
    if (togglePasswordBtns) {
        togglePasswordBtns.forEach((btn) => {
            const input = btn.parentElement.querySelector('input');
            const showIcon = btn.querySelector('[data-show]');
            const hideIcon = btn.querySelector('[data-hide]');

            btn.addEventListener('click', (e) => {
                e.preventDefault();
                if (input.type === 'password') {
                    input.type = 'text';
                    showIcon.classList.remove('hidden');
                    hideIcon.classList.add('hidden');
                } else {
                    input.type = 'password';
                    showIcon.classList.add('hidden');
                    hideIcon.classList.remove('hidden');
                }
            });
        });
    }

    // Form switching
    if (showRegisterBtn && registerForm) {
        showRegisterBtn?.addEventListener('click', (e) => {
            e.preventDefault();
            loginForm.classList.add('hidden');
            registerForm.classList.remove('hidden');
            loginMessage.classList.add('hidden');
            registerMessage.classList.add('hidden');
        });
    }

    if (showLoginBtn) {
        showLoginBtn?.addEventListener('click', (e) => {
            e.preventDefault();
            registerForm.classList.add('hidden');
            loginForm.classList.remove('hidden');
            loginMessage.classList.add('hidden');
            registerMessage.classList.add('hidden');
        });
    }

    if (showLostPasswordBtn) {
        showLostPasswordBtn?.addEventListener('click', (e) => {
            e.preventDefault();
            loginForm.classList.add('hidden');
            lostPasswordForm.classList.remove('hidden');
            loginMessage.classList.add('hidden');
            lostPasswordMessage.classList.add('hidden');
        });
    }

    if (backToLoginBtn) {
        backToLoginBtn?.addEventListener('click', (e) => {
            e.preventDefault();
            lostPasswordForm.classList.add('hidden');
            loginForm.classList.remove('hidden');
            loginMessage.classList.add('hidden');
            lostPasswordMessage.classList.add('hidden');
        });
    }

    // Show message function
    const showMessage = (element, message, isError = true) => {
        element.textContent = message;
        element.classList.remove('hidden');
        element.classList.remove('bg-green-500', 'bg-red-500');
        element.classList.add(isError ? 'bg-red-500' : 'bg-green-500');
    };

    // Handle form submission
    const handleSubmit = async (form, action, messageElement) => {
        const submitBtn = form.querySelector('button[type="submit"]');
        const submitText = submitBtn.querySelector('.text');
        const originalText = submitText.textContent;

        try {
            submitBtn.disabled = true;

            // Set loading text based on action
            if (action === 'login') {
                submitText.textContent = digicommerceVars.i18n.logging_in;
            } else if (action === 'register') {
                submitText.textContent = digicommerceVars.i18n.registering_in;
            } else if (action === 'lost_password') {
                submitText.textContent = digicommerceVars.i18n.sending_email;
            }

            // Use FormData to gather all form fields
            const formData = new FormData(form);

            // Add required fields for WordPress AJAX
            formData.append('action', `digicommerce_${action}`);

            // Get the nonce from the form
            const nonceInput = form.querySelector(`input[name="digicommerce_${action}_nonce"]`);
            if (!nonceInput) {
                console.error(`Nonce field not found: digicommerce_${action}_nonce`);
                throw new Error('Security token missing');
            }

            // Add the nonce
            formData.append('nonce', nonceInput.value);

            // Add reCAPTCHA token if enabled
            if (digicommerceVars.recaptcha_site_key) {
                try {
                    const token = await grecaptcha.execute(digicommerceVars.recaptcha_site_key, {
                        action: action,
                    });
                    formData.append('recaptcha_token', token);
                } catch (error) {
                    console.error('reCAPTCHA error:', error);
                }
            }

            // Make the AJAX request
            const response = await fetch(digicommerceVars.ajaxurl, {
                method: 'POST',
                credentials: 'same-origin',
                body: formData,
            });

            const data = await response.json();

            if (data.success) {
                showMessage(messageElement, data.data.message, false);
                if (data.data.redirect_url) {
                    setTimeout(() => {
                        window.location.href = data.data.redirect_url;
                    }, 1000);
                }
            } else {
                throw new Error(data.data ? data.data.message : digicommerceVars.i18n.error);
            }
        } catch (error) {
            showMessage(messageElement, error.message);
            if (action === 'login') {
                form.querySelector('#password').value = '';
            }
        } finally {
            submitBtn.disabled = false;
            submitText.textContent = originalText;
        }
    };

    // Login form submission
    if (loginForm) {
        loginForm?.addEventListener('submit', async (e) => {
            e.preventDefault();
            await handleSubmit(loginForm, 'login', loginMessage);
        });
    }

    // Register form submission
    if (registerForm) {
        registerForm?.addEventListener('submit', async (e) => {
            e.preventDefault();
            await handleSubmit(registerForm, 'register', registerMessage);
        });
    }

    // Lost password form submission
    if (lostPasswordForm) {
        lostPasswordForm?.addEventListener('submit', async (e) => {
            e.preventDefault();
            await handleSubmit(lostPasswordForm, 'lost_password', lostPasswordMessage);
        });
    }

    // Input focus
    const inputs = document.querySelectorAll('.digi__form .field input');

    if (inputs) {
        inputs.forEach((input) => {
            const handleInputState = () => {
                if (input.value !== '') {
                    input.classList.add('focused');
                } else {
                    input.classList.remove('focused');
                }
            };

            // Set initial state
            handleInputState();

            // Handle events
            input.addEventListener('blur', handleInputState);
            input.addEventListener('input', handleInputState);
        });
    }
});
