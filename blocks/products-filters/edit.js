/**
 * Products Filters Block Edit Component
 */

const { InspectorControls, useBlockProps } = wp.blockEditor;
const { 
    PanelBody, 
    ToggleControl,
    TextControl,
    SelectControl,
    Notice
} = wp.components;
const { __ } = wp.i18n;

const DigiCommerceProductsFiltersEdit = ({ attributes, setAttributes }) => {
    const { 
        showCategories, 
        showTags, 
        showCount, 
        categoriesTitle, 
        tagsTitle,
        filterStyle 
    } = attributes;

    const blockProps = useBlockProps();

    // Sample data for preview
    const sampleCategories = [
        { id: 1, name: __('Digital Downloads', 'digicommerce'), count: 12 },
        { id: 2, name: __('Templates', 'digicommerce'), count: 8 },
        { id: 3, name: __('Courses', 'digicommerce'), count: 5 },
        { id: 4, name: __('Software', 'digicommerce'), count: 15 }
    ];

    const sampleTags = [
        { id: 1, name: __('Premium', 'digicommerce'), count: 10 },
        { id: 2, name: __('Bestseller', 'digicommerce'), count: 7 },
        { id: 3, name: __('New', 'digicommerce'), count: 4 },
        { id: 4, name: __('Featured', 'digicommerce'), count: 6 }
    ];

    const renderCheckboxFilters = (items, name) => (
        <div className="digicommerce-products-filters__checkboxes">
            {items.map((item) => (
                <label key={item.id} className="digicommerce-products-filters__checkbox-label">
                    <input 
                        type="checkbox" 
                        className="digicommerce-products-filters__checkbox"
                        disabled
                    />
                    <span className="digicommerce-products-filters__checkbox-text">
                        {item.name}
                        {showCount && (
                            <span className="digicommerce-products-filters__count">
                                ({item.count})
                            </span>
                        )}
                    </span>
                </label>
            ))}
        </div>
    );

    const renderDropdownFilter = (items, placeholder) => (
        <select className="digicommerce-products-filters__select" disabled>
            <option>{placeholder}</option>
            {items.map((item) => (
                <option key={item.id}>
                    {item.name}
                    {showCount && ` (${item.count})`}
                </option>
            ))}
        </select>
    );

    return (
        <>
            <InspectorControls>
                <PanelBody title={__('Filter Settings', 'digicommerce')} initialOpen={true}>
                    <div style={{ marginBottom: '1rem' }}>
                        <Notice status="warning" isDismissible={false}>
                            <p style={{ margin: 0 }}>{__('This is a preview. The filters will be functional on the frontend.', 'digicommerce')}</p>
                        </Notice>
                    </div>

                    <SelectControl
                        label={__('Filter Style', 'digicommerce')}
                        value={filterStyle}
                        options={[
                            { label: __('Checkboxes', 'digicommerce'), value: 'checkboxes' },
                            { label: __('Dropdown', 'digicommerce'), value: 'dropdown' }
                        ]}
                        onChange={(value) => setAttributes({ filterStyle: value })}
                    />

                    <ToggleControl
                        label={__('Show Categories', 'digicommerce')}
                        checked={showCategories}
                        onChange={(value) => setAttributes({ showCategories: value })}
						__nextHasNoMarginBottom={true}
                    />

                    {showCategories && (
                        <TextControl
                            label={__('Categories Title', 'digicommerce')}
                            value={categoriesTitle}
                            onChange={(value) => setAttributes({ categoriesTitle: value })}
                            placeholder={__('Categories', 'digicommerce')}
                        />
                    )}

                    <ToggleControl
                        label={__('Show Tags', 'digicommerce')}
                        checked={showTags}
                        onChange={(value) => setAttributes({ showTags: value })}
						__nextHasNoMarginBottom={true}
                    />

                    {showTags && (
                        <TextControl
                            label={__('Tags Title', 'digicommerce')}
                            value={tagsTitle}
                            onChange={(value) => setAttributes({ tagsTitle: value })}
                            placeholder={__('Tags', 'digicommerce')}
                        />
                    )}

                    <ToggleControl
                        label={__('Show Count', 'digicommerce')}
                        checked={showCount}
                        onChange={(value) => setAttributes({ showCount: value })}
                        help={__('Display the number of products in each category/tag.', 'digicommerce')}
						__nextHasNoMarginBottom={true}
                    />
                </PanelBody>
            </InspectorControls>

            <div {...blockProps}>
                <form className="digicommerce-products-filters__form">
                    {showCategories && (
                        <div className="digicommerce-products-filters__section">
                            <h3 className="digicommerce-products-filters__title">
                                {categoriesTitle || __('Categories', 'digicommerce')}
                            </h3>
                            {filterStyle === 'checkboxes' 
                                ? renderCheckboxFilters(sampleCategories, 'product_cat')
                                : renderDropdownFilter(sampleCategories, __('All Categories', 'digicommerce'))
                            }
                        </div>
                    )}

                    {showTags && (
                        <div className="digicommerce-products-filters__section">
                            <h3 className="digicommerce-products-filters__title">
                                {tagsTitle || __('Tags', 'digicommerce')}
                            </h3>
                            {filterStyle === 'checkboxes'
                                ? renderCheckboxFilters(sampleTags, 'product_tag')
                                : renderDropdownFilter(sampleTags, __('All Tags', 'digicommerce'))
                            }
                        </div>
                    )}

                    <div className="digicommerce-products-filters__actions">
                        <button 
                            type="button" 
                            className="digicommerce-products-filters__submit wp-element-button"
                            disabled
                        >
                            {__('Apply Filters', 'digicommerce')}
                        </button>
                    </div>
                </form>
            </div>
        </>
    );
};

export default DigiCommerceProductsFiltersEdit;