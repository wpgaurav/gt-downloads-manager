(function($) {
    'use strict';
    
    const DmAdmin = {
        init: function() {
            this.bindEvents();
            this.setupFileSourceToggle();
        },
        
        bindEvents: function() {
            // Featured image selection
            $('.dm-select-image').on('click', this.openMediaLibrary.bind(this, 'image'));
            $('.dm-remove-image').on('click', this.removeMedia.bind(this, 'image'));
            
            // File selection
            $('.dm-select-file').on('click', this.openMediaLibrary.bind(this, 'file'));
            $('.dm-remove-file').on('click', this.removeMedia.bind(this, 'file'));
            
            // Form submission
            $('#dm-download-form').on('submit', this.handleFormSubmit.bind(this));
            
            // Delete download
            $('.dm-delete').on('click', this.handleDelete.bind(this));
            
            // Toggle file source
            $('input[name="file_source"]').on('change', this.toggleFileSource.bind(this));
        },
        
        setupFileSourceToggle: function() {
            this.toggleFileSource();
        },
        
        toggleFileSource: function() {
            const selectedSource = $('input[name="file_source"]:checked').val();
            
            if (selectedSource === 'media') {
                $('.dm-upload-container').show();
                $('.dm-direct-url-container').hide();
            } else {
                $('.dm-upload-container').hide();
                $('.dm-direct-url-container').show();
            }
        },
        
        openMediaLibrary: function(type, e) {
            e.preventDefault();
            
            let mediaOptions = {
                title: type === 'image' ? dmAdmin.i18n.selectImage : dmAdmin.i18n.selectFile,
                button: {
                    text: type === 'image' ? dmAdmin.i18n.useImage : dmAdmin.i18n.useFile
                },
                multiple: false
            };
            
            // For images, only show images
            if (type === 'image') {
                mediaOptions.library = { type: 'image' };
            }
            
            const mediaUploader = wp.media(mediaOptions);
            
            mediaUploader.on('select', function() {
                const attachment = mediaUploader.state().get('selection').first().toJSON();
                
                if (type === 'image') {
                    $('#featured_image_id').val(attachment.id);
                    $('.dm-featured-image-preview').html(
                        $('<img>').attr('src', attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url)
                    );
                } else {
                    $('#file_url').val(attachment.id);
                    $('.dm-file-preview').html(
                        $('<span class="dm-filename"></span>').text(attachment.filename)
                    );
                }
            });
            
            mediaUploader.open();
        },
        
        removeMedia: function(type, e) {
            e.preventDefault();
            
            if (type === 'image') {
                $('#featured_image_id').val('');
                $('.dm-featured-image-preview').html(
                    $('<div class="dm-no-image"></div>').text(dmAdmin.i18n.noImage)
                );
            } else {
                $('#file_url').val('');
                $('.dm-file-preview').html(
                    $('<span class="dm-no-file"></span>').text(dmAdmin.i18n.noFile)
                );
            }
        },
        
        handleFormSubmit: function(e) {
            e.preventDefault();
            
            const $form = $(e.currentTarget);
            const $feedback = $('#dm-form-feedback');
            
            $feedback.removeClass('notice-success notice-error').addClass('hidden');
            
            $.ajax({
                url: dmAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'dm_save_download',
                    nonce: dmAdmin.nonce,
                    id: $form.find('input[name="id"]').val(),
                    title: $form.find('#title').val(),
                    description: $form.find('#description').val(),
                    featured_image_id: $form.find('#featured_image_id').val(),
                    category: $form.find('#category').val(),
                    file_source: $form.find('input[name="file_source"]:checked').val(),
                    file_url: $form.find('#file_url').val(),
                    direct_url: $form.find('#direct_url').val()
                },
                success: function(response) {
                    if (response.success) {
                        $feedback
                            .removeClass('hidden notice-error')
                            .addClass('notice-success')
                            .find('p')
                            .text(response.data.message);
                            
                        setTimeout(function() {
                            window.location.href = response.data.redirect;
                        }, 1000);
                    } else {
                        $feedback
                            .removeClass('hidden notice-success')
                            .addClass('notice-error')
                            .find('p')
                            .text(response.data.message);
                    }
                },
                error: function() {
                    $feedback
                        .removeClass('hidden notice-success')
                        .addClass('notice-error')
                        .find('p')
                        .text(dmAdmin.i18n.errorSaving);
                }
            });
        },
        
        handleDelete: function(e) {
            e.preventDefault();
            
            if (!confirm(dmAdmin.i18n.confirmDelete)) {
                return;
            }
            
            const $row = $(e.currentTarget).closest('tr');
            const id = $(e.currentTarget).data('id');
            
            $.ajax({
                url: dmAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'dm_delete_download',
                    nonce: dmAdmin.nonce,
                    id: id
                },
                success: function(response) {
                    if (response.success) {
                        $row.fadeOut(400, function() {
                            $(this).remove();
                            
                            if ($('table tbody tr').length === 0) {
                                $('table tbody').append(
                                    $('<tr><td colspan="5"></td></tr>')
                                        .find('td')
                                        .text(dmAdmin.i18n.noDownloads)
                                        .end()
                                );
                            }
                        });
                    } else {
                        alert(response.data.message);
                    }
                },
                error: function() {
                    alert(dmAdmin.i18n.errorDeleting);
                }
            });
        }
    };
    
    $(document).ready(DmAdmin.init.bind(DmAdmin));
})(jQuery);