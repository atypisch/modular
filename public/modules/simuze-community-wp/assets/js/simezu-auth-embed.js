/**
 * Simezu Auth Embed Script
 * 
 * This script handles authentication integration between Simezu and WordPress.
 * Include this script from Simezu CDN and configure with your API key.
 * 
 * Usage:
 * <script src="https://cdn.simezu.com/auth/simuze-auth.js"></script>
 * <script>
 *   SimezuAuth.init({
 *     apiKey: 'your-api-key-here',
 *     apiUrl: 'https://api.simezu.com/auth',
 *     wordpressUrl: 'https://yoursite.com'
 *   });
 * </script>
 */

(function(window, document) {
    'use strict';

    /**
     * Simezu Auth Integration
     */
    const SimezuAuth = {
        config: {
            apiKey: null,
            apiUrl: 'https://api.simezu.com/auth',
            wordpressUrl: null,
            cookieName: 'simuze_jwt_token',
            cookieExpiry: 30 // days
        },

        /**
         * Initialize the auth integration
         */
        init: function(options) {
            if (!options || !options.apiKey) {
                console.error('SimezuAuth: API key is required');
                return;
            }

            // Merge config
            Object.assign(this.config, options);

            if (!this.config.wordpressUrl) {
                this.config.wordpressUrl = window.location.origin;
            }

            // Check for existing token
            this.checkAuth();

            // Listen for auth events from parent window (if in iframe)
            if (window.parent !== window) {
                window.addEventListener('message', this.handleMessage.bind(this));
            }

            // Expose to window
            window.SimezuAuth = this;
        },

        /**
         * Check authentication status
         */
        checkAuth: function() {
            const token = this.getToken();
            
            if (!token) {
                return false;
            }

            // Verify token with Simezu API
            this.verifyToken(token)
                .then(userData => {
                    if (userData) {
                        this.setToken(token);
                        this.syncToWordPress(userData);
                    } else {
                        this.clearToken();
                    }
                })
                .catch(error => {
                    console.error('SimezuAuth: Token verification failed', error);
                    this.clearToken();
                });

            return true;
        },

        /**
         * Get JWT token from cookie or localStorage
         */
        getToken: function() {
            // Check cookie first
            const cookies = document.cookie.split(';');
            for (let i = 0; i < cookies.length; i++) {
                const cookie = cookies[i].trim();
                if (cookie.indexOf(this.config.cookieName + '=') === 0) {
                    return cookie.substring(this.config.cookieName.length + 1);
                }
            }

            // Check localStorage
            return localStorage.getItem(this.config.cookieName);
        },

        /**
         * Set JWT token in cookie and localStorage
         */
        setToken: function(token) {
            // Set cookie
            const expiryDate = new Date();
            expiryDate.setTime(expiryDate.getTime() + (this.config.cookieExpiry * 24 * 60 * 60 * 1000));
            document.cookie = this.config.cookieName + '=' + token + ';expires=' + expiryDate.toUTCString() + ';path=/;SameSite=Lax';

            // Set localStorage
            localStorage.setItem(this.config.cookieName, token);
        },

        /**
         * Clear JWT token
         */
        clearToken: function() {
            // Clear cookie
            document.cookie = this.config.cookieName + '=;expires=Thu, 01 Jan 1970 00:00:00 UTC;path=/;';

            // Clear localStorage
            localStorage.removeItem(this.config.cookieName);
        },

        /**
         * Verify token with Simezu API
         */
        verifyToken: function(token) {
            return fetch(this.config.apiUrl + '/verify', {
                method: 'GET',
                headers: {
                    'Authorization': 'Bearer ' + token,
                    'Content-Type': 'application/json',
                    'X-API-Key': this.config.apiKey
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Token verification failed');
                }
                return response.json();
            });
        },

        /**
         * Sync user data to WordPress
         */
        syncToWordPress: function(userData) {
            // Send user data to WordPress via REST API or cookie
            // WordPress will handle the actual user creation/update server-side
            
            // Set user data in window object for WordPress to access
            window.simezuUserData = userData;

            // Trigger custom event
            const event = new CustomEvent('simezu:user:authenticated', {
                detail: userData
            });
            window.dispatchEvent(event);
        },

        /**
         * Handle messages from parent window (if in iframe)
         */
        handleMessage: function(event) {
            // Verify origin
            if (event.origin !== this.config.apiUrl.replace('/auth', '')) {
                return;
            }

            if (event.data.type === 'simezu:auth:token') {
                this.setToken(event.data.token);
                this.checkAuth();
            }
        },

        /**
         * Login user (called from Simezu login page)
         */
        login: function(token) {
            this.setToken(token);
            this.checkAuth();
        },

        /**
         * Logout user
         */
        logout: function() {
            this.clearToken();
            window.simezuUserData = null;

            // Trigger custom event
            const event = new CustomEvent('simezu:user:loggedout');
            window.dispatchEvent(event);
        }
    };

    // Auto-initialize if config is provided via data attributes
    if (document.currentScript) {
        const script = document.currentScript;
        const apiKey = script.getAttribute('data-api-key');
        const apiUrl = script.getAttribute('data-api-url');
        const wordpressUrl = script.getAttribute('data-wordpress-url');

        if (apiKey) {
            SimezuAuth.init({
                apiKey: apiKey,
                apiUrl: apiUrl || undefined,
                wordpressUrl: wordpressUrl || undefined
            });
        }
    }

    // Expose to window
    window.SimezuAuth = SimezuAuth;

})(window, document);











