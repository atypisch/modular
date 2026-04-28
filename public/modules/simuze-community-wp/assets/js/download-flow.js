/**
 * Simuze Download Flow - SIMPLIFIED VERSION
 * Simple, working modal system
 */

(function($) {
    'use strict';
    
    let currentProduct = null;
    let isProcessing = false;
    
    // Get modal elements
    const $overlay = $('#simuze-download-modal-overlay');
    const $modal = $('#simuze-download-modal');
    const $content = $modal.find('.simuze-modal-content');
    const $loading = $modal.find('.download-modal-loading');
    const $processing = $modal.find('.download-modal-processing');
    const $success = $modal.find('.download-modal-success');
    const $error = $modal.find('.download-modal-error');
    
    // Initialize
    $(document).ready(function() {
        console.log('Download Flow: Initializing...', {
            overlay: $overlay.length,
            modal: $modal.length,
            content: $content.length
        });
        
        // Bind download button clicks
        $(document).on('click', '.simuze-download-trigger, .simuze-purchase-btn, .simuze-purchase-track-btn', function(e) {
            e.preventDefault();
            const $btn = $(this);
            const productType = $btn.data('product-type') || 'song';
            const productId = $btn.data('product-id') || $btn.data('id');
            const uploadId = $btn.data('upload-id');
            
            if (productId) {
                openModal(productType, productId, uploadId);
            } else {
                console.error('Download Flow: No product ID found');
            }
        });
        
        // Close handlers
        $modal.on('click', '.simuze-modal-close', closeModal);
        $overlay.on('click', function(e) {
            if ($(e.target).is($overlay)) {
            closeModal();
            }
        });
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $overlay.is(':visible')) {
                closeModal();
            }
        });
        
        // Submit button
        $modal.on('click', '#download-submit-btn', submitPurchase);
        
        // Price input
        $modal.on('input', '#download-price-input', updateSubmitButton);
        });
    
    /**
     * Open modal
     */
    function openModal(productType, productId, uploadId) {
        console.log('Download Flow: Opening modal', { productType, productId, uploadId });
        
        if (!productId) {
            console.error('Download Flow: Missing product ID');
            return;
        }
        
        // Show modal - SIMPLE approach
        $overlay.css({
            'display': 'flex',
            'z-index': '99999'
        }).addClass('show');
        $('body').addClass('modal-open');
        console.log('Download Flow: Modal shown, overlay display:', $overlay.css('display'));
        
        // Show loading
        showLoading();
        
        // Fetch product info
        $.ajax({
            url: SimuzeDownload.ajaxUrl,
            type: 'POST',
            data: {
                action: 'simuze_get_product_info',
                nonce: SimuzeDownload.nonce,
                product_type: productType,
                product_id: productId,
                upload_id: uploadId || ''
            },
            success: function(response) {
                console.log('Download Flow: AJAX Success', response);
                if (response.success) {
                    currentProduct = response.data;
                    populateModal(currentProduct);
                    showContent();
                } else {
                    showError(response.data || 'Error loading product');
                }
            },
            error: function(xhr, status, error) {
                console.error('Download Flow: AJAX Error', { status, error, response: xhr.responseText });
                showError('Failed to load product information');
            }
        });
    }
    
    /**
     * Close modal
     */
    function closeModal() {
        if (isProcessing) return;
        
        $overlay.removeClass('show').css('display', 'none');
        $('body').removeClass('modal-open');
        currentProduct = null;
        resetModal();
    }
    
    /**
     * Reset modal
     */
    function resetModal() {
        $modal.find('.download-artwork').attr('src', '').hide();
        $modal.find('.download-title').text('');
        $modal.find('.download-artist').text('');
        $modal.find('.download-product-type').text('');
        $modal.find('#download-price-input').val('');
        $modal.find('#download-guest-email-input').val('');
        $modal.find('.error-message').text('');
    }
    
    /**
     * Populate modal with product data
     */
    function populateModal(product) {
        console.log('Download Flow: Populating modal', product);
        
        // Artwork
        if (product.artwork) {
            $modal.find('.download-artwork').attr('src', product.artwork).show();
        } else {
            $modal.find('.download-artwork').hide();
        }
        
        // Product info
        const typeLabel = product.product_type_label || (product.product_type === 'album' ? 'Album' : 'Track');
        const title = product.title || 'Untitled';
        const artist = product.artist || '';
        
        $modal.find('.download-product-type').text(typeLabel);
        $modal.find('.download-title').text(title);
        $modal.find('.download-artist').text(artist);
        
        // Price
        const price = product.price_default || product.base_price || product.price_min || 0;
        const $priceInput = $modal.find('#download-price-input');
        $priceInput.attr('min', product.price_min || 0).val(price);
        
        // Price hint
        if (product.price_min > 0) {
            $modal.find('.price-minimum').text('Minimum: €' + product.price_min.toFixed(2));
        } else {
            $modal.find('.price-minimum').text('Free');
        }
        
        // Guest email
        if (!SimuzeDownload.isLoggedIn) {
            $modal.find('.download-guest-email').show();
        } else {
            $modal.find('.download-guest-email').hide();
        }
        
        // Payment methods
        if (price > 0) {
            $modal.find('.download-payment-methods').show();
        } else {
            $modal.find('.download-payment-methods').hide();
        }
        
        updateSubmitButton();
    }
    
    /**
     * Update submit button
     */
    function updateSubmitButton() {
        const price = parseFloat($modal.find('#download-price-input').val()) || 0;
        const $btn = $modal.find('#download-submit-btn');
        const $btnText = $btn.find('.btn-text');
        const $btnPrice = $btn.find('.btn-price');
        
        $btnText.text('Download');
        $btnPrice.text('€' + price.toFixed(2));
        
        if (currentProduct && price < (currentProduct.price_min || 0)) {
            $btn.prop('disabled', true);
        } else {
            $btn.prop('disabled', false);
        }
    }
    
    /**
     * Submit purchase
     */
    function submitPurchase() {
        if (isProcessing || !currentProduct) return;
        
        const price = parseFloat($modal.find('#download-price-input').val()) || 0;
        const email = $modal.find('#download-guest-email-input').val();
        const paymentMethod = $modal.find('input[name="download_payment_method"]:checked').val() || 'paypal';
        
        if (price < (currentProduct.price_min || 0)) {
            showError('Price must be at least €' + (currentProduct.price_min || 0).toFixed(2));
            return;
        }
        
        isProcessing = true;
        showProcessing();
        
        $.ajax({
            url: SimuzeDownload.ajaxUrl,
            type: 'POST',
            data: {
                action: 'simuze_create_download_order',
                nonce: SimuzeDownload.nonce,
                product_type: currentProduct.product_type,
                product_id: currentProduct.product_id,
                custom_price: price,
                email: email,
                payment_method: paymentMethod
            },
            success: function(response) {
                isProcessing = false;
                if (response.success) {
                    if (response.data.is_free) {
                        showSuccess(response.data.download_url);
                    } else if (response.data.redirect_url) {
                        window.location.href = response.data.redirect_url;
                    }
                } else {
                    showError(response.data || 'Error processing payment');
                }
            },
            error: function() {
                isProcessing = false;
                showError('Failed to process payment');
            }
        });
    }
    
    /**
     * Show states
     */
    function showLoading() {
        $content.hide();
        $processing.hide();
        $success.hide();
        $error.hide();
        $loading.show();
    }
    
    function showContent() {
        $loading.hide();
        $processing.hide();
        $success.hide();
        $error.hide();
        $content.show();
        console.log('Download Flow: Content shown');
    }
    
    function showProcessing() {
        $content.hide();
        $loading.hide();
        $success.hide();
        $error.hide();
        $processing.show();
    }
    
    function showSuccess(downloadUrl) {
        $content.hide();
        $loading.hide();
        $processing.hide();
        $error.hide();
        $modal.find('.download-access-btn').attr('href', downloadUrl);
        $success.show();
    }
    
    function showError(message) {
        $content.hide();
        $loading.hide();
        $processing.hide();
        $success.hide();
        $modal.find('.error-message').text(message);
        $error.show();
    }
    
    // Expose globally
    window.SimuzeDownloadFlow = {
        open: openModal,
        close: closeModal
    };
    
})(jQuery);
