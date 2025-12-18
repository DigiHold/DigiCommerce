/**
 * Product Features Block Edit Component
 */

const { InspectorControls, useBlockProps } = wp.blockEditor;
const { 
    PanelBody, 
    ToggleControl,
    TextControl,
    Notice
} = wp.components;
const { __ } = wp.i18n;

const DigiCommerceProductFeaturesEdit = ({ attributes, setAttributes }) => {
    const { showTitle, title, showBorders, alternateRows } = attributes;

    // Build CSS classes for preview
    const cssClasses = [];
    if (showBorders) {
        cssClasses.push('digicommerce-features-bordered');
    }
    if (alternateRows) {
        cssClasses.push('digicommerce-features-striped');
    }

    const blockProps = useBlockProps({
        className: cssClasses.join(' ')
    });

    const displayTitle = title || __('Features', 'digicommerce');

    // Fake data for editor preview
    const fakeFeatures = [
        { name: __('File Format', 'digicommerce'), text: 'PDF' },
        { name: __('Pages', 'digicommerce'), text: '250' },
        { name: __('Language', 'digicommerce'), text: 'English' },
        { name: __('File Size', 'digicommerce'), text: '15 MB' },
        { name: __('License', 'digicommerce'), text: 'Personal Use' }
    ];

    return (
        <>
            <InspectorControls>
                <PanelBody title={__('Settings', 'digicommerce')} initialOpen={true}>
					<div style={{ marginBottom: '1rem' }}>
						<Notice status="warning" isDismissible={false}>
							<p style={{ margin: 0 }}>{__('This is a preview. The actual product features will be displayed on the frontend.', 'digicommerce')}</p>
						</Notice>
					</div>
                    
                    <ToggleControl
                        label={__('Show Title', 'digicommerce')}
                        checked={showTitle}
                        onChange={(value) => setAttributes({ showTitle: value })}
                        help={__('Display a title above the features table.', 'digicommerce')}
						__nextHasNoMarginBottom={true}
                    />

                    {showTitle && (
                        <TextControl
                            label={__('Title', 'digicommerce')}
                            value={title}
                            onChange={(value) => setAttributes({ title: value })}
                            placeholder={__('Features', 'digicommerce')}
                            help={__('Leave empty to use default "Features" title.', 'digicommerce')}
                        />
                    )}

                    <ToggleControl
                        label={__('Show Borders', 'digicommerce')}
                        checked={showBorders}
                        onChange={(value) => setAttributes({ showBorders: value })}
                        help={__('Add borders to the features table.', 'digicommerce')}
						__nextHasNoMarginBottom={true}
                    />

                    <ToggleControl
                        label={__('Alternate Row Colors', 'digicommerce')}
                        checked={alternateRows}
                        onChange={(value) => setAttributes({ alternateRows: value })}
                        help={__('Apply alternating background colors to table rows.', 'digicommerce')}
						__nextHasNoMarginBottom={true}
                    />
                </PanelBody>
            </InspectorControls>

            <div {...blockProps}>
				{showTitle && (
					<h3 className="digicommerce-product-features__title">
						{displayTitle}
					</h3>
				)}
				
				<table className="digicommerce-product-features__table">
					<tbody>
						{fakeFeatures.map((feature, index) => (
							<tr 
								key={index} 
								className={`digicommerce-product-features__row ${index % 2 === 0 ? 'digicommerce-product-features__row-even' : 'digicommerce-product-features__row-odd'}`}
							>
								<td className="digicommerce-product-features__name">{feature.name}</td>
								<td className="digicommerce-product-features__value">{feature.text}</td>
							</tr>
						))}
					</tbody>
				</table>
			</div>
        </>
    );
};

export default DigiCommerceProductFeaturesEdit;