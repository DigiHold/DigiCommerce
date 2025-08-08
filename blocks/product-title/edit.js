/**
 * Product Title Block Edit Component
 */

const { InspectorControls, BlockControls, useBlockProps } = wp.blockEditor;
const { 
    PanelBody, 
    SelectControl,
    Notice,
    ToolbarGroup,
    ToolbarDropdownMenu
} = wp.components;
const { __ } = wp.i18n;

const HEADING_LEVELS = [
    { label: __('H1', 'digicommerce'), value: 'h1' },
    { label: __('H2', 'digicommerce'), value: 'h2' },
    { label: __('H3', 'digicommerce'), value: 'h3' },
    { label: __('H4', 'digicommerce'), value: 'h4' },
    { label: __('H5', 'digicommerce'), value: 'h5' },
    { label: __('H6', 'digicommerce'), value: 'h6' }
];

const DigiCommerceProductTitleEdit = ({ attributes, setAttributes }) => {
    const { tagName } = attributes;

    const TagName = tagName || 'h1';

    const blockProps = useBlockProps();

    // Toolbar controls for heading level
    const toolbarControls = HEADING_LEVELS.map((level) => ({
        title: level.label,
        isActive: tagName === level.value,
        onClick: () => setAttributes({ tagName: level.value })
    }));

    return (
        <>
            <BlockControls>
                <ToolbarGroup>
                    <ToolbarDropdownMenu
                        icon="heading"
                        label={__('Heading Level', 'digicommerce')}
                        controls={toolbarControls}
                    />
                </ToolbarGroup>
            </BlockControls>

            <InspectorControls>
                <PanelBody title={__('Settings', 'digicommerce')} initialOpen={true}>
					<div style={{ marginBottom: '1rem' }}>
						<Notice status="warning" isDismissible={false}>
							<p style={{ margin: 0 }}>{__('This is a preview. The actual product title will be displayed on the frontend.', 'digicommerce')}</p>
						</Notice>
					</div>
                    
                    <SelectControl
                        label={__('HTML Tag', 'digicommerce')}
                        value={tagName}
                        options={HEADING_LEVELS}
                        onChange={(value) => setAttributes({ tagName: value })}
                    />
                </PanelBody>
            </InspectorControls>

            <TagName {...blockProps}>
                Premium Digital Product
            </TagName>
        </>
    );
};

export default DigiCommerceProductTitleEdit;