/**
 * Product Share Block Edit Component
 */

const { InspectorControls, useBlockProps } = wp.blockEditor;
const { 
    PanelBody, 
    ToggleControl,
    TextControl,
    CheckboxControl,
    Notice
} = wp.components;
const { __ } = wp.i18n;

const PLATFORM_OPTIONS = [
    { label: __('Facebook', 'digicommerce'), value: 'facebook' },
    { label: __('X (Twitter)', 'digicommerce'), value: 'twitter' },
    { label: __('Pinterest', 'digicommerce'), value: 'pinterest' },
    { label: __('LinkedIn', 'digicommerce'), value: 'linkedin' },
    { label: __('Email', 'digicommerce'), value: 'email' }
];

const DigiCommerceProductShareEdit = ({ attributes, setAttributes }) => {
    const { showTitle, title, platforms } = attributes;

    // WordPress automatically handles ALL styling including textAlign via useBlockProps
    const blockProps = useBlockProps();

    const handlePlatformChange = (platform, checked) => {
        const newPlatforms = checked 
            ? [...platforms, platform]
            : platforms.filter(p => p !== platform);
        setAttributes({ platforms: newPlatforms });
    };

    const getIcon = (platform) => {
        const icons = {
            facebook: <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="18" height="18" className="fill-current"><path d="M512 256C512 114.6 397.4 0 256 0S0 114.6 0 256C0 376 82.7 476.8 194.2 504.5V334.2H141.4V256h52.8V222.3c0-87.1 39.4-127.5 125-127.5c16.2 0 44.2 3.2 55.7 6.4V172c-6-.6-16.5-1-29.6-1c-42 0-58.2 15.9-58.2 57.2V256h83.6l-14.4 78.2H287V510.1C413.8 494.8 512 386.9 512 256h0z"/></svg>,
            twitter: <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="18" height="18" className="fill-current"><path d="M389.2 48h70.6L305.6 224.2 487 464H345L233.7 318.6 106.5 464H35.8L200.7 275.5 26.8 48H172.4L272.9 180.9 389.2 48zM364.4 421.8h39.1L151.1 88h-42L364.4 421.8z"/></svg>,
            pinterest: <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 496 512" width="18" height="18" className="fill-current"><path d="M496 256c0 137-111 248-248 248-25.6 0-50.2-3.9-73.4-11.1 10.1-16.5 25.2-43.5 30.8-65 3-11.6 15.4-59 15.4-59 8.1 15.4 31.7 28.5 56.8 28.5 74.8 0 128.7-68.8 128.7-154.3 0-81.9-66.9-143.2-152.9-143.2-107 0-163.9 71.8-163.9 150.1 0 36.4 19.4 81.7 50.3 96.1 4.7 2.2 7.2 1.2 8.3-3.3 .8-3.4 5-20.3 6.9-28.1 .6-2.5 .3-4.7-1.7-7.1-10.1-12.5-18.3-35.3-18.3-56.6 0-54.7 41.4-107.6 112-107.6 60.9 0 103.6 41.5 103.6 100.9 0 67.1-33.9 113.6-78 113.6-24.3 0-42.6-20.1-36.7-44.8 7-29.5 20.5-61.3 20.5-82.6 0-19-10.2-34.9-31.4-34.9-24.9 0-44.9 25.7-44.9 60.2 0 22 7.4 36.8 7.4 36.8s-24.5 103.8-29 123.2c-5 21.4-3 51.6-.9 71.2C65.4 450.9 0 361.1 0 256 0 119 111 8 248 8s248 111 248 248z"/></svg>,
            linkedin: <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" width="18" height="18" className="fill-current"><path d="M100.28 448H7.4V148.9h92.88zM53.79 108.1C24.09 108.1 0 83.5 0 53.8a53.79 53.79 0 0 1 107.58 0c0 29.7-24.1 54.3-53.79 54.3zM447.9 448h-92.68V302.4c0-34.7-.7-79.2-48.29-79.2-48.29 0-55.69 37.7-55.69 76.7V448h-92.78V148.9h89.08v40.8h1.3c12.4-23.5 42.69-48.3 87.88-48.3 94 0 111.28 61.9 111.28 142.3V448z"/></svg>,
            email: <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="18" height="18" className="fill-current"><path d="M48 64C21.5 64 0 85.5 0 112c0 15.1 7.1 29.3 19.2 38.4L236.8 313.6c11.4 8.5 27 8.5 38.4 0L492.8 150.4c12.1-9.1 19.2-23.3 19.2-38.4c0-26.5-21.5-48-48-48L48 64zM0 176L0 384c0 35.3 28.7 64 64 64l384 0c35.3 0 64-28.7 64-64l0-208L294.4 339.2c-22.8 17.1-54 17.1-76.8 0L0 176z"/></svg>
        };
        return icons[platform] || null;
    };

    const renderPreviewButtons = () => {
        const displayTitle = title || __('Share on:', 'digicommerce');
        
        return (
            <div {...blockProps}>
                {showTitle && (
                    <div className="digicommerce-share-title">{displayTitle}</div>
                )}
                <div className="digicommerce-share-buttons">
                    {platforms.map(platform => (
                        <span 
                            key={platform} 
                            className={`share-link digicommerce-share-link digicommerce-share-${platform}`}
                            data-platform={platform}
                        >
                            {getIcon(platform)}
                            <span className="sr-only">
                                {PLATFORM_OPTIONS.find(p => p.value === platform)?.label}
                            </span>
                        </span>
                    ))}
                </div>
            </div>
        );
    };

    return (
        <>
            <InspectorControls>
                <PanelBody title={__('Settings', 'digicommerce')} initialOpen={true}>
					<div style={{ marginBottom: '1rem' }}>
						<Notice status="warning" isDismissible={false}>
							<p style={{ margin: 0 }}>{__('This is a preview. The actual product sharing will be functional on the frontend.', 'digicommerce')}</p>
						</Notice>
					</div>
                    
                    <ToggleControl
                        label={__('Show Title', 'digicommerce')}
                        checked={showTitle}
                        onChange={(value) => setAttributes({ showTitle: value })}
                    />

                    {showTitle && (
                        <TextControl
                            label={__('Title Text', 'digicommerce')}
                            value={title}
                            onChange={(value) => setAttributes({ title: value })}
                            placeholder={__('Share on:', 'digicommerce')}
                        />
                    )}
                </PanelBody>

                <PanelBody title={__('Platforms', 'digicommerce')} initialOpen={false}>
                    {PLATFORM_OPTIONS.map(platform => (
                        <CheckboxControl
                            key={platform.value}
                            label={platform.label}
                            checked={platforms.includes(platform.value)}
                            onChange={(checked) => handlePlatformChange(platform.value, checked)}
                        />
                    ))}
                </PanelBody>
            </InspectorControls>

            {renderPreviewButtons()}
        </>
    );
};

export default DigiCommerceProductShareEdit;