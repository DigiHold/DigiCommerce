/**
 * Products Sorting Block Edit Component
 */

const { InspectorControls, useBlockProps } = wp.blockEditor;
const { 
    PanelBody, 
    ToggleControl,
    TextControl,
    Notice
} = wp.components;
const { __ } = wp.i18n;

const DigiCommerceProductsSortingEdit = ({ attributes, setAttributes }) => {
    const { showLabel, labelText } = attributes;

    const blockProps = useBlockProps();

    // Sample sorting options for preview
    const sortOptions = [
        { value: 'date', label: __('Latest', 'digicommerce') },
        { value: 'date-asc', label: __('Oldest', 'digicommerce') },
        { value: 'title', label: __('Name (A-Z)', 'digicommerce') },
        { value: 'title-desc', label: __('Name (Z-A)', 'digicommerce') },
        { value: 'price', label: __('Price (Low to High)', 'digicommerce') },
        { value: 'price-desc', label: __('Price (High to Low)', 'digicommerce') }
    ];

    return (
        <>
            <InspectorControls>
                <PanelBody title={__('Settings', 'digicommerce')} initialOpen={true}>
                    <div style={{ marginBottom: '1rem' }}>
                        <Notice status="warning" isDismissible={false}>
                            <p style={{ margin: 0 }}>{__('This is a preview. The sorting dropdown will be functional on the frontend.', 'digicommerce')}</p>
                        </Notice>
                    </div>

                    <ToggleControl
                        label={__('Show Label', 'digicommerce')}
                        checked={showLabel}
                        onChange={(value) => setAttributes({ showLabel: value })}
                        help={__('Display the label text before the dropdown.', 'digicommerce')}
                    />

                    {showLabel && (
                        <TextControl
                            label={__('Label Text', 'digicommerce')}
                            value={labelText}
                            onChange={(value) => setAttributes({ labelText: value })}
                            placeholder={__('Sort by:', 'digicommerce')}
                        />
                    )}
                </PanelBody>
            </InspectorControls>

            <div {...blockProps}>
                <form className="digicommerce-products-sorting__form">
                    {showLabel && (
                        <label 
                            htmlFor="digicommerce-orderby-preview" 
                            className="digicommerce-products-sorting__label"
                        >
                            {labelText || __('Sort by:', 'digicommerce')}
                        </label>
                    )}
                    
                    <select 
                        id="digicommerce-orderby-preview"
                        className="digicommerce-products-sorting__select"
                        aria-label={__('Sort products', 'digicommerce')}
                        disabled
                    >
                        {sortOptions.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </select>
                </form>
            </div>
        </>
    );
};

export default DigiCommerceProductsSortingEdit;