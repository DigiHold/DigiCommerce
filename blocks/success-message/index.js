/**
 * DigiCommerce Success Message Block
 */

import DigiCommerceSuccessMessageEdit from './edit';
import DigiCommerceSuccessMessageSave from './save';

const { registerBlockType } = wp.blocks;

registerBlockType('digicommerce/success-message', {
    icon: {
        src: <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" width="24" height="24"><path d="M320 96C443.7 96 544 196.3 544 320C544 443.7 443.7 544 320 544C196.3 544 96 443.7 96 320C96 196.3 196.3 96 320 96zM320 576C461.4 576 576 461.4 576 320C576 178.6 461.4 64 320 64C178.6 64 64 178.6 64 320C64 461.4 178.6 576 320 576zM397.6 264.5C402.3 257 400 247.1 392.5 242.5C385 237.9 375.1 240.1 370.5 247.6L302.9 355.8L268.9 310.5C263.6 303.4 253.6 302 246.5 307.3C239.4 312.6 238 322.6 243.3 329.7L291.3 393.7C294.5 397.9 299.5 400.3 304.8 400.1C310.1 399.9 314.9 397.1 317.7 392.6L397.7 264.6z"/></svg>,
        foreground: '#ccb161'
    },
    edit: DigiCommerceSuccessMessageEdit,
    save: DigiCommerceSuccessMessageSave
});