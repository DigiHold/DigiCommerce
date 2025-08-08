/**
 * Product Description Block Edit Component
 */

const { InspectorControls, useBlockProps } = wp.blockEditor;
const { 
    PanelBody, 
    Notice
} = wp.components;
const { __ } = wp.i18n;

const DigiCommerceProductDescriptionEdit = ({ attributes, setAttributes }) => {
    const blockProps = useBlockProps();

    // Fake data for editor preview
    const fakeDescription = __('This is a premium digital product designed to help you achieve your goals. It includes comprehensive resources, detailed guides, and step-by-step instructions. Perfect for professionals and beginners alike, this product offers exceptional value and proven results.', 'digicommerce');

    return (
        <>
            <InspectorControls>
                <PanelBody title={__('Settings', 'digicommerce')} initialOpen={true}>
					<div style={{ marginBottom: '1rem' }}>
						<Notice status="warning" isDismissible={false}>
							<p style={{ margin: 0 }}>{__('This is a preview. The actual product description will be displayed on the frontend.', 'digicommerce')}</p>
						</Notice>
					</div>
                </PanelBody>
            </InspectorControls>

            <div {...blockProps}>
                <div dangerouslySetInnerHTML={{ __html: fakeDescription }} />
            </div>
        </>
    );
};

export default DigiCommerceProductDescriptionEdit;