(function(blocks, element, components, editor, i18n, data, apiFetch) {
    const { __ } = i18n;
    const { registerBlockType } = blocks;
    const { InspectorControls } = editor;
    const { PanelBody, SelectControl, RangeControl, Notice, Placeholder, Spinner } = components;
    const { Fragment, useState, useEffect } = element;
    const el = element.createElement;

    // Icon for the blocks
    const blockIcon = el('svg', { width: 24, height: 24, viewBox: '0 0 24 24' },
        el('path', { d: 'M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z' })
    );

    // Function to fetch preview HTML
    const fetchPreview = (endpoint, params) => {
        return apiFetch({
            path: endpoint + '?' + new URLSearchParams(params),
            method: 'GET',
            headers: {
                'X-WP-Nonce': gtdmBlocks.nonce
            }
        }).then(response => {
            return response.html || '';
        }).catch(error => {
            console.error('Error fetching preview:', error);
            return '<div class="gtdm-error-message">' + 
                   __('Error loading preview. Please check that the download exists.', 'gtdownloads-manager') + 
                   '</div>';
        });
    };

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
            const [preview, setPreview] = useState('');
            const [loading, setLoading] = useState(false);

            // Load preview when attributes change
            useEffect(() => {
                if (id > 0) {
                    setLoading(true);
                    fetchPreview(`/gtdm/v1/preview-single/${id}/${image}`, {})
                        .then(html => {
                            setPreview(html);
                            setLoading(false);
                        });
                } else {
                    setPreview(''); // Clear preview when no id is selected
                }
            }, [id, image]);

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
                                options: imageSizes,
                                onChange: function(newSize) {
                                    setAttributes({ image: newSize });
                                }
                            }
                        )
                    )
                ),
                id === 0 
                    ? el(
                        Placeholder,
                        {
                            icon: 'download',
                            label: __('GT Single Download', 'gtdownloads-manager'),
                            instructions: __('Please select a download from the block settings.', 'gtdownloads-manager')
                        }
                    )
                    : loading
                        ? el(
                            Placeholder,
                            {
                                icon: 'download',
                                label: __('Loading Download Preview', 'gtdownloads-manager')
                            },
                            el(Spinner)
                        )
                        : el('div', {
                            dangerouslySetInnerHTML: { __html: preview }
                        })
            );
        },

        save: function() {
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
            const [preview, setPreview] = useState('');
            const [loading, setLoading] = useState(true);

            // Load preview when attributes change
            useEffect(() => {
                setLoading(true);
                fetchPreview('/gtdm/v1/preview-list', {
                    category: category,
                    type: type,
                    image: image
                }).then(html => {
                    setPreview(html);
                    setLoading(false);
                });
            }, [category, type, image]);

            const categories = gtdmBlocks.categories || [];
            const imageSizes = gtdmBlocks.imageSizes || [];
            const previewLimit = gtdmBlocks.previewLimit || 6;

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
                        perPage < 0 || perPage > previewLimit ? 
                            el(
                                Notice,
                                {
                                    status: 'info',
                                    isDismissible: false,
                                    className: 'gtdm-editor-notice'
                                },
                                __(`For performance reasons, preview is limited to ${previewLimit} items. All items will be shown on the frontend.`, 'gtdownloads-manager')
                            ) : null,
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
                                options: imageSizes,
                                onChange: function(newSize) {
                                    setAttributes({ image: newSize });
                                }
                            }
                        )
                    )
                ),
                loading
                    ? el(
                        Placeholder,
                        {
                            icon: 'download',
                            label: __('Loading Downloads Preview', 'gtdownloads-manager')
                        },
                        el(Spinner)
                    )
                    : el('div', {
                        dangerouslySetInnerHTML: { __html: preview }
                    })
            );
        },

        save: function() {
            return null;
        }
    });
})(
    window.wp.blocks,
    window.wp.element,
    window.wp.components,
    window.wp.blockEditor || window.wp.editor,
    window.wp.i18n,
    window.wp.data,
    window.wp.apiFetch
);