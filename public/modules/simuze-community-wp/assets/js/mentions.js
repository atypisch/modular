/**
 * Simuze @Mentions System
 * Provides autocomplete functionality for @username mentions
 */

(function($) {
    'use strict';
    
    const SimuzeMentions = {
        config: {
            triggerChar: '@',
            minChars: 1,
            maxResults: 8,
            debounceTime: 200,
            apiEndpoint: null,
            nonce: null
        },
        
        activeInput: null,
        dropdown: null,
        searchTimeout: null,
        selectedIndex: -1,
        results: [],
        
        init: function(options) {
            $.extend(this.config, options);
            
            if (!this.config.apiEndpoint) {
                this.config.apiEndpoint = simuzeCommunity.apiUrl + 'users/search';
            }
            if (!this.config.nonce) {
                this.config.nonce = simuzeCommunity.nonce;
            }
            
            this.createDropdown();
            this.bindEvents();
        },
        
        createDropdown: function() {
            this.dropdown = $('<div class="simuze-mentions-dropdown"></div>').appendTo('body');
            this.dropdown.hide();
        },
        
        bindEvents: function() {
            const self = this;
            
            // Listen for input on mention-enabled elements
            $(document).on('input', '[data-mentions="true"], .mentions-enabled', function(e) {
                self.handleInput(e, $(this));
            });
            
            // Handle keydown for navigation
            $(document).on('keydown', '[data-mentions="true"], .mentions-enabled', function(e) {
                if (self.dropdown.is(':visible')) {
                    self.handleKeydown(e);
                }
            });
            
            // Handle click on dropdown item
            $(document).on('click', '.mentions-item', function(e) {
                e.preventDefault();
                self.selectUser($(this).data('user'));
            });
            
            // Hide dropdown when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.simuze-mentions-dropdown').length) {
                    self.hideDropdown();
                }
            });
            
            // Hide dropdown on blur (with delay for click handling)
            $(document).on('blur', '[data-mentions="true"], .mentions-enabled', function() {
                setTimeout(function() {
                    self.hideDropdown();
                }, 200);
            });
        },
        
        handleInput: function(e, $input) {
            const self = this;
            this.activeInput = $input;
            
            const text = $input.val();
            const cursorPos = $input[0].selectionStart;
            
            // Find the @ trigger before cursor
            const textBeforeCursor = text.substring(0, cursorPos);
            const lastAtIndex = textBeforeCursor.lastIndexOf(this.config.triggerChar);
            
            if (lastAtIndex === -1) {
                this.hideDropdown();
                return;
            }
            
            // Check if there's a space between @ and cursor (not in a mention)
            const textAfterAt = textBeforeCursor.substring(lastAtIndex + 1);
            if (textAfterAt.includes(' ')) {
                this.hideDropdown();
                return;
            }
            
            // Check if @ is at start or preceded by whitespace
            if (lastAtIndex > 0 && !/\s/.test(text.charAt(lastAtIndex - 1))) {
                this.hideDropdown();
                return;
            }
            
            const query = textAfterAt;
            
            if (query.length < this.config.minChars) {
                this.hideDropdown();
                return;
            }
            
            // Debounce search
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(function() {
                self.searchUsers(query, $input);
            }, this.config.debounceTime);
        },
        
        searchUsers: function(query, $input) {
            const self = this;
            
            $.ajax({
                url: this.config.apiEndpoint,
                data: { query: query, limit: this.config.maxResults },
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', self.config.nonce);
                },
                success: function(response) {
                    if (response.users && response.users.length > 0) {
                        self.results = response.users;
                        self.showDropdown($input, response.users);
                    } else {
                        self.hideDropdown();
                    }
                },
                error: function() {
                    self.hideDropdown();
                }
            });
        },
        
        showDropdown: function($input, users) {
            const self = this;
            
            // Position dropdown
            const offset = $input.offset();
            const height = $input.outerHeight();
            
            this.dropdown.css({
                top: offset.top + height + 5,
                left: offset.left,
                minWidth: $input.outerWidth()
            });
            
            // Build dropdown content
            let html = '';
            users.forEach(function(user, index) {
                html += `
                    <div class="mentions-item ${index === self.selectedIndex ? 'selected' : ''}" 
                         data-user='${JSON.stringify(user)}' 
                         data-index="${index}">
                        <img src="${user.avatar_url || ''}" alt="" class="mentions-avatar">
                        <div class="mentions-info">
                            <span class="mentions-name">${user.display_name}</span>
                            <span class="mentions-username">@${user.username}</span>
                        </div>
                    </div>
                `;
            });
            
            this.dropdown.html(html).show();
            this.selectedIndex = -1;
        },
        
        hideDropdown: function() {
            this.dropdown.hide();
            this.selectedIndex = -1;
            this.results = [];
        },
        
        handleKeydown: function(e) {
            const itemCount = this.results.length;
            
            switch (e.keyCode) {
                case 40: // Down arrow
                    e.preventDefault();
                    this.selectedIndex = Math.min(this.selectedIndex + 1, itemCount - 1);
                    this.updateSelection();
                    break;
                    
                case 38: // Up arrow
                    e.preventDefault();
                    this.selectedIndex = Math.max(this.selectedIndex - 1, 0);
                    this.updateSelection();
                    break;
                    
                case 13: // Enter
                    e.preventDefault();
                    if (this.selectedIndex >= 0 && this.results[this.selectedIndex]) {
                        this.selectUser(this.results[this.selectedIndex]);
                    }
                    break;
                    
                case 27: // Escape
                    e.preventDefault();
                    this.hideDropdown();
                    break;
            }
        },
        
        updateSelection: function() {
            this.dropdown.find('.mentions-item').removeClass('selected');
            this.dropdown.find('.mentions-item[data-index="' + this.selectedIndex + '"]').addClass('selected');
        },
        
        selectUser: function(user) {
            if (!this.activeInput || !user) return;
            
            const $input = this.activeInput;
            const text = $input.val();
            const cursorPos = $input[0].selectionStart;
            
            // Find the @ trigger
            const textBeforeCursor = text.substring(0, cursorPos);
            const lastAtIndex = textBeforeCursor.lastIndexOf(this.config.triggerChar);
            
            if (lastAtIndex === -1) return;
            
            // Replace the @query with @username
            const textAfterCursor = text.substring(cursorPos);
            const newText = text.substring(0, lastAtIndex) + '@' + user.username + ' ' + textAfterCursor;
            
            $input.val(newText);
            
            // Set cursor position after the mention
            const newCursorPos = lastAtIndex + user.username.length + 2;
            $input[0].setSelectionRange(newCursorPos, newCursorPos);
            
            // Trigger input event for any listeners
            $input.trigger('input');
            $input.trigger('mention:added', [user]);
            
            this.hideDropdown();
        },
        
        // Parse mentions in text and return array of usernames
        parseMentions: function(text) {
            const regex = /@([a-zA-Z0-9_]+)/g;
            const mentions = [];
            let match;
            
            while ((match = regex.exec(text)) !== null) {
                mentions.push(match[1]);
            }
            
            return mentions;
        },
        
        // Convert @mentions to links
        linkifyMentions: function(text) {
            return text.replace(/@([a-zA-Z0-9_]+)/g, function(match, username) {
                return '<a href="/user/' + username + '/" class="mention-link">@' + username + '</a>';
            });
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        if (typeof simuzeCommunity !== 'undefined') {
            SimuzeMentions.init();
        }
    });
    
    // Expose globally
    window.SimuzeMentions = SimuzeMentions;
    
})(jQuery);











