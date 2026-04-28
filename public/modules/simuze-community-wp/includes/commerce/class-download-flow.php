<?php
/**
 * Download Flow Handler
 * 
 * Manages the complete download & payment experience:
 * - Pay-what-you-want modal
 * - Payment processing via SIMEZU
 * - Download access with format selection
 * - Email notifications
 * - Payment history
 */

if (!defined('ABSPATH')) {
    exit;
}

class Simuze_Download_Flow {
    
    private static $instance = null;
    
    // Available download formats
    const FORMATS = array(
        'mp3_320' => array(
            'label' => 'MP3 320kbps',
            'extension' => 'mp3',
            'mime' => 'audio/mpeg',
            'always_available' => true,
        ),
        'flac' => array(
            'label' => 'FLAC (Lossless)',
            'extension' => 'flac',
            'mime' => 'audio/flac',
            'always_available' => false,
        ),
        'wav' => array(
            'label' => 'WAV (Uncompressed)',
            'extension' => 'wav',
            'mime' => 'audio/wav',
            'always_available' => false,
        ),
        'aiff' => array(
            'label' => 'AIFF',
            'extension' => 'aiff',
            'mime' => 'audio/aiff',
            'always_available' => false,
        ),
    );
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // AJAX handlers for download flow
        add_action('wp_ajax_simuze_get_product_info', array($this, 'ajax_get_product_info'));
        add_action('wp_ajax_nopriv_simuze_get_product_info', array($this, 'ajax_get_product_info'));
        
        add_action('wp_ajax_simuze_create_download_order', array($this, 'ajax_create_download_order'));
        add_action('wp_ajax_nopriv_simuze_create_download_order', array($this, 'ajax_create_download_order'));
        
        add_action('wp_ajax_simuze_process_download', array($this, 'ajax_process_download'));
        add_action('wp_ajax_nopriv_simuze_process_download', array($this, 'ajax_process_download'));
        
        add_action('wp_ajax_simuze_get_download_formats', array($this, 'ajax_get_download_formats'));
        add_action('wp_ajax_nopriv_simuze_get_download_formats', array($this, 'ajax_get_download_formats'));
        
        // Render modal in footer
        add_action('wp_footer', array($this, 'render_download_modal'));
        
