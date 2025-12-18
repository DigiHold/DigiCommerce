/**
 * Product Meta Block Edit Component
 */

const { InspectorControls, useBlockProps } = wp.blockEditor;
const { 
    PanelBody, 
    ToggleControl,
    SelectControl,
    TextControl,
    Notice
} = wp.components;
const { __ } = wp.i18n;

const LAYOUT_OPTIONS = [
    { label: __('Stacked', 'digicommerce'), value: 'stacked' },
    { label: __('Inline', 'digicommerce'), value: 'inline' }
];

const DigiCommerceProductMetaEdit = ({ attributes, setAttributes }) => {
    const { showCategories, showTags, layout, separator } = attributes;

    // WordPress automatically handles ALL styling including textAlign and fontSize via useBlockProps
    const blockProps = useBlockProps({
        className: `digicommerce-meta-layout-${layout}`
    });

    const renderPreviewMeta = () => {
        const elements = [];
        
        // Fake data for editor preview
        const fakeCategories = [
			__('Software', 'digicommerce'),
			__('Digital Tools', 'digicommerce'),
		];
        const fakeTags = [
			__('productivity', 'digicommerce'),
			__('business', 'digicommerce'),
			__('automation', 'digicommerce'),
		];

        if (showCategories && fakeCategories.length > 0) {
            elements.push(
                <div key="categories" className="digicommerce-meta-item digicommerce-meta-categories">
                    <span className="digicommerce-meta-label">{__('Category:', 'digicommerce')}</span>
                    <span className="digicommerce-meta-value">
                        {fakeCategories.map((category, index) => (
                            <span key={index}>
                                <a href="#" className="digicommerce-meta-link" onClick={(e) => e.preventDefault()}>
                                    {category}
                                </a>
                                {index < fakeCategories.length - 1 && separator}
                            </span>
                        ))}
                    </span>
                </div>
            );
        }

        if (showTags && fakeTags.length > 0) {
            elements.push(
                <div key="tags" className="digicommerce-meta-item digicommerce-meta-tags">
                    <span className="digicommerce-meta-label">{__('Tags:', 'digicommerce')}</span>
                    <span className="digicommerce-meta-value">
                        {fakeTags.map((tag, index) => (
                            <span key={index}>
                                <a href="#" className="digicommerce-meta-link" onClick={(e) => e.preventDefault()}>
                                    {tag}
                                </a>
                                {index < fakeTags.length - 1 && separator}
                            </span>
                        ))}
                    </span>
                </div>
            );
        }

        return elements.length > 0 ? elements : (
            <p className="digicommerce-meta-placeholder">
                {__('No meta information to display. Enable categories or tags in the settings.', 'digicommerce')}
            </p>
        );
    };

    return (
        <>
            <InspectorControls>
                <PanelBody title={__('Settings', 'digicommerce')} initialOpen={true}>
					<div style={{ marginBottom: '1rem' }}>
						<Notice status="warning" isDismissible={false}>
							<p style={{ margin: 0 }}>{__('This is a preview. The actual product categories and tags will be displayed on the frontend.', 'digicommerce')}</p>
						</Notice>
					</div>
                    
                    <ToggleControl
                        label={__('Show Categories', 'digicommerce')}
                        checked={showCategories}
                        onChange={(value) => setAttributes({ showCategories: value })}
                        help={__('Display product categories.', 'digicommerce')}
						__nextHasNoMarginBottom={true}
                    />

                    <ToggleControl
                        label={__('Show Tags', 'digicommerce')}
                        checked={showTags}
                        onChange={(value) => setAttributes({ showTags: value })}
                        help={__('Display product tags.', 'digicommerce')}
						__nextHasNoMarginBottom={true}
                    />

                    <SelectControl
                        label={__('Layout', 'digicommerce')}
                        value={layout}
                        options={LAYOUT_OPTIONS}
                        onChange={(value) => setAttributes({ layout: value })}
                        help={__('Choose how to display the meta information.', 'digicommerce')}
                    />

                    <TextControl
                        label={__('Separator', 'digicommerce')}
                        value={separator}
                        onChange={(value) => setAttributes({ separator: value })}
                        placeholder=", "
                        help={__('Character(s) used to separate multiple terms.', 'digicommerce')}
                    />
                </PanelBody>
            </InspectorControls>

            <div {...blockProps}>
                {renderPreviewMeta()}
            </div>
        </>
    );
};

export default DigiCommerceProductMetaEdit;