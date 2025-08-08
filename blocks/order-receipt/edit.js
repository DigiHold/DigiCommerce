/**
 * Order Receipt Block Edit Component
 */

const { InspectorControls, useBlockProps } = wp.blockEditor;
const { PanelBody, Notice } = wp.components;
const { __ } = wp.i18n;

const DigiCommerceOrderReceiptEdit = ({ attributes, setAttributes }) => {
    const blockProps = useBlockProps();

    return (
        <>
            <InspectorControls>
                <PanelBody title={__('Settings', 'digicommerce')} initialOpen={true}>
                    <Notice status="warning" isDismissible={false}>
                        <p style={{ margin: 0 }}>
                            {__('This is a preview. The actual order receipt will be displayed on the success page.', 'digicommerce')}
                        </p>
                    </Notice>
                </PanelBody>
            </InspectorControls>

            <div {...blockProps}>
                <div className="digicommerce-receipt-header">
                    <div className="digicommerce-receipt-header__content">
                        <div className="digicommerce-receipt-header__logo">
                            <div style={{ width: '160px', height: '60px', background: '#f0f0f0', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                                {__('Logo', 'digicommerce')}
                            </div>
                        </div>
                        <div className="digicommerce-receipt-header__invoice">
                            <div className="digicommerce-receipt-header__order-id">
                                {__('Invoice ID: #0001', 'digicommerce')}
                            </div>
                            <div className="digicommerce-receipt-header__order-date">
                                <strong>{__('Date:', 'digicommerce')}</strong> {new Date().toLocaleDateString()}
                            </div>
                        </div>
                    </div>

                    <div className="digicommerce-receipt-info">
                        <div className="digicommerce-receipt-info__business">
                            <span className="digicommerce-receipt-info__business-name">{__('Your Business Name', 'digicommerce')}</span>
                            <div className="digicommerce-receipt-info__business-address">
                                <span>{__('123 Business Street', 'digicommerce')}</span>
                                <span>{__('New York, NY 10001', 'digicommerce')}</span>
                                <span>{__('United States', 'digicommerce')}</span>
                            </div>
                        </div>

                        <div className="digicommerce-receipt-info__billing">
                            <span className="digicommerce-receipt-info__billing-company">{__('Customer Company', 'digicommerce')}</span>
                            <div className="digicommerce-receipt-info__billing-address">
                                <span>{__('John Doe', 'digicommerce')}</span>
                                <span>{__('456 Customer Ave', 'digicommerce')}</span>
                                <span>{__('Los Angeles, CA 90001', 'digicommerce')}</span>
                                <span>{__('United States', 'digicommerce')}</span>
                            </div>
                            <div className="digicommerce-receipt-info__status">
                                <strong>{__('Status:', 'digicommerce')}</strong> {__('Completed', 'digicommerce')}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
};

export default DigiCommerceOrderReceiptEdit;