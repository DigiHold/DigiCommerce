/**
 * Product Price Block Edit Component
 */

const { InspectorControls, useBlockProps } = wp.blockEditor;
const { 
    PanelBody, 
    ToggleControl,
    Notice
} = wp.components;
const { __ } = wp.i18n;

const DigiCommerceProductPriceEdit = ({ attributes, setAttributes }) => {
    const { showVariations } = attributes;

    // WordPress automatically handles ALL styling including textAlign and fontSize via useBlockProps
    const blockProps = useBlockProps();

    const renderPreviewPrice = () => {
        if (showVariations) {
            return (
                <div className="digicommerce-product-price__container digicommerce-product-price__container--variations">
                    <span className="digicommerce-product-price__from">{__('From:', 'digicommerce')}</span>
                    <span className="digicommerce-product-price__sale">
                        <span className="price-wrapper">
                            <span className="price-symbol">$</span>
                            <span className="price">39.99</span>
                        </span>
                    </span>
                    <span className="digicommerce-product-price__regular">
                        <span className="price-wrapper">
                            <span className="price-symbol">$</span>
                            <span className="price">49.99</span>
                        </span>
                    </span>
                </div>
            );
        }

        return (
            <div className="digicommerce-product-price__container">
                <span className="digicommerce-product-price__sale">
                    <span className="price-wrapper">
                        <span className="price-symbol">$</span>
                        <span className="price">39.99</span>
                    </span>
                </span>
                <span className="digicommerce-product-price__regular">
                    <span className="price-wrapper">
                        <span className="price-symbol">$</span>
                        <span className="price">49.99</span>
                    </span>
                </span>
            </div>
        );
    };

    return (
        <>
            <InspectorControls>
                <PanelBody title={__('Settings', 'digicommerce')} initialOpen={true}>
					<div style={{ marginBottom: '1rem' }}>
						<Notice status="warning" isDismissible={false}>
							<p style={{ margin: 0 }}>{__('This is a preview. The actual product price will be displayed on the frontend.', 'digicommerce')}</p>
						</Notice>
					</div>

                    <ToggleControl
                        label={__('Show Variations', 'digicommerce')}
                        checked={showVariations}
                        onChange={(value) => setAttributes({ showVariations: value })}
                        help={__('Display "From:" prefix for products with price variations.', 'digicommerce')}
						__nextHasNoMarginBottom={true}
                    />
                </PanelBody>
            </InspectorControls>

            <div {...blockProps}>
                {renderPreviewPrice()}
            </div>
        </>
    );
};

export default DigiCommerceProductPriceEdit;