/**
 * Order Details Block Edit Component
 */

const { InspectorControls, useBlockProps } = wp.blockEditor;
const { PanelBody, Notice } = wp.components;
const { __ } = wp.i18n;

const DigiCommerceOrderDetailsEdit = ({ attributes, setAttributes }) => {
    const blockProps = useBlockProps();

    return (
        <>
            <InspectorControls>
                <PanelBody title={__('Settings', 'digicommerce')} initialOpen={true}>
                    <Notice status="warning" isDismissible={false}>
                        <p style={{ margin: 0 }}>
                            {__('This is a preview. The actual order details will be displayed on the success page.', 'digicommerce')}
                        </p>
                    </Notice>
                </PanelBody>
            </InspectorControls>

            <div {...blockProps}>
                <h2 className="digicommerce-order-details__title">{__('Order Details', 'digicommerce')}</h2>
                
                <table className="digicommerce-table">
                    <thead>
                        <tr>
                            <th>{__('Product', 'digicommerce')}</th>
                            <th className="end">{__('Total', 'digicommerce')}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <div className="digicommerce-order-details__product">
                                    <div className="digicommerce-order-details__product-name">
                                        {__('Premium Digital Product - Professional', 'digicommerce')}
                                    </div>
                                    <div className="digicommerce-order-details__download">
                                        <button type="button" className="digicommerce-order-details__download-btn download-item">
                                            <div className="icon">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="20" height="20" fill="currentColor">
                                                    <path d="m28 24v-4a1 1 0 0 0 -2 0v4a1 1 0 0 1 -1 1h-18a1 1 0 0 1 -1-1v-4a1 1 0 0 0 -2 0v4a3 3 0 0 0 3 3h18a3 3 0 0 0 3-3zm-6.38-5.22-5 4a1 1 0 0 1 -1.24 0l-5-4a1 1 0 0 1 1.24-1.56l3.38 2.7v-13.92a1 1 0 0 1 2 0v13.92l3.38-2.7a1 1 0 1 1 1.24 1.56z"/>
                                                </svg>
                                            </div>
                                            <span className="text">{__('Download', 'digicommerce')}</span>
                                        </button>
                                    </div>
                                </div>
                            </td>
                            <td className="end">
                                <span className="price-wrapper">
                                    <span className="price-symbol">$</span>
                                    <span className="price">99.00</span>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div className="digicommerce-order-details__product">
                                    <div className="digicommerce-order-details__product-name">
                                        {__('Digital Course Bundle', 'digicommerce')}
                                    </div>
                                    <div className="digicommerce-order-details__bundle">
                                        <div className="digicommerce-order-details__bundle-title">
                                            {__('Bundle includes:', 'digicommerce')}
                                        </div>
                                        <div className="digicommerce-order-details__bundle-product">
                                            <div className="digicommerce-order-details__bundle-product-name">
                                                {__('Course Module 1', 'digicommerce')}
                                            </div>
                                            <div className="digicommerce-order-details__download-group">
                                                <select className="digicommerce-order-details__file-select">
                                                    <option>{__('Lesson 1', 'digicommerce')}</option>
                                                    <option>{__('Lesson 2', 'digicommerce')}</option>
                                                </select>
                                                <button type="button" className="digicommerce-order-details__download-btn download-item">
                                                    <div className="icon">
                                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="20" height="20" fill="currentColor">
                                                            <path d="m28 24v-4a1 1 0 0 0 -2 0v4a1 1 0 0 1 -1 1h-18a1 1 0 0 1 -1-1v-4a1 1 0 0 0 -2 0v4a3 3 0 0 0 3 3h18a3 3 0 0 0 3-3zm-6.38-5.22-5 4a1 1 0 0 1 -1.24 0l-5-4a1 1 0 0 1 1.24-1.56l3.38 2.7v-13.92a1 1 0 0 1 2 0v13.92l3.38-2.7a1 1 0 1 1 1.24 1.56z"/>
                                                        </svg>
                                                    </div>
                                                    <span className="text">{__('Download', 'digicommerce')}</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td className="end">
                                <span className="price-wrapper">
                                    <span className="price-symbol">$</span>
                                    <span className="price">199.00</span>
                                </span>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th scope="row">{__('Subtotal:', 'digicommerce')}</th>
                            <td className="end">
                                <span className="price-wrapper">
                                    <span className="price-symbol">$</span>
                                    <span className="price">298.00</span>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">{__('VAT (20%):', 'digicommerce')}</th>
                            <td className="end">
                                <span className="price-wrapper">
                                    <span className="price-symbol">$</span>
                                    <span className="price">59.60</span>
                                </span>
                            </td>
                        </tr>
                        <tr className="order-total">
                            <th scope="row">{__('Total:', 'digicommerce')}</th>
                            <td className="end">
                                <span className="amount">
                                    <span className="price-wrapper">
                                        <span className="price-symbol">$</span>
                                        <span className="price">357.60</span>
                                    </span>
                                </span>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </>
    );
};

export default DigiCommerceOrderDetailsEdit;