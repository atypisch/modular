<?php
/**
 * Group Artists Template
 * For Label groups to manage their artists
 * Only accessible by Label group type members
 */

if (!defined('SIMUZE_MODULAR_RUN') && !defined('ABSPATH')) {
    exit;
}

$auth = Simuze_Auth::get_instance();
$current_user = $auth->get_current_user();
$is_admin = $current_user && $group->can_manage($current_user->id);

// Verify this is a Label group
$group_type_data = $group->get_group_type();
$group_type_slug = $group_type_data ? $group_type_data['slug'] : null;

if ($group_type_slug !== 'label') {
    echo '<div class="access-denied"><p>This page is only available for Label groups.</p></div>';
    return;
}

// Get label artists (accepted relationships)
// Get artist type ID
$artist_type_id = md_db_var(
    "SELECT id FROM simuze_group_types WHERE slug = 'artist' AND deleted_at IS NULL"
);

// Get accepted artists for this label
$accepted_artists = array();
if ($artist_type_id) {
    $accepted_artists = md_db_query(
        "SELECT g.*, la.status, la.created_at as relationship_date, la.id as relationship_id
        FROM simuze_label_artists la
        INNER JOIN simuze_groups g ON la.artist_group_id = g.id
        WHERE la.label_group_id = ?
        AND la.status = 'accepted'
        AND g.deleted_at IS NULL
        ORDER BY la.updated_at DESC",
        [$group->id]
    );
}

// Get pending invitations (sent by this label)
$pending_invitations = md_db_query(
    "SELECT la.*, g.name as artist_name, g.slug as artist_slug, g.unique_handle as artist_handle, g.avatar_url as artist_avatar
    FROM simuze_label_artists la
    INNER JOIN simuze_groups g ON la.artist_group_id = g.id
    WHERE la.label_group_id = ?
    AND la.status = 'pending'
    AND g.deleted_at IS NULL
    ORDER BY la.created_at DESC",
    [$group->id]
);
?>

