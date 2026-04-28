/**
 * Simuze Community - Group Creation Wizard
 */

jQuery(document).ready(function($) {
    // Modal controls
    const modal = $('#create-group-modal');
    const openBtn = $('#create-group-btn');
    const closeBtn = $('#close-modal');
    
    if (!modal.length) return;
    
    openBtn.on('click', function() {
        modal.fadeIn(200);
        $('body').css('overflow', 'hidden');
    });
    
    closeBtn.on('click', function() {
        modal.fadeOut(200);
        $('body').css('overflow', '');
    });
    
    modal.on('click', function(e) {
        if ($(e.target).is(modal)) {
            modal.fadeOut(200);
            $('body').css('overflow', '');
        }
    });
    
    // Wizard navigation
    let currentStep = 1;
    const totalSteps = 4;
    
    function updateWizard() {
        // Update panels
        $('.wizard-panel').removeClass('active');
        $(`.wizard-panel[data-step="${currentStep}"]`).addClass('active');
        
        // Update step indicators
        $('.wizard-step').removeClass('active completed');
        $('.wizard-step').each(function() {
            const step = $(this).data('step');
            if (step < currentStep) {
                $(this).addClass('completed');
            } else if (step === currentStep) {
                $(this).addClass('active');
            }
        });
        
        // Update buttons
        $('#wizard-prev').toggle(currentStep > 1);
        $('#wizard-next').toggle(currentStep < totalSteps);
        $('#wizard-submit').toggle(currentStep === totalSteps);
    }
    
    $('#wizard-next').on('click', function() {
        if (!validateStep(currentStep)) return;
        
        if (currentStep < totalSteps) {
            currentStep++;
            updateWizard();
        }
    });
    
    $('#wizard-prev').on('click', function() {
        if (currentStep > 1) {
            currentStep--;
            updateWizard();
        }
    });
    
    function validateStep(step) {
        if (step === 1) {
            const name = $('#group-name').val().trim();
            const type = $('#group-type').val();
            if (!name) {
                if (typeof simuzeAlertError === 'function') {
                    simuzeAlertError('Please enter a group name');
                } else {
                    alert('Please enter a group name');
                }
                return false;
            }
            if (!type) {
                if (typeof simuzeAlertError === 'function') {
                    simuzeAlertError('Please select a group type');
                } else {
                    alert('Please select a group type');
                }
                return false;
            }
        }
        if (step === 2) {
            const city = $('#group-city-name').val();
            if (!city) {
                if (typeof simuzeAlertError === 'function') {
                    simuzeAlertError('Please select a location');
                } else {
                    alert('Please select a location');
                }
                return false;
            }
        }
        return true;
    }
    
    // Location search with Geonames API
    let searchTimeout;
    const cityInput = $('#group-city');
    const cityDropdown = $('#city-dropdown');
    
    cityInput.on('input', function() {
        const query = $(this).val().trim();
        
        clearTimeout(searchTimeout);
        
        if (query.length < 2) {
            cityDropdown.removeClass('show').empty();
            return;
        }
        
        searchTimeout = setTimeout(function() {
            searchCities(query);
        }, 300);
    });
    
    function searchCities(query) {
        $.ajax({
            url: simuzeCommunity.ajaxUrl,
            type: 'POST',
            data: {
                action: 'simuze_search_cities',
                query: query,
                nonce: simuzeCommunity.nonce
            },
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    displayCityResults(response.data);
                } else {
                    cityDropdown.html('<div class="location-item"><span class="city-name">No cities found</span></div>').addClass('show');
                }
            },
            error: function() {
                cityDropdown.removeClass('show');
            }
        });
    }
    
    function displayCityResults(cities) {
        let html = '';
        cities.forEach(function(city) {
            html += `
                <div class="location-item" 
                     data-city="${city.name}"
                     data-province="${city.adminName1 || ''}"
                     data-country="${city.countryName || ''}"
                     data-geo-id="${city.geonameId || ''}"
                     data-lat="${city.lat || ''}"
                     data-lng="${city.lng || ''}">
                    <div class="city-name">${city.name}</div>
                    <div class="region-info">${city.adminName1 || ''}, ${city.countryName || ''}</div>
                </div>
            `;
        });
        cityDropdown.html(html).addClass('show');
    }
    
    // Select city from dropdown
    $(document).on('click', '.location-item', function() {
        const $item = $(this);
        const city = $item.data('city');
        const province = $item.data('province');
        const country = $item.data('country');
        
        // Set hidden fields
        $('#group-city-name').val(city);
        $('#group-province').val(province);
        $('#group-country').val(country);
        $('#group-geo-id').val($item.data('geo-id'));
        $('#group-lat').val($item.data('lat'));
        $('#group-lng').val($item.data('lng'));
        
        // Update display
        $('#location-display').text(`${city}, ${province}, ${country}`);
        $('#selected-location').show();
        
        // Clear input and hide dropdown
        cityInput.val('');
        cityDropdown.removeClass('show');
    });
    
    // Clear location
    $('#clear-location').on('click', function() {
        $('#group-city-name, #group-province, #group-country, #group-geo-id, #group-lat, #group-lng').val('');
        $('#selected-location').hide();
    });
    
    // Close dropdown on outside click
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.location-search-container').length) {
            cityDropdown.removeClass('show');
        }
        if (!$(e.target).closest('.member-search-container').length) {
            $('#member-dropdown').removeClass('show');
        }
    });
    
    // Image preview
    $('#group-avatar').on('change', function() {
        previewImage(this, '#avatar-preview');
    });
    
    $('#group-header').on('change', function() {
        previewImage(this, '#header-preview');
    });
    
    function previewImage(input, previewSelector) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $(previewSelector).html(`<img src="${e.target.result}" alt="Preview">`);
            };
            reader.readAsDataURL(input.files[0]);
        }
    }
    
    // Member search
    let memberSearchTimeout;
    const memberInput = $('#member-search');
    const memberDropdown = $('#member-dropdown');
    const invitedMembers = [];
    
    memberInput.on('input', function() {
        const query = $(this).val().trim();
        
        clearTimeout(memberSearchTimeout);
        
        if (query.length < 2) {
            memberDropdown.removeClass('show').empty();
            return;
        }
        
        memberSearchTimeout = setTimeout(function() {
            searchMembers(query);
        }, 300);
    });
    
    function searchMembers(query) {
        $.ajax({
            url: simuzeCommunity.apiUrl + 'search/users',
            type: 'GET',
            data: { query: query },
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', simuzeCommunity.nonce);
            },
            success: function(response) {
                if (response && response.length > 0) {
                    displayMemberResults(response);
                } else {
                    memberDropdown.html('<div class="member-item"><span class="member-name">No users found</span></div>').addClass('show');
                }
            }
        });
    }
    
    function displayMemberResults(users) {
        let html = '';
        users.forEach(function(user) {
            if (invitedMembers.includes(user.id)) return;
            
            html += `
                <div class="member-item" data-id="${user.id}" data-name="${user.display_name}" data-avatar="${user.avatar_url}" ${user.simezu_id ? 'data-simezu-user-id="' + user.simezu_id + '"' : ''}>
                    <img src="${user.avatar_url || ''}" 
                         alt=""
                         ${user.simezu_id ? 'data-simezu-user-id="' + user.simezu_id + '"' : ''}>
                    <div>
                        <div class="member-name">${user.display_name}</div>
                        <div class="member-username">@${user.username}</div>
                    </div>
                </div>
            `;
        });
        memberDropdown.html(html || '<div class="member-item"><span class="member-name">No more users to invite</span></div>').addClass('show');
    }
    
    // Add member to invite list
    $(document).on('click', '.member-item[data-id]', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        const avatar = $(this).data('avatar');
        
        if (invitedMembers.includes(id)) return;
        
        invitedMembers.push(id);
        updateInvitedMembersField();
        
        const chip = `
            <div class="member-chip" data-id="${id}">
                <img src="${avatar}" alt="">
                <span>${name}</span>
                <button type="button" class="remove-member">&times;</button>
            </div>
        `;
        $('#members-list').append(chip);
        
        memberInput.val('');
        memberDropdown.removeClass('show');
    });
    
    // Remove member from invite list
    $(document).on('click', '.remove-member', function() {
        const $chip = $(this).closest('.member-chip');
        const id = $chip.data('id');
        
        const index = invitedMembers.indexOf(id);
        if (index > -1) {
            invitedMembers.splice(index, 1);
        }
        
        updateInvitedMembersField();
        $chip.remove();
    });
    
    function updateInvitedMembersField() {
        $('#invited-member-ids').val(invitedMembers.join(','));
    }
    
    // Form submission
    $('#create-group-form').on('submit', function(e) {
        e.preventDefault();
        
        if (!validateStep(currentStep)) return;
        
        const formData = new FormData(this);
        
        const avatarFile = $('#group-avatar')[0].files[0];
        const headerFile = $('#group-header')[0].files[0];
        
        if (avatarFile) formData.append('avatar', avatarFile);
        if (headerFile) formData.append('header', headerFile);
        
        $('#wizard-submit').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Creating...');
        
        $.ajax({
            url: simuzeCommunity.apiUrl + 'groups',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', simuzeCommunity.nonce);
            },
            success: function(response) {
                if (response.id) {
                    window.location.href = response.url || simuzeCommunity.homeUrl + 'group/' + response.slug + '/';
                } else {
                    if (typeof simuzeAlertSuccess === 'function') {
                        simuzeAlertSuccess('Group created successfully!');
                    } else {
                        alert('Group created successfully!');
                    }
                    location.reload();
                }
            },
            error: function(xhr) {
                const error = xhr.responseJSON?.message || 'Failed to create group';
                if (typeof simuzeAlertError === 'function') {
                    simuzeAlertError(error);
                } else {
                    alert(error);
                }
                $('#wizard-submit').prop('disabled', false).html('<i class="fas fa-check"></i> Create Group');
            }
        });
    });
    
    // Invitation handling
    $('.accept-invite').on('click', function() {
        const groupId = $(this).data('group-id');
        const $card = $(this).closest('.invitation-card');
        
        $.ajax({
            url: simuzeCommunity.apiUrl + 'groups/' + groupId + '/accept-invitation',
            method: 'POST',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', simuzeCommunity.nonce);
            },
            success: function() {
                $card.fadeOut(function() { $(this).remove(); });
            },
            error: function() {
                if (typeof simuzeAlertError === 'function') {
                    simuzeAlertError('Failed to accept invitation.');
                } else {
                    alert('Failed to accept invitation.');
                }
            }
        });
    });
    
    $('.decline-invite').on('click', function() {
        const groupId = $(this).data('group-id');
        const $card = $(this).closest('.invitation-card');
        
        $.ajax({
            url: simuzeCommunity.apiUrl + 'groups/' + groupId + '/reject-invitation',
            method: 'POST',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', simuzeCommunity.nonce);
            },
            success: function() {
                $card.fadeOut(function() { $(this).remove(); });
            },
            error: function() {
                if (typeof simuzeAlertError === 'function') {
                    simuzeAlertError('Failed to decline invitation.');
                } else {
                    alert('Failed to decline invitation.');
                }
            }
        });
    });
});











