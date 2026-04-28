/**
 * Simuze Community - Activity JavaScript
 * Handles WYSIWYG editor, image/video embeds, and post creation
 */

jQuery(document).ready(function($) {
    let imageUrl = '';
    let videoUrl = '';
    
    // Add Image Button
    $('#add-image-btn').on('click', function() {
        const imageUrlInput = prompt('Enter image URL:');
        if (imageUrlInput && imageUrlInput.trim()) {
            imageUrl = imageUrlInput.trim();
            $('#post-image-url').val(imageUrl);
            $('#image-preview-img').attr('src', imageUrl);
            $('#image-preview').show();
            $('#media-preview').show();
        }
    });
    
    // Add Video Button
    $('#add-video-btn').on('click', function() {
        const videoUrlInput = prompt('Enter video URL (YouTube, Vimeo, etc.):');
        if (videoUrlInput && videoUrlInput.trim()) {
            videoUrl = videoUrlInput.trim();
            $('#post-video-url').val(videoUrl);
            
            // Try to get oEmbed preview
            $.ajax({
                url: simuzeCommunity.apiUrl.replace('/simuze/v1/', '/wp/v2/') + 'oembed',
                type: 'GET',
                data: { url: videoUrl },
                success: function(response) {
                    if (response.html) {
                        $('#video-embed-preview').html(response.html);
                        $('#video-preview').show();
                        $('#media-preview').show();
                    }
                },
                error: function() {
                    // Fallback: show URL
                    $('#video-embed-preview').html('<p>Video: <a href="' + videoUrl + '" target="_blank">' + videoUrl + '</a></p>');
                    $('#video-preview').show();
                    $('#media-preview').show();
                }
            });
        }
    });
    
    // Remove Media
    $(document).on('click', '.remove-media', function() {
        const type = $(this).data('type');
        if (type === 'image') {
            imageUrl = '';
            $('#post-image-url').val('');
            $('#image-preview').hide();
        } else if (type === 'video') {
            videoUrl = '';
            $('#post-video-url').val('');
            $('#video-preview').hide();
        }
        
        if (!$('#image-preview').is(':visible') && !$('#video-preview').is(':visible')) {
            $('#media-preview').hide();
        }
    });
    
    // Create Post
    $('#create-post-form').on('submit', function(e) {
        e.preventDefault();
        
        const content = $('#post-content-editor').val().trim();
        
        if (!content) {
            if (typeof simuzeAlertError === 'function') {
                simuzeAlertError('Please write something first');
            } else {
                alert('Please write something first');
            }
            return;
        }
        
        const formData = {
            content: content,
            visibility: $('#post-visibility').val(),
            image_url: $('#post-image-url').val() || '',
            video_url: $('#post-video-url').val() || ''
        };
        
        $.ajax({
            url: simuzeCommunity.apiUrl + 'activity/posts',
            type: 'POST',
            data: formData,
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', simuzeCommunity.nonce);
                $('#post-submit').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Posting...');
            },
            success: function(response) {
                if (response.success || response.id) {
                    location.reload();
                } else {
                    if (typeof simuzeAlertError === 'function') {
                        simuzeAlertError('Failed to create post');
                    } else {
                        alert('Failed to create post');
                    }
                }
            },
            error: function(xhr) {
                const errorMsg = xhr.responseJSON?.message || 'Failed to create post';
                if (typeof simuzeAlertError === 'function') {
                    simuzeAlertError(errorMsg);
                } else {
                    alert(errorMsg);
                }
            },
            complete: function() {
                $('#post-submit').prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Post');
            }
        });
    });
    
    // Edit Post
    $(document).on('click', '.edit-post', function() {
        const $card = $(this).closest('.activity-card');
        const postId = $card.data('id');
        const isGroup = $card.data('group') === 1;
        const content = $card.find('.post-content').html();
        
        $('#edit-post-id').val(postId);
        $('#edit-is-group').val(isGroup ? '1' : '0');
        
        // Initialize editor for edit modal
        if (typeof wp !== 'undefined' && wp.editor) {
            wp.editor.initialize('edit-post-editor', {
                tinymce: {
                    toolbar1: 'bold,italic,underline,|,link,|,bullist,numlist,|,undo,redo',
                    menubar: false,
                    statusbar: false,
                    height: 200,
                    content_css: false,
                    body_class: 'activity-editor'
                },
                quicktags: false
            });
            
            setTimeout(function() {
                if (wp.editor.getDefaultSettings && wp.editor.getDefaultSettings().tinymce) {
                    const editor = tinymce.get('edit-post-editor');
                    if (editor) {
                        editor.setContent(content);
                    }
                }
            }, 500);
        } else {
            $('#edit-post-editor').val(content);
        }
        
        $('#edit-post-modal').fadeIn(200);
    });
    
    $('#cancel-edit, #edit-post-modal .simuze-modal-close, #edit-post-modal .simuze-modal-overlay').on('click', function() {
        $('#edit-post-modal').fadeOut(200);
    });
    
    $('#edit-post-form').on('submit', function(e) {
        e.preventDefault();
        
        const postId = $('#edit-post-id').val();
        const isGroup = $('#edit-is-group').val() === '1';
        let content = '';
        
        if (typeof tinymce !== 'undefined') {
            const editor = tinymce.get('edit-post-editor');
            if (editor) {
                content = editor.getContent();
            } else {
                content = $('#edit-post-editor').val();
            }
        } else {
            content = $('#edit-post-editor').val();
        }
        
        $.ajax({
            url: simuzeCommunity.apiUrl + 'activity/posts/' + postId,
            type: 'PUT',
            data: JSON.stringify({
                content: content,
                is_group_post: isGroup
            }),
            contentType: 'application/json',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', simuzeCommunity.nonce);
            },
            success: function() {
                location.reload();
            },
            error: function(xhr) {
                const errorMsg = xhr.responseJSON?.message || 'Failed to update post';
                if (typeof simuzeAlertError === 'function') {
                    simuzeAlertError(errorMsg);
                } else {
                    alert(errorMsg);
                }
            }
        });
    });
    
    // Delete Post
    $(document).on('click', '.delete-post', function() {
        const $card = $(this).closest('.activity-card');
        const postId = $card.data('id');
        const isGroup = $card.data('group') === 1;
        
        const deletePost = function() {
            $.ajax({
                url: simuzeCommunity.apiUrl + 'activity/posts/' + postId,
                type: 'DELETE',
                data: JSON.stringify({ is_group_post: isGroup }),
                contentType: 'application/json',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', simuzeCommunity.nonce);
                },
                success: function() {
                    $card.fadeOut(function() { $(this).remove(); });
                },
                error: function(xhr) {
                    const errorMsg = xhr.responseJSON?.message || 'Failed to delete post';
                    if (typeof simuzeAlertError === 'function') {
                        simuzeAlertError(errorMsg);
                    } else {
                        alert(errorMsg);
                    }
                }
            });
        };
        
        if (typeof simuzeConfirm === 'function') {
            simuzeConfirm('Are you sure you want to delete this post?', 'Delete Post', deletePost);
        } else {
            if (confirm('Are you sure you want to delete this post?')) {
                deletePost();
            }
        }
    });
});


