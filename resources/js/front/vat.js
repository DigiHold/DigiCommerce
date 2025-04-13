class VATCalculator {
    constructor() {
        // Elements
        this.form = document.getElementById('digicommerce-checkout-form');
        this.countrySelect = document.getElementById('country');
        this.vatNumberField = document.getElementById('vat_number_field');
        this.vatNumberInput = document.getElementById('vat_number');
        this.cartVatElement = document.getElementById('cart-vat');
        this.cartTotalElement = document.getElementById('cart-total');
        this.vatSection = document.getElementById('vat_section');
        this.cartSubtotalElement = document.getElementById('cart-subtotal');
        this.discountType = null;
        this.subtotalValue = 0;
        this.discountAmount = 0;
        this.businessCountry = digicommerceVars.businessCountry;

        // Ensure all required elements are present
        if (!this.form || !this.countrySelect || !this.cartSubtotalElement) {
            return;
        }
        
        // Get tax rates from data attribute
        try {
            this.taxRates = JSON.parse(this.form.dataset.taxRates || '{}');
        } catch (error) {
            console.warn('Invalid tax rates JSON:', error);
            this.taxRates = {};
        }
        
        // Initialize event listeners
        this.initializeEventListeners();
        
        // Initial calculation
        this.updateFromSubtotal();
    }

    initializeEventListeners() {
        // Country select change event
        if (this.countrySelect) {
            this.countrySelect.addEventListener('change', () => {
                this.updateFromSubtotal();
            });
        }

        // VAT number input event
        if (this.vatNumberInput) {
            this.vatNumberInput.addEventListener('input', () => {
                this.updateFromSubtotal();
            });
        }
    }

    updateFromSubtotal() {
        if (!this.cartSubtotalElement) return;

        const subtotalPriceElement = this.cartSubtotalElement.querySelector('.subtotal-price .price');
        if (subtotalPriceElement) {
            this.subtotalValue = parseFloat(subtotalPriceElement.textContent.replace(/[^0-9.-]+/g, '')) || 0;
            
            // Get both discount amount and type
            if (this.cartTotalElement) {
                this.discountAmount = parseFloat(this.cartTotalElement.dataset.discountRaw || 0);
                this.discountType = this.cartTotalElement.dataset.discountType || 'fixed';
            }
            
            this.updateVATDisplay(this.countrySelect.value);
        }
    }

    isEUCountry(countryCode) {
        return this.taxRates[countryCode]?.eu === true;
    }

    validateVATNumber(vatNumber, countryCode) {
        if (!vatNumber) return false;
        
        // Remove spaces and convert to uppercase
        vatNumber = vatNumber.toUpperCase().replace(/[^A-Z0-9]/g, '');
        
        // Only validate for EU countries
        if (!this.isEUCountry(countryCode)) {
            return false;
        }
        
        // Check if starts with country code
        if (!vatNumber.startsWith(countryCode)) {
            if (this.vatNumberInput) {
                this.vatNumberInput.setCustomValidity(digicommerceVars.i18n.vat_number);
                this.vatNumberInput.reportValidity();
            }
            return false;
        }

        // Basic format validation (EU VAT numbers are at least 8 characters)
        if (vatNumber.length < 8) {
            if (this.vatNumberInput) {
                this.vatNumberInput.setCustomValidity(digicommerceVars.i18n.vat_short);
                this.vatNumberInput.reportValidity();
            }
            return false;
        }
        
        // Country-specific format validation
        const countryFormats = {
            'AT': /^ATU[0-9]{8}$/, // Austria
            'BE': /^BE[0-9]{10}$/, // Belgium
            'BG': /^BG[0-9]{9,10}$/, // Bulgaria
            'CY': /^CY[0-9]{8}[A-Z]$/, // Cyprus
            'CZ': /^CZ[0-9]{8,10}$/, // Czech Republic
            'DE': /^DE[0-9]{9}$/, // Germany
            'DK': /^DK[0-9]{8}$/, // Denmark
            'EE': /^EE[0-9]{9}$/, // Estonia
            'EL': /^EL[0-9]{9}$/, // Greece
            'ES': /^ES[A-Z0-9][0-9]{7}[A-Z0-9]$/, // Spain
            'FI': /^FI[0-9]{8}$/, // Finland
            'FR': /^FR[0-9A-Z]{2}[0-9]{9}$/, // France
            'HR': /^HR[0-9]{11}$/, // Croatia
            'HU': /^HU[0-9]{8}$/, // Hungary
            'IE': /^IE[0-9][A-Z0-9][0-9]{5}[A-Z]$/, // Ireland
            'IT': /^IT[0-9]{11}$/, // Italy
            'LT': /^LT([0-9]{9}|[0-9]{12})$/, // Lithuania
            'LU': /^LU[0-9]{8}$/, // Luxembourg
            'LV': /^LV[0-9]{11}$/, // Latvia
            'MT': /^MT[0-9]{8}$/, // Malta
            'NL': /^NL[0-9]{9}B[0-9]{2}$/, // Netherlands
            'PL': /^PL[0-9]{10}$/, // Poland
            'PT': /^PT[0-9]{9}$/, // Portugal
            'RO': /^RO[0-9]{2,10}$/, // Romania
            'SE': /^SE[0-9]{12}$/, // Sweden
            'SI': /^SI[0-9]{8}$/, // Slovenia
            'SK': /^SK[0-9]{10}$/ // Slovakia
        };
        
        // If we have a specific format for this country, validate against it
        if (countryFormats[countryCode]) {
            if (!countryFormats[countryCode].test(vatNumber)) {
                if (this.vatNumberInput) {
                    this.vatNumberInput.setCustomValidity(digicommerceVars.i18n.vat_invalid + countryCode);
                    this.vatNumberInput.reportValidity();
                }
                return false;
            }
        }

        // Clear any previous validation messages
        if (this.vatNumberInput) {
            this.vatNumberInput.setCustomValidity('');
        }

        return true;
    }

	updateVATDisplay(buyerCountry) {
		const selectedCountry = this.taxRates[buyerCountry] || { rate: 0, eu: false };
		const businessTaxRate = this.taxRates[this.businessCountry]?.rate || 0;
		const taxRate = parseFloat(selectedCountry.rate) || 0;
		const isEU = this.isEUCountry(buyerCountry);
		const isSameCountry = buyerCountry === this.businessCountry;
	
		// Show VAT number field only for EU buyers (except domestic)
		if (this.vatNumberField) {
			this.vatNumberField.style.display = (isEU && !isSameCountry) ? 'block' : 'none';
		}
	
		// 1. Calculate discount on the original subtotal
		let discountValue = 0;
		if (this.discountType === 'percentage' && this.discountAmount > 0) {
			discountValue = (this.subtotalValue * this.discountAmount) / 100;
		} else {
			discountValue = Math.min(this.discountAmount, this.subtotalValue);
		}
	
		// 2. Calculate discounted subtotal
		const discountedSubtotal = this.subtotalValue - discountValue;
	
		// 3. Determine VAT rate
		let appliedVatRate = 0;
		const vatNumber = this.vatNumberInput ? this.vatNumberInput.value.trim() : '';
		
		// VAT Logic:
		// 1. Same country as seller: Always charge seller's country VAT
		// 2. Different EU country: Charge buyer's VAT rate unless valid VAT number
		// 3. Non-EU country: No VAT
		if (isSameCountry) {
			// Domestic sale - always charge local VAT rate
			appliedVatRate = businessTaxRate;
		} else if (isEU) {
			// EU cross-border sale
			const isValidVAT = vatNumber && this.validateVATNumber(vatNumber, buyerCountry);
			if (!isValidVAT) {
				// No valid VAT number - charge buyer's country rate
				appliedVatRate = taxRate;
			}
			// If valid VAT number - no VAT (appliedVatRate remains 0)
		}
		// For non-EU buyers, appliedVatRate remains 0
	
		// 4. Calculate VAT on the discounted subtotal
		const vatAmount = discountedSubtotal * appliedVatRate;
	
		// 5. Calculate final total (discounted subtotal + VAT)
		const totalAmount = discountedSubtotal + vatAmount;
	
		// Update displays with rounded values
		const roundedVat = Math.round(vatAmount * 100) / 100;
		const roundedTotal = Math.round(totalAmount * 100) / 100;
	
		// Update VAT display
		if (this.cartVatElement) {
			const vatPriceElement = this.cartVatElement.querySelector('.vat-price .price');
			if (vatPriceElement) {
				vatPriceElement.textContent = roundedVat.toFixed(2);
			}
		}
	
		// Update total display
		if (this.cartTotalElement) {
			const totalPriceElement = this.cartTotalElement.querySelector('.total-price .price');
			if (totalPriceElement) {
				totalPriceElement.textContent = roundedTotal.toFixed(2);
			}
			this.cartTotalElement.dataset.currentTotal = roundedTotal;
		}
	
		// Update VAT rate display
		const vatRateElement = document.getElementById('vat_rate');
		if (vatRateElement) {
			if (roundedVat > 0) {
				vatRateElement.textContent = `(${(appliedVatRate * 100).toFixed(2)}%)`;
			} else {
				vatRateElement.textContent = '(0%)';
			}
		}
	}
}

// Initialize when DOM is loaded and store the instance globally
document.addEventListener('DOMContentLoaded', () => {
    window.vatCalculator = new VATCalculator();
});