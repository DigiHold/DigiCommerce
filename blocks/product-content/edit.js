/**
 * Product Content Block Edit Component
 */

const { InspectorControls, useBlockProps } = wp.blockEditor;
const { 
    PanelBody, 
    ToggleControl,
    Notice
} = wp.components;
const { __ } = wp.i18n;

const DigiCommerceProductContentEdit = ({ attributes, setAttributes }) => {
    const { showTitle } = attributes;

    const blockProps = useBlockProps();

    // Fake content for editor preview
    const fakeContent = `
        <h3>Product Features</h3>
        <p>This comprehensive digital product includes everything you need to get started. Our expertly crafted content covers all the essential topics with practical examples and real-world applications.</p>
        
        <h3>What's Included</h3>
        <ul>
            <li>Complete step-by-step guide</li>
            <li>Downloadable resources and templates</li>
            <li>Video tutorials and demonstrations</li>
            <li>Bonus materials and case studies</li>
        </ul>
        
        <h3>Benefits</h3>
        <p>By using this product, you'll gain valuable insights and practical skills that can be immediately applied. Whether you're a beginner or looking to enhance your existing knowledge, this product provides the tools and knowledge you need to succeed.</p>
        
        <p><strong>Money-back guarantee:</strong> We're confident in the quality of our product. If you're not satisfied, we offer a full refund within 30 days of purchase.</p>
    `;

    const renderPreviewContent = () => {
        return (
            <>
                {showTitle && (
                    <h2 className="digicommerce-product-content__title">{__('Description', 'digicommerce')}</h2>
                )}
                <div 
                    className="digicommerce-product-content__body"
                    dangerouslySetInnerHTML={{ __html: fakeContent }}
                />
            </>
        );
    };

    return (
        <>
            <InspectorControls>
                <PanelBody title={__('Settings', 'digicommerce')} initialOpen={true}>
					<div style={{ marginBottom: '1rem' }}>
						<Notice status="warning" isDismissible={false}>
							<p style={{ margin: 0 }}>{__('This is a preview. The actual product content will be displayed on the frontend.', 'digicommerce')}</p>
						</Notice>
					</div>
                    
                    <ToggleControl
                        label={__('Show Title', 'digicommerce')}
                        checked={showTitle}
                        onChange={(value) => setAttributes({ showTitle: value })}
                        help={__('Display "Description" title above the content.', 'digicommerce')}
						__nextHasNoMarginBottom={true}
                    />
                </PanelBody>
            </InspectorControls>

            <div {...blockProps}>
                {renderPreviewContent()}
            </div>
        </>
    );
};

export default DigiCommerceProductContentEdit;