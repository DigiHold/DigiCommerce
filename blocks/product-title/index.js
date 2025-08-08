/**
 * DigiCommerce Product Title Block
 */

import DigiCommerceProductTitleEdit from './edit';
import DigiCommerceProductTitleSave from './save';

const { registerBlockType } = wp.blocks;

registerBlockType('digicommerce/product-title', {
	icon: {
        src: <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" width="24" height="24" aria-hidden="true" focusable="false"><path d="M0 48c0-8.8 7.2-16 16-16l64 0 64 0c8.8 0 16 7.2 16 16s-7.2 16-16 16L96 64l0 160 256 0 0-160-48 0c-8.8 0-16-7.2-16-16s7.2-16 16-16l64 0 64 0c8.8 0 16 7.2 16 16s-7.2 16-16 16l-48 0 0 176 0 208 48 0c8.8 0 16 7.2 16 16s-7.2 16-16 16l-128 0c-8.8 0-16-7.2-16-16s7.2-16 16-16l48 0 0-192L96 256l0 192 48 0c8.8 0 16 7.2 16 16s-7.2 16-16 16L16 480c-8.8 0-16-7.2-16-16s7.2-16 16-16l48 0 0-208L64 64 16 64C7.2 64 0 56.8 0 48z"></path></svg>,
        foreground: '#ccb161'
    },
    edit: DigiCommerceProductTitleEdit,
    save: DigiCommerceProductTitleSave
});