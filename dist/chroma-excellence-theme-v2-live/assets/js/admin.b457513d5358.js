/**
 * Admin JavaScript for Media Uploader
 *
 * @package Chroma_Excellence
 */

jQuery(document).ready(function($) {
    'use strict';

    function getPreviewContainer(button) {
        return button.siblings('.chroma-image-preview').first();
    }

    function renderMediaPreview(previewContainer, url, previewType) {
        if (!previewContainer.length) {
            return;
        }

        if (!url) {
            previewContainer.empty();
            return;
        }

        if (previewType === 'file') {
            const fileName = url.split('/').pop();
            previewContainer.html(
                '<div style="margin-top: 10px; padding: 10px 12px; border: 1px solid #ddd; border-radius: 4px; background: #f8f8f8;">' +
                    '<strong>Selected file:</strong> ' +
                    '<a href="' + url + '" target="_blank" rel="noopener noreferrer">' + fileName + '</a>' +
                '</div>'
            );
            return;
        }

        previewContainer.html(
            '<img src="' + url + '" style="max-width: 200px; height: auto; margin-top: 10px; border: 1px solid #ddd; padding: 5px; border-radius: 4px;" />'
        );
    }

    // Media uploader for location assets
    $(document).on('click', '.chroma-upload-button', function(e) {
        e.preventDefault();

        const button = $(this);
        const fieldId = button.data('field');
        const inputField = $('#' + fieldId);
        const previewContainer = getPreviewContainer(button);
        const attachmentFieldId = button.data('attachmentField');
        const attachmentField = attachmentFieldId ? $('#' + attachmentFieldId) : $();
        const mediaType = button.data('mediaType') || 'image';
        const previewType = button.data('previewType') || (mediaType === 'image' ? 'image' : 'file');
        const uploaderTitle = button.data('uploaderTitle') || (mediaType === 'image' ? 'Select Image' : 'Select File');
        const buttonText = button.data('buttonText') || (mediaType === 'image' ? 'Use this image' : 'Use this file');

        if (typeof wp === 'undefined' || !wp.media) {
            window.alert('The WordPress media uploader is unavailable on this screen. Please refresh and try again.');
            return;
        }

        const mediaArgs = {
            title: uploaderTitle,
            button: {
                text: buttonText
            },
            multiple: false
        };

        if (mediaType) {
            mediaArgs.library = { type: mediaType };
        }

        // Create WordPress media frame
        const mediaUploader = wp.media(mediaArgs);

        // When media is selected
        mediaUploader.on('select', function() {
            const attachment = mediaUploader.state().get('selection').first().toJSON();
            inputField.val(attachment.url);
            if (attachmentField.length) {
                attachmentField.val(attachment.id || '');
            }

            renderMediaPreview(previewContainer, attachment.url, previewType);
        });

        // Open media uploader
        mediaUploader.open();
    });

    // Clear image button
    $(document).on('click', '.chroma-clear-button', function(e) {
        e.preventDefault();

        const button = $(this);
        const fieldId = button.data('field');
        const inputField = $('#' + fieldId);
        const previewContainer = getPreviewContainer(button);
        const attachmentFieldId = button.data('attachmentField');
        const attachmentField = attachmentFieldId ? $('#' + attachmentFieldId) : $();

        inputField.val('');
        if (attachmentField.length) {
            attachmentField.val('');
        }

        renderMediaPreview(previewContainer, '', 'image');
    });

    // Show preview for existing assets
    $('.chroma-image-field, .chroma-media-field').each(function() {
        const inputField = $(this);
        const assetUrl = inputField.val();
        const previewContainer = inputField.siblings('.chroma-image-preview');
        const previewType = inputField.data('previewType') || (inputField.hasClass('chroma-image-field') ? 'image' : 'file');

        renderMediaPreview(previewContainer, assetUrl, previewType);
    });
});