<div class="group-artists-page">
    <h2>Artists</h2>
    
    <!-- Search and Add Artists Section (Admin Only) -->
    <?php if ($is_admin): ?>
        <div class="add-artist-section" style="margin-bottom: 40px; padding: 25px; background: rgba(255,255,255,0.03); border-radius: 12px;">
            <h3>Add Artist</h3>
            <div class="artist-search-form">
                <div class="search-input-wrapper">
                    <input type="text" id="artist-search" placeholder="Search for artist name..." class="search-input">
                    <div id="artist-search-results" class="search-results"></div>
                </div>
                
                <div id="selected-artist" class="selected-artist" style="display: none; margin-top: 15px; padding: 15px; background: rgba(255,255,255,0.05); border-radius: 8px;">
                    <div class="selected-artist-info"></div>
                    <button type="button" class="button remove-artist" style="margin-top: 10px;">Remove</button>
                </div>
                
                <div class="invite-message" style="margin-top: 15px;">
                    <label for="artist-invite-message">Message to artist (optional)</label>
                    <textarea id="artist-invite-message" rows="3" placeholder="Add a personal message to your invitation..."></textarea>
                </div>
                
                <button type="button" id="send-artist-invite" class="button button-primary" disabled style="margin-top: 15px;">Send Invitation</button>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Accepted Artists -->
    <div class="artists-section">
        <h3>Artists (<?php echo count($accepted_artists); ?>)</h3>
        
        <?php if (!empty($accepted_artists)): ?>
            <div class="artists-grid">
                <?php foreach ($accepted_artists as $artist_data): 
                    $artist_group = Simuze_Group::get($artist_data['id']);
                    if (!$artist_group) continue;
                    
                    $artist_avatar_url = $artist_group->avatar_url ?: md_asset_url('images/default-group.png');
                ?>
                    <div class="artist-card" data-artist-id="<?php echo esc_attr($artist_group->id); ?>">
                        <a href="<?php echo esc_url($artist_group->get_url()); ?>" class="artist-link">
                            <img src="<?php echo esc_url($artist_avatar_url); ?>" 
                                 alt="<?php echo esc_attr($artist_group->name); ?>" 
                                 class="artist-avatar">
                            <div class="artist-info">
                                <h4><?php echo esc_html($artist_group->name); ?></h4>
                                <span class="artist-date">Added <?php echo esc_html(human_time_diff(strtotime($artist_data['relationship_date']))); ?> ago</span>
                            </div>
                        </a>
                        <?php if ($is_admin): ?>
                            <div class="artist-actions">
                                <button class="button button-danger remove-artist-relationship" 
                                        data-artist-id="<?php echo esc_attr($artist_group->id); ?>"
                                        data-relationship-id="<?php echo esc_attr($artist_data['relationship_id']); ?>">
                                    Remove
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="no-artists">No artists added yet.</p>
        <?php endif; ?>
    </div>
    
    <!-- Pending Invitations (Admin Only) -->
    <?php if ($is_admin && !empty($pending_invitations)): ?>
        <div class="pending-invitations-section" style="margin-top: 40px; padding-top: 40px; border-top: 1px solid rgba(255,255,255,0.1);">
            <h3>Pending Invitations (<?php echo count($pending_invitations); ?>)</h3>
            <div class="invitations-list">
                <?php foreach ($pending_invitations as $invitation): ?>
                    <div class="invitation-item" data-invitation-id="<?php echo esc_attr($invitation['id']); ?>">
                        <div class="invitation-artist">
                            <img src="<?php echo esc_url($invitation['artist_avatar'] ?: md_asset_url('images/default-group.png')); ?>" 
                                 alt="<?php echo esc_attr($invitation['artist_name']); ?>" 
                                 class="artist-avatar">
                            <div class="artist-info">
                                <h4><?php echo esc_html($invitation['artist_name']); ?></h4>
                                <span class="invited-date">Invited <?php echo esc_html(human_time_diff(strtotime($invitation['created_at']))); ?> ago</span>
                            </div>
                        </div>
                        <div class="invitation-actions">
                            <button class="button cancel-artist-invitation" data-id="<?php echo esc_attr($invitation['id']); ?>">Cancel</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.group-artists-page {
    padding: 20px 0;
}

.artists-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.artist-card {
    background: rgba(255,255,255,0.03);
    border-radius: 12px;
    padding: 20px;
    transition: all 0.3s ease;
}

.artist-card:hover {
    background: rgba(255,255,255,0.05);
    transform: translateY(-2px);
}

.artist-link {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-decoration: none;
    color: inherit;
}

.artist-avatar {
    width: 120px;
    height: 120px;
    border-radius: 8px;
    object-fit: cover;
    margin-bottom: 15px;
}

.artist-info {
    text-align: center;
}

.artist-info h4 {
    margin: 0 0 8px 0;
    font-size: 1.1rem;
}

.artist-date {
    font-size: 0.85rem;
    color: rgba(255,255,255,0.6);
}

.artist-actions {
    margin-top: 15px;
    text-align: center;
}

.search-input-wrapper {
    position: relative;
    width: 100%;
}

.search-input {
    width: 100%;
    padding: 12px 20px;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 8px;
    color: #fff;
    font-size: 1rem;
    box-sizing: border-box;
}

.search-results {
    position: absolute;
    top: calc(100% + 5px);
    left: 0;
    right: 0;
    max-height: 300px;
    overflow-y: auto;
    background: rgba(0,0,0,0.95);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 8px;
    z-index: 1000;
    display: none;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
}

.search-results.active {
    display: block;
}

.search-result-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 15px;
    cursor: pointer;
    transition: background 0.2s;
}

.search-result-item:hover {
    background: rgba(255,255,255,0.1);
}

.search-result-item img {
    width: 48px;
    height: 48px;
    border-radius: 8px;
    flex-shrink: 0;
}

.search-result-item .artist-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
    text-align: left;
}

