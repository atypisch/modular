/**
 * Simuze Community - Create Group Page
 * Full page wizard with image cropping and validation
 */

jQuery(document).ready(function($) {
    
    // Minimum image dimensions
    const MIN_AVATAR_SIZE = 200;
    const MIN_HEADER_WIDTH = 1200;
    const MIN_HEADER_HEIGHT = 300;
    
    // Cropper instances
    let avatarCropper = null;
    let headerCropper = null;
    
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
        
        // Scroll to top
        $('html, body').animate({ scrollTop: 0 }, 300);
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
                showMessage('Please enter a group name', 'error');
                $('#group-name').focus();
                return false;
            }
            if (!type) {
                showMessage('Please select a group type', 'error');
                $('#group-type').focus();
                return false;
            }
        }
        return true;
    }
    
    function showMessage(message, type) {
        const $msg = $('#group-creation-message');
        $msg.removeClass('error success').addClass(type).text(message).fadeIn(200);
        setTimeout(function() {
            $msg.fadeOut(200);
        }, 3000);
    }
    
    // Location search
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
                    cityDropdown.html('<div class="location-item">No cities found</div>').addClass('show');
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
    
    $(document).on('click', '.location-item', function() {
        const $item = $(this);
        const city = $item.data('city');
        const province = $item.data('province');
        const country = $item.data('country');
        
        $('#group-city-name').val(city);
        $('#group-province').val(province);
        $('#group-country').val(country);
        $('#group-geo-id').val($item.data('geo-id'));
        $('#group-lat').val($item.data('lat'));
        $('#group-lng').val($item.data('lng'));
        
        $('#location-display').text(`${city}, ${province}, ${country}`);
        $('#selected-location').show();
        
        cityInput.val('');
        cityDropdown.removeClass('show');
    });
    
    $('#clear-location').on('click', function() {
        $('#group-city-name, #group-province, #group-country, #group-geo-id, #group-lat, #group-lng').val('');
        $('#selected-location').hide();
        $('#location-display').empty();
    });
    
    // Image Upload & Crop - Avatar
    $('#avatar-upload-area').on('click', function() {
        $('#group-avatar').click();
    });
    
    $('#group-avatar').on('change', function() {
        handleImageUpload(this, 'avatar', MIN_AVATAR_SIZE, MIN_AVATAR_SIZE, 1);
    });
    
    $('#avatar-change').on('click', function() {
        resetImageUpload('avatar');
    });
    
    $('#avatar-cancel-crop').on('click', function() {
        cancelCrop('avatar');
    });
    
    $('#avatar-apply-crop').on('click', function() {
        applyCrop('avatar', MIN_AVATAR_SIZE, MIN_AVATAR_SIZE, 1);
    });
    
    // Image Upload & Crop - Header
    $('#header-upload-area').on('click', function() {
        $('#group-header').click();
    });
    
    $('#group-header').on('change', function() {
        handleImageUpload(this, 'header', MIN_HEADER_WIDTH, MIN_HEADER_HEIGHT, 4/1);
    });
    
    $('#header-change').on('click', function() {
        resetImageUpload('header');
    });
    
    $('#header-cancel-crop').on('click', function() {
        cancelCrop('header');
    });
    
    $('#header-apply-crop').on('click', function() {
        applyCrop('header', MIN_HEADER_WIDTH, MIN_HEADER_HEIGHT, 4/1);
    });
    
    function handleImageUpload(input, type, minWidth, minHeight, aspectRatio) {
        const file = input.files[0];
        if (!file) {
            console.log('No file selected');
            return;
        }
        
        // Validate file type
        if (!file.type.match('image.*')) {
            showMessage('Please select an image file', 'error');
            $(input).val('');
            return;
        }
        
        const reader = new FileReader();
        reader.onerror = function() {
            showMessage('Error reading image file', 'error');
            $(input).val('');
        };
        
        reader.onload = function(e) {
            const imageSrc = e.target.result;
            const img = new Image();
            img.onerror = function() {
                showMessage('Error loading image', 'error');
                $(input).val('');
            };
            img.onload = function() {
                // Check minimum dimensions
                if (img.width < minWidth || img.height < minHeight) {
                    showMessage(`${type === 'avatar' ? 'Avatar' : 'Header'} image is too small. Minimum size: ${minWidth}x${minHeight}px.`, 'error');
                    $(input).val('');
                    return;
                }
                
                // Step 1: Show image preview first
                showImagePreview(type, imageSrc, minWidth, minHeight, aspectRatio);
            };
            img.src = imageSrc;
        };
        reader.readAsDataURL(file);
    }
    
    function showImagePreview(type, imageSrc, minWidth, minHeight, aspectRatio) {
        console.log('showImagePreview called for', type, 'ImageSrc length:', imageSrc ? imageSrc.length : 0);
        
        const $uploadArea = $(`#${type}-upload-area`);
        const $cropContainer = $(`#${type}-crop-container`);
        const $cropImage = $(`#${type}-crop-image`);
        
        if (!$cropContainer.length || !$cropImage.length) {
            console.error('Crop container or image element not found');
            showMessage('Error: Crop elements not found', 'error');
            return;
        }
        
        // Hide upload area
        $uploadArea.hide();
        
        // Show crop container with explicit styles
        $cropContainer.css({
            'display': 'block !important',
            'visibility': 'visible',
            'opacity': '1'
        }).show();
        
        // Get the image element
        const imgElement = $cropImage[0];
        if (!imgElement) {
            console.error('Image element not found:', `#${type}-crop-image`);
            showMessage('Error: Image element not found', 'error');
            return;
        }
        
        // Clear any previous src
        imgElement.src = '';
        
        // Set up load handler BEFORE setting src
        $cropImage.off('load error').on('load', function() {
            console.log('Image loaded successfully for', type, 'Dimensions:', imgElement.naturalWidth, 'x', imgElement.naturalHeight);
            // Ensure image is visible and properly sized
            if (type === 'header') {
                $(this).css({
                    'display': 'block',
                    'visibility': 'visible',
                    'opacity': '1',
                    'max-width': '100%',
                    'width': '100%',
                    'height': 'auto',
                    'min-height': '400px'
                });
            } else {
                $(this).css({
                    'display': 'block',
                    'visibility': 'visible',
                    'opacity': '1',
                    'max-width': '100%',
                    'width': 'auto',
                    'height': 'auto'
                });
            }
            
            // Initialize cropper after image is loaded
            setTimeout(function() {
                initializeCropperForImage(type, imgElement, minWidth, minHeight, aspectRatio);
            }, 200);
        }).on('error', function() {
            console.error('Image failed to load for', type);
            showMessage('Failed to load image. Please try again.', 'error');
            $uploadArea.show();
            $cropContainer.hide();
        });
        
        // Set image source - this will trigger the load event
        imgElement.src = imageSrc;
        
        // Force image to be visible
        if (type === 'header') {
            $cropImage.css({
                'display': 'block',
                'max-width': '100%',
                'width': '100%',
                'height': 'auto',
                'min-height': '400px',
                'visibility': 'visible',
                'opacity': '1'
            });
        } else {
            $cropImage.css({
                'display': 'block',
                'max-width': '100%',
                'width': 'auto',
                'height': 'auto',
                'visibility': 'visible',
                'opacity': '1'
            });
        }
        
        console.log('Image src set for', type, 'Container visible:', $cropContainer.is(':visible'));
        
        // If image is already loaded (cached), trigger load manually
        if (imgElement.complete && imgElement.naturalHeight !== 0) {
            console.log('Image already loaded (cached) for', type);
            $cropImage.trigger('load');
        }
    }
    
    function initializeCropperForImage(type, imgElement, minWidth, minHeight, aspectRatio) {
        console.log('initializeCropperForImage called for', type);
        
        // Check if Cropper is available
        if (typeof Cropper === 'undefined') {
            console.error('Cropper.js is not loaded');
            showMessage('Image cropper is not loaded. Please refresh the page.', 'error');
            return;
        }
        
        // Check if image is loaded
        if (!imgElement.complete || imgElement.naturalWidth === 0) {
            console.error('Image not fully loaded - complete:', imgElement.complete, 'naturalWidth:', imgElement.naturalWidth);
            showMessage('Image failed to load properly. Please try again.', 'error');
            return;
        }
        
        console.log('Initializing Cropper for', type, 'Image dimensions:', imgElement.naturalWidth, 'x', imgElement.naturalHeight);
        
        // Destroy existing cropper if any
        if (type === 'avatar' && avatarCropper) {
            avatarCropper.destroy();
            avatarCropper = null;
        } else if (type === 'header' && headerCropper) {
            headerCropper.destroy();
            headerCropper = null;
        }
        
        try {
            // Calculate minimum crop box size based on image display size
            // For header images, use a percentage of the image size instead of absolute pixels
            const imgDisplayWidth = imgElement.offsetWidth || imgElement.naturalWidth;
            const imgDisplayHeight = imgElement.offsetHeight || imgElement.naturalHeight;
            
            // For header, use 50% of display size as minimum, but ensure it's at least 400px wide
            let minCropWidth = type === 'header' ? Math.max(400, imgDisplayWidth * 0.5) : minWidth;
            let minCropHeight = type === 'header' ? Math.max(100, imgDisplayHeight * 0.5) : minHeight;
            
            // Initialize Cropper.js
            const cropperOptions = {
                aspectRatio: aspectRatio,
                viewMode: 1,
                dragMode: 'move',
                autoCropArea: 0.8,
                restore: false,
                guides: true,
                center: true,
                highlight: false,
                cropBoxMovable: true,
                cropBoxResizable: true,
                toggleDragModeOnDblclick: false,
                minCropBoxWidth: minCropWidth,
                minCropBoxHeight: minCropHeight,
                ready: function() {
                    console.log('Cropper ready for', type);
                    // Ensure crop box meets minimum size
                    const cropper = type === 'avatar' ? avatarCropper : headerCropper;
                    if (cropper) {
                        const cropBoxData = cropper.getCropBoxData();
                        // For header, ensure aspect ratio is maintained
                        if (type === 'header') {
                            const currentAspect = cropBoxData.width / cropBoxData.height;
                            const targetAspect = aspectRatio;
                            if (Math.abs(currentAspect - targetAspect) > 0.1) {
                                // Adjust to maintain aspect ratio
                                const newWidth = cropBoxData.height * targetAspect;
                                cropper.setCropBoxData({
                                    width: Math.max(newWidth, minCropWidth),
                                    height: cropBoxData.height
                                });
                            }
                        }
                    }
                }
            };
            
            if (type === 'avatar') {
                avatarCropper = new Cropper(imgElement, cropperOptions);
                console.log('Avatar cropper initialized successfully');
            } else {
                headerCropper = new Cropper(imgElement, cropperOptions);
                console.log('Header cropper initialized successfully');
            }
        } catch (error) {
            console.error('Error initializing Cropper:', error);
            showMessage('Error initializing image cropper: ' + error.message, 'error');
        }
    }
    
    
    function applyCrop(type, minWidth, minHeight, aspectRatio) {
        console.log('applyCrop called for', type);
        const cropper = type === 'avatar' ? avatarCropper : headerCropper;
        if (!cropper) {
            console.error('No cropper instance found for', type);
            showMessage('Crop tool not initialized. Please try uploading again.', 'error');
            return;
        }
        
        try {
            const canvas = cropper.getCroppedCanvas({
                width: minWidth,
                height: minHeight,
                imageSmoothingEnabled: true,
                imageSmoothingQuality: 'high'
            });
            
            if (!canvas) {
                console.error('Failed to get cropped canvas');
                showMessage('Failed to crop image. Please try again.', 'error');
                return;
            }
            
            console.log('Canvas created:', canvas.width, 'x', canvas.height);
            
            // Validate final dimensions
            if (canvas.width < minWidth || canvas.height < minHeight) {
                showMessage(`Cropped image is too small. Please ensure it meets minimum dimensions: ${minWidth}x${minHeight}px.`, 'error');
                return;
            }
            
            const croppedDataUrl = canvas.toDataURL('image/png');
            console.log('Cropped data URL length:', croppedDataUrl.length);
            
            if (!croppedDataUrl || croppedDataUrl.length < 100) {
                console.error('Invalid cropped data URL');
                showMessage('Failed to generate cropped image. Please try again.', 'error');
                return;
            }
            
            // Save cropped data
            $(`#${type}-cropped-data`).val(croppedDataUrl);
            
            // Show preview
            const $preview = $(`#${type}-preview`);
            const $previewImg = $(`#${type}-preview-img`);
            
            if ($preview.length && $previewImg.length) {
                $previewImg.attr('src', croppedDataUrl);
                $preview.show();
                console.log('Preview shown for', type);
            } else {
                console.error('Preview elements not found:', `#${type}-preview`, `#${type}-preview-img`);
            }
            
            // Hide crop interface
            $(`#${type}-crop-container`).hide();
            
            // Destroy cropper
            cropper.destroy();
            if (type === 'avatar') {
                avatarCropper = null;
            } else {
                headerCropper = null;
            }
            
            showMessage(`${type === 'avatar' ? 'Avatar' : 'Header'} image saved successfully!`, 'success');
        } catch (error) {
            console.error('Error in applyCrop:', error);
            showMessage('Error cropping image: ' + error.message, 'error');
        }
    }
    
    function cancelCrop(type) {
        const cropper = type === 'avatar' ? avatarCropper : headerCropper;
        if (cropper) {
            cropper.destroy();
            if (type === 'avatar') {
                avatarCropper = null;
            } else {
                headerCropper = null;
            }
        }
        
        $(`#${type}-crop-container`).hide();
        $(`#${type}-upload-area`).show();
        $(`#group-${type}`).val('');
    }
    
    function resetImageUpload(type) {
        $(`#${type}-preview`).hide();
        $(`#${type}-upload-area`).show();
        $(`#group-${type}`).val('');
        $(`#${type}-cropped-data`).val('');
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
                    memberDropdown.html('<div class="member-item">No users found</div>').addClass('show');
                }
            },
            error: function() {
                memberDropdown.removeClass('show');
            }
        });
    }
    
    function displayMemberResults(users) {
        let html = '';
        users.forEach(function(user) {
            if (invitedMembers.includes(user.id) || (simuzeCommunity.currentUser && user.id === simuzeCommunity.currentUser.id)) {
                return;
            }
            
            html += `
                <div class="member-item" data-id="${user.id}" data-name="${user.display_name}" data-avatar="${user.avatar_url || ''}">
                    <img src="${user.avatar_url || simuzeCommunity.defaultAvatar || ''}" alt="">
                    <div>
                        <div class="member-name">${user.display_name}</div>
                        <div class="member-username">@${user.username}</div>
                    </div>
                </div>
            `;
        });
        memberDropdown.html(html || '<div class="member-item">No more users to invite</div>').addClass('show');
    }
    
    $(document).on('click', '.member-item[data-id]', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        const avatar = $(this).data('avatar');
        
        if (invitedMembers.includes(id)) return;
        
        invitedMembers.push(id);
        updateInvitedMembersField();
        
        const chip = `
            <div class="member-chip" data-id="${id}">
                <img src="${avatar || simuzeCommunity.defaultAvatar || ''}" alt="">
                <span>${name}</span>
                <button type="button" class="remove-member">&times;</button>
            </div>
        `;
        $('#members-list').append(chip);
        $('#no-members-text').hide();
        
        memberInput.val('');
        memberDropdown.removeClass('show');
    });
    
    $(document).on('click', '.remove-member', function() {
        const $chip = $(this).closest('.member-chip');
        const id = $chip.data('id');
        
        const index = invitedMembers.indexOf(id);
        if (index > -1) {
            invitedMembers.splice(index, 1);
        }
        
        updateInvitedMembersField();
        $chip.remove();
        
        if ($('#members-list .member-chip').length === 0) {
            $('#no-members-text').show();
        }
    });
    
    function updateInvitedMembersField() {
        $('#invited-member-ids').val(invitedMembers.join(','));
    }
    
    // Close dropdowns on outside click
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.location-search-container').length) {
            cityDropdown.removeClass('show');
        }
        if (!$(e.target).closest('.member-search-container').length) {
            memberDropdown.removeClass('show');
        }
    });
    
    // Form submission
    $('#create-group-form').on('submit', function(e) {
        e.preventDefault();
        
        if (!validateStep(currentStep)) return;
        
        // Validate required fields
        const name = $('#group-name').val().trim();
        const groupTypeId = $('#group-type').val();
        
        if (!name) {
            showMessage('Please enter a group name', 'error');
            $('#group-name').focus();
            return;
        }
        
        if (!groupTypeId) {
            showMessage('Please select a group type', 'error');
            $('#group-type').focus();
            return;
        }
        
        // Prepare form data
        const formData = new FormData();
        formData.append('name', name);
        formData.append('group_type_id', groupTypeId);
        
        const description = $('#group-description').val().trim();
        if (description) {
            formData.append('description', description);
        }
        
        // Location data
        const city = $('#group-city-name').val();
        const province = $('#group-province').val();
        const country = $('#group-country').val();
        const geoId = $('#group-geo-id').val();
        const lat = $('#group-lat').val();
        const lng = $('#group-lng').val();
        
        if (city) formData.append('city', city);
        if (province) formData.append('province', province);
        if (country) formData.append('country', country);
        if (geoId) formData.append('geo_id', geoId);
        if (lat) formData.append('lat', lat);
        if (lng) formData.append('lng', lng);
        
        // Invited members
        const invitedMembers = $('#invited-member-ids').val();
        if (invitedMembers) {
            formData.append('invited_members', invitedMembers);
        }
        
        // Add cropped images
        const avatarData = $('#avatar-cropped-data').val();
        if (avatarData) {
            formData.append('avatar_cropped', avatarData);
            console.log('Avatar data length:', avatarData.length);
        }
        
        const headerData = $('#header-cropped-data').val();
        if (headerData) {
            formData.append('header_cropped', headerData);
            console.log('Header data length:', headerData.length);
        }
        
        console.log('Submitting group creation form...');
        console.log('Group name:', name);
        console.log('Group type ID:', groupTypeId);
        
        $('#wizard-submit').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Creating...');
        showMessage('Creating group...', 'success');
        
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
                console.log('Group creation response:', response);
                if (response && response.id) {
                    showMessage('Group created successfully! Redirecting...', 'success');
                    setTimeout(function() {
                        const redirectUrl = response.url || (simuzeCommunity.homeUrl + 'group/' + response.slug + '/');
                        console.log('Redirecting to:', redirectUrl);
                        window.location.href = redirectUrl;
                    }, 1500);
                } else {
                    const errorMsg = (response && response.message) ? response.message : 'Failed to create group. Please check the console for details.';
                    showMessage(errorMsg, 'error');
                    console.error('Group creation failed - Response:', response);
                    $('#wizard-submit').prop('disabled', false).html('<i class="fas fa-check"></i> Create Group');
                }
            },
            error: function(xhr) {
                console.error('Group creation error:', xhr);
                console.error('Response text:', xhr.responseText);
                console.error('Status:', xhr.status);
                
                let errorMsg = 'An error occurred while creating the group.';
                
                if (xhr.responseJSON) {
                    if (xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    } else if (xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        errorMsg = xhr.responseJSON.data.message;
                    }
                    console.error('Error details:', xhr.responseJSON);
                }
                
                showMessage(errorMsg, 'error');
                $('#wizard-submit').prop('disabled', false).html('<i class="fas fa-check"></i> Create Group');
            }
        });
    });
    
    // Initial wizard state
    updateWizard();
});
