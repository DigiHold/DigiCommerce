const { __ } = wp.i18n;
const { useBlockProps } = wp.blockEditor;

export default function ButtonSave({ attributes, clientId }) {
    const { productId, customTitle, showPrice, subtitle, customClass, variationId } = attributes;
    // Move blockProps to the button
    const blockProps = useBlockProps.save();

    // Build button classes
    const buttonClasses = ['dc-button', customClass].filter(Boolean);

	// Build checkout URL
	let checkoutUrl = window.digicommerceBlocksData?.checkoutUrl || '/checkout/';
	checkoutUrl = `${checkoutUrl}?id=${productId}`;
	if (variationId) {
		checkoutUrl += `&variation=${variationId}`;
	}

    // Get products and currency data for preview
	const products = window.digicommerceBlocksData?.products || [];
	const currencies = window.digicommerceBlocksData?.currencies || {};
	const selectedCurrency = window.digicommerceBlocksData?.selectedCurrency || 'USD';
	const currencyPosition = window.digicommerceBlocksData?.currencyPosition || 'left';
	const currencySymbol = currencies[selectedCurrency]?.symbol || '$';

	const selectedProduct = products.find(p => p.value === productId);
    const productVariations = selectedProduct?.variations || [];
    const selectedVariation = variationId ? 
        productVariations[parseInt(variationId) - 1] : null;

    // Format price with currency
    const formatPrice = (price, salePrice = null) => {
		if (!price && price !== 0) return '';
		
		const regularPrice = parseFloat(price).toFixed(2);
		let priceStr = regularPrice;
		
		// Only show sale price format if it exists and is lower than regular price
		if (salePrice && parseFloat(salePrice) > 0 && parseFloat(salePrice) < parseFloat(price)) {
			const salePriceFormatted = parseFloat(salePrice).toFixed(2);
			// Apply currency formatting to both prices separately
			const regularPriceWithCurrency = applyCurrencyFormat(regularPrice);
			const salePriceWithCurrency = applyCurrencyFormat(salePriceFormatted);
			return `${salePriceWithCurrency} <del>${regularPriceWithCurrency}</del>`;
		}
	
		// Apply currency format based on position
		return applyCurrencyFormat(priceStr);
	};
	
	// Helper function to apply currency format
	const applyCurrencyFormat = (price) => {
		switch (currencyPosition) {
			case 'left':
				return `${currencySymbol}${price}`;
			case 'right':
				return `${price}${currencySymbol}`;
			case 'left_space':
				return `${currencySymbol} ${price}`;
			case 'right_space':
				return `${price} ${currencySymbol}`;
			default:
				return `${currencySymbol}${price}`;
		}
	};

    // Determine button text
	let buttonText = customTitle || selectedProduct?.label || __('Buy Now', 'digicommerce');

	if (showPrice && selectedProduct) {
		const productVariations = selectedProduct?.variations || [];
    	const hasVariations = productVariations.length > 0;

		if (hasVariations) {
			// Variable product
			if (selectedVariation) {
				const price = formatPrice(
					selectedVariation.price,
					selectedVariation.salePrice
				);
				buttonText += ` - ${price}`;
			}
		} else {
			// Single price product
			const price = formatPrice(
				selectedProduct.price,
				selectedProduct.sale_price
			);
			buttonText += ` - ${price}`;
		}
	}

    return (
		<div className="dc-button-wrapper">
			<a 
				{...blockProps}
				href={checkoutUrl}
				className={[...buttonClasses, blockProps.className].filter(Boolean).join(' ')}
			>
				<span className="dc-button-text" dangerouslySetInnerHTML={{ __html: buttonText }} />
				{subtitle && (
					<span className="dc-button-subtitle">
						{subtitle}
					</span>
				)}
			</a>
		</div>
    );
}