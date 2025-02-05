const { registerBlockType } = wp.blocks;
const { __ } = wp.i18n;

import ButtonEdit from '../../../blocks/button/edit';
import ButtonSave from '../../../blocks/button/save';
import ArchivesEdit from '../../../blocks/archives/edit';

// Register Button Block
registerBlockType('digicommerce/button', {
    apiVersion: 2,
    title: __('Button', 'digicommerce'),
    category: 'digicommerce',
    icon: {
        src: <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" width="24" height="24"><path d="M0 24C0 10.7 10.7 0 24 0L69.5 0c22 0 41.5 12.8 50.6 32l411 0c26.3 0 45.5 25 38.6 50.4l-41 152.3c-8.5 31.4-37 53.3-69.5 53.3l-288.5 0 5.4 28.5c2.2 11.3 12.1 19.5 23.6 19.5L488 336c13.3 0 24 10.7 24 24s-10.7 24-24 24l-288.3 0c-34.6 0-64.3-24.6-70.7-58.5L77.4 54.5c-.7-3.8-4-6.5-7.9-6.5L24 48C10.7 48 0 37.3 0 24zM128 464a48 48 0 1 1 96 0 48 48 0 1 1 -96 0zm336-48a48 48 0 1 1 0 96 48 48 0 1 1 0-96z"/></svg>,
    },
    description: __('Display a custom product button', 'digicommerce'),
    attributes: {
        productId: {
            type: 'number'
        },
        customTitle: {
            type: 'string'
        },
        showPrice: {
            type: 'boolean',
            default: true
        },
        subtitle: {
            type: 'string'
        },
        customClass: {
            type: 'string'
        },
        variationId: {
            type: 'string'
        }
    },
	supports: {
        spacing: {
            margin: true,
            padding: true
        },
        typography: {
            fontSize: true,
            lineHeight: true,
            __experimentalFontFamily: false,
            __experimentalFontStyle: true,
            __experimentalFontWeight: true,
            __experimentalLetterSpacing: true,
            __experimentalTextTransform: true,
            __experimentalTextDecoration: false
        },
		align: ['full', 'left', 'right', 'center'],
        __experimentalBorder: {
            color: true,
            radius: true,
            style: true,
            width: true
        },
        color: {
            text: true,
            background: true,
            gradients: true
        }
    },
	example: {
        attributes: {
            customTitle: __('My Product', 'digicommerce'),
            showPrice: true,
            subtitle: 'One-time purchase',
            customClass: '',
        },
        viewportWidth: 400
    },
    edit: ButtonEdit,
    save: ButtonSave,
});

// Register Archives Block
registerBlockType('digicommerce/archives', {
    apiVersion: 2,
    title: __('Archives', 'digicommerce'),
    category: 'digicommerce',
    icon: {
        src: <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" width="24" height="24"><path d="M88 64c4.4 0 8 3.6 8 8l0 48c0 4.4-3.6 8-8 8l-48 0c-4.4 0-8-3.6-8-8l0-48c0-4.4 3.6-8 8-8l48 0zM40 32C17.9 32 0 49.9 0 72l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48c0-22.1-17.9-40-40-40L40 32zM88 224c4.4 0 8 3.6 8 8l0 48c0 4.4-3.6 8-8 8l-48 0c-4.4 0-8-3.6-8-8l0-48c0-4.4 3.6-8 8-8l48 0zM40 192c-22.1 0-40 17.9-40 40l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48c0-22.1-17.9-40-40-40l-48 0zm0 192l48 0c4.4 0 8 3.6 8 8l0 48c0 4.4-3.6 8-8 8l-48 0c-4.4 0-8-3.6-8-8l0-48c0-4.4 3.6-8 8-8zM0 392l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48c0-22.1-17.9-40-40-40l-48 0c-22.1 0-40 17.9-40 40zM248 64c4.4 0 8 3.6 8 8l0 48c0 4.4-3.6 8-8 8l-48 0c-4.4 0-8-3.6-8-8l0-48c0-4.4 3.6-8 8-8l48 0zM200 32c-22.1 0-40 17.9-40 40l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48c0-22.1-17.9-40-40-40l-48 0zm0 192l48 0c4.4 0 8 3.6 8 8l0 48c0 4.4-3.6 8-8 8l-48 0c-4.4 0-8-3.6-8-8l0-48c0-4.4 3.6-8 8-8zm-40 8l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48c0-22.1-17.9-40-40-40l-48 0c-22.1 0-40 17.9-40 40zm88 152c4.4 0 8 3.6 8 8l0 48c0 4.4-3.6 8-8 8l-48 0c-4.4 0-8-3.6-8-8l0-48c0-4.4 3.6-8 8-8l48 0zm-48-32c-22.1 0-40 17.9-40 40l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48c0-22.1-17.9-40-40-40l-48 0zM360 64l48 0c4.4 0 8 3.6 8 8l0 48c0 4.4-3.6 8-8 8l-48 0c-4.4 0-8-3.6-8-8l0-48c0-4.4 3.6-8 8-8zm-40 8l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48c0-22.1-17.9-40-40-40l-48 0c-22.1 0-40 17.9-40 40zm88 152c4.4 0 8 3.6 8 8l0 48c0 4.4-3.6 8-8 8l-48 0c-4.4 0-8-3.6-8-8l0-48c0-4.4 3.6-8 8-8l48 0zm-48-32c-22.1 0-40 17.9-40 40l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48c0-22.1-17.9-40-40-40l-48 0zm0 192l48 0c4.4 0 8 3.6 8 8l0 48c0 4.4-3.6 8-8 8l-48 0c-4.4 0-8-3.6-8-8l0-48c0-4.4 3.6-8 8-8zm-40 8l0 48c0 22.1 17.9 40 40 40l48 0c22.1 0 40-17.9 40-40l0-48c0-22.1-17.9-40-40-40l-48 0c-22.1 0-40 17.9-40 40z"/></svg>,
    },
    description: __('Display a grid of products with customizable settings', 'digicommerce'),
    edit: ArchivesEdit,
    save: () => null
});