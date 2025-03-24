(() => {
  // resources/js/front/checkout.js
  var DigiUI = {
    showMessage: (element, message, isError = true) => {
      element.textContent = message;
      element.classList.remove("hidden", "bg-green-500", "bg-red-500");
      element.classList.add(isError ? "bg-red-500" : "bg-green-500", "text-white");
      const elementPosition = element.getBoundingClientRect().top;
      const offsetPosition = elementPosition + window.pageYOffset - 100;
      window.scrollTo({
        top: offsetPosition,
        behavior: "smooth"
      });
    },
    hideMessage: (element, delay = 5e3) => {
      setTimeout(() => {
        element.classList.add("hidden");
      }, delay);
    },
    toggleLoading: (overlay, show) => {
      if (show) {
        overlay.classList.remove("hidden");
        overlay.classList.add("flex");
      } else {
        overlay.classList.add("hidden");
        overlay.classList.remove("flex");
      }
    },
    resetButton: (button, originalText) => {
      button.disabled = false;
      button.textContent = originalText;
    },
    handleValidationFailure: (messageEl, loadingOverlay, submitButton, originalButtonText, message) => {
      DigiUI.showMessage(messageEl, message);
      DigiUI.toggleLoading(loadingOverlay, false);
      DigiUI.resetButton(submitButton, originalButtonText);
    }
  };
  var DigiStripe = {
    async handlePayment(formData, cardElement2, stripeInstance2) {
      try {
        const setupResponse = await fetch(digicommerceVars.ajaxurl, {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: new URLSearchParams({
            action: "digicommerce_process_stripe_payment",
            nonce: formData.get("checkout_nonce"),
            ...this.getFormFields(formData)
          })
        });
        const setupResult = await setupResponse.json();
        if (!setupResult.success) {
          throw new Error(setupResult.data?.message || "Payment setup failed");
        }
        const paymentData = {
          payment_method: {
            card: cardElement2,
            billing_details: this.getBillingDetails(formData)
          }
        };
        let finalData = {
          customer_id: setupResult.data.customerId
        };
        const hasSubscription = setupResult.data.setupIntent !== void 0;
        if (setupResult.data.setupIntent) {
          const { setupIntent, error: setupError } = await stripeInstance2.confirmCardSetup(
            setupResult.data.setupIntent.client_secret,
            paymentData
          );
          if (setupError) {
            throw new Error(setupError.message);
          }
          finalData.payment_method = setupIntent.payment_method;
          finalData.setup_intent_id = setupIntent.id;
        }
        if (setupResult.data.paymentIntent) {
          const paymentConfirmData = finalData.payment_method ? { payment_method: finalData.payment_method } : paymentData;
          const { paymentIntent, error: paymentError } = await stripeInstance2.confirmCardPayment(
            setupResult.data.paymentIntent.client_secret,
            paymentConfirmData
          );
          if (paymentError) {
            throw new Error(paymentError.message);
          }
          finalData.payment_intent_id = paymentIntent.id;
          if (!finalData.payment_method) {
            finalData.payment_method = paymentIntent.payment_method;
          }
        }
        if (finalData.payment_method && hasSubscription && finalData.setup_intent_id) {
          const subscriptionResponse = await fetch(digicommerceVars.ajaxurl, {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({
              action: "digicommerce_process_stripe_payment",
              nonce: formData.get("checkout_nonce"),
              stripe_payment_data: JSON.stringify(finalData),
              ...this.getFormFields(formData)
            })
          });
          const subscriptionResult = await subscriptionResponse.json();
          if (!subscriptionResult.success) {
            throw new Error(subscriptionResult.data?.message || "Failed to create subscription");
          }
          if (subscriptionResult.data.requiresAction && subscriptionResult.data.clientSecret) {
            const { paymentIntent, error: confirmError } = await stripeInstance2.confirmCardPayment(
              subscriptionResult.data.clientSecret
            );
            if (confirmError) {
              throw new Error(confirmError.message);
            }
            finalData.payment_intent_id = paymentIntent.id;
          }
          if (subscriptionResult.data.subscriptionId) {
            finalData.subscription_id = subscriptionResult.data.subscriptionId;
          }
        }
        return await this.processCheckout(new URLSearchParams({
          action: "digicommerce_process_checkout",
          checkout_nonce: formData.get("checkout_nonce"),
          payment_method: "stripe",
          stripe_payment_data: JSON.stringify(finalData),
          ...this.getFormFields(formData)
        }));
      } catch (error) {
        console.error("Payment error:", error);
        throw error;
      }
    },
    processCheckout(data) {
      return fetch(digicommerceVars.ajaxurl, {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded"
        },
        body: data
      }).then((response) => response.json()).then((result) => {
        if (!result.success) {
          throw new Error(result.data?.message || "Checkout processing failed");
        }
        return result;
      });
    },
    getBillingDetails(formData) {
      return {
        name: `${formData.get("billing_first_name")} ${formData.get("billing_last_name")}`,
        email: formData.get("billing_email"),
        phone: formData.get("billing_phone"),
        address: {
          line1: formData.get("billing_address"),
          city: formData.get("billing_city"),
          postal_code: formData.get("billing_postcode"),
          country: formData.get("billing_country")
        }
      };
    },
    getFormFields(formData) {
      const fields = {
        first_name: formData.get("billing_first_name"),
        last_name: formData.get("billing_last_name"),
        email: formData.get("billing_email"),
        phone: formData.get("billing_phone"),
        company: formData.get("billing_company") || "",
        address: formData.get("billing_address"),
        city: formData.get("billing_city"),
        postcode: formData.get("billing_postcode"),
        country: formData.get("billing_country"),
        vat_number: formData.get("billing_vat_number") || ""
      };
      const mailingListCheckbox = document.getElementById("subscribe_mailing_list");
      if (mailingListCheckbox) {
        fields.subscribe_mailing_list = mailingListCheckbox.checked ? "1" : "0";
      }
      const urlParams = new URLSearchParams(window.location.search);
      if (urlParams.get("from_abandoned") === "1") {
        fields.from_abandoned = "1";
        if (urlParams.get("coupon")) {
          fields.recovery_coupon = urlParams.get("coupon");
        }
      }
      return fields;
    }
  };
  var stripeInstance;
  var cardElement;
  document.addEventListener("DOMContentLoaded", function() {
    const checkoutForm = document.getElementById("digicommerce-checkout-form");
    const urlParams = new URLSearchParams(window.location.search);
    if (checkoutForm) {
      if (digicommerceVars.stripeEnabled) {
        stripeInstance = Stripe(digicommerceVars.publishableKey);
        const elements = stripeInstance.elements();
        cardElement = elements.create("card", {
          hidePostalCode: true,
          style: {
            base: {
              fontSize: "16px",
              color: "#32325d",
              fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
              "::placeholder": {
                color: "#aab7c4"
              }
            },
            invalid: {
              color: "#fa755a",
              iconColor: "#fa755a"
            }
          }
        });
        cardElement.mount("#card-element");
        cardElement.addEventListener("change", function(event) {
          const displayError = document.getElementById("card-errors");
          if (event.error) {
            displayError.textContent = event.error.message;
            displayError.classList.remove("hidden");
            displayError.classList.add("flex");
          } else {
            displayError.textContent = "";
            displayError.classList.remove("flex");
            displayError.classList.add("hidden");
          }
        });
      }
      if (digicommerceVars.paypalEnabled) {
        const cartItems = JSON.parse(digicommerceVars.cartItems || "[]");
        const hasSubscription = cartItems.some((item) => item.subscription_enabled);
        const subscriptionItem = hasSubscription ? cartItems.find((item) => item.subscription_enabled) : null;
        const paypalConfig = {
          fundingSource: paypal.FUNDING.PAYPAL,
          style: {
            layout: "vertical",
            shape: "rect",
            label: hasSubscription ? "subscribe" : "pay"
          }
        };
        if (hasSubscription) {
          paypalConfig.createSubscription = async (data, actions) => {
            try {
              if (!checkoutForm)
                throw new Error("Checkout form not found");
              const formData = new FormData(checkoutForm);
              DigiUI.toggleLoading(document.getElementById("loading-overlay"), true);
              const planResponse = await fetch(digicommerceVars.ajaxurl, {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: new URLSearchParams({
                  action: "digicommerce_create_paypal_plan",
                  nonce: formData.get("checkout_nonce"),
                  first_name: formData.get("billing_first_name"),
                  last_name: formData.get("billing_last_name"),
                  email: formData.get("billing_email"),
                  country: formData.get("billing_country"),
                  vat_number: formData.get("billing_vat_number")
                })
              });
              const planResult = await planResponse.json();
              if (!planResult.success || !planResult.data.plan_id) {
                throw new Error(planResult.data?.message || "Failed to create PayPal plan");
              }
              const subscriptionConfig = {
                plan_id: planResult.data.plan_id,
                application_context: {
                  shipping_preference: "NO_SHIPPING"
                },
                subscriber: {
                  name: {
                    given_name: formData.get("billing_first_name"),
                    surname: formData.get("billing_last_name")
                  },
                  email_address: formData.get("billing_email")
                }
              };
              DigiUI.toggleLoading(document.getElementById("loading-overlay"), false);
              return await actions.subscription.create(subscriptionConfig);
            } catch (error) {
              console.error("Subscription creation error:", error);
              DigiUI.showMessage(document.getElementById("checkout-message"), error.message);
              throw error;
            }
          };
        } else {
          paypalConfig.createOrder = async (data, actions) => {
            try {
              if (!checkoutForm)
                throw new Error("Checkout form not found");
              const formData = new FormData(checkoutForm);
              const subtotal = cartItems.reduce((sum, item) => sum + parseFloat(item.price), 0);
              const buyerCountry = formData.get("billing_country");
              const sellerCountry = digicommerceVars.businessCountry;
              const vatNumber = formData.get("billing_vat_number");
              let vatRate = 0;
              const countries = digicommerceVars.countries || {};
              if (!digicommerceVars.removeTaxes) {
                if (buyerCountry === sellerCountry) {
                  vatRate = countries[sellerCountry]?.tax_rate || 0;
                } else if (countries[buyerCountry]?.eu && countries[sellerCountry]?.eu) {
                  if (!vatNumber || !window.vatCalculator?.validateVATNumber(vatNumber, buyerCountry)) {
                    vatRate = countries[buyerCountry]?.tax_rate || 0;
                  }
                }
              }
              const vatAmount = subtotal * vatRate;
              const totalWithVat = subtotal + vatAmount;
              let discountAmount = 0;
              if (digicommerceVars.cartDiscount) {
                const discount = JSON.parse(digicommerceVars.cartDiscount);
                if (discount.type === "percentage") {
                  discountAmount = totalWithVat * discount.amount / 100;
                } else {
                  discountAmount = Math.min(discount.amount, totalWithVat);
                }
              }
              const finalTotal = totalWithVat - discountAmount;
              return actions.order.create({
                purchase_units: [{
                  amount: {
                    currency_code: digicommerceVars.currency,
                    value: finalTotal.toFixed(2),
                    breakdown: {
                      item_total: {
                        currency_code: digicommerceVars.currency,
                        value: subtotal.toFixed(2)
                      },
                      tax_total: vatAmount > 0 ? {
                        currency_code: digicommerceVars.currency,
                        value: vatAmount.toFixed(2)
                      } : void 0,
                      discount: discountAmount > 0 ? {
                        currency_code: digicommerceVars.currency,
                        value: discountAmount.toFixed(2)
                      } : void 0
                    }
                  },
                  items: cartItems.map((item) => ({
                    name: item.name,
                    unit_amount: {
                      currency_code: digicommerceVars.currency,
                      value: item.price.toFixed(2)
                    },
                    quantity: 1
                  }))
                }]
              });
            } catch (error) {
              console.error("Order creation error:", error);
              DigiUI.showMessage(document.getElementById("checkout-message"), error.message);
              throw error;
            }
          };
        }
        paypalConfig.onApprove = async (data, actions) => {
          try {
            let captureResult;
            if (data.orderID && !data.subscriptionID) {
              captureResult = await actions.order.capture();
            }
            const formData = new FormData(checkoutForm);
            const messageEl = document.getElementById("checkout-message");
            DigiUI.toggleLoading(document.getElementById("loading-overlay"), true);
            const checkoutData = new URLSearchParams({
              action: "digicommerce_process_checkout",
              checkout_nonce: formData.get("checkout_nonce"),
              payment_method: "paypal",
              paypal_order_id: data.orderID,
              paypal_subscription_id: data.subscriptionID,
              email: formData.get("billing_email"),
              first_name: formData.get("billing_first_name"),
              last_name: formData.get("billing_last_name"),
              company: formData.get("billing_company"),
              country: formData.get("billing_country"),
              address: formData.get("billing_address"),
              city: formData.get("billing_city"),
              postcode: formData.get("billing_postcode"),
              phone: formData.get("billing_phone"),
              vat_number: formData.get("billing_vat_number")
            });
            const mailingListCheckbox = document.getElementById("subscribe_mailing_list");
            if (mailingListCheckbox) {
              checkoutData.append("subscribe_mailing_list", mailingListCheckbox.checked ? "1" : "0");
            }
            if (urlParams.get("from_abandoned") === "1") {
              checkoutData.append("from_abandoned", "1");
              if (urlParams.get("coupon")) {
                checkoutData.append("recovery_coupon", urlParams.get("coupon"));
              }
            }
            const response = await fetch(digicommerceVars.ajaxurl, {
              method: "POST",
              headers: { "Content-Type": "application/x-www-form-urlencoded" },
              body: checkoutData
            });
            const result = await response.json();
            if (result.success && result.data.redirect) {
              DigiUI.showMessage(messageEl, digicommerceVars.i18n.success, false);
              if (result.data.order_id) {
                localStorage.setItem("last_order_id", result.data.order_id);
              }
              setTimeout(() => {
                window.location.href = result.data.redirect;
              }, 1500);
            } else {
              throw new Error(result.data?.message || "Payment processing failed");
            }
          } catch (error) {
            console.error("PayPal payment processing error:", error);
            DigiUI.showMessage(document.getElementById("checkout-message"), error.message);
            DigiUI.toggleLoading(document.getElementById("loading-overlay"), false);
          }
        };
        paypalConfig.onError = (err) => {
          console.error("PayPal error:", err);
          DigiUI.showMessage(document.getElementById("checkout-message"), "PayPal payment failed");
          DigiUI.toggleLoading(document.getElementById("loading-overlay"), false);
        };
        paypalConfig.onCancel = () => {
          DigiUI.showMessage(document.getElementById("checkout-message"), "Payment cancelled");
          DigiUI.toggleLoading(document.getElementById("loading-overlay"), false);
        };
        paypal.Buttons(paypalConfig).render("#paypal-button-container");
      }
    }
    const countrySelect = document.getElementById("country");
    if (countrySelect) {
      const choices = new Choices(countrySelect, {
        searchEnabled: true,
        searchPlaceholderValue: countrySelect.dataset.placeholder,
        searchResultLimit: -1
      });
      const countryCode = urlParams.get("country");
      if (countryCode) {
        const optionToSelect = countrySelect.querySelector(`option[value="${countryCode}"]`);
        if (optionToSelect) {
          choices.setChoiceByValue(countryCode);
          countrySelect.value = countryCode;
          setTimeout(() => {
            if (window.vatCalculator) {
              window.vatCalculator.updateFromSubtotal();
            }
          }, 0);
        }
      }
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
    const DigiValidation = {
      validateCountry: (countrySelect2, handlers) => {
        if (!countrySelect2.value) {
          handlers.onFailure(digicommerceVars.i18n.select_country);
          return false;
        }
        return true;
      },
      validateRequiredFields: (form, handlers) => {
        const requiredFields = form.querySelectorAll("input[required]");
        let isValid = true;
        requiredFields.forEach((field) => {
          if (!field.value.trim()) {
            isValid = false;
          }
        });
        if (!isValid) {
          handlers.onFailure(digicommerceVars.i18n.required_fields);
          return false;
        }
        return true;
      },
      validateVATNumber: (form, countrySelect2, handlers) => {
        if (!window.vatCalculator)
          return true;
        const countryCode = countrySelect2.value;
        const vatNumberField = document.getElementById("vat_number");
        const vatNumberContainer = document.getElementById("vat_number_field");
        if (!vatNumberField || vatNumberContainer.style.display === "none") {
          return true;
        }
        if (window.vatCalculator.isEUCountry(countryCode)) {
          const vatNumber = vatNumberField.value.trim();
          if (vatNumber && !window.vatCalculator.validateVATNumber(vatNumber, countryCode)) {
            handlers.onFailure(digicommerceVars.i18n.vat_invalid || "Invalid VAT number format");
            vatNumberField.classList.add("border-red-500");
            return false;
          }
        }
        return true;
      },
      validateForm: (form, countrySelect2, handlers) => {
        return DigiValidation.validateCountry(countrySelect2, handlers) && DigiValidation.validateVATNumber(form, countrySelect2, handlers) && DigiValidation.validateRequiredFields(form, handlers);
      }
    };
    const stripeRadio = document.getElementById("payment_method_stripe");
    const paypalRadio = document.getElementById("payment_method_paypal");
    const stripeSection = document.querySelector(".digicommerce-stripe");
    const paypalSection = document.querySelector(".digicommerce-paypal");
    const checkoutButton = document.querySelector(".digicommerce-checkout-button");
    const paypalContainer = document.getElementById("paypal-button-container");
    if (stripeRadio && paypalRadio && stripeSection && paypalSection) {
      let togglePaymentMethod2 = function(isStripe) {
        if (isStripe) {
          paypalSection.style.opacity = "0";
          setTimeout(() => {
            paypalSection.style.display = "none";
            stripeSection.style.display = "flex";
            checkoutButton.style.display = "flex";
            paypalContainer.style.display = "none";
            void stripeSection.offsetWidth;
            stripeSection.style.opacity = "1";
            checkoutButton.style.opacity = "1";
          }, 300);
        } else {
          stripeSection.style.opacity = "0";
          checkoutButton.style.opacity = "0";
          setTimeout(() => {
            stripeSection.style.display = "none";
            paypalSection.style.display = "flex";
            checkoutButton.style.display = "none";
            paypalContainer.style.display = "flex";
            void paypalSection.offsetWidth;
            paypalSection.style.opacity = "1";
          }, 300);
        }
      };
      var togglePaymentMethod = togglePaymentMethod2;
      paypalSection.style.opacity = "0";
      paypalSection.style.display = "none";
      paypalContainer.style.display = "none";
      stripeRadio.addEventListener("change", function() {
        if (this.checked) {
          togglePaymentMethod2(true);
        }
      });
      paypalRadio.addEventListener("change", function() {
        if (this.checked) {
          togglePaymentMethod2(false);
        }
      });
      if (stripeRadio.checked) {
        stripeSection.style.display = "flex";
        stripeSection.style.opacity = "1";
        checkoutButton.style.display = "flex";
        checkoutButton.style.opacity = "1";
        paypalContainer.style.display = "none";
      } else if (paypalRadio.checked) {
        paypalSection.style.display = "flex";
        paypalSection.style.opacity = "1";
        checkoutButton.style.display = "none";
        paypalContainer.style.display = "flex";
      }
      stripeSection.style.transition = "opacity 300ms ease-in-out";
      paypalSection.style.transition = "opacity 300ms ease-in-out";
      checkoutButton.style.transition = "all 300ms ease-in-out";
    }
    const loadingOverlay = document.getElementById("loading-overlay");
    const checkoutMessage = document.getElementById("checkout-message");
    if (checkoutForm && countrySelect && (digicommerceVars.stripeEnabled || digicommerceVars.paypalEnabled)) {
      countrySelect.removeAttribute("required");
      checkoutForm.setAttribute("novalidate", "");
      const vatNumberField = document.getElementById("vat_number");
      if (vatNumberField) {
        vatNumberField.addEventListener("input", () => {
          vatNumberField.classList.remove("border-red-500");
          checkoutMessage.classList.add("hidden");
        });
      }
      checkoutForm.addEventListener("submit", async function(e) {
        e.preventDefault();
        const submitButton = checkoutForm.querySelector("button.digicommerce-checkout-button .text");
        const originalButtonText = submitButton.textContent;
        DigiUI.toggleLoading(loadingOverlay, true);
        submitButton.disabled = true;
        submitButton.textContent = digicommerceVars.i18n.processing_payment;
        const validationHandlers = {
          onFailure: (message) => {
            DigiUI.handleValidationFailure(
              checkoutMessage,
              loadingOverlay,
              submitButton,
              originalButtonText,
              message
            );
          }
        };
        if (!DigiValidation.validateForm(checkoutForm, countrySelect, validationHandlers)) {
          return;
        }
        const emailField = checkoutForm.querySelector('input[name="billing_email"]');
        const emailValue = emailField?.value.trim();
        if (!emailValue || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailValue)) {
          DigiUI.handleValidationFailure(
            checkoutMessage,
            loadingOverlay,
            submitButton,
            originalButtonText,
            digicommerceVars.i18n.invalid_email
          );
          return;
        }
        checkoutMessage.classList.add("hidden");
        try {
          if (stripeInstance && cardElement) {
            const formData = new FormData(checkoutForm);
            const result = await DigiStripe.handlePayment(formData, cardElement, stripeInstance);
            if (result.success) {
              DigiUI.showMessage(checkoutMessage, digicommerceVars.i18n.success, false);
              if (result.data.order_id) {
                localStorage.setItem("last_order_id", result.data.order_id);
              }
              setTimeout(() => {
                if (result.data.redirect) {
                  window.location.href = result.data.redirect;
                } else if (digicommerceVars.payment_success_page) {
                  window.location.href = digicommerceVars.payment_success_page;
                }
              }, 1500);
            } else {
              throw new Error(result.data?.message || digicommerceVars.i18n.payment_error);
            }
          }
        } catch (error) {
          console.error("Checkout Error:", error);
          DigiUI.showMessage(checkoutMessage, error.message);
          DigiUI.resetButton(submitButton, originalButtonText);
        } finally {
          DigiUI.toggleLoading(loadingOverlay, false);
        }
      });
      checkoutForm.querySelectorAll("input[required]").forEach((input) => {
        input.addEventListener("invalid", function(event) {
          event.preventDefault();
          input.classList.add("invalid");
        });
        input.addEventListener("input", function() {
          input.classList.remove("invalid");
        });
      });
    }
  });
})();
