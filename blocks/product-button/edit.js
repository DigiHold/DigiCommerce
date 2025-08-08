/**
 * Product Button Block Edit Component
 */

const { 
	InspectorControls, 
	useBlockProps,
	__experimentalUseColorProps: useColorProps,
	__experimentalUseBorderProps: useBorderProps,
	__experimentalGetSpacingClassesAndStyles: useSpacingProps
} = wp.blockEditor;
const {
	PanelBody,
	TextControl,
	ToggleControl,
	SelectControl,
	RadioControl,
	Notice
} = wp.components;
const { __ } = wp.i18n;
const { useState, useEffect, useMemo } = wp.element;
const { useSelect } = wp.data;

const DigiCommerceProductButtonEdit = ({ attributes, setAttributes }) => {
	const { productId, buttonText, showPrice, openInNewTab, actionType, variationId } = attributes;
	const [productVariations, setProductVariations] = useState([]);

	// Get block props for wrapper
	// We need to manually filter out padding-related styles
	const rawBlockProps = useBlockProps();
	
	// Filter out padding styles from the wrapper
	const blockProps = {
		...rawBlockProps,
		style: {
			...rawBlockProps.style,
			// Keep margin but remove padding
			padding: undefined,
			paddingTop: undefined,
			paddingRight: undefined,
			paddingBottom: undefined,
			paddingLeft: undefined
		}
	};
	
	// Remove padding classes from wrapper
	if (blockProps.className) {
		blockProps.className = blockProps.className
			.split(' ')
			.filter(cls => !cls.includes('has-') || !cls.includes('-padding'))
			.join(' ');
	}
	
	// Extract serialized props for the button using official hooks
	const colorProps = useColorProps(attributes);
	const borderProps = useBorderProps(attributes);
	const spacingProps = useSpacingProps(attributes);
	
	// Build button styles
	const buttonStyles = {
		...colorProps.style,
		...borderProps.style,
		// Extract only padding from spacing props
		...(spacingProps.style && {
			padding: spacingProps.style.padding,
			paddingTop: spacingProps.style.paddingTop,
			paddingRight: spacingProps.style.paddingRight,
			paddingBottom: spacingProps.style.paddingBottom,
			paddingLeft: spacingProps.style.paddingLeft
		}),
		// Handle typography manually since there's no official hook yet
		...(attributes.style?.typography && attributes.style.typography),
		// Handle font size preset
		...(attributes.fontSize && {
			fontSize: `var(--wp--preset--font-size--${attributes.fontSize})`
		})
	};
	
	// Add border-style: solid if border width is set but no style specified
	const hasBorderWidth = buttonStyles.borderWidth || 
		buttonStyles.borderTopWidth || 
		buttonStyles.borderRightWidth || 
		buttonStyles.borderBottomWidth || 
		buttonStyles.borderLeftWidth ||
		attributes.style?.border?.width;
		
	const hasBorderStyle = buttonStyles.borderStyle || attributes.style?.border?.style;
	
	if (hasBorderWidth && !hasBorderStyle) {
		buttonStyles.borderStyle = 'solid';
	}
	
	const buttonProps = {
		className: [
			'wp-element-button',
			colorProps.className,
			borderProps.className
		].filter(Boolean).join(' ').trim(),
		style: buttonStyles
	};
	
	// Add font size class if preset is used
	if (attributes.fontSize) {
		buttonProps.className += ` has-${attributes.fontSize}-font-size`;
	}

	const rawProducts = useSelect((select) =>
		select('core').getEntityRecords('postType', 'digi_product', {
			per_page: -1,
			status: 'publish'
		}), []);

	const products = useMemo(() => {
		if (!rawProducts) return [];

		return [
			{ value: 0, label: __('Select a product', 'digicommerce') },
			...rawProducts.map((product) => ({
				value: product.id,
				label: product.title.rendered || product.title.raw || __('(No title)', 'digicommerce')
			}))
		];
	}, [rawProducts]);

	useEffect(() => {
		if (productId > 0) {
			wp.apiFetch({ path: `/wp/v2/digi_product/${productId}`, method: 'GET' })
				.then((response) => {
					const mode = response.meta?.digi_price_mode;
					const rawVariations = response.meta?.digi_price_variations;

					if (mode === 'variations' && Array.isArray(rawVariations) && rawVariations.length > 0) {
						const variationOptions = rawVariations.map((variation, index) => ({
							value: index,
							label: variation.name || `Variation ${index + 1}`
						}));

						setProductVariations(variationOptions);

						if (variationId === -1) {
							setAttributes({ variationId: 0 });
						}
					} else {
						setProductVariations([]);
						setAttributes({ variationId: -1 });
					}
				})
				.catch(() => {
					setProductVariations([]);
					setAttributes({ variationId: -1 });
				});
		} else {
			setProductVariations([]);
			setAttributes({ variationId: -1 });
		}
	}, [productId]);

	let previewText = buttonText || __('View Product', 'digicommerce');
	if (showPrice) {
		previewText += ' - $19.99';
	}

	return (
		<>
			<InspectorControls>
				<PanelBody title={__('Settings', 'digicommerce')} initialOpen={true}>
					<div style={{ marginBottom: '1rem' }}>
						<Notice status="warning" isDismissible={false}>
							<p style={{ margin: 0 }}>
								{__('This is a preview. The actual button will work on the frontend.', 'digicommerce')}
							</p>
						</Notice>
					</div>

					<SelectControl
						label={__('Select Product', 'digicommerce')}
						value={productId}
						options={products}
						onChange={(value) => setAttributes({
							productId: parseInt(value),
							variationId: -1
						})}
					/>

					{productId > 0 && (
						<>
							<RadioControl
								label={__('Button Action', 'digicommerce')}
								selected={actionType}
								options={[
									{ label: __('Link to Product Page', 'digicommerce'), value: 'link' },
									{ label: __('Direct to Checkout', 'digicommerce'), value: 'checkout' }
								]}
								onChange={(value) => setAttributes({ actionType: value })}
							/>

							{productVariations.length > 0 && actionType === 'checkout' && (
								<SelectControl
									label={__('Select Variation', 'digicommerce')}
									value={variationId}
									options={productVariations}
									onChange={(value) => setAttributes({ variationId: parseInt(value) })}
								/>
							)}
						</>
					)}

					<TextControl
						label={__('Button Text', 'digicommerce')}
						value={buttonText}
						onChange={(value) => setAttributes({ buttonText: value })}
						placeholder={__('View Product', 'digicommerce')}
					/>

					<ToggleControl
						label={__('Show Price', 'digicommerce')}
						checked={showPrice}
						onChange={(value) => setAttributes({ showPrice: value })}
					/>

					<ToggleControl
						label={__('Open in New Tab', 'digicommerce')}
						checked={openInNewTab}
						onChange={(value) => setAttributes({ openInNewTab: value })}
					/>
				</PanelBody>
			</InspectorControls>

			<div {...blockProps}>
				<button {...buttonProps}>
					{previewText}
				</button>
			</div>
		</>
	);
};

export default DigiCommerceProductButtonEdit;