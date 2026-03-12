/**
 * Simuze Purchase Flow
 * Handles the purchase modal and payment process
 */

(function($) {
    'use strict';
    
    var PurchaseFlow = {
        
        modal: null,
        currentProduct: null,
        
        init: function() {
            this.modal = $('#simuze-purchase-modal');
            this.bindEvents();
        },
        
        bindEvents: function() {
            var self = this;
            
            // Purchase button click
            $(document).on('click', '.simuze-purchase-btn', function(e) {
                e.preventDefault();
                var $btn = $(this);
                self.openPurchaseModal({
                    type: $btn.data('product-type'),
                    id: $btn.data('product-id'),
                    price: $btn.data('price'),
                    title: $btn.data('title')
                });
            });
            
            // Purchase track button (for tracks without Simuze_Song)
            $(document).on('click', '.simuze-purchase-track-btn', function(e) {
                e.preventDefault();
                var $btn = $(this);
                var uploadId = $btn.data('upload-id');
                var trackPostId = $btn.data('track-post-id');
                var price = $btn.data('price');
                var title = $btn.data('title');
                
                // First, try to create or get Simuze_Song for this track
                $.ajax({
                    url: SimuzePurchase.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'simuze_get_or_create_song',
                        nonce: SimuzePurchase.nonce,
                        upload_id: uploadId,
                        track_post_id: trackPostId,
                        price: price,
                        title: title
                    },
                    success: function(response) {
                        if (response.success && response.data && response.data.song_id) {
                            // Song created/found - open purchase modal
                            self.openPurchaseModal({
                                type: 'song',
                                id: response.data.song_id,
                                price: price,
                                title: title
                            });
                        } else {
                            // Fallback: open modal with upload_id (will need backend handling)
                            alert('Error: Could not initialize purchase. Please try again.');
                        }
                    },
                    error: function() {
                        alert('Error: Could not initialize purchase. Please try again.');
                    }
                });
            });
            
            // Free download button
            $(document).on('click', '.simuze-free-download', function(e) {
                e.preventDefault();
                var $btn = $(this);
                self.handleFreeDownload($btn.data('product-type'), $btn.data('product-id'));
            });
            
            // Modal close
            this.modal.on('click', '.simuze-modal-close, .simuze-modal-overlay', function() {
                self.closeModal();
            });
            
            // Complete purchase button
            $('#btn-complete-purchase').on('click', function() {
                self.completePurchase();
            });
            
            // ESC key to close
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && self.modal.is(':visible')) {
                    self.closeModal();
                }
            });
        },
        
        openPurchaseModal: function(product) {
            this.currentProduct = product;
            
            // Update modal content
            this.modal.find('.product-title').text(product.title);
            this.modal.find('.price-amount').text(SimuzePurchase.currencySymbol + parseFloat(product.price).toFixed(2));
            
            // Show/hide guest checkout form
            if (!SimuzePurchase.isLoggedIn) {
                this.modal.find('.guest-checkout-form').show();
            } else {
                this.modal.find('.guest-checkout-form').hide();
            }
            
            // Reset state
            this.modal.find('.purchase-modal-body').show();
            this.modal.find('.purchase-modal-loading').hide();
            
            // Show modal
            this.modal.fadeIn(200);
            $('body').addClass('modal-open');
        },
        
        closeModal: function() {
            this.modal.fadeOut(200);
            $('body').removeClass('modal-open');
            this.currentProduct = null;
        },
        
        completePurchase: function() {
            var self = this;
            var paymentMethod = this.modal.find('input[name="payment_method"]:checked').val();
            
            // Validate guest email
            if (!SimuzePurchase.isLoggedIn) {
                var email = $('#guest-email').val();
                if (!this.isValidEmail(email)) {
                    alert('Please enter a valid email address');
                    return;
                }
            }
            
            // Show loading
            this.modal.find('.purchase-modal-body').hide();
            this.modal.find('.purchase-modal-loading').show();
            
            // Determine which endpoint to use
            var action = SimuzePurchase.isLoggedIn ? 'simuze_init_purchase' : 'simuze_guest_checkout';
            
            var data = {
                action: action,
                nonce: SimuzePurchase.nonce,
                product_type: this.currentProduct.type,
                product_id: this.currentProduct.id,
                payment_method: paymentMethod
            };
            
            if (!SimuzePurchase.isLoggedIn) {
                data.email = $('#guest-email').val();
            }
            
            $.ajax({
                url: SimuzePurchase.ajaxUrl,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        if (response.data.redirect_url) {
                            // Redirect to payment provider
                            window.location.href = response.data.redirect_url;
                        } else if (response.data.guest_checkout) {
                            // Show guest checkout form
                            self.modal.find('.purchase-modal-body').show();
                            self.modal.find('.purchase-modal-loading').hide();
                            self.modal.find('.guest-checkout-form').show();
                        }
                    } else {
                        alert('Error: ' + response.data);
                        self.modal.find('.purchase-modal-body').show();
                        self.modal.find('.purchase-modal-loading').hide();
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                    self.modal.find('.purchase-modal-body').show();
                    self.modal.find('.purchase-modal-loading').hide();
                }
            });
        },
        
        handleFreeDownload: function(productType, productId) {
            // For free downloads, create a temporary token and download
            $.ajax({
                url: SimuzePurchase.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'simuze_free_download',
                    nonce: SimuzePurchase.nonce,
                    product_type: productType,
                    product_id: productId
                },
                success: function(response) {
                    if (response.success && response.data.download_url) {
                        // Trigger download
                        var link = document.createElement('a');
                        link.href = response.data.download_url;
                        link.download = '';
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    } else {
                        alert('Download not available');
                    }
                }
            });
        },
        
        isValidEmail: function(email) {
            var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        PurchaseFlow.init();
    });
    
})(jQuery);










