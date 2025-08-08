/**
 * Products Grid Block Edit Component
 */

const { InspectorControls, useBlockProps } = wp.blockEditor;
const { 
    PanelBody, 
    RangeControl,
    ToggleControl,
    Notice
} = wp.components;
const { __ } = wp.i18n;

const DigiCommerceProductsGridEdit = ({ attributes, setAttributes }) => {
    const { 
        columns, 
        rows, 
        showPagination, 
        showImage, 
        showTitle, 
        showPrice, 
        showButton 
    } = attributes;

    const blockProps = useBlockProps();

    // 6 fake product images from Unsplash
    const productImages = [
        'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=400&h=600&fit=crop&crop=center',
        'https://images.unsplash.com/photo-1572635196237-14b3f281503f?w=400&h=600&fit=crop&crop=center',
        'https://images.unsplash.com/photo-1560472354-b33ff0c44a43?w=400&h=600&fit=crop&crop=center',
        'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=400&h=600&fit=crop&crop=center',
        'https://images.unsplash.com/photo-1549298916-b41d501d3772?w=400&h=600&fit=crop&crop=center',
        'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=400&h=600&fit=crop&crop=center'
    ];

    // Generate fake products for preview
    const generateFakeProducts = () => {
        const productCount = Math.min(columns * rows, 12); // Limit preview to 12 items
        const products = [];
        
        // Translatable product names
        const productNames = [
            __('Premium Digital Template', 'digicommerce'),
            __('Professional eBook Bundle', 'digicommerce'),
            __('Video Course Collection', 'digicommerce'),
            __('Software License Pack', 'digicommerce'),
            __('Design Assets Library', 'digicommerce'),
            __('Audio Masterclass', 'digicommerce'),
            __('Photography Presets', 'digicommerce'),
            __('Web Development Kit', 'digicommerce'),
            __('Marketing Templates', 'digicommerce'),
            __('Business Documents Pack', 'digicommerce'),
            __('Creative Graphics Bundle', 'digicommerce'),
            __('Learning Resources Set', 'digicommerce')
        ];

        const prices = [
            { regular: __('49.99', 'digicommerce'), sale: __('39.99', 'digicommerce') },
            { regular: __('29.99', 'digicommerce'), sale: null },
            { regular: __('99.99', 'digicommerce'), sale: __('79.99', 'digicommerce') },
            { regular: __('19.99', 'digicommerce'), sale: null },
            { regular: __('149.99', 'digicommerce'), sale: __('119.99', 'digicommerce') },
            { regular: __('59.99', 'digicommerce'), sale: __('44.99', 'digicommerce') }
        ];

        for (let i = 0; i < productCount; i++) {
            const priceData = prices[i % prices.length];
            products.push({
                id: i + 1,
                title: productNames[i % productNames.length],
                price: priceData.regular,
                salePrice: priceData.sale,
                image: productImages[i % productImages.length] // Cycle through 6 images
            });
        }

        return products;
    };

    const fakeProducts = generateFakeProducts();

    const renderProduct = (product) => {
        return (
            <article key={product.id} className="digicommerce-products-grid__product">
                <div className="digicommerce-products-grid__product-inner">
                    {showImage && (
                        <div className="digicommerce-products-grid__product-image">
                            <img src={product.image} alt={product.title} />
                        </div>
                    )}
                    
                    <div className="digicommerce-products-grid__product-content">
                        {showTitle && (
                            <h3 className="digicommerce-products-grid__product-title">
                                <a href="#">{product.title}</a>
                            </h3>
                        )}
                        
                        {showPrice && (
                            <div className="digicommerce-products-grid__product-price">
                                {product.salePrice ? (
                                    <>
                                        <span className="digicommerce-price-sale">
                                            <span className="price-wrapper">
                                                <span className="price-symbol">$</span>
                                                <span className="price">{product.salePrice}</span>
                                            </span>
                                        </span>
                                        <span className="digicommerce-price-regular">
                                            <span className="price-wrapper">
                                                <span className="price-symbol">$</span>
                                                <span className="price">{product.price}</span>
                                            </span>
                                        </span>
                                    </>
                                ) : (
                                    <span className="digicommerce-price">
                                        <span className="price-wrapper">
                                            <span className="price-symbol">$</span>
                                            <span className="price">{product.price}</span>
                                        </span>
                                    </span>
                                )}
                            </div>
                        )}
                        
                        {showButton && (
                            <div className="digicommerce-products-grid__product-button">
                                <a href="#" className="digicommerce-button">
                                    {__('View Product', 'digicommerce')}
                                </a>
                            </div>
                        )}
                    </div>
                </div>
            </article>
        );
    };

    const renderPagination = () => {
        if (!showPagination) return null;

        return (
            <nav className="digicommerce-products-grid__pagination">
                <ul className="page-numbers">
                    <li><span aria-current="page" className="page-numbers current">1</span></li>
                    <li><a className="page-numbers" href="#">2</a></li>
                    <li><a className="page-numbers" href="#">3</a></li>
                    <li><a className="next page-numbers" href="#">{__('Next', 'digicommerce')} →</a></li>
                </ul>
            </nav>
        );
    };

    return (
        <>
            <InspectorControls>
                <PanelBody title={__('Layout', 'digicommerce')} initialOpen={true}>
                    <div style={{ marginBottom: '1rem' }}>
                        <Notice status="warning" isDismissible={false}>
                            <p style={{ margin: 0 }}>{__('This block automatically displays products based on the current page context (archive, category, or tag).', 'digicommerce')}</p>
                        </Notice>
                    </div>

                    <RangeControl
                        label={__('Columns', 'digicommerce')}
                        value={columns}
                        onChange={(value) => setAttributes({ columns: value })}
                        min={1}
                        max={6}
                    />

                    <RangeControl
                        label={__('Rows', 'digicommerce')}
                        value={rows}
                        onChange={(value) => setAttributes({ rows: value })}
                        min={1}
                        max={10}
                        help={__('Number of rows to display before pagination.', 'digicommerce')}
                    />
                </PanelBody>

                <PanelBody title={__('Display Settings', 'digicommerce')} initialOpen={false}>
                    <ToggleControl
                        label={__('Show Product Image', 'digicommerce')}
                        checked={showImage}
                        onChange={(value) => setAttributes({ showImage: value })}
                    />

                    <ToggleControl
                        label={__('Show Product Title', 'digicommerce')}
                        checked={showTitle}
                        onChange={(value) => setAttributes({ showTitle: value })}
                    />

                    <ToggleControl
                        label={__('Show Product Price', 'digicommerce')}
                        checked={showPrice}
                        onChange={(value) => setAttributes({ showPrice: value })}
                    />

                    <ToggleControl
                        label={__('Show View Button', 'digicommerce')}
                        checked={showButton}
                        onChange={(value) => setAttributes({ showButton: value })}
                    />

                    <ToggleControl
                        label={__('Show Pagination', 'digicommerce')}
                        checked={showPagination}
                        onChange={(value) => setAttributes({ showPagination: value })}
                    />
                </PanelBody>
            </InspectorControls>

            <div {...blockProps}>
                <div className={`digicommerce-products-grid__products digicommerce-products-grid__products--cols-${columns}`}>
                    {fakeProducts.map((product) => renderProduct(product))}
                </div>
                {renderPagination()}
            </div>
        </>
    );
};

export default DigiCommerceProductsGridEdit;