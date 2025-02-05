const { __ } = wp.i18n;
const { useBlockProps, InspectorControls } = wp.blockEditor;
const {
    PanelBody,
    RangeControl,
    SelectControl,
    ToggleControl,
    Spinner,
	ComboboxControl,
    FormTokenField
} = wp.components;
const { useSelect } = wp.data;

export default function ArchivesEdit({ attributes, setAttributes }) {
    const {
        postsPerPage,
        columns,
        showTitle,
        showPrice,
        showButton,
        showPagination,
        selectedCategories,
        selectedTags
    } = attributes;

    const blockProps = useBlockProps({
        className: 'digicommerce-archive digicommerce py-12'
    });

    // Get taxonomies and terms using useSelect
    const { products, isLoading, categories, tags } = useSelect((select) => {
        const { getEntityRecords, isResolving } = select('core');
        
        // Build query parameters
        const query = {
            per_page: postsPerPage === -1 ? 100 : postsPerPage,
            _embed: true,
            post_type: 'digi_product'
        };

        // Add taxonomy queries if categories or tags are selected
        if (selectedCategories?.length > 0) {
            query.digi_product_cat = selectedCategories.join(',');
        }
        if (selectedTags?.length > 0) {
            query.digi_product_tag = selectedTags.join(',');
        }

        return {
            products: getEntityRecords('postType', 'digi_product', query),
            isLoading: isResolving('core', 'getEntityRecords', ['postType', 'digi_product', query]),
            categories: getEntityRecords('taxonomy', 'digi_product_cat', { per_page: -1 }) || [],
            tags: getEntityRecords('taxonomy', 'digi_product_tag', { per_page: -1 }) || []
        };
    }, [postsPerPage, selectedCategories, selectedTags]);

    // Format categories and tags for SelectControl
    const categoryOptions = categories?.map(cat => ({
        label: cat.name,
        value: cat.id.toString()
    })) || [];

    const tagOptions = tags?.map(tag => ({
        label: tag.name,
        value: tag.id.toString()
    })) || [];

    const renderPrice = (product) => {
        const priceMode = product.meta?.digi_price_mode || 'single';
        const singlePrice = product.meta?.digi_price;
        const salePrice = product.meta?.digi_sale_price;
        const priceVariations = product.meta?.digi_price_variations;

        if (priceMode === 'single' && singlePrice) {
            if (salePrice && parseFloat(salePrice) < parseFloat(singlePrice)) {
                return (
                    <div className="product-prices">
                        <span className="normal-price">
                            {formatPrice(salePrice)}
                        </span>
                        <span className="regular-price">
                            {formatPrice(singlePrice)}
                        </span>
                    </div>
                );
            }
            return (
                <span className="normal-price">
                    {formatPrice(singlePrice)}
                </span>
            );
        }

        if (priceMode === 'variations' && priceVariations?.length) {
            const prices = priceVariations.map(v => ({
                regular: parseFloat(v.price) || 0,
                sale: parseFloat(v.salePrice) || 0
            }));

            const lowestRegular = Math.min(...prices.map(p => p.regular));
            const validSalePrices = prices.filter(p => p.sale && p.sale < p.regular);
            const lowestSale = validSalePrices.length > 0 
                ? Math.min(...validSalePrices.map(p => p.sale))
                : null;

            if (lowestSale) {
                return (
                    <div className="product-prices">
                        <span className="from">{__('From:', 'digicommerce')}</span>
                        <span className="normal-price">
                            {formatPrice(lowestSale)}
                        </span>
                        <span className="regular-price">
                            {formatPrice(lowestRegular)}
                        </span>
                    </div>
                );
            }
            
            return (
                <div className="product-prices">
                    <span className="from">{__('From:', 'digicommerce')}</span>
                    <span className="price">
                        {formatPrice(lowestRegular)}
                    </span>
                </div>
            );
        }

        return null;
    };

    const formatPrice = (price) => {
        const currencies = window.digicommerceBlocksData?.currencies || {};
        const selectedCurrency = window.digicommerceBlocksData?.selectedCurrency || 'USD';
        const currencyPosition = window.digicommerceBlocksData?.currencyPosition || 'left';
        const currencySymbol = currencies[selectedCurrency]?.symbol || '$';

        if (!price && price !== 0) return '';
        
        const formattedPrice = parseFloat(price).toFixed(2);
        
        switch (currencyPosition) {
            case 'left':
                return `${currencySymbol}${formattedPrice}`;
            case 'right':
                return `${formattedPrice}${currencySymbol}`;
            case 'left_space':
                return `${currencySymbol} ${formattedPrice}`;
            case 'right_space':
                return `${formattedPrice} ${currencySymbol}`;
            default:
                return `${currencySymbol}${formattedPrice}`;
        }
    };

    return (
        <>
            <InspectorControls>
                <PanelBody title={__('Archive Settings', 'digicommerce')}>
				<div className="components-base-control">
                        <label className="components-base-control__label">
                            {__('Categories', 'digicommerce')}
                        </label>
                        <FormTokenField
                            value={
                                selectedCategories
                                    ? categoryOptions
                                        .filter(cat => selectedCategories.includes(cat.value))
                                        .map(cat => cat.label)
                                    : []
                            }
                            suggestions={categoryOptions.map(cat => cat.label)}
                            onChange={(tokens) => {
                                const newSelectedCategories = tokens
                                    .map(token => 
                                        categoryOptions.find(cat => cat.label === token)?.value
                                    )
                                    .filter(Boolean);
                                setAttributes({ selectedCategories: newSelectedCategories });
                            }}
                            placeholder={__('Select categories...', 'digicommerce')}
                            maxSuggestions={10}
                        />
                    </div>

                    <div className="components-base-control">
                        <label className="components-base-control__label">
                            {__('Tags', 'digicommerce')}
                        </label>
                        <FormTokenField
                            value={
                                selectedTags
                                    ? tagOptions
                                        .filter(tag => selectedTags.includes(tag.value))
                                        .map(tag => tag.label)
                                    : []
                            }
                            suggestions={tagOptions.map(tag => tag.label)}
                            onChange={(tokens) => {
                                const newSelectedTags = tokens
                                    .map(token => 
                                        tagOptions.find(tag => tag.label === token)?.value
                                    )
                                    .filter(Boolean);
                                setAttributes({ selectedTags: newSelectedTags });
                            }}
                            placeholder={__('Select tags...', 'digicommerce')}
                            maxSuggestions={10}
                        />
                    </div>

                    <RangeControl
                        label={__('Products per page', 'digicommerce')}
                        value={postsPerPage}
                        onChange={(value) => setAttributes({ postsPerPage: value })}
                        min={-1}
                        max={100}
                        help={__('-1 shows all products', 'digicommerce')}
                        __nextHasNoMarginBottom={true}
                    />
                    
                    <RangeControl
                        label={__('Columns', 'digicommerce')}
                        value={columns}
                        onChange={(value) => setAttributes({ columns: value })}
                        min={1}
                        max={6}
                        __nextHasNoMarginBottom={true}
                    />

                    <ToggleControl
                        label={__('Show product title', 'digicommerce')}
                        checked={showTitle}
                        onChange={(value) => setAttributes({ showTitle: value })}
                        __nextHasNoMarginBottom={true}
                    />

                    <ToggleControl
                        label={__('Show product price', 'digicommerce')}
                        checked={showPrice}
                        onChange={(value) => setAttributes({ showPrice: value })}
                        __nextHasNoMarginBottom={true}
                    />

                    <ToggleControl
                        label={__('Show product button', 'digicommerce')}
                        checked={showButton}
                        onChange={(value) => setAttributes({ showButton: value })}
                        __nextHasNoMarginBottom={true}
                    />

                    <ToggleControl
                        label={__('Show pagination', 'digicommerce')}
                        checked={showPagination}
                        onChange={(value) => setAttributes({ showPagination: value })}
                        __nextHasNoMarginBottom={true}
                    />
                </PanelBody>
            </InspectorControls>

            <div {...blockProps}>
                {isLoading ? (
                    <div style="text-align: center">
                        <Spinner />
                    </div>
                ) : products?.length ? (
                    <>
                        <div className={`dc-inner col-${columns}`}>
                            {products.map(product => (
                                <article key={product.id} className="product-card">
                                    <a href="#" className="product-link" onClick={(e) => e.preventDefault()}>
                                        {product._embedded?.['wp:featuredmedia']?.[0]?.source_url && (
                                            <div className="product-img">
                                                <img 
                                                    src={product._embedded['wp:featuredmedia'][0].source_url}
                                                    alt={product._embedded['wp:featuredmedia'][0].alt_text || product.title.rendered}
                                                />
                                            </div>
                                        )}

                                        <div className="product-content">
                                            {showTitle && (
                                                <h2 
                                                    dangerouslySetInnerHTML={{ __html: product.title.rendered }}
                                                />
                                            )}

                                            {showPrice && (
												<>
                                                	{renderPrice(product)}
												</>
                                            )}
                                        </div>
                                    </a>

                                    {showButton && (
                                        <div className="product-button">
                                            <a 
                                                href="#" 
                                                onClick={(e) => e.preventDefault()}
                                            >
                                                {__('View Product', 'digicommerce')}
                                            </a>
                                        </div>
                                    )}
                                </article>
                            ))}
                        </div>

                        {showPagination && (
                            <nav className="pagination">
                                <ul className="page-numbers">
                                    <span className="page-numbers current">1</span>
                                    <span className="page-numbers">2</span>
                                    <span className="page-numbers">3</span>
                                </ul>
                            </nav>
                        )}
                    </>
                ) : (
					<p className="no-product">
						{__('No products found.', 'digicommerce')}
					</p>
                )}
            </div>
        </>
    );
}