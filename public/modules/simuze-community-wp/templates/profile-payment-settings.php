<?php
/**
 * User Payment Settings Template
 * Connects and syncs with SIMEZU payment API
 */

if (!defined('ABSPATH')) {
    exit;
}

$auth = Simuze_Auth::get_instance();
$current_user = $auth->get_current_user();

if (!$current_user || $current_user->id !== $user->id) {
    echo '<p>Access denied.</p>';
    return;
}

// Get SIMEZU user ID
$simezu_user_id = $user->simezu_id ?? '';

// Get current payment settings from SIMEZU
$payment_settings = array(
    'connected' => false,
    'account_status' => 'not_connected',
    'payment_methods' => array(),
    'last_sync' => null,
);
?>

<div class="payment-settings" style="background: #1a1a1a; color: #fff; padding: 30px; border-radius: 8px;">
    <h2 style="color: #a8d5ba; margin-bottom: 30px; border-bottom: 2px solid #a8d5ba; padding-bottom: 10px;">
        <i class="fas fa-credit-card"></i> Payment Settings
    </h2>
    
    <div class="payment-settings-info" style="background: #2a2a2a; padding: 20px; border-radius: 8px; margin-bottom: 30px; border-left: 4px solid #a8d5ba;">
        <p style="margin: 0; color: #fff;">
            <i class="fas fa-info-circle"></i> Configure your payment methods to receive payments for your music sales.
        </p>
    </div>
    
    <div id="payment-settings-status" style="margin-bottom: 30px;">
        <div class="status-card" style="background: #2a2a2a; padding: 20px; border-radius: 8px; border: 1px solid #4a4a4a;">
            <div style="display: flex; align-items: center; gap: 15px;">
                <div id="connection-status-icon" style="width: 40px; height: 40px; border-radius: 50%; background: #4a4a4a; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-circle" style="color: #999; font-size: 20px;"></i>
                </div>
                <div style="flex: 1;">
                    <h3 style="margin: 0; color: #fff; font-size: 18px;">Payment Account</h3>
                    <p id="connection-status-text" style="margin: 5px 0 0 0; color: #999; font-size: 14px;">Loading...</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- PayPal Settings Section -->
    <div class="form-section payment-method-section" style="background: #2a2a2a; padding: 25px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #4a4a4a;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <i class="fab fa-paypal" style="font-size: 28px; color: #0070ba;"></i>
                <h3 style="margin: 0; color: #fff; font-size: 18px; font-weight: 600;">PayPal</h3>
            </div>
            <div id="paypal-connection-status" style="display: none;">
                <span class="connection-badge" style="background: #2a4a2a; color: #a8d5ba; padding: 6px 12px; border-radius: 4px; font-size: 12px; font-weight: 600;">
                    <i class="fas fa-check"></i> Connected
                </span>
            </div>
        </div>
        <p style="margin: 0 0 20px 0; color: #999; font-size: 14px;">Connect your PayPal account to receive payments</p>
        
        <div class="form-group" style="margin-bottom: 15px;">
            <label for="paypal-email" style="color: #fff; font-weight: 600; margin-bottom: 8px; display: block; font-size: 14px;">PayPal Email Address</label>
            <input type="email" id="paypal-email" name="paypal_email" 
                   placeholder="your.email@example.com"
                   style="width: 100%; padding: 12px 14px; background: #1a1a1a; border: 1px solid #4a4a4a; border-radius: 6px; font-size: 14px; color: #fff; box-sizing: border-box;">
            <small style="display: block; margin-top: 6px; color: #999; font-size: 12px;">Enter the email address associated with your PayPal account</small>
        </div>
        
        <div style="display: flex; gap: 10px;">
            <button type="button" id="test-paypal-btn" class="button" 
                    style="background: #4a4a4a; color: #fff; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px;">
                <i class="fas fa-plug"></i> Test Connection
            </button>
            <button type="button" id="save-paypal-btn" class="button" 
                    style="background: #a8d5ba; color: #1a1a1a; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 700; font-size: 14px;">
                <i class="fas fa-save"></i> Save PayPal Settings
            </button>
        </div>
        <div id="paypal-status-message" style="margin-top: 10px; font-size: 13px;"></div>
    </div>
    
    <!-- Stripe Settings Section -->
    <div class="form-section payment-method-section" style="background: #2a2a2a; padding: 25px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #4a4a4a;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <i class="fab fa-cc-stripe" style="font-size: 28px; color: #635bff;"></i>
                <h3 style="margin: 0; color: #fff; font-size: 18px; font-weight: 600;">Stripe</h3>
            </div>
            <div id="stripe-connection-status" style="display: none;">
                <span class="connection-badge" style="background: #2a4a2a; color: #a8d5ba; padding: 6px 12px; border-radius: 4px; font-size: 12px; font-weight: 600;">
                    <i class="fas fa-check"></i> Connected
                </span>
            </div>
        </div>
        <p style="margin: 0 0 20px 0; color: #999; font-size: 14px;">Connect your Stripe account using API keys</p>
        
        <div class="form-group" style="margin-bottom: 15px;">
            <label for="stripe-publishable-key" style="color: #fff; font-weight: 600; margin-bottom: 8px; display: block; font-size: 14px;">Publishable Key</label>
            <input type="text" id="stripe-publishable-key" name="stripe_publishable_key" 
                   placeholder="pk_test_..."
                   style="width: 100%; padding: 12px 14px; background: #1a1a1a; border: 1px solid #4a4a4a; border-radius: 6px; font-size: 14px; color: #fff; box-sizing: border-box; font-family: monospace;">
        </div>
        
        <div class="form-group" style="margin-bottom: 15px;">
            <label for="stripe-secret-key" style="color: #fff; font-weight: 600; margin-bottom: 8px; display: block; font-size: 14px;">Secret Key (will be encrypted)</label>
            <input type="password" id="stripe-secret-key" name="stripe_secret_key" 
                   placeholder="sk_test_..."
                   style="width: 100%; padding: 12px 14px; background: #1a1a1a; border: 1px solid #4a4a4a; border-radius: 6px; font-size: 14px; color: #fff; box-sizing: border-box; font-family: monospace;">
        </div>
        
        <div style="display: flex; gap: 10px;">
            <button type="button" id="test-stripe-btn" class="button" 
                    style="background: #4a4a4a; color: #fff; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px;">
                <i class="fas fa-plug"></i> Test Connection
            </button>
            <button type="button" id="save-stripe-btn" class="button" 
                    style="background: #a8d5ba; color: #1a1a1a; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 700; font-size: 14px;">
                <i class="fas fa-save"></i> Save Stripe Settings
            </button>
        </div>
        <div id="stripe-status-message" style="margin-top: 10px; font-size: 13px;"></div>
    </div>
    
    <!-- Credit Card Settings Section -->
    <div class="form-section payment-method-section" style="background: #2a2a2a; padding: 25px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #4a4a4a;">
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 10px;">
            <i class="fas fa-credit-card" style="font-size: 20px; color: #a8d5ba;"></i>
            <h3 style="margin: 0; color: #fff; font-size: 18px; font-weight: 600;">Credit Card</h3>
        </div>
        <p style="margin: 0 0 20px 0; color: #999; font-size: 14px;">Manage your credit card preferences</p>
        
        <div class="form-group" style="margin-bottom: 15px;">
            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                <input type="checkbox" id="enable-credit-card" name="enable_credit_card" 
                       style="width: 18px; height: 18px; cursor: pointer;">
                <span style="color: #fff; font-weight: 600; font-size: 14px;">Enable credit card payments</span>
            </label>
            <small style="display: block; margin-top: 6px; margin-left: 28px; color: #999; font-size: 12px;">Allow credit card payments for your account</small>
        </div>
        
        <button type="button" id="save-credit-card-btn" class="button" 
                style="background: #a8d5ba; color: #1a1a1a; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 700; font-size: 14px;">
            <i class="fas fa-save"></i> Save Credit Card Settings
        </button>
        <div id="credit-card-status-message" style="margin-top: 10px; font-size: 13px;"></div>
    </div>
    
    <!-- Default Payment Method Section -->
    <div class="form-section payment-method-section" style="background: #2a2a2a; padding: 25px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #4a4a4a;">
        <h3 style="margin: 0 0 10px 0; color: #fff; font-size: 18px; font-weight: 600;">Default Payment Method</h3>
        <p style="margin: 0 0 20px 0; color: #999; font-size: 14px;">Select Default Payment Method</p>
        
        <div class="form-group" style="margin-bottom: 15px;">
            <label for="default-payment-method" style="color: #fff; font-weight: 600; margin-bottom: 8px; display: block; font-size: 14px;">Payment Method</label>
            <select id="default-payment-method" name="default_payment_method" 
                    style="width: 100%; padding: 12px 14px; background: #1a1a1a; border: 1px solid #4a4a4a; border-radius: 6px; font-size: 14px; color: #fff; box-sizing: border-box; cursor: pointer;">
                <option value="paypal">PayPal</option>
                <option value="stripe">Credit Card (Stripe)</option>
            </select>
            <small style="display: block; margin-top: 6px; color: #999; font-size: 12px;">This will be used as your default payment method for transactions</small>
        </div>
        
        <button type="button" id="save-default-payment-btn" class="button" 
                style="background: #a8d5ba; color: #1a1a1a; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 700; font-size: 14px;">
            <i class="fas fa-save"></i> Save Default Payment Method
        </button>
        <div id="default-payment-status-message" style="margin-top: 10px; font-size: 13px;"></div>
    </div>
    
