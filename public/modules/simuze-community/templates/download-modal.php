<?php
/**
 * Download Modal Template - SIMPLIFIED VERSION
 * Pay-what-you-want modal for purchasing music downloads
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div id="simuze-download-modal-overlay" class="simuze-modal-overlay" style="display: none;">
    <div id="simuze-download-modal" class="simuze-modal simuze-download-modal">
        <!-- Header -->
        <div class="simuze-modal-header">
            <h3 class="simuze-modal-title">Download</h3>
            <button type="button" class="simuze-modal-close">&times;</button>
        </div>
        
        <!-- Loading State -->
        <div class="download-modal-loading" style="display: none;">
            <div class="loading-spinner"></div>
            <p>Loading...</p>
        </div>
        
        <!-- Main Content -->
        <div class="simuze-modal-content">
            <!-- Product Info -->
            <div class="download-product-info">
                <img class="download-artwork" src="" alt="" style="display: none;">
                <div class="download-details">
                    <span class="download-product-type"></span>
                    <h2 class="download-title"></h2>
                    <p class="download-artist"></p>
                </div>
            </div>
            
            <!-- Price Input -->
            <div class="download-price-section">
                <label for="download-price-input" class="download-price-label">Name your price</label>
                <div class="download-price-input-wrapper">
                    <span class="download-price-currency">€</span>
                    <input type="number" id="download-price-input" class="download-price-input" step="0.01" min="0" placeholder="0.00">
                </div>
                <p class="download-price-hint">
                    <span class="price-minimum"></span>
                    <span class="price-support">Pay more to support the artist</span>
                </p>
            </div>
            
            <!-- Guest Email -->
            <div class="download-guest-email" style="display: none;">
                <label for="download-guest-email-input">Your email address</label>
                <input type="email" id="download-guest-email-input" placeholder="your@email.com" required>
            </div>
            
            <!-- Payment Methods -->
            <div class="download-payment-methods" style="display: none;">
                <p class="payment-methods-label">Pay with</p>
                <div class="payment-method-options">
                    <label class="payment-method-option active">
                        <input type="radio" name="download_payment_method" value="paypal" checked>
                        <span class="payment-method-icon"><i class="fab fa-paypal"></i></span>
                        <span class="payment-method-name">PayPal</span>
                    </label>
                </div>
            </div>
            
            <!-- Submit Button -->
            <div class="download-actions">
                <button type="button" id="download-submit-btn" class="download-submit-btn">
                    <span class="btn-text">Download</span>
                    <span class="btn-price"></span>
                </button>
        </div>
        </div>
        
        <!-- Processing State -->
        <div class="download-modal-processing" style="display: none;">
            <div class="loading-spinner"></div>
            <p>Processing your payment...</p>
        </div>
        
        <!-- Success State -->
        <div class="download-modal-success" style="display: none;">
            <div class="success-icon"><i class="fas fa-check-circle"></i></div>
            <h3>Download Ready!</h3>
            <a href="#" class="download-access-btn" target="_blank">Go to Downloads</a>
        </div>
        
        <!-- Error State -->
        <div class="download-modal-error" style="display: none;">
            <div class="error-icon"><i class="fas fa-exclamation-circle"></i></div>
            <h3>Something went wrong</h3>
            <p class="error-message"></p>
            <button type="button" class="download-retry-btn">Try Again</button>
        </div>
    </div>
</div>
