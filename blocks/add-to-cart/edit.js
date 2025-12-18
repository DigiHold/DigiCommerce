/**
 * Add to Cart Block Edit Component
 */

const { InspectorControls, useBlockProps } = wp.blockEditor;
const { 
    PanelBody, 
    TextControl,
    ToggleControl,
    Notice
} = wp.components;
const { __ } = wp.i18n;

const DigiCommerceAddToCartEdit = ({ attributes, setAttributes }) => {
    const { 
        buttonText, 
        showVariationLabels
    } = attributes;

    const blockProps = useBlockProps();

    // Mock variation data for preview
    const mockVariations = [
        { name: __('Tome 1', 'digicommerce'), price: '4', salePrice: '1', isDefault: true },
        { name: __('Tome 2', 'digicommerce'), price: '199', salePrice: '98' },
        { name: __('Tome 3', 'digicommerce'), price: '99' }
    ];

    return (
        <>
            <InspectorControls>
                <PanelBody title={__('Settings', 'digicommerce')} initialOpen={true}>
					<div style={{ marginBottom: '1rem' }}>
						<Notice status="warning" isDismissible={false}>
							<p style={{ margin: 0 }}>{__('This is a preview. The actual add to cart functionality will work on the frontend.', 'digicommerce')}</p>
						</Notice>
					</div>
                    
                    <TextControl
                        label={__('Button Text', 'digicommerce')}
                        value={buttonText}
                        onChange={(value) => setAttributes({ buttonText: value })}
                        placeholder={__('Purchase for $29.99', 'digicommerce')}
                        help={__('Leave empty to use automatic text based on product price.', 'digicommerce')}
                    />

                    <ToggleControl
                        label={__('Show Variation Labels', 'digicommerce')}
                        checked={showVariationLabels}
                        onChange={(value) => setAttributes({ showVariationLabels: value })}
                        help={__('Show "Select an option" label above variations.', 'digicommerce')}
                        __nextHasNoMarginBottom={true}
                    />
                </PanelBody>
            </InspectorControls>

            <div {...blockProps}>
                <form className="digicommerce-add-to-cart">
                    <div className="variation-prices">
                        {showVariationLabels && (
                            <p className="variation-label">
                                {__('Select an option', 'digicommerce')}
                            </p>
                        )}
                        
                        <div className="variations-container">
                            {mockVariations.map((variation, index) => (
                                <div key={index} className="variation-option">
                                    <input 
                                        type="radio" 
                                        id={`variation-${index}`}
                                        name="price_variation" 
                                        defaultChecked={variation.isDefault}
                                        disabled
                                    />
                                    <label htmlFor={`variation-${index}`} className="cursor-pointer default-transition">
                                        <span className="variation-name">{variation.name}</span>
                                        <span className="variation-pricing">
                                            {variation.salePrice ? (
                                                <>
                                                    <span className="price-wrapper variation-sale-price">
                                                        <span className="price-symbol">$</span>
                                                        <span className="price">{variation.salePrice}</span>
                                                    </span>
                                                    <span className="price-wrapper variation-regular-price text-sm line-through">
                                                        <span className="price-symbol">$</span>
                                                        <span className="price">{variation.price}</span>
                                                    </span>
                                                </>
                                            ) : (
                                                <span className="price-wrapper variation-price">
                                                    <span className="price-symbol">$</span>
                                                    <span className="price">{variation.price}</span>
                                                </span>
                                            )}
                                        </span>
                                    </label>
                                </div>
                            ))}
                        </div>
                    </div>
                    
                    <button 
                        type="button" 
                        className="add-to-cart-button wp-element-button" 
                        disabled
                    >
                        {buttonText || __('Select an option', 'digicommerce')}
                    </button>
                </form>
            </div>
        </>
    );
};

export default DigiCommerceAddToCartEdit;