</div>

<style>
.payment-settings .button {
    transition: all 0.3s ease;
}

.payment-settings .button:hover {
    opacity: 0.9;
    transform: translateY(-1px);
}

.payment-settings .button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
}

.payment-settings .status-connected {
    background: #2a4a2a !important;
    border-color: #a8d5ba !important;
}

.payment-settings .status-connected #connection-status-icon {
    background: #a8d5ba !important;
}

.payment-settings .status-connected #connection-status-icon i {
    color: #1a1a1a !important;
}

.payment-settings .status-disconnected {
    background: #4a2a2a !important;
    border-color: #ff6b6b !important;
}

.payment-settings .status-disconnected #connection-status-icon {
    background: #ff6b6b !important;
}

.payment-settings .status-disconnected #connection-status-icon i {
    color: #fff !important;
}
</style>

<script>
(function() {
    var SIMEZU_APP_ID = '85912f38-cb0f-11f0-89cd-d6acdb43bded';
    var SIMEZU_API_KEY = '85912f42-cb0f-11f0-89cd-d6acdb43bded';
    var SIMEZU_BASE_URL = '/simezu';
    var SIMEZU_USER_ID = '<?php echo esc_js($simezu_user_id); ?>';
    var API_URL = (typeof simuzeCommunity !== 'undefined' && simuzeCommunity.apiUrl) 
        ? simuzeCommunity.apiUrl 
        : '<?php echo esc_js(rest_url("simuze/v1/")); ?>';
    var NONCE = (typeof simuzeCommunity !== 'undefined' && simuzeCommunity.nonce) 
        ? simuzeCommunity.nonce 
        : '<?php echo esc_js(wp_create_nonce("wp_rest")); ?>';

    function getCookie(name) {
        var match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
        return match ? decodeURIComponent(match[2]) : null;
    }

    function getAccessToken() {
        try {
            if (typeof SimezuAuth !== 'undefined') {
                if (typeof SimezuAuth.getToken === 'function') {
                    var t = SimezuAuth.getToken();
                    if (t) return t;
                }
                if (typeof SimezuAuth.getAccessToken === 'function') {
                    return SimezuAuth.getAccessToken();
                }
            }
        } catch (e) {
            console.warn('SimezuAuth token fetch failed', e);
        }
        return getCookie('accessToken') ||
               getCookie('simuze_jwt_token') ||
               getCookie('simezu_token') ||
               window.localStorage.getItem('simezu_token') ||
               null;
    }

    function simezuHeaders(token) {
        return {
            'X-App-ID': SIMEZU_APP_ID,
            'X-API-Key': SIMEZU_API_KEY,
            'Authorization': 'Bearer ' + token,
            'Content-Type': 'application/json'
        };
    }

    // Removed showMessage - no user-facing messages needed for automatic syncing

    function updateConnectionStatus(connected, accountStatus) {
        var statusCard = document.querySelector('.status-card');
        var statusIcon = document.getElementById('connection-status-icon');
        var statusText = document.getElementById('connection-status-text');
        
        if (connected) {
            statusCard.classList.add('status-connected');
            statusCard.classList.remove('status-disconnected');
            statusIcon.innerHTML = '<i class="fas fa-check" style="color: #1a1a1a; font-size: 20px;"></i>';
            statusText.textContent = 'Account active';
            statusText.style.color = '#a8d5ba';
        } else {
            statusCard.classList.add('status-disconnected');
            statusCard.classList.remove('status-connected');
            statusIcon.innerHTML = '<i class="fas fa-times" style="color: #fff; font-size: 20px;"></i>';
            statusText.textContent = 'Account not configured';
            statusText.style.color = '#ff6b6b';
        }
    }

    function showStatusMessage(elementId, message, type) {
        var el = document.getElementById(elementId);
        if (el) {
            var color = type === 'success' ? '#a8d5ba' : type === 'error' ? '#ff6b6b' : '#999';
            el.innerHTML = '<span style="color: ' + color + ';">' + message + '</span>';
            setTimeout(function() {
                el.innerHTML = '';
            }, 5000);
        }
    }
    
    function populatePaymentSettings(data) {
        console.log('Populating payment settings with data:', data);
        
        // Populate PayPal settings
        if (data.paypal) {
            var paypalEmail = data.paypal.email || '';
            if (paypalEmail) {
                document.getElementById('paypal-email').value = paypalEmail;
            }
            
            // Show connection status if PayPal is connected (has email or explicitly connected)
            var paypalConnected = data.paypal.connected === true || 
                                 data.paypal.status === 'connected' ||
                                 data.paypal.status === 'active' ||
                                 (paypalEmail && paypalEmail.length > 0);
            
            if (paypalConnected) {
                document.getElementById('paypal-connection-status').style.display = 'block';
                console.log('PayPal is connected with email:', paypalEmail);
            } else {
                document.getElementById('paypal-connection-status').style.display = 'none';
            }
        }
        
        // Populate Stripe settings
        if (data.stripe) {
            var stripeKey = data.stripe.publishable_key || '';
            if (stripeKey) {
                document.getElementById('stripe-publishable-key').value = stripeKey;
            }
            if (data.stripe.secret_key) {
                // Don't populate secret key for security, but show it's set
                document.getElementById('stripe-secret-key').placeholder = '•••••••• (encrypted)';
            }
            
            // Show connection status if Stripe is connected
            var stripeConnected = data.stripe.connected === true ||
                                 data.stripe.status === 'connected' ||
                                 data.stripe.status === 'active' ||
                                 (stripeKey && stripeKey.length > 0);
            
            if (stripeConnected) {
                document.getElementById('stripe-connection-status').style.display = 'block';
                console.log('Stripe is connected');
            } else {
                document.getElementById('stripe-connection-status').style.display = 'none';
            }
        }
        
        // Populate Credit Card settings
        if (data.credit_card !== undefined) {
            document.getElementById('enable-credit-card').checked = data.credit_card.enabled || false;
        }
        
        // Populate Default Payment Method
        if (data.default_payment_method) {
            document.getElementById('default-payment-method').value = data.default_payment_method;
        }
    }

    // Automatically load payment settings from SIMEZU (quietly, no user interaction)
    function loadPaymentSettings() {
        // Use WordPress REST API endpoint which handles SIMEZU connection internally
        fetch(API_URL + 'payment-settings', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': NONCE
            },
            credentials: 'include'
        })
        .then(function(response) {
            if (!response.ok) {
                // Try to get error message from response
                return response.json().then(function(errorData) {
                    console.error('Payment settings API error:', errorData);
                    throw new Error(errorData.message || 'Failed to fetch payment settings (Status: ' + response.status + ')');
                }).catch(function() {
                    throw new Error('Failed to fetch payment settings (Status: ' + response.status + ')');
                });
            }
            return response.json();
        })
        .then(function(data) {
            console.log('Payment settings response:', data);
            
            // Handle both direct response and nested data structure
            var paymentData = data.data || data;
            
            if (paymentData) {
                // Check PayPal connection - SIMEZU shows it as connected
                var paypalConnected = false;
                if (paymentData.paypal) {
                    // PayPal is connected if it has an email or explicitly marked as connected
                    paypalConnected = paymentData.paypal.connected === true || 
                                     paymentData.paypal.status === 'connected' ||
                                     paymentData.paypal.status === 'active' ||
                                     !!(paymentData.paypal.email && paymentData.paypal.email.length > 0);
                }
                
                // Check Stripe connection
                var stripeConnected = false;
                if (paymentData.stripe) {
                    stripeConnected = paymentData.stripe.connected === true ||
                                     paymentData.stripe.status === 'connected' ||
                                     paymentData.stripe.status === 'active' ||
                                     !!(paymentData.stripe.publishable_key && paymentData.stripe.publishable_key.length > 0);
                }
                
                // Overall connection status
                var connected = paymentData.connected === true || 
                               paymentData.account_status === 'active' || 
                               paymentData.account_status === 'verified' ||
                               paypalConnected ||
                               stripeConnected;
                
                var accountStatus = paymentData.account_status || (connected ? 'active' : 'not_connected');
                
                console.log('Payment status check:', {
                    paypalConnected: paypalConnected,
                    stripeConnected: stripeConnected,
                    connected: connected,
                    paypal: paymentData.paypal,
                    stripe: paymentData.stripe
                });
                
                updateConnectionStatus(connected, accountStatus);
                
                // Populate form fields with fetched data from SIMEZU
                populatePaymentSettings(paymentData);
            } else {
                // Fallback for empty response
                console.warn('Payment settings: Empty response from API');
                updateConnectionStatus(false, 'not_connected');
            }
        })
        .catch(function(err) {
            console.error('Error loading payment settings:', err);
            updateConnectionStatus(false, 'error');
            if (err.message) {
                console.error('Payment settings error details:', err.message);
            }
        });
    }
    
    // Save PayPal settings
    function savePayPalSettings() {
        var email = document.getElementById('paypal-email').value.trim();
        if (!email || !email.includes('@')) {
            showStatusMessage('paypal-status-message', 'Please enter a valid email address', 'error');
            return;
        }
        
        var btn = document.getElementById('save-paypal-btn');
        var originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        
        fetch(API_URL + 'payment-settings', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': NONCE
            },
            credentials: 'include',
            body: JSON.stringify({
                paypal: {
                    email: email
                }
            })
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                showStatusMessage('paypal-status-message', 'PayPal settings saved successfully', 'success');
                document.getElementById('paypal-connection-status').style.display = 'block';
                loadPaymentSettings(); // Reload to get updated status
            } else {
                showStatusMessage('paypal-status-message', data.message || 'Failed to save PayPal settings', 'error');
            }
        })
        .catch(function(err) {
            console.error('Save PayPal error:', err);
            showStatusMessage('paypal-status-message', 'Failed to save PayPal settings', 'error');
        })
        .finally(function() {
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    }
    
    // Save Stripe settings
    function saveStripeSettings() {
        var publishableKey = document.getElementById('stripe-publishable-key').value.trim();
        var secretKey = document.getElementById('stripe-secret-key').value.trim();
        
        if (!publishableKey) {
            showStatusMessage('stripe-status-message', 'Please enter a publishable key', 'error');
            return;
        }
        
        var btn = document.getElementById('save-stripe-btn');
        var originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        
        var payload = {
            stripe: {
                publishable_key: publishableKey
            }
        };
        if (secretKey) {
            payload.stripe.secret_key = secretKey;
        }
        
        fetch(API_URL + 'payment-settings', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': NONCE
            },
            credentials: 'include',
            body: JSON.stringify(payload)
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                showStatusMessage('stripe-status-message', 'Stripe settings saved successfully', 'success');
                document.getElementById('stripe-connection-status').style.display = 'block';
                document.getElementById('stripe-secret-key').value = ''; // Clear secret key after save
                document.getElementById('stripe-secret-key').placeholder = '•••••••• (encrypted)';
                loadPaymentSettings();
            } else {
                showStatusMessage('stripe-status-message', data.message || 'Failed to save Stripe settings', 'error');
            }
        })
        .catch(function(err) {
            console.error('Save Stripe error:', err);
            showStatusMessage('stripe-status-message', 'Failed to save Stripe settings', 'error');
        })
        .finally(function() {
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    }
    
    // Save Credit Card settings
    function saveCreditCardSettings() {
        var enabled = document.getElementById('enable-credit-card').checked;
        
        var btn = document.getElementById('save-credit-card-btn');
        var originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        
        fetch(API_URL + 'payment-settings', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': NONCE
            },
            credentials: 'include',
            body: JSON.stringify({
                credit_card: {
                    enabled: enabled
                }
            })
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                showStatusMessage('credit-card-status-message', 'Credit card settings saved successfully', 'success');
                loadPaymentSettings();
            } else {
                showStatusMessage('credit-card-status-message', data.message || 'Failed to save credit card settings', 'error');
            }
        })
        .catch(function(err) {
            console.error('Save Credit Card error:', err);
            showStatusMessage('credit-card-status-message', 'Failed to save credit card settings', 'error');
        })
        .finally(function() {
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    }
    
    // Save Default Payment Method
    function saveDefaultPaymentMethod() {
        var method = document.getElementById('default-payment-method').value;
        
        var btn = document.getElementById('save-default-payment-btn');
        var originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        
        fetch(API_URL + 'payment-settings', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': NONCE
            },
            credentials: 'include',
            body: JSON.stringify({
                default_payment_method: method
            })
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                showStatusMessage('default-payment-status-message', 'Default payment method saved successfully', 'success');
                loadPaymentSettings();
            } else {
                showStatusMessage('default-payment-status-message', data.message || 'Failed to save default payment method', 'error');
            }
        })
        .catch(function(err) {
            console.error('Save Default Payment error:', err);
            showStatusMessage('default-payment-status-message', 'Failed to save default payment method', 'error');
        })
        .finally(function() {
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    }
    
    // Test connection functions
    function testPayPalConnection() {
        var btn = document.getElementById('test-paypal-btn');
        var originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Testing...';
        
        fetch(API_URL + 'payment-settings/paypal/test', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': NONCE
            },
            credentials: 'include'
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                showStatusMessage('paypal-status-message', 'PayPal connection test successful', 'success');
            } else {
                showStatusMessage('paypal-status-message', data.message || 'PayPal connection test failed', 'error');
            }
        })
        .catch(function(err) {
            console.error('Test PayPal error:', err);
            showStatusMessage('paypal-status-message', 'Connection test failed', 'error');
        })
        .finally(function() {
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    }
    
    function testStripeConnection() {
        var btn = document.getElementById('test-stripe-btn');
        var originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Testing...';
        
        fetch(API_URL + 'payment-settings/stripe/test', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': NONCE
            },
            credentials: 'include'
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                showStatusMessage('stripe-status-message', 'Stripe connection test successful', 'success');
            } else {
                showStatusMessage('stripe-status-message', data.message || 'Stripe connection test failed', 'error');
            }
        })
        .catch(function(err) {
            console.error('Test Stripe error:', err);
            showStatusMessage('stripe-status-message', 'Connection test failed', 'error');
        })
        .finally(function() {
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    }

    // Initialize on DOM ready - automatically load settings and bind events
    function initPaymentSettings() {
        // Wait a bit for simuzeCommunity to be available if it's not yet loaded
        if (typeof simuzeCommunity === 'undefined' || !simuzeCommunity.nonce) {
            // Try to get nonce from the inline script
            if (!NONCE || NONCE === '') {
                console.warn('Payment Settings: Nonce not available, retrying in 500ms...');
                setTimeout(initPaymentSettings, 500);
                return;
            }
        } else {
            // Update NONCE if simuzeCommunity is available
            NONCE = simuzeCommunity.nonce;
        }
        
        // Verify nonce is available before proceeding
        if (!NONCE || NONCE === '') {
            console.error('Payment Settings: Nonce is still empty after retry');
            updateConnectionStatus(false, 'error');
            return;
        }
        
        loadPaymentSettings();
        
        // Bind save buttons
        document.getElementById('save-paypal-btn').addEventListener('click', savePayPalSettings);
        document.getElementById('save-stripe-btn').addEventListener('click', saveStripeSettings);
        document.getElementById('save-credit-card-btn').addEventListener('click', saveCreditCardSettings);
        document.getElementById('save-default-payment-btn').addEventListener('click', saveDefaultPaymentMethod);
        
        // Bind test buttons
        document.getElementById('test-paypal-btn').addEventListener('click', testPayPalConnection);
        document.getElementById('test-stripe-btn').addEventListener('click', testStripeConnection);
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPaymentSettings);
    } else {
        initPaymentSettings();
    }
})();
</script>

