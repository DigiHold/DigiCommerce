/**
 * Product Gallery Block Edit Component
 */

const { InspectorControls, useBlockProps } = wp.blockEditor;
const { 
    PanelBody, 
    ToggleControl,
    SelectControl,
    Notice
} = wp.components;
const { __ } = wp.i18n;
const { useState } = wp.element;

const THUMBNAIL_POSITION_OPTIONS = [
    { label: __('Bottom', 'digicommerce'), value: 'bottom' },
    { label: __('Top', 'digicommerce'), value: 'top' }
];

const DigiCommerceProductGalleryEdit = ({ attributes, setAttributes }) => {
    const { showThumbnails, thumbnailsPosition, enableLightbox } = attributes;
    const [activeImageIndex, setActiveImageIndex] = useState(0);

    // Sample gallery data for preview
    const sampleGallery = [
        {
            id: 1,
            src: 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=600&h=600&fit=crop&crop=center',
            thumb: 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=150&h=150&fit=crop&crop=center',
            alt: 'Product Image 1'
        },
        {
            id: 2,
            src: 'https://images.unsplash.com/photo-1572635196237-14b3f281503f?w=600&h=600&fit=crop&crop=center',
            thumb: 'https://images.unsplash.com/photo-1572635196237-14b3f281503f?w=150&h=150&fit=crop&crop=center',
            alt: 'Product Image 2'
        },
        {
            id: 3,
            src: 'https://images.unsplash.com/photo-1560472354-b33ff0c44a43?w=600&h=600&fit=crop&crop=center',
            thumb: 'https://images.unsplash.com/photo-1560472354-b33ff0c44a43?w=150&h=150&fit=crop&crop=center',
            alt: 'Product Image 3'
        },
        {
            id: 4,
            src: 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=600&h=600&fit=crop&crop=center',
            thumb: 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=150&h=150&fit=crop&crop=center',
            alt: 'Product Image 4'
        },
        {
            id: 5,
            src: 'https://images.unsplash.com/photo-1549298916-b41d501d3772?w=600&h=600&fit=crop&crop=center',
            thumb: 'https://images.unsplash.com/photo-1549298916-b41d501d3772?w=150&h=150&fit=crop&crop=center',
            alt: 'Product Image 5'
        }
    ];

    const blockProps = useBlockProps({
        className: 'product-gallery'
    });

    const handleThumbnailClick = (index) => {
        setActiveImageIndex(index);
    };

    const renderMainImage = () => {
        const activeImage = sampleGallery[activeImageIndex] || sampleGallery[0];
        
        return (
            <div className="gallery-main-container">
                <img 
                    src={activeImage.src} 
                    alt={activeImage.alt}
                    className="gallery-main-image"
                />
            </div>
        );
    };

    const renderThumbnails = () => {
        if (!showThumbnails || sampleGallery.length <= 1) {
            return null;
        }

        const remainingImages = sampleGallery.slice(1);

        return (
            <div className="gallery-thumbnails">
                {remainingImages.map((image, index) => (
                    <div key={image.id} className="gallery-thumbnail-container">
                        {enableLightbox ? (
                            <button
                                type="button"
                                className="gallery-thumbnail-button"
                                onClick={() => handleThumbnailClick(index + 1)}
                                style={{
                                    border: (index + 1) === activeImageIndex ? '2px solid #ccb161' : 'none'
                                }}
                            >
                                <img 
                                    src={image.thumb} 
                                    alt={image.alt}
                                    className="gallery-thumbnail-image"
                                />
                            </button>
                        ) : (
                            <img 
                                src={image.thumb} 
                                alt={image.alt}
                                className="gallery-thumbnail-image"
                            />
                        )}
                    </div>
                ))}
            </div>
        );
    };

    const renderGallery = () => {
        const mainImage = renderMainImage();
        const thumbnails = renderThumbnails();

        if (thumbnailsPosition === 'top') {
            return (
                <>
                    {thumbnails}
                    {mainImage}
                </>
            );
        }

        return (
            <>
                {mainImage}
                {thumbnails}
            </>
        );
    };

    return (
        <>
            <InspectorControls>
                <PanelBody title={__('Gallery Settings', 'digicommerce')} initialOpen={true}>
					<div style={{ marginBottom: '1rem' }}>
						<Notice status="warning" isDismissible={false}>
							<p style={{ margin: 0 }}>{__('This is a preview with sample images. The actual product gallery will be displayed on the frontend.', 'digicommerce')}</p>
						</Notice>
					</div>
                    
                    <ToggleControl
                        label={__('Show Thumbnails', 'digicommerce')}
                        checked={showThumbnails}
                        onChange={(value) => setAttributes({ showThumbnails: value })}
                        help={__('Display thumbnail images for gallery navigation.', 'digicommerce')}
                    />

                    {showThumbnails && (
                        <SelectControl
                            label={__('Thumbnails Position', 'digicommerce')}
                            value={thumbnailsPosition}
                            options={THUMBNAIL_POSITION_OPTIONS}
                            onChange={(value) => setAttributes({ thumbnailsPosition: value })}
                            help={__('Choose where to display the thumbnail images.', 'digicommerce')}
                        />
                    )}

                    <ToggleControl
                        label={__('Enable Lightbox', 'digicommerce')}
                        checked={enableLightbox}
                        onChange={(value) => setAttributes({ enableLightbox: value })}
                        help={__('Allow users to view full-size images in a lightbox.', 'digicommerce')}
                    />
                </PanelBody>
            </InspectorControls>

            <div {...blockProps}>
                {renderGallery()}
            </div>
        </>
    );
};

export default DigiCommerceProductGalleryEdit;