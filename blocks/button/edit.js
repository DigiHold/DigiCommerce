const { __ } = wp.i18n;
const { useBlockProps, InspectorControls } = wp.blockEditor;
const {
    PanelBody,
    SelectControl,
    TextControl,
    ToggleControl
} = wp.components;

export default function ButtonEdit({ attributes, setAttributes }) {
    const { productId, customTitle, showPrice, subtitle, customClass, variationId } = attributes;
    // Move blockProps to the button
    const blockProps = useBlockProps();

    // Build button classes
    const buttonClasses = ['dc-button', customClass].filter(Boolean);

    // Access products and settings from the localized data
    const products = window.digicommerceBlocksData?.products || [];
    const currencies = window.digicommerceBlocksData?.currencies || {};
    const selectedCurrency = window.digicommerceBlocksData?.selectedCurrency || 'USD';
    const currencyPosition = window.digicommerceBlocksData?.currencyPosition || 'left';
    const currencySymbol = currencies[selectedCurrency]?.symbol || '$';
    
    // Get selected product and its variations if available
	const selectedProduct = products.find(p => p.value === productId);
	const productVariations = selectedProduct?.variations || [];
	const hasVariations = productVariations.length > 0;

	// Get the variation if selected
	const selectedVariation = hasVariations && variationId ? 
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
        <>
            <InspectorControls>
                <PanelBody title={__('Button Settings', 'digicommerce')}>
                    <SelectControl
                        label={__('Select Product', 'digicommerce')}
                        value={productId || ''}
                        options={[
                            { label: __('Select a product...', 'digicommerce'), value: '' },
                            ...products
                        ]}
                        onChange={value => {
                            setAttributes({ 
                                productId: value ? parseInt(value) : null,
                                variationId: '' // Reset variation when product changes
                            });
                        }}
						__nextHasNoMarginBottom={true}
                    />
                    
                    {hasVariations && (
                        <SelectControl
							label={__('Select Variation', 'digicommerce')}
							value={variationId || ''}
							options={[
								{ label: __('Select a variation...', 'digicommerce'), value: '' },
								...productVariations.map((v, index) => ({
									label: v.name,
									value: (index + 1).toString()
								}))
							]}
							onChange={value => setAttributes({ variationId: value })}
							__nextHasNoMarginBottom={true}
						/>
                    )}

                    <TextControl
                        label={__('Custom Button Text', 'digicommerce')}
                        value={customTitle || ''}
                        onChange={value => setAttributes({ customTitle: value })}
                        help={__('Leave empty to use product name', 'digicommerce')}
						__nextHasNoMarginBottom={true}
                    />

                    <ToggleControl
                        label={__('Show Price', 'digicommerce')}
                        checked={!!showPrice}
                        onChange={value => setAttributes({ showPrice: value })}
						__nextHasNoMarginBottom={true}
                    />

                    <TextControl
                        label={__('Subtitle', 'digicommerce')}
                        value={subtitle || ''}
                        onChange={value => setAttributes({ subtitle: value })}
						__nextHasNoMarginBottom={true}
                    />

                    <TextControl
                        label={__('Custom Class', 'digicommerce')}
                        value={customClass || ''}
                        onChange={value => setAttributes({ customClass: value })}
                        help={__('Add custom CSS classes to the button', 'digicommerce')}
						__nextHasNoMarginBottom={true}
                    />
                </PanelBody>
            </InspectorControls>
            
			<div className="dc-button-wrapper">
				<a 
					{...blockProps}
					href="#" 
					className={[...buttonClasses, blockProps.className].filter(Boolean).join(' ')}
					onClick={(e) => e.preventDefault()}
				>
					<span className="dc-button-text" dangerouslySetInnerHTML={{ __html: buttonText }} />
					{subtitle && (
						<span className="dc-button-subtitle">
							{subtitle}
						</span>
					)}
				</a>
			</div>
        </>
    );
}