/**
 * Success Message Block Edit Component
 */

const { useBlockProps, InspectorControls, RichText } = wp.blockEditor;
const { PanelBody, ToggleControl, TextControl, Notice } = wp.components;
const { __ } = wp.i18n;
const { useState } = wp.element;

const DigiCommerceSuccessMessageEdit = ({ attributes, setAttributes }) => {
    const { 
        successTitle, 
        successMessage, 
        expiredTitle, 
        expiredMessage, 
        expiredButtonText, 
        showIcon 
    } = attributes;
    
    const [isExpiredView, setIsExpiredView] = useState(false);
    
    const blockProps = useBlockProps();

    return (
        <>
            <InspectorControls>
                <PanelBody title={__('Preview Settings', 'digicommerce')} initialOpen={true}>
                    <div style={{ marginBottom: '1rem' }}>
                        <Notice status="info" isDismissible={false}>
                            <p style={{ margin: 0 }}>
                                {__('Toggle between success and expired session preview. Use {name} to display customer name.', 'digicommerce')}
                            </p>
                        </Notice>
                    </div>
                    <ToggleControl
                        label={__('Show Expired Session Preview', 'digicommerce')}
                        checked={isExpiredView}
                        onChange={(value) => setIsExpiredView(value)}
                        help={__('Preview how the block looks when session is expired.', 'digicommerce')}
                    />
                </PanelBody>

                <PanelBody title={__('Expired Session Settings', 'digicommerce')} initialOpen={false}>
                    <div style={{ marginBottom: '1rem' }}>
                        <Notice status="warning" isDismissible={false}>
                            <p style={{ margin: 0 }}>
                                {__('These settings apply when the order session has expired or is invalid.', 'digicommerce')}
                            </p>
                        </Notice>
                    </div>
                    
                    <ToggleControl
                        label={__('Show Icon', 'digicommerce')}
                        checked={showIcon}
                        onChange={(value) => setAttributes({ showIcon: value })}
                        help={__('Display the clock icon for expired sessions.', 'digicommerce')}
                    />

                    <TextControl
                        label={__('Button Text', 'digicommerce')}
                        value={expiredButtonText}
                        onChange={(value) => setAttributes({ expiredButtonText: value })}
                        placeholder={__('Go to your account', 'digicommerce')}
                    />
                </PanelBody>
            </InspectorControls>

            <div {...blockProps}>
                {!isExpiredView ? (
                    <div className="digicommerce-success-message-editor">
                        <RichText
                            tagName="h2"
                            className="digicommerce-success-title"
                            value={successTitle}
                            onChange={(value) => setAttributes({ successTitle: value })}
                            placeholder={__('Thank you for your purchase {name}!', 'digicommerce')}
                            allowedFormats={['core/bold', 'core/italic']}
                        />
                        <RichText
                            tagName="p"
                            className="digicommerce-success-subtitle"
                            value={successMessage}
                            onChange={(value) => setAttributes({ successMessage: value })}
                            placeholder={__('View the details of your order below.', 'digicommerce')}
                            allowedFormats={['core/bold', 'core/italic', 'core/link']}
                        />
                    </div>
                ) : (
                    <div className="digicommerce-expired-session has-text-align-center">
                        {showIcon && (
                            <div className="digicommerce-expired-icon">
                                <svg fill="none" viewBox="0 0 24 24" width="96" height="96" stroke="currentColor" aria-hidden="true">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        )}
                        <RichText
                            tagName="h2"
                            className="digicommerce-expired-title"
                            value={expiredTitle}
                            onChange={(value) => setAttributes({ expiredTitle: value })}
                            placeholder={__('Your session has expired', 'digicommerce')}
                            allowedFormats={['core/bold', 'core/italic']}
                        />
                        <RichText
                            tagName="p"
                            className="digicommerce-expired-text"
                            value={expiredMessage}
                            onChange={(value) => setAttributes({ expiredMessage: value })}
                            placeholder={__('Your session has expired. Please log in to view your orders.', 'digicommerce')}
                            allowedFormats={['core/bold', 'core/italic']}
                        />
                        <div className="digicommerce-expired-action">
                            <button className="digicommerce-button wp-element-button">
                                {expiredButtonText || __('Go to your account', 'digicommerce')}
                            </button>
                        </div>
                    </div>
                )}
            </div>
        </>
    );
};

export default DigiCommerceSuccessMessageEdit;