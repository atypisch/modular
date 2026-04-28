/**
 * Simuze Community JavaScript
 */

(function($) {
    'use strict';

    if (typeof window === 'undefined' || typeof window.simuzeCommunity === 'undefined') {
        return;
    }

    const simuzeConfig = window.simuzeCommunity;

    if (!simuzeConfig || !simuzeConfig.apiUrl) {
        return;
    }

    const SimuzeCommunity = {
        init: function() {
            this.setupAPI();
            this.setupGroups();
            this.setupMessages();
            this.setupDashboard();
        },
        
        setupAPI: function() {
            // Make API available globally
            window.simuzeAPI = {
                url: simuzeConfig.apiUrl,
                nonce: simuzeConfig.nonce,

                request: function(endpoint, method, data) {
                    return $.ajax({
                        url: this.url + endpoint,
                        method: method || 'GET',
                        data: data,
                        beforeSend: function(xhr) {
                            xhr.setRequestHeader('X-WP-Nonce', simuzeConfig.nonce);
                        }
                    });
                }
            };
        },
        
        setupGroups: function() {
            // Join group
            $(document).on('click', '.join-group', function(e) {
                e.preventDefault();
                const groupId = $(this).data('group-id');
                
                window.simuzeAPI.request('groups/' + groupId + '/join', 'POST')
                    .done(function(response) {
                        location.reload();
                    })
                    .fail(function(error) {
                        if (typeof simuzeAlertError === 'function') {
                            simuzeAlertError('Failed to join group');
                        } else {
                            alert('Failed to join group');
                        }
                    });
            });
            
            // Leave group
            $(document).on('click', '.leave-group', function(e) {
                e.preventDefault();
                const groupId = $(this).data('group-id');
                
                const leaveGroup = function() {
                    window.simuzeAPI.request('groups/' + groupId + '/leave', 'POST')
                        .done(function(response) {
                            location.reload();
                        })
                        .fail(function(error) {
                            if (typeof simuzeAlertError === 'function') {
                                simuzeAlertError('Failed to leave group');
                            } else {
                                alert('Failed to leave group');
                            }
                        });
                };
                
                if (typeof simuzeConfirm === 'function') {
                    simuzeConfirm('Are you sure you want to leave this group?', 'Leave Group', leaveGroup);
                } else {
                    if (confirm('Are you sure you want to leave this group?')) {
                        leaveGroup();
                    }
                }
            });
        },
        
        setupMessages: function() {
            // Send message
            $(document).on('submit', '.send-message-form', function(e) {
                e.preventDefault();
                const form = $(this);
                const data = {
                    recipient_id: form.find('[name="recipient_id"]').val(),
                    message: form.find('[name="message"]').val(),
                    subject: form.find('[name="subject"]').val()
                };
                
                window.simuzeAPI.request('messages', 'POST', data)
                    .done(function(response) {
                        form[0].reset();
                        if (typeof simuzeAlertSuccess === 'function') {
                            simuzeAlertSuccess('Message sent!');
                        } else {
                            alert('Message sent!');
                        }
                    })
                    .fail(function(error) {
                        if (typeof simuzeAlertError === 'function') {
                            simuzeAlertError('Failed to send message');
                        } else {
                            alert('Failed to send message');
                        }
                    });
            });
        },
        
        setupDashboard: function() {
            // Make widgets clickable
            $(document).on('click', '.widget[data-link]', function(e) {
                const link = $(this).data('link');
                if (link) {
                    window.location.href = link;
                }
            });
        }
    };
    
    $(document).ready(function() {
        try {
            SimuzeCommunity.init();
        } catch (e) {
            if (window.console && typeof window.console.warn === 'function') {
                window.console.warn('[SimuzeCommunity] Init failed', e);
            }
        }
    });
    
})(jQuery);