.search-result-item .artist-name {
    font-weight: 500;
    color: #fff;
}

.search-result-item .artist-type {
    font-size: 0.85rem;
    color: rgba(255, 255, 255, 0.6);
}

.no-artists {
    text-align: center;
    padding: 40px;
    color: rgba(255,255,255,0.5);
}

.invitations-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
    margin-top: 20px;
}

.invitation-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 15px;
    background: rgba(255,255,255,0.03);
    border-radius: 8px;
}

.invitation-artist {
    display: flex;
    align-items: center;
    gap: 15px;
}

.invitation-artist .artist-avatar {
    width: 60px;
    height: 60px;
    margin-bottom: 0;
}

.invitation-artist .artist-info {
    text-align: left;
}

.invitation-artist .artist-info h4 {
    margin: 0 0 5px 0;
}

@media (max-width: 768px) {
    .artists-grid {
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    let selectedArtist = null;
    let searchTimeout;
    
    // Search for artists
    $('#artist-search').on('input', function() {
        const query = $(this).val().trim();
        
        clearTimeout(searchTimeout);
        
        if (query.length < 2) {
            $('#artist-search-results').removeClass('active');
            return;
        }
        
        searchTimeout = setTimeout(function() {
            $.ajax({
                url: simuzeCommunity.apiUrl + 'groups/search',
                data: { 
                    query: query, 
                    type: 'artist',
                    limit: 10,
                    exclude_group_id: <?php echo $group->id; ?>
                },
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', simuzeCommunity.nonce);
                },
                success: function(response) {
                    const results = $('#artist-search-results');
                    results.empty();
                    
                    if (response && response.groups && response.groups.length > 0) {
                        response.groups.forEach(function(artist) {
                            // Skip if already selected
                            if (selectedArtist && selectedArtist.id === artist.id) return;
                            
                            results.append(`
                                <div class="search-result-item" data-artist-id="${artist.id}" data-artist-name="${artist.name}" data-artist-handle="${artist.unique_handle || artist.slug}">
                                    <img src="${artist.avatar_url || '<?php echo htmlspecialchars(md_asset_url('images/default-group.png'), ENT_QUOTES); ?>'}" alt="">
                                    <div class="artist-info">
                                        <span class="artist-name">${artist.name}</span>
                                        <span class="artist-type">Artist</span>
                                    </div>
                                </div>
                            `);
                        });
                        results.addClass('active');
                    } else {
                        results.html('<div class="search-result-item no-results">No artists found</div>');
                        results.addClass('active');
                    }
                },
                error: function() {
                    $('#artist-search-results').removeClass('active');
                }
            });
        }, 300);
    });
    
    // Select artist from search results
    $(document).on('click', '.search-result-item:not(.no-results)', function() {
        const artistId = $(this).data('artist-id');
        const artistName = $(this).data('artist-name');
        const artistHandle = $(this).data('artist-handle');
        const artistAvatar = $(this).find('img').attr('src');
        
        selectedArtist = { 
            id: artistId, 
            name: artistName, 
            handle: artistHandle,
            avatar: artistAvatar
        };
        
        updateSelectedArtist();
        $('#artist-search').val('');
        $('#artist-search-results').removeClass('active');
    });
    
    // Remove selected artist
    $('.remove-artist').on('click', function() {
        selectedArtist = null;
        updateSelectedArtist();
    });
    
    function updateSelectedArtist() {
        const container = $('#selected-artist');
        if (selectedArtist) {
            container.show();
            container.find('.selected-artist-info').html(`
                <div style="display: flex; align-items: center; gap: 12px;">
                    <img src="${selectedArtist.avatar}" alt="${selectedArtist.name}" style="width: 48px; height: 48px; border-radius: 8px;">
                    <div>
                        <strong>${selectedArtist.name}</strong>
                        <div style="font-size: 0.85rem; color: rgba(255,255,255,0.6);">Artist</div>
                    </div>
                </div>
            `);
            $('#send-artist-invite').prop('disabled', false);
        } else {
            container.hide();
            $('#send-artist-invite').prop('disabled', true);
        }
    }
    
    // Send artist invitation
    $('#send-artist-invite').on('click', function() {
        if (!selectedArtist) return;
        
        const message = $('#artist-invite-message').val();
        const $btn = $(this);
        
        $btn.prop('disabled', true).text('Sending...');
        
        $.ajax({
            url: simuzeCommunity.apiUrl + 'groups/<?php echo $group->id; ?>/artists/invite',
            method: 'POST',
            data: JSON.stringify({ 
                artist_group_id: selectedArtist.id, 
                message: message 
            }),
            contentType: 'application/json',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', simuzeCommunity.nonce);
            },
            success: function(response) {
                if (typeof simuzeShowModal === 'function') {
                    simuzeShowModal('Invitation sent successfully!', 'success', function() {
                        window.location.reload();
                    });
                } else {
                    alert('Invitation sent successfully!');
                    window.location.reload();
                }
            },
            error: function(xhr) {
                let errorMessage = 'Failed to send invitation.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                if (typeof simuzeShowModal === 'function') {
                    simuzeShowModal(errorMessage, 'error');
                } else {
                    alert(errorMessage);
                }
                $btn.prop('disabled', false).text('Send Invitation');
            }
        });
    });
    
    // Cancel artist invitation
    $('.cancel-artist-invitation').on('click', function() {
        const invitationId = $(this).data('id');
        const $item = $(this).closest('.invitation-item');
        
        const proceedWithCancel = function() {
            $.ajax({
                url: simuzeCommunity.apiUrl + 'groups/<?php echo $group->id; ?>/artists/invitations/' + invitationId,
                method: 'DELETE',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', simuzeCommunity.nonce);
                },
                success: function() {
                    $item.fadeOut(function() { 
                        $(this).remove();
                    });
                },
                error: function(xhr) {
                    let errorMessage = 'Failed to cancel invitation.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    if (typeof simuzeShowModal === 'function') {
                        simuzeShowModal(errorMessage, 'error');
                    } else {
                        alert(errorMessage);
                    }
                }
            });
        };
        
        if (typeof simuzeShowDeleteModal === 'function') {
            simuzeShowDeleteModal('Are you sure you want to cancel this invitation?', proceedWithCancel);
        } else if (typeof simuzeConfirm === 'function') {
            simuzeConfirm('Are you sure you want to cancel this invitation?', 'Cancel Invitation', proceedWithCancel);
        } else {
            if (confirm('Are you sure you want to cancel this invitation?')) {
                proceedWithCancel();
            }
        }
    });
    
    // Remove artist relationship
    $('.remove-artist-relationship').on('click', function() {
        const artistId = $(this).data('artist-id');
        const relationshipId = $(this).data('relationship-id');
        const $card = $(this).closest('.artist-card');
        
        const proceedWithRemove = function() {
            $.ajax({
                url: simuzeCommunity.apiUrl + 'groups/<?php echo $group->id; ?>/artists/' + relationshipId,
                method: 'DELETE',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', simuzeCommunity.nonce);
                },
                success: function() {
                    $card.fadeOut(function() { 
                        $(this).remove();
                    });
                },
                error: function(xhr) {
                    let errorMessage = 'Failed to remove artist.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    if (typeof simuzeShowModal === 'function') {
                        simuzeShowModal(errorMessage, 'error');
                    } else {
                        alert(errorMessage);
                    }
                }
            });
        };
        
        if (typeof simuzeShowDeleteModal === 'function') {
            simuzeShowDeleteModal('Are you sure you want to remove this artist?', proceedWithRemove);
        } else if (typeof simuzeConfirm === 'function') {
            simuzeConfirm('Are you sure you want to remove this artist?', 'Remove Artist', proceedWithRemove);
        } else {
            if (confirm('Are you sure you want to remove this artist?')) {
                proceedWithRemove();
            }
        }
    });
});
</script>