        // Enqueue scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Handle download page
        add_action('template_redirect', array($this, 'handle_download_page'));
    }
    
    /**
     * Enqueue download flow scripts and styles
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            'simuze-download-flow',
            SIMUZE_COMMUNITY_PLUGIN_URL . 'assets/js/download-flow.js',
            array('jquery'),
            SIMUZE_COMMUNITY_VERSION,
            true
        );
        
        wp_enqueue_style(
            'simuze-download-flow',
            SIMUZE_COMMUNITY_PLUGIN_URL . 'assets/css/download-flow.css',
            array(),
            SIMUZE_COMMUNITY_VERSION
        );
        
        $auth = Simuze_Auth::get_instance();
        $current_user = $auth->get_current_user();
        
        wp_localize_script('simuze-download-flow', 'SimuzeDownload', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('simuze_download'),
            'isLoggedIn' => $current_user !== null,
            'userId' => $current_user ? $current_user->id : null,
            'userName' => $current_user ? ($current_user->display_name ?? $current_user->username ?? '') : '',
            'userEmail' => $current_user ? ($current_user->email ?? '') : '',
            'currency' => 'EUR',
            'currencySymbol' => '€',
            'simezu_payment_url' => get_option('simezu_payment_url', 'http://localhost/simezu/api/payments'),
            'i18n' => array(
                'download' => __('Download', 'simuze'),
                'buy' => __('Buy', 'simuze'),
                'free' => __('Free', 'simuze'),
                'processing' => __('Processing...', 'simuze'),
                'error' => __('An error occurred', 'simuze'),
                'minimum_price' => __('Minimum price', 'simuze'),
                'support_artist' => __('Pay more to support the artist', 'simuze'),
                'logged_in_as' => __('Logged in as', 'simuze'),
                'not_logged_in' => __('Not logged in', 'simuze'),
            ),
        ));
    }
    
    /**
     * AJAX: Get product info for modal
     */
    public function ajax_get_product_info() {
        // Better error handling
        try {
            // Check nonce with better error message
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'simuze_download')) {
                error_log('Simuze Download Flow: Nonce verification failed. Nonce: ' . ($_POST['nonce'] ?? 'not set'));
                wp_send_json_error('Security check failed. Please refresh the page and try again.');
                return;
            }
        
        $product_type = sanitize_text_field($_POST['product_type'] ?? '');
        $product_id = intval($_POST['product_id'] ?? 0);
            $upload_id_from_request = intval($_POST['upload_id'] ?? 0); // Get upload_id directly from request if provided
        
        if (!in_array($product_type, array('song', 'album', 'track'))) {
            wp_send_json_error('Invalid product type');
        }
        
        // Normalize 'track' to 'song'
        if ($product_type === 'track') {
            $product_type = 'song';
        }
        
        $product = null;
        $group = null;
        $artwork = '';
        
        // Store original product_id (might be upload_id) for price lookup
        $original_product_id = $product_id;
        $upload_id_for_price = $upload_id_from_request > 0 ? $upload_id_from_request : null;
        
        // Log what we received
        error_log('Simuze Download Flow: AJAX request - product_type=' . $product_type . ', product_id=' . $product_id . ', upload_id_from_request=' . $upload_id_from_request);
        
        if ($product_type === 'song') {
            // If upload_id was passed directly from request, use it (highest priority)
            if (!$upload_id_for_price) {
                // First check if product_id is actually an upload_id in wp_music_uploads
                global $wpdb;
                $uploads_table = $wpdb->prefix . 'music_uploads';
                $upload_check = $wpdb->get_row($wpdb->prepare(
                    "SELECT id, price FROM {$uploads_table} WHERE id = %d",
                    $product_id
                ), ARRAY_A);
                
                if ($upload_check) {
                    // product_id is an upload_id - use it directly for price lookup
                    $upload_id_for_price = $upload_check['id'];
                    error_log('Simuze Download Flow: product_id is upload_id - ' . $upload_id_for_price);
                }
            } else {
                error_log('Simuze Download Flow: Using upload_id from request - ' . $upload_id_for_price);
            }
            
            // First try to get by Simuze_Song ID
            $product = Simuze_Song::get($product_id);
            
            // If not found by ID, try to find by upload_id (for WordPress music_uploads)
            if (!$product && $upload_id_for_price) {
                if (!$wpdb) {
                    error_log('Simuze Download Flow: ERROR - $wpdb is not available when trying to find song by upload_id!');
                } else {
                    $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'songs';
                    $song_data = $wpdb->get_row($wpdb->prepare(
                        "SELECT * FROM $table WHERE upload_id = %d AND deleted_at IS NULL",
                        $upload_id_for_price
                    ), ARRAY_A);
                    
                    if ($song_data) {
                        $product = new Simuze_Song($song_data);
                        $product_id = $product->id; // Update product_id to the actual song ID
                    }
                }
            }
            
            // If still not found, try to create from WordPress upload
            // This handles cases where product_id is actually an upload_id from wp_music_uploads
            if (!$product && $upload_id_for_price) {
                $product = $this->get_or_create_song_from_upload($upload_id_for_price);
                // If we created from upload, keep using the upload_id as product_id
                if ($product && $product->id === 0) {
                    $product_id = $upload_id_for_price; // Use upload_id for response
                }
            }
            
            // CRITICAL: For songs, we MUST have upload_id to get price from wp_music_uploads
            // If we have a product but no upload_id yet, try to get it
            if ($product && !$upload_id_for_price) {
                // First try product's upload_id property
                if (isset($product->upload_id) && $product->upload_id > 0) {
                    $upload_id_for_price = $product->upload_id;
                    error_log('Simuze Download Flow: Got upload_id from product->upload_id: ' . $upload_id_for_price);
                } elseif (isset($product->id) && $product->id > 0) {
                    // Get upload_id from simuze_songs table
                    $songs_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'songs';
                    $upload_id_from_song = $wpdb->get_var($wpdb->prepare(
                        "SELECT upload_id FROM {$songs_table} WHERE id = %d AND deleted_at IS NULL",
                        $product->id
                    ));
                    if ($upload_id_from_song) {
                        $upload_id_for_price = $upload_id_from_song;
                        error_log('Simuze Download Flow: Got upload_id from simuze_songs table: ' . $upload_id_for_price);
                    } else {
                        error_log('Simuze Download Flow: WARNING - Could not find upload_id for song_id=' . $product->id);
                    }
                }
            }
            
            // Final fallback: if product_id might be upload_id, check it
            if (!$upload_id_for_price && $product_type === 'song') {
                $upload_check_fallback = $wpdb->get_row($wpdb->prepare(
                    "SELECT id FROM {$uploads_table} WHERE id = %d",
                    $original_product_id
                ), ARRAY_A);
                if ($upload_check_fallback) {
                    $upload_id_for_price = $original_product_id;
                    error_log('Simuze Download Flow: Using original_product_id as upload_id: ' . $upload_id_for_price);
                }
            }
            
            if ($product) {
                try {
                $group = $product->get_group();
                $artwork = $product->artwork_url ?: ($group ? $group->get_avatar_url('medium') : '');
                } catch (Exception $e) {
                    error_log('Simuze Download Flow: Error getting group: ' . $e->getMessage());
                    $group = null;
                    $artwork = $product->artwork_url ?: '';
            }
        } else {
                // Log for debugging
                error_log('Simuze Download Flow: Product not found. product_type=' . $product_type . ', product_id=' . $original_product_id);
            }
        } else {
            // Try to get from simuze_albums first
            error_log('Simuze Download Flow: Attempting to get album with product_id=' . $product_id);
            $product = Simuze_Album::get($product_id);
            error_log('Simuze Download Flow: Simuze_Album::get result: ' . ($product ? 'Found (ID: ' . $product->id . ')' : 'Not found'));
            
            // If not found, try wp_music_albums table (for albums from latest albums on homepage)
            if (!$product) {
                global $wpdb;
                $music_albums_table = $wpdb->prefix . 'music_albums';
                error_log('Simuze Download Flow: Trying wp_music_albums table with id=' . $product_id);
                $album_data = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$music_albums_table} WHERE id = %d",
                    $product_id
                ), ARRAY_A);
                
                error_log('Simuze Download Flow: wp_music_albums query result: ' . ($album_data ? 'Found' : 'Not found'));
                if ($album_data) {
                    error_log('Simuze Download Flow: Album data found: ' . print_r($album_data, true));
                    // Get album_post_id by finding WordPress post with this album_id in meta
                    $album_post_id = null;
                    $album_posts = get_posts(array(
                        'post_type' => 'album',
                        'meta_key' => '_simuze_album_id',
                        'meta_value' => $product_id,
                        'posts_per_page' => 1,
                        'post_status' => 'any'
                    ));
                    if (!empty($album_posts)) {
                        $album_post_id = $album_posts[0]->ID;
                    }
                    
                    // Get price from post meta first (most reliable)
                    $album_price = 0;
                    if ($album_post_id) {
                        $price_meta = get_post_meta($album_post_id, 'album_price', true);
                        if ($price_meta !== '' && $price_meta !== null && is_numeric($price_meta)) {
                            $album_price = floatval($price_meta);
                        }
                    }
                    
                    // Fallback to wp_music_albums price column
                    if ($album_price <= 0 && isset($album_data['price']) && is_numeric($album_data['price'])) {
                        $album_price = floatval($album_data['price']);
                    }
                    
                    // Final fallback to default
                    if ($album_price <= 0) {
                        $album_price = 10.00;
                    }
                    
                    // Create a temporary Simuze_Album object from wp_music_albums data
                    $product = new Simuze_Album(array(
                        'id' => $album_data['id'],
                        'group_id' => $album_data['group_id'] ?? 0,
                        'title' => $album_data['album_name'] ?? '',
                        'slug' => $album_data['album_nicename'] ?? '',
                        'description' => $album_data['description'] ?? '',
                        'price' => $album_price,
                        'cover_image' => !empty($album_data['artwork_path']) ? home_url('/wp-content/uploads' . $album_data['artwork_path']) : (!empty($album_data['artwork_thumbnail_path']) ? home_url('/wp-content/uploads' . $album_data['artwork_thumbnail_path']) : ''),
                        'release_date' => $album_data['release_date'] ?? $album_data['publication_date'] ?? '',
                        'is_active' => 1,
                        'is_purchasable' => 1,
                    ));
                    error_log('Simuze Download Flow: Created Simuze_Album object from wp_music_albums data');
                } else {
                    error_log('Simuze Download Flow: Album not found in wp_music_albums table either');
                }
            }
            
            if ($product) {
                error_log('Simuze Download Flow: Product found, getting group and artwork');
                // Get group - handle both simuze_albums and wp_music_albums cases
                $group = null;
                if (method_exists($product, 'get_group')) {
                    try {
                $group = $product->get_group();
                        error_log('Simuze Download Flow: Got group via get_group(): ' . ($group ? 'Found (ID: ' . $group->id . ')' : 'Not found'));
                    } catch (Exception $e) {
                        error_log('Simuze Download Flow: Exception in get_group(): ' . $e->getMessage());
                        // If get_group fails, try to get group directly
                        if (isset($product->group_id) && $product->group_id > 0 && class_exists('Simuze_Group')) {
                            $group = Simuze_Group::get($product->group_id);
                            error_log('Simuze Download Flow: Got group directly: ' . ($group ? 'Found (ID: ' . $group->id . ')' : 'Not found'));
                        }
                    }
                } elseif (isset($product->group_id) && $product->group_id > 0 && class_exists('Simuze_Group')) {
                    $group = Simuze_Group::get($product->group_id);
                    error_log('Simuze Download Flow: Got group from product->group_id: ' . ($group ? 'Found (ID: ' . $group->id . ')' : 'Not found'));
                }
                
                // Get artwork
                $artwork = '';
                if (!empty($product->cover_image)) {
                    $artwork = $product->cover_image;
                    error_log('Simuze Download Flow: Using product cover_image: ' . $artwork);
                } elseif ($group && method_exists($group, 'get_avatar_url')) {
                    $artwork = $group->get_avatar_url('medium');
                    error_log('Simuze Download Flow: Using group avatar: ' . $artwork);
                } else {
                    error_log('Simuze Download Flow: No artwork found');
                }
            } else {
                error_log('Simuze Download Flow: Product is null after all attempts');
            }
        }
        
        if (!$product) {
            error_log('Simuze Download Flow: FINAL - Product not found. product_type=' . $product_type . ', product_id=' . $original_product_id . ', upload_id=' . $upload_id_from_request);
            error_log('Simuze Download Flow: Debug info - POST data: ' . print_r($_POST, true));
            wp_send_json_error('Product not found. Please check the product ID and try again.');
            return;
        }
        
        // Get pricing info
        try {
        $auth = Simuze_Auth::get_instance();
        $current_user = $auth->get_current_user();
            
            // Check if get_final_price method exists
            if (method_exists($product, 'get_final_price')) {
        $pricing = $product->get_final_price($current_user ? $current_user->id : null);
            } else {
                error_log('Simuze Download Flow: WARNING - get_final_price method does not exist on product object');
                // Fallback pricing
                $pricing = array(
                    'final_price' => $product->price ?? 0,
                    'discount' => 0,
                    'discount_type' => null
                );
            }
        
        // Check if user has already purchased
        $has_purchased = false;
            if ($current_user && class_exists('Simuze_Purchase_Flow')) {
                try {
            $has_purchased = Simuze_Purchase_Flow::has_purchased($current_user->id, $product_type, $product_id);
                } catch (Exception $e) {
                    error_log('Simuze Download Flow: Exception checking purchase status: ' . $e->getMessage());
                    $has_purchased = false;
                }
            }
        } catch (Exception $e) {
            error_log('Simuze Download Flow: Exception getting pricing info: ' . $e->getMessage());
            error_log('Simuze Download Flow: Stack trace: ' . $e->getTraceAsString());
            // Fallback pricing
            $pricing = array(
                'final_price' => $product->price ?? 0,
                'discount' => 0,
                'discount_type' => null
            );
            $has_purchased = false;
        }
        
        // Get price settings (min, default)
        // CRITICAL: For songs, ALWAYS get price from wp_music_uploads table
        // This is the source of truth - simuze_songs table may have stale prices
        $base_price = 0;
        
        if ($product_type === 'song') {
            global $wpdb;
            $uploads_table = $wpdb->prefix . 'music_uploads';
            
            // Ensure we have upload_id - get it from product if not already set
            if (!$upload_id_for_price && $product) {
                if (isset($product->upload_id) && $product->upload_id > 0) {
                    $upload_id_for_price = $product->upload_id;
                } elseif (isset($product->id) && $product->id > 0) {
                    // Get upload_id from simuze_songs table
                    $songs_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'songs';
                    $upload_id_from_song = $wpdb->get_var($wpdb->prepare(
                        "SELECT upload_id FROM {$songs_table} WHERE id = %d AND deleted_at IS NULL",
                        $product->id
                    ));
                    if ($upload_id_from_song) {
                        $upload_id_for_price = $upload_id_from_song;
                    }
                }
            }
            
            // If we still don't have upload_id, try using product_id as upload_id
            if (!$upload_id_for_price) {
                $upload_check = $wpdb->get_row($wpdb->prepare(
                    "SELECT id FROM {$uploads_table} WHERE id = %d",
                    $original_product_id
                ), ARRAY_A);
                if ($upload_check) {
                    $upload_id_for_price = $original_product_id;
                }
            }
            
            // NOW get price from upload table - this is the AUTHORITATIVE source
            if ($upload_id_for_price && $upload_id_for_price > 0) {
                $upload_row = $wpdb->get_row($wpdb->prepare(
                    "SELECT id, song_name, price FROM {$uploads_table} WHERE id = %d",
                    $upload_id_for_price
                ), ARRAY_A);
                
                if ($upload_row) {
                    $upload_price = $upload_row['price'];
                    
                    // Debug logging with full details
                    error_log('Simuze Download Flow: Checking upload table - upload_id=' . $upload_id_for_price . ', song_name=' . ($upload_row['song_name'] ?? 'N/A') . ', upload_price=' . var_export($upload_price, true) . ', upload_price_type=' . gettype($upload_price));
                    
                    // If we found a price in the upload table, use it (even if it's 0)
                    // The upload table is the authoritative source
                    if ($upload_price !== null && $upload_price !== '' && is_numeric($upload_price)) {
                        $base_price = floatval($upload_price);
                        // Update the product object's price for consistency
                        $product->price = $base_price;
                        error_log('Simuze Download Flow: UPDATED base_price from upload table - ' . $base_price);
                    } else {
                        error_log('Simuze Download Flow: WARNING - No valid price in upload table! upload_price=' . var_export($upload_price, true));
                    }
                } else {
                    error_log('Simuze Download Flow: WARNING - Upload row not found for upload_id=' . $upload_id_for_price);
                }
            } else {
                error_log('Simuze Download Flow: WARNING - Could not determine upload_id! product_id=' . $original_product_id);
            }
            
            // If upload table price is 0 or not set, use default of 1.00
            if ($base_price <= 0) {
                error_log('Simuze Download Flow: base_price is 0 or not found, setting to default 1.00');
                $base_price = 1.00;
                $product->price = $base_price;
            }
        } else {
            // For albums, get price from product object (already set from wp_music_albums or post meta)
            $base_price = floatval($product->price ?? 0);
            
            // If still no price, try to get from post meta directly
            if ($base_price <= 0) {
                // Try to find album_post_id by looking for WordPress post with this album_id in meta
                $album_posts = get_posts(array(
                    'post_type' => 'album',
                    'meta_key' => '_simuze_album_id',
                    'meta_value' => $product_id,
                    'posts_per_page' => 1,
                    'post_status' => 'any'
                ));
                
                if (!empty($album_posts)) {
                    $price_meta = get_post_meta($album_posts[0]->ID, 'album_price', true);
                    if ($price_meta !== '' && $price_meta !== null && is_numeric($price_meta)) {
                        $base_price = floatval($price_meta);
                        $product->price = $base_price;
                    }
                }
            }
            
            // Final fallback to default
            if ($base_price <= 0) {
                $base_price = 10.00;
                $product->price = $base_price;
            }
        }
        
        // After updating base_price from upload table, ensure product object reflects this
        // Set download_price_min and download_price_default to match base_price
        // This ensures the helper functions use the correct values
        if ($product_type === 'song') {
            $product->download_price_min = $base_price;
            $product->download_price_default = $base_price;
        }
        
        // Get price_min and price_default - these should now use the updated base_price
        $price_min = $this->get_product_price_min($product, $base_price);
        $price_default = $this->get_product_price_default($product, $base_price);
        
        // Final override: ensure price_default always matches base_price from upload table
        $price_default = max($price_min, $base_price);
        
        // Debug logging
        error_log('Simuze Download Flow: Product price info - product_id=' . $product_id . ', upload_id=' . ($upload_id_for_price ?? 'N/A') . ', base_price=' . $base_price . ', price_min=' . $price_min . ', price_default=' . $price_default . ', product->price=' . ($product->price ?? 'N/A') . ', product->download_price_default=' . ($product->download_price_default ?? 'N/A'));
        
        // Ensure title is set correctly
        $product_title = '';
        if (isset($product->title) && !empty($product->title)) {
            $product_title = $product->title;
        } elseif (isset($product->album_name) && !empty($product->album_name)) {
            $product_title = $product->album_name;
        } else {
            $product_title = __('Untitled', 'simuze');
        }
        
        // Get artist name
        $artist_name = '';
        if ($group && isset($group->name)) {
            $artist_name = $group->name;
        } elseif (isset($product->artist_name) && !empty($product->artist_name)) {
            $artist_name = $product->artist_name;
        }
        
        // Debug logging
        error_log('Simuze Download Flow: Sending response - title=' . $product_title . ', artist=' . $artist_name . ', artwork=' . ($artwork ? 'yes' : 'no') . ', price_min=' . $price_min . ', price_default=' . $price_default);
        
        wp_send_json_success(array(
            'product_type' => $product_type,
            'product_id' => $product_id,
            'title' => $product_title,
            'artist' => $artist_name,
            'artist_id' => $group ? $group->id : null,
            'artwork' => $artwork,
            'price_min' => $price_min,
            'price_default' => $price_default,
            'base_price' => $base_price,
            'final_price' => $pricing['final_price'],
            'is_free' => $base_price <= 0, // Use base_price, not final_price
            'has_purchased' => $has_purchased,
            'product_type_label' => $product_type === 'song' ? __('Track', 'simuze') : __('Album', 'simuze'),
        ));
        
        } catch (Exception $e) {
            error_log('Simuze Download Flow: Exception in ajax_get_product_info - ' . $e->getMessage());
            error_log('Simuze Download Flow: Stack trace - ' . $e->getTraceAsString());
            wp_send_json_error('An error occurred: ' . $e->getMessage());
        } catch (Error $e) {
            error_log('Simuze Download Flow: Fatal error in ajax_get_product_info - ' . $e->getMessage());
            error_log('Simuze Download Flow: Stack trace - ' . $e->getTraceAsString());
            wp_send_json_error('A fatal error occurred: ' . $e->getMessage());
        }
    }
    
    /**
     * Get or create a Simuze_Song from a WordPress music upload
     */
    private function get_or_create_song_from_upload($upload_id) {
        global $wpdb;
        
        // Get the upload from WordPress music_uploads table
        $uploads_table = $wpdb->prefix . 'music_uploads';
        $upload = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $uploads_table WHERE id = %d",
            $upload_id
        ), ARRAY_A);
        
        if (!$upload) {
            return null;
        }
        
        // Create a temporary Song object with the upload data
        // This allows the modal to work even if the song hasn't been synced to simuze_songs
        // Get price from upload table - this is the source of truth
        $upload_price = 1.00; // Default value
        if (isset($upload['price']) && $upload['price'] !== null && $upload['price'] !== '' && is_numeric($upload['price'])) {
            $upload_price = floatval($upload['price']);
        }
        // If price is 0 or negative, use default of 1.00
        // But preserve the actual value from database if it's set (even if 0)
        if ($upload_price <= 0) {
            $upload_price = 1.00;
        }
        
        $song_data = array(
            'id' => 0, // Not in simuze_songs table
            'upload_id' => $upload_id,
            'group_id' => isset($upload['group_id']) ? intval($upload['group_id']) : 0,
            'title' => $upload['song_name'] ?? $upload['file_name'] ?? 'Unknown Track',
            'slug' => sanitize_title($upload['song_name'] ?? 'track-' . $upload_id),
            'description' => '',
            'price' => $upload_price,
            'download_price_min' => $upload_price, // Use actual price as minimum
            'download_price_default' => $upload_price,
            'file_path' => $upload['file_path'] ?? '',
            'file_size' => $upload['file_size'] ?? 0,
            'duration' => $upload['duration'] ?? 0,
            'artwork_url' => '',
            'genre' => $upload['genre'] ?? '',
            'license' => $upload['license'] ?? '',
            'is_active' => 1,
            'is_purchasable' => 1,
            'download_count' => 0,
            'created_at' => $upload['created_at'] ?? current_time('mysql'),
            'updated_at' => current_time('mysql'),
        );
        
        return new Simuze_Song($song_data);
    }
    
    /**
     * Get minimum price for a product
     */
    private function get_product_price_min($product, $base_price_override = null) {
        // If base_price_override is provided (from upload table), use it
        if ($base_price_override !== null && $base_price_override > 0) {
            return floatval($base_price_override);
        }
        
        // Check for explicit minimum price in product settings (should be set from upload table)
        if (isset($product->download_price_min) && $product->download_price_min > 0) {
            return floatval($product->download_price_min);
        }
        
        // Use the product's price property (which should be updated from upload table)
        $base_price = floatval($product->price ?? 0);
        if ($base_price > 0) {
            return $base_price;
        }
        
        // Otherwise use base price as minimum (or 0 for free)
        $pricing = $product->get_final_price();
        return max(0, $pricing['final_price']);
    }
    
    /**
     * Get default price for a product
     * @param object $product The product object
     * @param float $base_price_override Optional base price from upload table to use instead of product price
     */
    private function get_product_price_default($product, $base_price_override = null) {
        // If base_price_override is provided (from upload table), use it
        if ($base_price_override !== null && $base_price_override > 0) {
            return floatval($base_price_override);
        }
        
        // Check for explicit default price in product settings (should be set from upload table)
        if (isset($product->download_price_default) && $product->download_price_default > 0) {
            return floatval($product->download_price_default);
        }
        
        // Use the product's price property (which should be updated from upload table)
        $base_price = floatval($product->price ?? 0);
        if ($base_price > 0) {
            return $base_price;
        }
        
        // Default to 1.00 for songs, 10.00 for albums if not set
        $is_album = (get_class($product) === 'Simuze_Album' || isset($product->album_name));
        return $is_album ? 10.00 : 1.00;
    }
    
    /**
     * AJAX: Create download order with custom price
     */
    public function ajax_create_download_order() {
        check_ajax_referer('simuze_download', 'nonce');
        
        $product_type = sanitize_text_field($_POST['product_type'] ?? '');
        $product_id = intval($_POST['product_id'] ?? 0);
        $custom_price = floatval($_POST['custom_price'] ?? 0);
        $email = sanitize_email($_POST['email'] ?? '');
        $payment_method = sanitize_text_field($_POST['payment_method'] ?? 'paypal');
        
        // Normalize product type
        if ($product_type === 'track') {
            $product_type = 'song';
        }
        
        if (!in_array($product_type, array('song', 'album'))) {
            wp_send_json_error('Invalid product type');
        }
        
        // Get product
        $product = null;
        if ($product_type === 'song') {
            // Store original product_id (might be upload_id)
            $original_product_id = $product_id;
            
            // First try to get by Simuze_Song ID
            $product = Simuze_Song::get($product_id);
            
            // If not found by ID, try to find by upload_id
            if (!$product) {
                global $wpdb;
                $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'songs';
                $song_data = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM $table WHERE upload_id = %d AND deleted_at IS NULL",
                    $product_id
                ), ARRAY_A);
                
                if ($song_data) {
                    $product = new Simuze_Song($song_data);
                    $product_id = $product->id; // Update product_id to actual song ID
                }
            }
            
            // If still not found, try to create from WordPress upload
            if (!$product) {
                $product = $this->get_or_create_song_from_upload($original_product_id);
                // If created from upload, use upload_id as product_id
                if ($product && $product->id === 0) {
                    $product_id = $original_product_id;
                }
            }
        } else {
            $product = Simuze_Album::get($product_id);
        }
        
        if (!$product) {
            wp_send_json_error('Product not found');
        }
        
        // Validate minimum price
        $price_min = $this->get_product_price_min($product);
        if ($custom_price < $price_min) {
            wp_send_json_error(sprintf(__('Minimum price is €%s', 'simuze'), number_format($price_min, 2)));
        }
        
        // Get current user
        $auth = Simuze_Auth::get_instance();
        $current_user = $auth->get_current_user();
        
        // Enforce minimum price - no free downloads unless product is explicitly free
        $price_min = $this->get_product_price_min($product);
        if ($custom_price <= 0 && $price_min > 0) {
            wp_send_json_error(sprintf(__('Minimum price is €%s. Free downloads are not allowed.', 'simuze'), number_format($price_min, 2)));
            return;
        }
        
        // Only allow free download if product is explicitly free (price_min = 0)
        if ($custom_price <= 0 && $price_min <= 0) {
            return $this->handle_free_download($product_type, $product_id, $current_user, $email);
        }
        
        // Guest checkout requires email
        if (!$current_user && !is_email($email)) {
            wp_send_json_error(__('Valid email address required for guest checkout', 'simuze'));
        }
        
        $buyer_id = $current_user ? $current_user->id : 0;
        $buyer_email = $current_user ? $current_user->email : $email;
        
        // Create order
        global $wpdb;
        $orders_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'orders';
        $items_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'order_items';
        
        // Generate order number
        $order_number = 'SIM' . date('Ymd') . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
        
        // Calculate fees (15% platform fee)
        $simuze_fee_percentage = floatval(get_option('simuze_commerce_fee_percentage', 15));
        $simuze_fee = round($custom_price * ($simuze_fee_percentage / 100), 2);
        $seller_earnings = round($custom_price - $simuze_fee, 2);
        
        // Get seller (group) ID
        $seller_id = $product->group_id;
        
        // Create order record
        $wpdb->insert($orders_table, array(
            'order_number' => $order_number,
            'buyer_id' => $buyer_id,
            'buyer_email' => $buyer_email,
            'subtotal' => $custom_price,
            'discount_amount' => 0,
            'total_price' => $custom_price,
            'currency' => 'EUR',
            'payment_status' => 'pending',
            'payment_method' => $payment_method,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ));
        
        $order_id = $wpdb->insert_id;
        
        if (!$order_id) {
            wp_send_json_error('Failed to create order');
        }
        
        // Create order item
        $wpdb->insert($items_table, array(
            'order_id' => $order_id,
            'seller_type' => 'group',
            'seller_id' => $seller_id,
            'product_type' => $product_type,
            'product_id' => $product_id,
            'product_title' => $product->title,
            'quantity' => 1,
            'unit_price' => $custom_price,
            'discount_amount' => 0,
            'total_price' => $custom_price,
            'simuze_fee' => $simuze_fee,
            'seller_earnings' => $seller_earnings,
        ));
        
        // Get order object
        $order = Simuze_Order::get($order_id);
        
        // Create payment via SIMEZU (ONLY - no direct PayPal/Stripe fallbacks)
        $payments = Simuze_Simezu_Payments::get_instance();
        $result = $payments->create_payment($order, $payment_method);
        
        if (is_wp_error($result)) {
            // All payments must go through SIMEZU - no direct payment provider fallbacks
            error_log('SIMEZU Payment Error: ' . $result->get_error_message());
            wp_send_json_error(array(
                'message' => $result->get_error_message() ?: 'Payment could not be processed through SIMEZU. Please try again later.',
                'code' => 'simezu_payment_failed'
                        ));
                        return;
        }
        
        wp_send_json_success(array(
            'order_id' => $order_id,
            'order_number' => $order_number,
            'redirect_url' => $result['redirect_url'] ?? $result['paypal_redirect_url'] ?? null,
        ));
    }
    
    /**
     * Handle free download (no payment needed)
     */
    private function handle_free_download($product_type, $product_id, $current_user, $email) {
        global $wpdb;
        $orders_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'orders';
        $items_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'order_items';
        
        // Get product
        $product = null;
        if ($product_type === 'song') {
            $product = Simuze_Song::get($product_id);
        } else {
            $product = Simuze_Album::get($product_id);
        }
        
        if (!$product) {
            wp_send_json_error('Product not found');
        }
        
        $buyer_id = $current_user ? $current_user->id : 0;
        $buyer_email = $current_user ? $current_user->email : $email;
        
        // Create free order
        $order_number = 'SIM' . date('Ymd') . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
        
        $wpdb->insert($orders_table, array(
            'order_number' => $order_number,
            'buyer_id' => $buyer_id,
            'buyer_email' => $buyer_email,
            'subtotal' => 0,
            'discount_amount' => 0,
            'total_price' => 0,
            'currency' => 'EUR',
            'payment_status' => 'paid', // Free = immediately paid
            'payment_method' => 'free',
            'paid_at' => current_time('mysql'),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        ));
        
        $order_id = $wpdb->insert_id;
        
        // Create order item
        $wpdb->insert($items_table, array(
            'order_id' => $order_id,
            'seller_type' => 'group',
            'seller_id' => $product->group_id,
            'product_type' => $product_type,
            'product_id' => $product_id,
            'product_title' => $product->title,
            'quantity' => 1,
            'unit_price' => 0,
            'discount_amount' => 0,
            'total_price' => 0,
            'simuze_fee' => 0,
            'seller_earnings' => 0,
        ));
        
        $order_item_id = $wpdb->insert_id;
        
        // Create download token
        $token = Simuze_Download_Token::create(array(
            'order_item_id' => $order_item_id,
            'buyer_id' => $buyer_id,
            'product_type' => $product_type,
            'product_id' => $product_id,
        ));
        
        if ($token) {
            // Send email notification
            $this->send_download_email($buyer_email, $product, $token, 0);
            
            wp_send_json_success(array(
                'order_id' => $order_id,
                'order_number' => $order_number,
                'is_free' => true,
                'download_url' => home_url('/download/access/?token=' . $token->token),
            ));
        }
        
        wp_send_json_error('Failed to create download token');
    }
    
    /**
     * AJAX: Get available download formats
     */
    public function ajax_get_download_formats() {
        check_ajax_referer('simuze_download', 'nonce');
        
        $token = sanitize_text_field($_POST['token'] ?? '');
        
        if (!$token) {
            wp_send_json_error('Invalid token');
        }
        
        $download_token = Simuze_Download_Token::get_by_token($token);
        
        if (!$download_token || !$download_token->is_valid()) {
            wp_send_json_error('Invalid or expired download token');
        }
        
        // Get available formats for this product
        $formats = $this->get_available_formats($download_token->product_type, $download_token->product_id);
        
        $product = $download_token->get_product();
        
        wp_send_json_success(array(
            'product' => $product ? $product->to_array() : null,
            'formats' => $formats,
            'downloads_remaining' => $download_token->max_downloads ? max(0, $download_token->max_downloads - $download_token->download_count) : 'unlimited',
        ));
    }
    
    /**
     * Get available download formats for a product
     */
    private function get_available_formats($product_type, $product_id) {
        $available = array();
        
        // MP3 320 is always available
        $available['mp3_320'] = self::FORMATS['mp3_320'];
        $available['mp3_320']['available'] = true;
        $available['mp3_320']['size'] = $this->estimate_file_size($product_type, $product_id, 'mp3_320');
        
        // Check for other formats
        foreach (array('flac', 'wav', 'aiff') as $format) {
            $file_exists = $this->check_format_exists($product_type, $product_id, $format);
            if ($file_exists) {
                $available[$format] = self::FORMATS[$format];
                $available[$format]['available'] = true;
                $available[$format]['size'] = $this->estimate_file_size($product_type, $product_id, $format);
            }
        }
        
        return $available;
    }
    
    /**
     * Check if a specific format file exists
     */
    private function check_format_exists($product_type, $product_id, $format) {
        // For now, only MP3 is guaranteed. Other formats would need to be uploaded separately
        // This can be extended to check for actual files
        
        if ($format === 'mp3_320') {
            return true;
        }
        
        // Check if format file exists in upload directory
        // TODO: Implement actual file checking based on upload structure
        return false;
    }
    
    /**
     * Estimate file size for a format
     */
    private function estimate_file_size($product_type, $product_id, $format) {
        // Get duration if available
        $duration = 0;
        
        if ($product_type === 'song') {
            $product = Simuze_Song::get($product_id);
            if ($product && $product->duration) {
                $duration = $product->duration;
            }
        }
        
        if (!$duration) {
            $duration = 240; // Default 4 minutes
        }
        
        // Estimate based on format bitrate
        $bitrates = array(
            'mp3_320' => 320, // kbps
            'flac' => 1000,   // ~1000 kbps average
            'wav' => 1411,    // 16bit 44.1kHz
            'aiff' => 1411,
        );
        
        $bitrate = $bitrates[$format] ?? 320;
        $size_kb = ($bitrate * $duration) / 8;
        
        return $this->format_file_size($size_kb * 1024);
    }
    
    /**
     * Format file size for display
     */
    private function format_file_size($bytes) {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 1) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 1) . ' KB';
        }
        return $bytes . ' bytes';
    }
    
    /**
     * AJAX: Process actual file download
     */
    public function ajax_process_download() {
        $token = sanitize_text_field($_GET['token'] ?? $_POST['token'] ?? '');
        $format = sanitize_text_field($_GET['format'] ?? $_POST['format'] ?? 'mp3_320');
        
        if (!$token) {
            wp_die('Invalid download request');
        }
        
        $download_token = Simuze_Download_Token::get_by_token($token);
        
        if (!$download_token) {
            wp_die('Download token not found');
        }
        
        if (!$download_token->is_valid()) {
            wp_die('Download token has expired or reached maximum downloads');
        }
        
        // Get product file
        $product = $download_token->get_product();
        if (!$product) {
            wp_die('Product not found');
        }
        
        $file_path = $product->get_file_path();
        
        if (!$file_path || !file_exists($file_path)) {
            wp_die('File not found');
        }
        
        // Record download
        $download_token->record_download($_SERVER['REMOTE_ADDR'] ?? null);
        
        // Serve file
        $filename = sanitize_file_name($product->title) . '.' . (self::FORMATS[$format]['extension'] ?? 'mp3');
        $mime = self::FORMATS[$format]['mime'] ?? 'audio/mpeg';
        
        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($file_path));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        
        readfile($file_path);
        exit;
    }
    
    /**
     * Handle download page routing
     */
    public function handle_download_page() {
        $uri = $_SERVER['REQUEST_URI'];
        
        // Match /download/success/
        if (preg_match('#/download/success/?#', $uri)) {
            $this->render_download_success_page();
            exit;
        }
        
        // Match /download/access/
        if (preg_match('#/download/access/?#', $uri)) {
            $this->render_download_access_page();
            exit;
        }
    }
    
    /**
     * Render download success page
     */
    private function render_download_success_page() {
        $order_number = sanitize_text_field($_GET['order'] ?? '');
        
        if (!$order_number) {
            wp_redirect(home_url());
            exit;
        }
        
        $order = Simuze_Order::get_by_number($order_number);
        
        if (!$order) {
            wp_redirect(home_url());
            exit;
        }
        
        // Process payment confirmation if needed
        if ($order->payment_status === 'processing' || $order->payment_status === 'pending') {
            // Check payment status with SIMEZU
            $payments = Simuze_Simezu_Payments::get_instance();
            if ($order->payment_session_id) {
                $status = $payments->get_payment_status($order->payment_session_id);
                
                if (!is_wp_error($status) && ($status['status'] === 'paid' || $status['status'] === 'completed')) {
                    $order->mark_as_paid($status['transaction_id'] ?? null);
                    
                    // Send emails
                    $this->send_order_emails($order);
                }
            }
        }
        
        get_header();
        include SIMUZE_COMMUNITY_PLUGIN_DIR . 'templates/download-success.php';
        get_footer();
    }
    
    /**
     * Render download access page (format selection)
     */
    private function render_download_access_page() {
        $token = sanitize_text_field($_GET['token'] ?? '');
        
        if (!$token) {
            wp_redirect(home_url());
            exit;
        }
        
        $download_token = Simuze_Download_Token::get_by_token($token);
        
        if (!$download_token || !$download_token->is_valid()) {
            wp_redirect(home_url());
            exit;
        }
        
        $product = $download_token->get_product();
        $formats = $this->get_available_formats($download_token->product_type, $download_token->product_id);
        
        get_header();
        include SIMUZE_COMMUNITY_PLUGIN_DIR . 'templates/download-access.php';
        get_footer();
    }
    
    /**
     * Send download email to buyer
     */
    public function send_download_email($email, $product, $token, $amount) {
        $subject = sprintf(__('Your download: %s', 'simuze'), $product->title);
        
        $group = $product->get_group();
        
        $message = sprintf(
            __("Thank you for your purchase!\n\n" .
               "Product: %s\n" .
               "Artist: %s\n" .
               "Amount: €%s\n\n" .
               "Download your music here:\n%s\n\n" .
               "This link is valid for 30 days and allows up to 5 downloads.\n\n" .
               "Enjoy your music!\n" .
               "The Simuze Team", 'simuze'),
            $product->title,
            $group ? $group->name : 'Unknown Artist',
            number_format($amount, 2),
            home_url('/download/access/?token=' . $token->token)
        );
        
        $headers = array('Content-Type: text/plain; charset=UTF-8');
        
        wp_mail($email, $subject, $message, $headers);
    }
    
    /**
     * Send order emails (buyer and seller)
     */
    public function send_order_emails($order) {
        $items = $order->get_items();
        
        foreach ($items as $item) {
            // Get product
            $product = null;
            if ($item['product_type'] === 'song') {
                $product = Simuze_Song::get($item['product_id']);
            } else {
                $product = Simuze_Album::get($item['product_id']);
            }
            
            if (!$product) {
                continue;
            }
            
            // Get download token
            $token = Simuze_Download_Token::get_by_order_item($item['id']);
            
            if ($token) {
                // Send buyer email
                $this->send_download_email($order->buyer_email, $product, $token, $item['total_price']);
            }
            
            // Send seller notification
            $this->send_seller_notification($item, $order);
        }
    }
    
    /**
     * Send notification to seller (artist group admin)
     */
    private function send_seller_notification($item, $order) {
        if ($item['seller_type'] !== 'group') {
            return;
        }
        
        $group = Simuze_Group::get($item['seller_id']);
        if (!$group) {
            return;
        }
        
        // Get group admins
        global $wpdb;
        $members_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'group_members';
        
        $admins = $wpdb->get_results($wpdb->prepare(
            "SELECT user_id FROM $members_table WHERE group_id = %d AND role IN ('owner', 'admin')",
            $group->id
        ), ARRAY_A);
        
        if (empty($admins)) {
            return;
        }
        
        // Get product
        $product = null;
        if ($item['product_type'] === 'song') {
            $product = Simuze_Song::get($item['product_id']);
        } else {
            $product = Simuze_Album::get($item['product_id']);
        }
        
        // Get buyer info
        $buyer_name = 'Guest purchase';
        if ($order->buyer_id > 0) {
            $buyer = Simuze_User::get($order->buyer_id);
            if ($buyer) {
                $buyer_name = $buyer->display_name ?: $buyer->username;
            }
        }
        
        $subject = sprintf(__('New sale: %s', 'simuze'), $product ? $product->title : $item['product_title']);
        
        $message = sprintf(
            __("You have a new sale!\n\n" .
               "Product: %s\n" .
               "Buyer: %s\n" .
               "Amount: €%s\n" .
               "Platform fee: €%s\n" .
               "Your earnings: €%s\n\n" .
               "View your earnings in your group dashboard.\n\n" .
               "The Simuze Team", 'simuze'),
            $product ? $product->title : $item['product_title'],
            $buyer_name,
            number_format($item['total_price'], 2),
            number_format($item['simuze_fee'], 2),
            number_format($item['seller_earnings'], 2)
        );
        
        $headers = array('Content-Type: text/plain; charset=UTF-8');
        
        foreach ($admins as $admin) {
            $user = Simuze_User::get($admin['user_id']);
            if ($user && $user->email) {
                wp_mail($user->email, $subject, $message, $headers);
            }
        }
    }
    
    /**
     * Render download modal in footer
     */
    public function render_download_modal() {
        include SIMUZE_COMMUNITY_PLUGIN_DIR . 'templates/download-modal.php';
    }
    
    /**
     * Get user's purchase history
     */
    public static function get_user_purchases($user_id, $args = array()) {
        return Simuze_Order::get_by_buyer($user_id, array_merge(array('status' => 'paid'), $args));
    }
    
    /**
     * Get group's sales history
     */
    public static function get_group_sales($group_id, $args = array()) {
        return Simuze_Order::get_by_seller('group', $group_id, array_merge(array('status' => 'paid'), $args));
    }
}

// Initialize
Simuze_Download_Flow::get_instance();

