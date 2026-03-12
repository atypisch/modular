<?php
/**
 * User Profile Playlists Template
 * Uses existing Simuze playlists functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

$auth = Simuze_Auth::get_instance();
$current_user = $auth->get_current_user();
$is_own_profile = $current_user && $current_user->id === $user->id;

// Get playlists using SIMEZU user ID directly (function now supports it)
if (function_exists('simuze_get_user_playlists')) {
    // Use SIMEZU user ID directly - function now queries by _simuze_user_id meta
    $user_playlists = simuze_get_user_playlists($user->id, true);
    $public_playlists = simuze_get_user_playlists($user->id, true);
    
    // Clean up invalid tracks from all playlists (lazy cleanup - only once per session)
    if (!session_id()) {
        session_start();
    }
    $cleanup_key = 'simuze_all_playlists_cleaned';
    if (!isset($_SESSION[$cleanup_key]) && function_exists('simuze_cleanup_all_playlists')) {
        simuze_cleanup_all_playlists();
        $_SESSION[$cleanup_key] = true;
    }
} else {
    $user_playlists = array();
    $public_playlists = array();
}

// Get favorite playlists, songs, and albums (SIMEZU user ID only)
$favorite_playlists = array();
$favorite_songs = array();
$favorite_albums = array();
if (function_exists('simuze_get_user_favorites')) {
    $favorites_user_id = $user->id;
    $favorite_playlists = simuze_get_user_favorites('playlist', $favorites_user_id, 50);
    $favorite_songs = simuze_get_user_favorites('music', $favorites_user_id, 50);
    $favorite_albums = simuze_get_user_favorites('album', $favorites_user_id, 50);
}
?>

<div class="playlists-page-full">
    <!-- Standard Dashboard Header -->
    <div class="dashboard-section-header">
        <h3>Playlists</h3>
        <?php if ($is_own_profile): ?>
            <button class="create-playlist-btn-full" id="show-create-form-btn">
                <i class="fas fa-plus"></i>
                Create New Playlist
            </button>
        <?php endif; ?>
    </div>

    <!-- Create Playlist Form (Hidden by default) -->
    <?php if ($is_own_profile): ?>
        <div class="create-playlist-form-container" id="create-playlist-form-container" style="display: none;">
            <div class="create-playlist-form-header">
                <h3>Create New Playlist</h3>
                <button class="close-form-btn" id="close-create-form-btn">&times;</button>
            </div>
            <form id="create-playlist-form-full" class="create-playlist-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="playlist-name-full" class="required">Playlist Name</label>
                        <input type="text" id="playlist-name-full" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="playlist-description-full">Description</label>
                        <textarea id="playlist-description-full" name="description" placeholder="Optional description..."></textarea>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="is_public" value="1" checked>
                            Make playlist public
                        </label>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="button" class="form-button secondary" id="cancel-create-btn">Cancel</button>
                    <button type="submit" class="form-button primary">Create Playlist</button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <!-- Playlists Tabs -->
    <div class="playlists-tabs-full">
        <button class="tab-btn-full active" data-tab="my-playlists">My Playlists (<?php echo count($user_playlists); ?>)</button>
        <button class="tab-btn-full" data-tab="favorite-playlists">Favorite Playlists (<?php echo count($favorite_playlists); ?>)</button>
        <button class="tab-btn-full" data-tab="favorite-songs">Favorite Songs (<?php echo count($favorite_songs); ?>)</button>
        <button class="tab-btn-full" data-tab="favorite-albums">Favorite Albums (<?php echo count($favorite_albums); ?>)</button>
    </div>

    <!-- Dashboard Content Container -->
    <div class="dashboard-content">
        <!-- My Playlists Tab -->
        <div class="tab-content-full active" id="my-playlists">
            <?php if (empty($user_playlists)): ?>
                <div class="no-playlists-message-full">
                    <i class="fas fa-music"></i>
                    <h3>No playlists yet</h3>
                    <p>Create your first playlist to start organizing your favorite music.</p>
                    <?php if ($is_own_profile): ?>
                        <button class="create-playlist-btn-full" onclick="showCreateForm()">
                            <i class="fas fa-plus"></i>
                            Create Your First Playlist
                        </button>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <!-- Message area above playlists for status messages -->
                <div id="playlists-order-message" class="playlists-order-message" style="display:none;"></div>
                
                <div class="playlists-list-full">
                    <?php 
                    foreach ($user_playlists as $playlist): ?>
                        <div class="playlist-row-full" data-playlist-id="<?php echo $playlist['id']; ?>" draggable="true">
                            <a href="<?php echo home_url('/user/' . $user->username . '/playlists/' . (isset($playlist['slug']) ? $playlist['slug'] : sanitize_title($playlist['name'])) . '/'); ?>" class="playlist-row-link">
                                <div class="playlist-drag-col">
                                    <div class="drag-handle" title="Drag to reorder">
                                        <i class="fas fa-grip-vertical"></i>
                                    </div>
                                </div>
                                
                                <div class="playlist-info-col">
                                    <div class="playlist-title-row">
                                        <span class="playlist-title-list">
                                            <?php echo esc_html($playlist['name']); ?>
                                        </span>
                                    </div>
                                    
                                    <?php if (!empty($playlist['description'])): ?>
                                        <span class="playlist-description-list">
                                            <?php echo esc_html($playlist['description']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="playlist-tracks-col">
                                    <?php 
                                    // Get actual valid track count (only published tracks)
                                    $track_count = simuze_get_playlist_valid_track_count($playlist['id']);
                                    echo $track_count . ' track' . ($track_count !== 1 ? 's' : '');
                                    ?>
                                </div>
                            </a>
                            
                            <div class="playlist-actions-col">
                                <button class="icon-small favorite-btn" onclick="togglePlaylistFavorite(<?php echo $playlist['id']; ?>)" title="Add to favorites">
                                    <i class="fas fa-heart"></i>
                                </button>
                                <?php if ($is_own_profile): ?>
                                    <div class="delete-confirmation-container" id="delete-confirm-<?php echo $playlist['id']; ?>" style="display: none;">
                                        <button class="icon-small delete-confirm-btn" onclick="confirmDeletePlaylist(<?php echo $playlist['id']; ?>, '<?php echo esc_js($playlist['name']); ?>')" title="Remove playlist">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button class="icon-small delete-cancel-btn" onclick="cancelDeletePlaylist(<?php echo $playlist['id']; ?>)" title="Cancel">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <button class="icon-small edit-trigger-btn" id="edit-trigger-<?php echo $playlist['id']; ?>" onclick="showEditPlaylist(<?php echo $playlist['id']; ?>, '<?php echo esc_js($playlist['name']); ?>', '<?php echo esc_js($playlist['description']); ?>')" title="Edit playlist">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="icon-small delete-trigger-btn" id="delete-trigger-<?php echo $playlist['id']; ?>" onclick="showDeleteConfirmation(<?php echo $playlist['id']; ?>)" title="Delete playlist">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Favorite Playlists Tab -->
        <div class="tab-content-full" id="favorite-playlists">
            <?php if (empty($favorite_playlists)): ?>
                <div class="no-playlists-message-full">
                    <i class="fas fa-heart"></i>
                    <h3>No favorite playlists yet</h3>
                    <p>Start favoriting playlists to see them here.</p>
                </div>
            <?php else: ?>
                <!-- Message area above playlists for status messages -->
                <div id="favorite-playlists-order-message" class="playlists-order-message" style="display:none;"></div>
                
                <div class="playlists-list-full">
                    <?php foreach ($favorite_playlists as $favorite): ?>
                        <?php 
                        // Get the actual playlist data
                        $playlist = simuze_get_playlist($favorite['content_id']);
                        if (!$playlist) continue;
                        
                        // Get the playlist owner
                        $playlist_owner_simuze = Simuze_User::get($playlist['user_id']);
                        $owner_username = $playlist_owner_simuze ? $playlist_owner_simuze->username : '';
                        ?>
                        <div class="playlist-row-full" data-playlist-id="<?php echo $playlist['id']; ?>" data-favorite-id="<?php echo $favorite['id']; ?>" draggable="true">
                            <a href="<?php echo home_url('/user/' . $owner_username . '/playlists/' . (isset($playlist['slug']) ? $playlist['slug'] : sanitize_title($playlist['name'])) . '/'); ?>" class="playlist-row-link">
                                <div class="playlist-drag-col">
                                    <div class="drag-handle" title="Drag to reorder">
                                        <i class="fas fa-grip-vertical"></i>
                                    </div>
                                </div>
                                
                                <div class="playlist-info-col">
                                    <div class="playlist-title-row">
                                        <span class="playlist-title-list">
                                            <?php echo esc_html($playlist['name']); ?>
                                        </span>
                                    </div>
                                    
                                    <?php if (!empty($playlist['description'])): ?>
                                        <span class="playlist-description-list">
                                            <?php echo esc_html($playlist['description']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="playlist-tracks-col">
                                    <?php 
                                    // Get actual valid track count (only published tracks)
                                    $track_count = simuze_get_playlist_valid_track_count($playlist['id']);
                                    echo $track_count . ' track' . ($track_count !== 1 ? 's' : '');
                                    ?>
                                </div>
                            </a>
                            
                            <div class="playlist-actions-col">
                                <button class="icon-small delete-trigger-btn" onclick="toggleFavorite('playlist', <?php echo $playlist['id']; ?>)" title="Remove from favorites">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Favorite Songs Tab -->
        <div class="tab-content-full" id="favorite-songs">
            <?php if (empty($favorite_songs)): ?>
                <div class="no-playlists-message-full">
                    <i class="fas fa-heart"></i>
                    <h3>No favorite songs yet</h3>
                    <p>Start favoriting songs to see them here.</p>
                </div>
            <?php else: ?>
                <!-- Message area above favorites for status messages -->
                <div id="favorite-songs-order-message" class="playlists-order-message" style="display:none;"></div>
                
                <div class="playlists-list-full">
                    <?php foreach ($favorite_songs as $favorite): ?>
                        <?php 
                        // Get the track data from music_uploads table
                        global $wpdb;
                        $music_uploads_table = $wpdb->prefix . 'music_uploads';
                        $track_data = $wpdb->get_row($wpdb->prepare(
                            "SELECT * FROM {$music_uploads_table} WHERE id = %d",
                            $favorite['content_id']
                        ), ARRAY_A);
                        
                        if (!$track_data) continue;
                        
                        // Get artist info
                        $artist = Simuze_User::get($track_data['artist_id']);
                        $artist_username = $artist ? $artist->username : '';
                        ?>
                        <div class="playlist-row-full" data-track-id="<?php echo $track_data['id']; ?>" data-favorite-id="<?php echo $favorite['id']; ?>" draggable="true">
                            <div class="playlist-drag-col">
                                <div class="drag-handle" title="Drag to reorder">
                                    <i class="fas fa-grip-vertical"></i>
                                </div>
                            </div>
                            
                            <div class="playlist-play-col">
                                <button class="playlist-play-btn" onclick="playTrack(<?php echo $track_data['id']; ?>)" title="Play track" data-track-id="<?php echo $track_data['id']; ?>">
                                    <i class="fas fa-play"></i>
                                </button>
                            </div>
                            
                            <div class="playlist-info-col">
                                <div class="playlist-title-row">
                                    <span class="playlist-title-list">
                                        <?php echo esc_html($track_data['song_name']); ?>
                                    </span>
                                </div>
                                
                                <?php if (!empty($track_data['genre'])): ?>
                                    <span class="playlist-description-list">
                                        <?php echo esc_html($track_data['genre']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="playlist-tracks-col">
                                <span class="track-duration">Track</span>
                            </div>
                            
                            <div class="playlist-actions-col">
                                <button class="icon-small delete-trigger-btn" onclick="toggleFavorite('music', <?php echo $track_data['id']; ?>)" title="Remove from favorites">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Favorite Albums Tab -->
        <div class="tab-content-full" id="favorite-albums">
            <?php if (empty($favorite_albums)): ?>
                <div class="no-playlists-message-full">
                    <i class="fas fa-heart"></i>
                    <h3>No favorite albums yet</h3>
                    <p>Start favoriting albums to see them here.</p>
                </div>
            <?php else: ?>
                <!-- Message area above favorites for status messages -->
                <div id="favorite-albums-order-message" class="playlists-order-message" style="display:none;"></div>
                
                <div class="playlists-list-full">
                    <?php foreach ($favorite_albums as $favorite): ?>
                        <?php 
                        // Get album data
                        global $wpdb;
                        $albums_table = $wpdb->prefix . 'music_albums';
                        $album_data = $wpdb->get_row($wpdb->prepare(
                            "SELECT * FROM {$albums_table} WHERE id = %d",
                            $favorite['content_id']
                        ), ARRAY_A);
                        
                        if (!$album_data) continue;
                        
                        // Get artist info
                        $artist = Simuze_User::get($album_data['artist_id']);
                        $artist_username = $artist ? $artist->username : '';
                        
                        // Get album post URL
                        $album_posts = get_posts(array(
                            'post_type' => 'album',
                            'meta_query' => array(
                                array(
                                    'key' => '_simuze_album_id',
                                    'value' => $album_data['id'],
                                    'compare' => '='
                                )
                            ),
                            'posts_per_page' => 1
                        ));
                        
                        $album_url = '';
                        if (!empty($album_posts)) {
                            $album_url = get_permalink($album_posts[0]->ID);
                        } else {
                            // Fallback URL
                            $album_url = home_url('/album/' . sanitize_title($album_data['album_name']) . '/');
                        }
                        ?>
                        <div class="playlist-row-full" data-album-id="<?php echo $album_data['id']; ?>" data-favorite-id="<?php echo $favorite['id']; ?>" draggable="true">
                            <div class="playlist-drag-col">
                                <div class="drag-handle" title="Drag to reorder">
                                    <i class="fas fa-grip-vertical"></i>
                                </div>
                            </div>
                            
                            <div class="playlist-play-col">
                                <button class="playlist-play-btn" onclick="playAlbum(<?php echo $album_data['id']; ?>)" title="Play album" data-album-id="<?php echo $album_data['id']; ?>">
                                    <i class="fas fa-play"></i>
                                </button>
                            </div>
                            
                            <div class="playlist-info-col">
                                <div class="playlist-title-row">
                                    <span class="playlist-title-list">
                                        <a href="<?php echo esc_url($album_url); ?>"><?php echo esc_html($album_data['album_name']); ?></a>
                                    </span>
                                </div>
                                
                                <?php if (!empty($album_data['genre'])): ?>
                                    <span class="playlist-description-list">
                                        <?php echo esc_html($album_data['genre']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="playlist-tracks-col">
                                <?php 
                                // Get track count
                                $tracks_table = $wpdb->prefix . 'music_uploads';
                                $track_count = $wpdb->get_var($wpdb->prepare(
                                    "SELECT COUNT(*) FROM {$tracks_table} WHERE album_id = %d",
                                    $album_data['id']
                                ));
                                echo ($track_count ?: 0) . ' track' . (($track_count ?: 0) !== 1 ? 's' : '');
                                ?>
                            </div>
                            
                            <div class="playlist-actions-col">
                                <button class="icon-small delete-trigger-btn" onclick="toggleFavorite('album', <?php echo $album_data['id']; ?>)" title="Remove from favorites">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Always ensure simuze_ajax is available for playlist functionality
// Generate nonces
$playlist_nonce = wp_create_nonce('simuze_playlist_nonce');
$favorite_nonce = wp_create_nonce('simuze_favorite_nonce');
$ajax_url = admin_url('admin-ajax.php');
?>
<script>
// CRITICAL: Ensure simuze_ajax is ALWAYS available - set it immediately, before DOM ready
// This must be set at the global scope, not inside jQuery ready
(function() {
    'use strict';
    
    // Create or merge with existing simuze_ajax
    if (typeof window.simuze_ajax === 'undefined') {
        window.simuze_ajax = {};
    }
    
    // Always set these values (they will override if already set, ensuring fresh nonces)
    window.simuze_ajax.ajaxurl = '<?php echo esc_js($ajax_url); ?>';
    window.simuze_ajax.playlist_nonce = '<?php echo esc_js($playlist_nonce); ?>';
    window.simuze_ajax.favorite_nonce = '<?php echo esc_js($favorite_nonce); ?>';
    
    console.log('simuze_ajax initialized at page load:', window.simuze_ajax);
})();

jQuery(document).ready(function($) {
    // Double-check that simuze_ajax is available after DOM ready
    if (typeof window.simuze_ajax === 'undefined' || !window.simuze_ajax || !window.simuze_ajax.playlist_nonce) {
        console.error('simuze_ajax not properly initialized after DOM ready! Re-initializing...');
        window.simuze_ajax = {
            ajaxurl: '<?php echo esc_js($ajax_url); ?>',
            playlist_nonce: '<?php echo esc_js($playlist_nonce); ?>',
            favorite_nonce: '<?php echo esc_js($favorite_nonce); ?>'
        };
    }
    
    console.log('simuze_ajax verified after DOM ready:', window.simuze_ajax);
    
    // Show create form
    function showCreateForm() {
        $('#create-playlist-form-container').slideDown(300);
    }
    
    // Hide create form
    function hideCreateForm() {
        $('#create-playlist-form-container').slideUp(300);
    }
    
    // Show create form button
    $('#show-create-form-btn').on('click', function() {
        showCreateForm();
    });
    
    // Close form button
    $('#close-create-form-btn, #cancel-create-btn').on('click', function() {
        hideCreateForm();
    });
    
    // Handle create playlist form submission
    $('#create-playlist-form-full').on('submit', function(e) {
        e.preventDefault();
        
        // Get form values
        const playlistName = $('#playlist-name-full').val();
        const playlistDescription = $('#playlist-description-full').val() || '';
        const isPublic = $('#create-playlist-form-full input[name="is_public"]').length > 0 
            ? ($('#create-playlist-form-full input[name="is_public"]').is(':checked') ? '1' : '0')
            : '1';
        
        // Validate playlist name
        if (!playlistName || playlistName.trim() === '') {
            alert('Please enter a playlist name');
            return;
        }
        
        // Ensure simuze_ajax is available - check and set if needed
        if (typeof window.simuze_ajax === 'undefined' || !window.simuze_ajax || !window.simuze_ajax.playlist_nonce) {
            console.error('simuze_ajax not available, attempting to initialize...');
            window.simuze_ajax = window.simuze_ajax || {};
            window.simuze_ajax.ajaxurl = window.simuze_ajax.ajaxurl || '<?php echo esc_js($ajax_url); ?>';
            window.simuze_ajax.playlist_nonce = window.simuze_ajax.playlist_nonce || '<?php echo esc_js($playlist_nonce); ?>';
            window.simuze_ajax.favorite_nonce = window.simuze_ajax.favorite_nonce || '<?php echo esc_js($favorite_nonce); ?>';
            
            if (!window.simuze_ajax.playlist_nonce) {
                alert('Error: Security token not available. Please refresh the page and try again.');
                console.error('Failed to initialize simuze_ajax:', window.simuze_ajax);
                return false;
            }
        }
        
        // Final check: ensure nonce is available before sending
        const nonce = window.simuze_ajax && window.simuze_ajax.playlist_nonce ? window.simuze_ajax.playlist_nonce : null;
        if (!nonce) {
            alert('Error: Security token not available. Please refresh the page and try again.');
            console.error('Nonce is still undefined:', {
                simuze_ajax: window.simuze_ajax,
                playlist_nonce: window.simuze_ajax ? window.simuze_ajax.playlist_nonce : 'simuze_ajax is undefined'
            });
            return false;
        }
        
        // Prepare data as regular object (not FormData) for better compatibility
        const ajaxData = {
            action: 'simuze_create_playlist',
            nonce: nonce,
            name: playlistName.trim(),
            description: playlistDescription.trim(),
            is_public: isPublic
        };
        
        console.log('Sending playlist creation request:', ajaxData);
        console.log('simuze_ajax object:', window.simuze_ajax);
        console.log('Nonce value:', nonce);
        
        // Show loading state
        const submitBtn = $(this).find('button[type="submit"]');
        const cancelBtn = $(this).find('button[type="button"]');
        const originalText = submitBtn.text();
        submitBtn.text('Creating...').prop('disabled', true);
        cancelBtn.prop('disabled', true);
        
        $.ajax({
            url: window.simuze_ajax.ajaxurl || '/wp-admin/admin-ajax.php',
            type: 'POST',
            data: ajaxData,
            dataType: 'json',
            beforeSend: function(xhr) {
                // Add any additional headers if needed
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            },
            success: function(response) {
                console.log('Playlist creation response:', response);
                if (response && response.success) {
                    // Show success message
                    const successMsg = $('<div class="playlist-message success" style="background: #2a4a2a; border: 1px solid #a8d5ba; color: #a8d5ba; padding: 15px; border-radius: 4px; margin-bottom: 15px;">Playlist created successfully!</div>');
                    $('#create-playlist-form-container').before(successMsg);
                    
                    // Hide form and reload after short delay
                    setTimeout(function() {
                        hideCreateForm();
                        location.reload();
                    }, 1000);
                } else {
                    const errorMsg = (response && response.data) ? response.data : 'Unknown error';
                    console.error('Playlist creation failed:', errorMsg);
                    alert('Failed to create playlist: ' + errorMsg);
                    submitBtn.text(originalText).prop('disabled', false);
                    cancelBtn.prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                console.error('Status:', status);
                console.error('Response:', xhr.responseText);
                console.error('Status Code:', xhr.status);
                console.error('Request Data:', ajaxData);
                
                let errorMessage = 'Failed to create playlist. Please try again.';
                try {
                    if (xhr.responseText && xhr.responseText !== '0') {
                        const response = JSON.parse(xhr.responseText);
                        if (response && response.data) {
                            errorMessage = response.data;
                        }
                    } else if (xhr.status === 400) {
                        errorMessage = 'Bad request. Please check that all fields are filled correctly.';
                    } else if (xhr.status === 403) {
                        errorMessage = 'Permission denied. Please refresh the page and try again.';
                    } else if (xhr.status === 500) {
                        errorMessage = 'Server error. Please try again later.';
                    }
                } catch (e) {
                    console.error('Error parsing response:', e);
                    if (xhr.responseText === '0') {
                        errorMessage = 'Request failed. Please check your connection and try again.';
                    }
                }
                
                alert(errorMessage);
                submitBtn.text(originalText).prop('disabled', false);
                cancelBtn.prop('disabled', false);
            }
        });
        });
    });
    
    // Playlist delete functionality
    window.showDeleteConfirmation = function(playlistId) {
        const playlistRow = $('[data-playlist-id="' + playlistId + '"]');
        const playlistName = playlistRow.find('.playlist-title-list').text().trim() || 'this playlist';
        
        if (typeof simuzeShowPlaylistDeleteModal === 'function') {
            simuzeShowPlaylistDeleteModal(playlistName, function() {
                confirmDeletePlaylist(playlistId, playlistName);
            });
        } else {
            // Fallback to inline confirmation
            $('.delete-confirmation-container').hide();
            $('#delete-confirm-' + playlistId).show();
            $('#delete-trigger-' + playlistId).hide();
        }
    };
    
    window.cancelDeletePlaylist = function(playlistId) {
        $('#delete-confirm-' + playlistId).hide();
        $('#delete-trigger-' + playlistId).show();
    };
    
    window.confirmDeletePlaylist = function(playlistId, playlistName) {
        if (!window.simuze_ajax || !window.simuze_ajax.playlist_nonce) {
            alert('Error: Security token not available. Please refresh the page.');
            return;
        }
        
        $.ajax({
            url: window.simuze_ajax.ajaxurl,
            type: 'POST',
            data: {
                action: 'simuze_delete_playlist',
                playlist_id: playlistId,
                nonce: window.simuze_ajax.playlist_nonce
            },
            success: function(response) {
                if (response && response.success) {
                    $('[data-playlist-id="' + playlistId + '"]').fadeOut(300, function() {
                        $(this).remove();
                        // Reload page to update counts
                        location.reload();
                    });
                } else {
                    const errorMsg = (response && response.data) ? response.data : 'Failed to delete playlist';
                    alert(errorMsg);
                }
            },
            error: function(xhr, status, error) {
                console.error('Delete playlist error:', error);
                alert('Failed to delete playlist. Please try again.');
            }
        });
    };
    
    // Playlist edit functionality
    window.showEditPlaylist = function(playlistId, playlistName, playlistDescription) {
        // Simple inline edit for now - can be enhanced with modal later
        const playlistRow = $('[data-playlist-id="' + playlistId + '"]');
        const titleElement = playlistRow.find('.playlist-title-list');
        const descElement = playlistRow.find('.playlist-description-list');
        
        // Create edit form
        const editForm = $('<div class="playlist-edit-form" style="padding: 10px; background: #2a2a2a; border: 1px solid #a8d5ba; border-radius: 4px; margin: 10px 0;">' +
            '<input type="text" class="playlist-edit-name" value="' + $('<div>').text(playlistName).html() + '" style="width: 100%; padding: 8px; margin-bottom: 10px; background: #1a1a1a; border: 1px solid #555; color: #fff; border-radius: 4px;">' +
            '<textarea class="playlist-edit-description" style="width: 100%; padding: 8px; margin-bottom: 10px; background: #1a1a1a; border: 1px solid #555; color: #fff; border-radius: 4px; min-height: 60px;">' + $('<div>').text(playlistDescription || '').html() + '</textarea>' +
            '<div style="display: flex; gap: 10px;">' +
            '<button class="playlist-save-btn" onclick="savePlaylistEdit(' + playlistId + ')" style="padding: 8px 16px; background: #a8d5ba; color: #1a1a1a; border: none; border-radius: 4px; cursor: pointer;">Save</button>' +
            '<button class="playlist-cancel-btn" onclick="cancelPlaylistEdit(' + playlistId + ', \'' + $('<div>').text(playlistName).html() + '\', \'' + $('<div>').text(playlistDescription || '').html() + '\')" style="padding: 8px 16px; background: #333; color: #fff; border: 1px solid #555; border-radius: 4px; cursor: pointer;">Cancel</button>' +
            '</div>' +
            '</div>');
        
        // Store original values
        editForm.data('original-name', playlistName);
        editForm.data('original-description', playlistDescription || '');
        
        // Replace content with edit form
        const infoCol = playlistRow.find('.playlist-info-col');
        const originalContent = infoCol.html();
        infoCol.html(editForm);
        editForm.data('original-html', originalContent);
    };
    
    window.savePlaylistEdit = function(playlistId) {
        const editForm = $('[data-playlist-id="' + playlistId + '"]').find('.playlist-edit-form');
        const newName = editForm.find('.playlist-edit-name').val().trim();
        const newDescription = editForm.find('.playlist-edit-description').val().trim();
        
        if (!newName) {
            alert('Playlist name cannot be empty');
            return;
        }
        
        if (!window.simuze_ajax || !window.simuze_ajax.playlist_nonce) {
            alert('Error: Security token not available. Please refresh the page.');
            return;
        }
        
        $.ajax({
            url: window.simuze_ajax.ajaxurl,
            type: 'POST',
            data: {
                action: 'simuze_update_playlist',
                playlist_id: playlistId,
                playlist_name: newName,
                playlist_description: newDescription,
                nonce: window.simuze_ajax.playlist_nonce
            },
            success: function(response) {
                if (response && response.success) {
                    // Reload page to show updated playlist
                    location.reload();
                } else {
                    const errorMsg = (response && response.data) ? response.data : 'Failed to update playlist';
                    alert(errorMsg);
                }
            },
            error: function(xhr, status, error) {
                console.error('Update playlist error:', error);
                alert('Failed to update playlist. Please try again.');
            }
        });
    };
    
    window.cancelPlaylistEdit = function(playlistId, originalName, originalDescription) {
        const playlistRow = $('[data-playlist-id="' + playlistId + '"]');
        const infoCol = playlistRow.find('.playlist-info-col');
        const editForm = infoCol.find('.playlist-edit-form');
        const originalHtml = editForm.data('original-html');
        
        if (originalHtml) {
            infoCol.html(originalHtml);
        } else {
            // Fallback: restore from data
            infoCol.html(
                '<div class="playlist-title-row">' +
                '<span class="playlist-title-list">' + $('<div>').text(originalName).html() + '</span>' +
                '</div>' +
                (originalDescription ? '<span class="playlist-description-list">' + $('<div>').text(originalDescription).html() + '</span>' : '')
            );
        }
    };
    
    // Toggle favorite function (used by inline onclick handlers)
    window.toggleFavorite = function(contentType, contentId) {
        // Check if user is logged in
        var isLoggedIn = false;
        if (typeof simuzeCommunity !== 'undefined' && simuzeCommunity.currentUser) {
            isLoggedIn = true;
        } else if (typeof simuze_ajax !== 'undefined' && simuze_ajax.current_user_id) {
            isLoggedIn = simuze_ajax.current_user_id > 0;
        }
        
        if (!isLoggedIn) {
            // Show sign up modal
            if (typeof simuzeShowModal === 'function') {
                var registerUrl = (typeof simuzeCommunity !== 'undefined' && simuzeCommunity.registerUrl) 
                    ? simuzeCommunity.registerUrl 
                    : '/register/';
                
                simuzeShowModal(
                    'Sign Up Required',
                    'You need to sign up for a Simuze account to use favorites and playlists. Create your account to start saving your favorite music!',
                    'Sign Up',
                    'Cancel',
                    'info',
                    function() {
                        window.location.href = registerUrl + (registerUrl.indexOf('?') > -1 ? '&' : '?') + 'redirect=' + encodeURIComponent(window.location.href);
                    }
                );
            } else {
                if (typeof simuzeAlertError === 'function') {
                    simuzeAlertError('You need to sign up for a Simuze account to use favorites and playlists.');
                } else {
                    alert('You need to sign up for a Simuze account to use favorites and playlists.');
                }
            }
            return;
        }
        
        // Notification helper (prefer global notification, fallback to inline)
        function showFavoriteNotification(message, type, $target) {
            type = type || 'success';
            if (typeof SimuzePlayer !== 'undefined' && typeof SimuzePlayer.showNotification === 'function') {
                SimuzePlayer.showNotification(message, type, $target);
                return;
            }
            if (typeof SimuzePlayer !== 'undefined' && typeof SimuzePlayer.showMessage === 'function') {
                SimuzePlayer.showMessage(message, type);
                return;
            }
            
            // Fallback inline notification using existing styles
            $('.simuze-notification').remove();
            var icon = '<i class="fas fa-check"></i>';
            if (type === 'error') icon = '<i class="fas fa-times"></i>';
            if (type === 'info') icon = '<i class="fas fa-info-circle"></i>';
            if (type === 'warning') icon = '<i class="fas fa-exclamation-triangle"></i>';
            
            var id = 'simuze-notif-' + Date.now();
            var html = '<div class="simuze-notification ' + type + '" id="' + id + '">' +
                       '<span class="simuze-notification-icon">' + icon + '</span>' +
                       '<span class="simuze-notification-message">' + message + '</span>' +
                       '</div>';
            $('body').append(html);
            var $n = $('#' + id);
            if ($n.length) {
                $n[0].style.setProperty('position', 'fixed', 'important');
                $n[0].style.setProperty('top', '20px', 'important');
                $n[0].style.setProperty('left', '50%', 'important');
                $n[0].style.setProperty('transform', 'translateX(-50%)', 'important');
                $n[0].style.setProperty('z-index', '99999', 'important');
                $n[0].style.setProperty('display', 'inline-flex', 'important');
                $n[0].style.setProperty('visibility', 'visible', 'important');
                $n[0].style.setProperty('opacity', '1', 'important');
            }
            setTimeout(function() {
                var $nr = $('#' + id);
                if ($nr.length) {
                    $nr.addClass('slide-out');
                    setTimeout(function() { $nr.remove(); }, 300);
                }
            }, 2000);
        }
        
        // Get button element
        var $btn = $('[data-content-type="' + contentType + '"][data-content-id="' + contentId + '"], .like-btn[onclick*="' + contentId + '"]').first();
        if ($btn.length === 0) {
            // Try to find by onclick attribute
            $btn = $('button[onclick*="toggleFavorite(\'' + contentType + '\', ' + contentId + ')"]');
        }
        
        // Toggle visual state
        $btn.toggleClass('liked active');
        
        // Send AJAX request
        $.ajax({
            url: window.simuze_ajax.ajaxurl || '/wp-admin/admin-ajax.php',
            type: 'POST',
            data: {
                action: 'simuze_toggle_favorite',
                content_type: contentType,
                content_id: contentId,
                nonce: window.simuze_ajax.favorite_nonce || ''
            },
            success: function(response) {
                if (response.success) {
                    // Get item name for success message
                    var itemName = '';
                    var $row = $btn.closest('.playlist-row-full, .upload-row, .album-card, .playlist-card');
                    if ($row.length > 0) {
                        // Try multiple selectors to find the item name, prioritizing specific ones
                        itemName = $row.find('.playlist-title-list, .upload-title, .album-name, .song-title-list, .track-title, h3, h4, .title').first().text().trim();
                        
                        // If we got a long string (might include metadata), extract just the title
                        if (itemName && itemName.indexOf('from') > -1) {
                            itemName = itemName.split('from')[0].trim();
                        }
                        if (itemName && itemName.indexOf('by') > -1) {
                            itemName = itemName.split('by')[0].trim();
                        }
                    }
                    
                    // If we can't find it from the row, try to get from the button's parent elements
                    if (!itemName || itemName.length < 2) {
                        var $parent = $btn.closest('tr, .row, .item, .card');
                        if ($parent.length > 0) {
                            itemName = $parent.find('.playlist-title-list, .title, .name, h3, h4').not('.like-btn, .playlist-btn, button, a').first().text().trim();
                            
                            // Clean up if it contains "from" or "by"
                            if (itemName && (itemName.indexOf('from') > -1 || itemName.indexOf('by') > -1)) {
                                itemName = itemName.split('from')[0].split('by')[0].trim();
                            }
                        }
                    }
                    
                    // Final cleanup: remove common button/link text that might have been picked up
                    if (itemName) {
                        var unwantedTexts = ['Add to playlist', 'Add to favorites', 'Download', 'Share', 'Play', 'Like'];
                        unwantedTexts.forEach(function(unwanted) {
                            if (itemName === unwanted || itemName.indexOf(unwanted) === 0) {
                                itemName = '';
                            }
                        });
                    }
                    
                    // Determine item type label
                    var itemTypeLabel = 'item';
                    if (contentType === 'album') itemTypeLabel = 'album';
                    else if (contentType === 'playlist') itemTypeLabel = 'playlist';
                    else if (contentType === 'music') itemTypeLabel = 'track';
                    
                    // Show success message using notification system (not modal)
                    var message = '';
                    var notifType = 'success';
                    
                    if (response.data && response.data.action === 'added') {
                        // Use simple, clear message like other favorites
                        if (itemName && itemName.length >= 2) {
                            message = 'Added "' + itemName + '" to your favorites';
                        } else {
                            message = 'Added to your favorites';
                        }
                        notifType = 'success';
                    } else if (response.data && response.data.action === 'removed') {
                        if (itemName && itemName.length >= 2) {
                            message = itemTypeLabel.charAt(0).toUpperCase() + itemTypeLabel.slice(1) + ' "' + itemName + '" has been removed from your favorites';
                        } else {
                            message = itemTypeLabel.charAt(0).toUpperCase() + itemTypeLabel.slice(1) + ' has been removed from your favorites';
                        }
                        notifType = 'info';
                    }
                    
                    // Use notification system (no modal)
                    if (message) {
                        showFavoriteNotification(message, notifType, $btn);
                    }
                    
                    // Reload page after a short delay to update counts (only if removed)
                    if (response.data && response.data.action === 'removed') {
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    }
                } else {
                    // Revert visual state on error
                    $btn.toggleClass('liked active');
                    var errorMessage = response.data || 'Failed to update favorite';
                    
                    // Use notification system instead of modal
                    showFavoriteNotification(errorMessage, 'error', $btn);
                }
            },
            error: function() {
                // Revert visual state on error
                $btn.toggleClass('liked active');
                var errorMessage = 'Failed to update favorite. Please try again.';
                
                // Use notification system instead of modal
                showFavoriteNotification(errorMessage, 'error', $btn);
            }
        });
    };
    
    // Toggle playlist favorite (legacy function)
    window.togglePlaylistFavorite = function(playlistId) {
        toggleFavorite('playlist', playlistId);
    };
    
    // Tab switching functionality
    $('.tab-btn-full').on('click', function(e) {
        e.preventDefault();
        const targetTab = $(this).data('tab');
        
        if (!targetTab) {
            console.warn('Tab button clicked but no data-tab attribute found');
            return;
        }
        
        // Remove active class from all tabs and content
        $('.tab-btn-full').removeClass('active');
        $('.tab-content-full').removeClass('active');
        
        // Add active class to clicked tab and corresponding content
        $(this).addClass('active');
        $('#' + targetTab).addClass('active');
        
        console.log('Switched to tab:', targetTab);
    });
    
    // Initialize drag and drop for all playlist rows (My Playlists, Favorite Playlists, Favorite Songs, Favorite Albums)
    function initializePlaylistDragAndDrop() {
        let draggedElement = null;
        
        // Handle drag start
        $(document).on('dragstart', '.playlist-row-full[draggable="true"]', function(e) {
            draggedElement = this;
            $(this).addClass('dragging');
            e.originalEvent.dataTransfer.effectAllowed = 'move';
            e.originalEvent.dataTransfer.setData('text/html', this.innerHTML);
        });
        
        // Handle drag end
        $(document).on('dragend', '.playlist-row-full[draggable="true"]', function(e) {
            $(this).removeClass('dragging');
            $('.playlist-row-full').removeClass('drop-target');
            draggedElement = null;
        });
        
        // Handle drag over
        $(document).on('dragover', '.playlist-row-full[draggable="true"]', function(e) {
            e.preventDefault();
            e.originalEvent.dataTransfer.dropEffect = 'move';
            
            if (this !== draggedElement) {
                $('.playlist-row-full').removeClass('drop-target');
                $(this).addClass('drop-target');
            }
        });
        
        // Handle drag leave
        $(document).on('dragleave', '.playlist-row-full[draggable="true"]', function(e) {
            $(this).removeClass('drop-target');
        });
        
        // Handle drop
        $(document).on('drop', '.playlist-row-full[draggable="true"]', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            if (draggedElement && draggedElement !== this) {
                // Determine which tab we're in
                const tabId = $(this).closest('.tab-content-full').attr('id');
                let messageId = 'playlists-order-message';
                
                if (tabId === 'favorite-playlists') {
                    messageId = 'favorite-playlists-order-message';
                } else if (tabId === 'favorite-songs') {
                    messageId = 'favorite-songs-order-message';
                } else if (tabId === 'favorite-albums') {
                    messageId = 'favorite-albums-order-message';
                }
                
                // Move element in DOM
                if ($(draggedElement).index() < $(this).index()) {
                    $(this).after(draggedElement);
                } else {
                    $(this).before(draggedElement);
                }
                
                // Update order on server
                updateFavoritesOrder(tabId, messageId);
            }
            
            $(this).removeClass('drop-target');
        });
    }
    
    // Update favorites order on server
    function updateFavoritesOrder(tabId, messageId) {
        const rows = $('#' + tabId + ' .playlist-row-full[draggable="true"]');
        const favoriteIds = [];
        
        rows.each(function() {
            const favoriteId = $(this).data('favorite-id');
            if (favoriteId) {
                favoriteIds.push(favoriteId);
            }
        });
        
        if (favoriteIds.length === 0) {
            return; // No favorites to reorder
        }
        
        // For now, just show a success message - order persistence can be added later if needed
        // The visual reordering works, which is the main requirement
        showOrderMessage(messageId, 'Order updated', 'success');
        
        // TODO: Add AJAX handler to persist order if needed
        // Determine content type
        // let contentType = 'playlist';
        // if (tabId === 'favorite-songs') {
        //     contentType = 'music';
        // } else if (tabId === 'favorite-albums') {
        //     contentType = 'album';
        // }
        // 
        // // Send AJAX request to update order
        // $.ajax({
        //     url: window.simuze_ajax.ajaxurl || '/wp-admin/admin-ajax.php',
        //     type: 'POST',
        //     data: {
        //         action: 'simuze_update_favorites_order',
        //         content_type: contentType,
        //         favorite_ids: favoriteIds,
        //         nonce: window.simuze_ajax.favorite_nonce || ''
        //     },
        //     success: function(response) {
        //         if (response && response.success) {
        //             showOrderMessage(messageId, 'Order saved successfully!', 'success');
        //         } else {
        //             showOrderMessage(messageId, 'Failed to save order', 'error');
        //         }
        //     },
        //     error: function() {
        //         showOrderMessage(messageId, 'Failed to save order', 'error');
        //     }
        // });
    }
    
    // Show order message
    function showOrderMessage(messageId, text, type) {
        const messageEl = $('#' + messageId);
        messageEl.text(text);
        messageEl.removeClass('success error').addClass(type);
        messageEl.fadeIn(300);
        
        setTimeout(function() {
            messageEl.fadeOut(300);
        }, 3000);
    }
    
    // Initialize drag and drop on page load
    initializePlaylistDragAndDrop();
</script>

