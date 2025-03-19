(function(blocks, element, components, editor, i18n, data) {
    const { __ } = i18n;
    const { registerBlockType } = blocks;
    const { InspectorControls } = editor;
    const { PanelBody, SelectControl, RangeControl, ToggleControl } = components;
    const { Fragment } = element;
    const el = element.createElement;

    // Icon for the blocks
    const blockIcon = el('svg', { width: 24, height: 24, viewBox: '0 0 24 24' },
        el('path', { d: 'M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z' })
    );

    // Register single download block
    registerBlockType('gtdm/single-download', {
        title: __('GT Single Download', 'gtdownloads-manager'),
        description: __('Display a single download item', 'gtdownloads-manager'),
        icon: blockIcon,
        category: 'widgets',
        keywords: [__('download', 'gtdownloads-manager'), __('file', 'gtdownloads-manager'), __('resource', 'gtdownloads-manager')],
        
        attributes: {
            id: {
                type: 'number',
                default: 0
            },
            image: {
                type: 'string',
                default: 'medium'
            }
        },

        edit: function(props) {
            const { attributes, setAttributes } = props;
            const { id, image } = attributes;

            const downloads = gtdmBlocks.downloads || [];
            const imageSizes = gtdmBlocks.imageSizes || [];

            return el(
                Fragment,
                {},
                el(
                    InspectorControls,
                    {},
                    el(
                        PanelBody,
                        {
                            title: __('Download Settings', 'gtdownloads-manager'),
                            initialOpen: true
                        },
                        el(
                            SelectControl,
                            {
                                label: __('Select Download', 'gtdownloads-manager'),
                                value: id,
                                options: [
                                    { value: 0, label: __('-- Select a Download --', 'gtdownloads-manager') },
                                    ...downloads
                                ],
                                onChange: function(newId) {
                                    setAttributes({ id: parseInt(newId, 10) });
                                }
                            }
                        ),
                        el(
                            SelectControl,
                            {
                                label: __('Featured Image Size', 'gtdownloads-manager'),
                                value: image,
                                options: imageSizes.map(size => ({ value: size.value, label: size.label })),
                                onChange: function(newSize) {
                                    setAttributes({ image: newSize });
                                }
                            }
                        )
                    )
                ),
                el(
                    'div',
                    { className: 'gtdm-block-preview gtdm-single-download-preview' },
                    id === 0 
                        ? el('p', {}, __('Please select a download from the block settings.', 'gtdownloads-manager'))
                        : el('div', { className: 'gtdm-block-content' },
                            el('div', { className: 'gtdm-block-title' }, 
                                el('span', { className: 'dashicons dashicons-download' }),
                                ' ' + downloads.find(d => d.value === id)?.label || __('Download', 'gtdownloads-manager')
                            ),
                            el('p', { className: 'gtdm-block-description' },
                                __('This download will be displayed here with the selected settings.', 'gtdownloads-manager')
                            )
                        )
                )
            );
        },

        save: function() {
            // Dynamic block, so render is handled server-side
            return null;
        }
    });

    // Register downloads list block
    registerBlockType('gtdm/downloads-list', {
        title: __('GT Downloads List', 'gtdownloads-manager'),
        description: __('Display a list of downloads', 'gtdownloads-manager'),
        icon: blockIcon,
        category: 'widgets',
        keywords: [__('downloads', 'gtdownloads-manager'), __('files', 'gtdownloads-manager'), __('resources', 'gtdownloads-manager')],
        
        attributes: {
            category: {
                type: 'string',
                default: ''
            },
            perPage: {
                type: 'number',
                default: -1
            },
            page: {
                type: 'number',
                default: 1
            },
            type: {
                type: 'string',
                default: 'grid'
            },
            image: {
                type: 'string',
                default: 'medium'
            }
        },

        edit: function(props) {
            const { attributes, setAttributes } = props;
            const { category, perPage, type, image } = attributes;

            const categories = gtdmBlocks.categories || [];
            const imageSizes = gtdmBlocks.imageSizes || [];

            return el(
                Fragment,
                {},
                el(
                    InspectorControls,
                    {},
                    el(
                        PanelBody,
                        {
                            title: __('Download List Settings', 'gtdownloads-manager'),
                            initialOpen: true
                        },
                        el(
                            SelectControl,
                            {
                                label: __('Category', 'gtdownloads-manager'),
                                value: category,
                                options: [
                                    { value: '', label: __('All Categories', 'gtdownloads-manager') },
                                    ...categories
                                ],
                                onChange: function(newCategory) {
                                    setAttributes({ category: newCategory });
                                }
                            }
                        ),
                        el(
                            RangeControl,
                            {
                                label: __('Items Per Page', 'gtdownloads-manager'),
                                value: perPage,
                                min: -1,
                                max: 50,
                                help: __('-1 shows all downloads', 'gtdownloads-manager'),
                                onChange: function(newPerPage) {
                                    setAttributes({ perPage: newPerPage });
                                }
                            }
                        ),
                        el(
                            SelectControl,
                            {
                                label: __('Layout Type', 'gtdownloads-manager'),
                                value: type,
                                options: [
                                    { value: 'grid', label: __('Grid Layout', 'gtdownloads-manager') },
                                    { value: 'table', label: __('Table Layout', 'gtdownloads-manager') }
                                ],
                                onChange: function(newType) {
                                    setAttributes({ type: newType });
                                }
                            }
                        ),
                        el(
                            SelectControl,
                            {
                                label: __('Featured Image Size', 'gtdownloads-manager'),
                                value: image,
                                options: imageSizes.map(size => ({ value: size.value, label: size.label })),
                                onChange: function(newSize) {
                                    setAttributes({ image: newSize });
                                }
                            }
                        )
                    )
                ),
                el(
                    'div',
                    { className: 'gtdm-block-preview gtdm-downloads-list-preview' },
                    el('div', { className: 'gtdm-block-content' },
                        el('div', { className: 'gtdm-block-title' },
                            el('span', { className: 'dashicons dashicons-download' }),
                            ' ' + __('Downloads List', 'gtdownloads-manager')
                        ),
                        el('p', { className: 'gtdm-block-description' },
                            __('This block will display your downloads with the following settings:', 'gtdownloads-manager')
                        ),
                        el('ul', { className: 'gtdm-block-settings-list' },
                            el('li', {}, __('Layout: ', 'gtdownloads-manager') + 
                                (type === 'grid' ? __('Grid', 'gtdownloads-manager') : __('Table', 'gtdownloads-manager'))),
                            el('li', {}, __('Category: ', 'gtdownloads-manager') + 
                                (category ? category : __('All Categories', 'gtdownloads-manager'))),
                            el('li', {}, __('Items: ', 'gtdownloads-manager') + 
                                (perPage === -1 ? __('All', 'gtdownloads-manager') : perPage)),
                            el('li', {}, __('Image Size: ', 'gtdownloads-manager') + image)
                        )
                    )
                )
            );
        },

        save: function() {
            // Dynamic block, so render is handled server-side
            return null;
        }
    });
})(
    window.wp.blocks,
    window.wp.element,
    window.wp.components,
    window.wp.blockEditor || window.wp.editor,
    window.wp.i18n,
    window.wp.data
);