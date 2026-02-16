(function ($) {
    'use strict';

    var frames = {};

    function updateSourcePanels() {
        var source = $('input[name="file_source"]:checked').val();

        $('.gtdm-source-media').toggle(source === 'media');
        $('.gtdm-source-direct').toggle(source === 'direct');
    }

    function getFrame(key, options) {
        if (frames[key]) {
            return frames[key];
        }

        frames[key] = wp.media(options);
        return frames[key];
    }

    function resolveAttachmentLabel(attachment, fallback) {
        if (attachment.filename) {
            return attachment.filename;
        }

        if (attachment.title) {
            return attachment.title;
        }

        if (attachment.url) {
            return attachment.url;
        }

        return fallback;
    }

    function attachmentPreviewUrl(attachment) {
        if (attachment.sizes) {
            if (attachment.sizes.medium && attachment.sizes.medium.url) {
                return attachment.sizes.medium.url;
            }

            if (attachment.sizes.thumbnail && attachment.sizes.thumbnail.url) {
                return attachment.sizes.thumbnail.url;
            }
        }

        return attachment.url || '';
    }

    function openMediaFrame(event) {
        event.preventDefault();

        var button = $(event.currentTarget);
        var targetInput = $('#' + button.data('targetInput'));
        var targetLabel = $('#' + button.data('targetLabel'));
        var targetPreview = button.data('targetPreview') ? $('#' + button.data('targetPreview')) : $();
        var libraryType = button.data('libraryType') || '';
        var frameTitle = button.data('frameTitle') || gtdmAdmin.chooseFile;
        var buttonText = button.data('buttonText') || gtdmAdmin.useFile;
        var forceSource = button.data('forceSource') || '';

        if (!targetInput.length || typeof wp === 'undefined' || !wp.media) {
            return;
        }

        var frameOptions = {
            title: frameTitle,
            button: {
                text: buttonText,
            },
            multiple: false,
        };

        if (libraryType) {
            frameOptions.library = { type: libraryType };
        }

        var frameKey = [targetInput.attr('id'), libraryType, frameTitle].join(':');
        var frame = getFrame(frameKey, frameOptions);

        frame.off('select.gtdm');
        frame.on('select.gtdm', function () {
            var attachment = frame.state().get('selection').first().toJSON();
            var fallbackLabel = targetLabel.data('emptyLabel') || gtdmAdmin.noFile;
            var label = resolveAttachmentLabel(attachment, fallbackLabel);

            targetInput.val(attachment.id).trigger('change');
            targetLabel.text(label).addClass('is-selected');

            if (targetPreview.length) {
                var previewUrl = attachmentPreviewUrl(attachment);
                if (previewUrl) {
                    targetPreview.html('<img src="' + previewUrl + '" alt="" />');
                } else {
                    targetPreview.empty();
                }
            }

            if (forceSource) {
                $('input[name="file_source"][value="' + forceSource + '"]').prop('checked', true);
                updateSourcePanels();
            }
        });

        frame.open();
    }

    function clearMedia(event) {
        event.preventDefault();

        var button = $(event.currentTarget);
        var targetInput = $('#' + button.data('targetInput'));
        var targetLabel = $('#' + button.data('targetLabel'));
        var targetPreview = button.data('targetPreview') ? $('#' + button.data('targetPreview')) : $();

        if (!targetInput.length) {
            return;
        }

        targetInput.val('0').trigger('change');

        if (targetLabel.length) {
            targetLabel
                .text(targetLabel.data('emptyLabel') || gtdmAdmin.noFile)
                .removeClass('is-selected');
        }

        if (targetPreview.length) {
            targetPreview.empty();
        }
    }

    function maybeConfirmDeleteTerm(event) {
        if (!window.confirm(gtdmAdmin.confirmDeleteTerm)) {
            event.preventDefault();
        }
    }

    $(document).ready(function () {
        updateSourcePanels();

        $(document).on('change', 'input[name="file_source"]', updateSourcePanels);
        $(document).on('click', '.gtdm-open-media', openMediaFrame);
        $(document).on('click', '.gtdm-clear-media', clearMedia);
        $(document).on('click', '.gtdm-delete-term', maybeConfirmDeleteTerm);
    });
})(jQuery);
