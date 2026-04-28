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

if (!defined('SIMUZE_MODULAR_RUN') && !defined('ABSPATH')) {
    exit;
}

class Simuze_Download_Flow {

    private static $instance = null;

    const FORMATS = [
        'mp3_320' => ['label' => 'MP3 320kbps',       'extension' => 'mp3',  'mime' => 'audio/mpeg', 'always_available' => true],
        'flac'    => ['label' => 'FLAC (Lossless)',    'extension' => 'flac', 'mime' => 'audio/flac', 'always_available' => false],
        'wav'     => ['label' => 'WAV (Uncompressed)', 'extension' => 'wav',  'mime' => 'audio/wav',  'always_available' => false],
        'aiff'    => ['label' => 'AIFF',               'extension' => 'aiff', 'mime' => 'audio/aiff', 'always_available' => false],
    ];

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        md_hook_action('wp_ajax_simuze_get_product_info',        [$this, 'ajax_get_product_info']);
        md_hook_action('wp_ajax_nopriv_simuze_get_product_info', [$this, 'ajax_get_product_info']);

        md_hook_action('wp_ajax_simuze_create_download_order',        [$this, 'ajax_create_download_order']);
        md_hook_action('wp_ajax_nopriv_simuze_create_download_order', [$this, 'ajax_create_download_order']);

        md_hook_action('wp_ajax_simuze_process_download',        [$this, 'ajax_process_download']);
        md_hook_action('wp_ajax_nopriv_simuze_process_download', [$this, 'ajax_process_download']);

        md_hook_action('wp_ajax_simuze_get_download_formats',        [$this, 'ajax_get_download_formats']);
        md_hook_action('wp_ajax_nopriv_simuze_get_download_formats', [$this, 'ajax_get_download_formats']);

        md_hook_action('wp_footer',          [$this, 'render_download_modal']);
        md_hook_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        md_hook_action('template_redirect',  [$this, 'handle_download_page']);
    }

    public function enqueue_scripts() {
        if (function_exists('wp_enqueue_script')) {
            wp_enqueue_script('simuze-download-flow', SIMUZE_COMMUNITY_PLUGIN_URL . 'assets/js/download-flow.js', ['jquery'], SIMUZE_COMMUNITY_VERSION, true);
        }
        if (function_exists('wp_enqueue_style')) {
            wp_enqueue_style('simuze-download-flow', SIMUZE_COMMUNITY_PLUGIN_URL . 'assets/css/download-flow.css', [], SIMUZE_COMMUNITY_VERSION);
        }

        $auth         = Simuze_Auth::get_instance();
        $current_user = $auth->get_current_user();

        $payment_url = md_db_var("SELECT option_value FROM settings WHERE option_name = 'simezu_payment_url' LIMIT 1") ?: 'http://localhost/simezu/api/payments';

        $data = [
            'ajaxUrl'             => md_url('/api/simezu-ajax.php'),
            'nonce'               => md_csrf_token(),
            'isLoggedIn'          => $current_user !== null,
            'userId'              => $current_user ? $current_user->id : null,
            'userName'            => $current_user ? ($current_user->display_name ?? $current_user->username ?? '') : '',
            'userEmail'           => $current_user ? ($current_user->email ?? '') : '',
            'currency'            => 'EUR',
            'currencySymbol'      => '€',
            'simezu_payment_url'  => $payment_url,
            'i18n'                => [
                'download'       => 'Download',
                'buy'            => 'Buy',
                'free'           => 'Free',
                'processing'     => 'Processing...',
                'error'          => 'An error occurred',
                'minimum_price'  => 'Minimum price',
                'support_artist' => 'Pay more to support the artist',
                'logged_in_as'   => 'Logged in as',
                'not_logged_in'  => 'Not logged in',
            ],
        ];

        if (function_exists('wp_localize_script')) {
            wp_localize_script('simuze-download-flow', 'SimuzeDownload', $data);
        }
    }

    // -------------------------------------------------------------------------
    // AJAX: Get product info for modal
    // -------------------------------------------------------------------------

    public function ajax_get_product_info() {
        try {
            if (!md_verify_csrf($_POST['nonce'] ?? '')) {
                error_log('Simuze Download Flow: Nonce verification failed.');
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'data' => 'Security check failed. Please refresh the page and try again.']);
                exit;
            }

            $product_type           = md_sanitize_text($_POST['product_type'] ?? '');
            $product_id             = (int) ($_POST['product_id']             ?? 0);
            $upload_id_from_request = (int) ($_POST['upload_id']              ?? 0);

            if (!in_array($product_type, ['song', 'album', 'track'])) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'data' => 'Invalid product type']);
                exit;
            }

            if ($product_type === 'track') $product_type = 'song';

            $product             = null;
            $group               = null;
            $artwork             = '';
            $original_product_id = $product_id;
            $upload_id_for_price = $upload_id_from_request > 0 ? $upload_id_from_request : null;

            error_log('Simuze Download Flow: AJAX request - product_type=' . $product_type . ', product_id=' . $product_id . ', upload_id_from_request=' . $upload_id_from_request);

            if ($product_type === 'song') {
                // Check if product_id is an upload_id in md_music_uploads
                if (!$upload_id_for_price) {
                    $upload_check = md_db_row("SELECT id, price FROM md_music_uploads WHERE id = ?", [$product_id]);
                    if ($upload_check) {
                        $upload_id_for_price = $upload_check['id'];
                        error_log('Simuze Download Flow: product_id is upload_id - ' . $upload_id_for_price);
                    }
                }

                // Try Simuze_Song by ID
                $product = Simuze_Song::get($product_id);

                // Try by upload_id in simuze_songs
                if (!$product && $upload_id_for_price) {
                    $song_data = md_db_row("SELECT * FROM simuze_songs WHERE upload_id = ? AND deleted_at IS NULL", [$upload_id_for_price]);
                    if ($song_data) {
                        $product    = new Simuze_Song($song_data);
                        $product_id = $product->id;
                    }
                }

                // Fallback: create temporary from upload record
                if (!$product && $upload_id_for_price) {
                    $product = $this->get_or_create_song_from_upload($upload_id_for_price);
                    if ($product && $product->id === 0) {
                        $product_id = $upload_id_for_price;
                    }
                }

                // Resolve upload_id from product if still not set
                if ($product && !$upload_id_for_price) {
                    if (isset($product->upload_id) && $product->upload_id > 0) {
                        $upload_id_for_price = $product->upload_id;
                    } elseif (isset($product->id) && $product->id > 0) {
                        $uid = md_db_var("SELECT upload_id FROM simuze_songs WHERE id = ? AND deleted_at IS NULL", [$product->id]);
                        if ($uid) $upload_id_for_price = $uid;
                    }
                }

                // Final fallback: original product_id might be upload_id
                if (!$upload_id_for_price) {
                    $check = md_db_row("SELECT id FROM md_music_uploads WHERE id = ?", [$original_product_id]);
                    if ($check) $upload_id_for_price = $original_product_id;
                }

                if ($product) {
                    try {
                        $group   = $product->get_group();
                        $artwork = $product->artwork_url ?: ($group ? $group->get_avatar_url('medium') : '');
                    } catch (Exception $e) {
                        error_log('Simuze Download Flow: Error getting group: ' . $e->getMessage());
                        $group   = null;
                        $artwork = $product->artwork_url ?: '';
                    }
                } else {
                    error_log('Simuze Download Flow: Product not found. product_type=' . $product_type . ', product_id=' . $original_product_id);
                }

            } else {
                // Album
                error_log('Simuze Download Flow: Attempting to get album with product_id=' . $product_id);
                $product = Simuze_Album::get($product_id);
                error_log('Simuze Download Flow: Simuze_Album::get result: ' . ($product ? 'Found (ID: ' . $product->id . ')' : 'Not found'));

                if (!$product) {
                    // Try md_music_albums
                    $album_data = md_db_row("SELECT * FROM md_music_albums WHERE id = ?", [$product_id]);
                    error_log('Simuze Download Flow: md_music_albums query result: ' . ($album_data ? 'Found' : 'Not found'));

                    if ($album_data) {
                        // Get price from md_music_albums
                        $album_price = ($album_data['price'] !== null && is_numeric($album_data['price']) && $album_data['price'] > 0)
                            ? floatval($album_data['price'])
                            : 10.00;

                        $upload  = md_upload_dir();
                        $art_url = '';
                        if (!empty($album_data['artwork_path'])) {
                            $art_url = rtrim($upload['baseurl'], '/') . '/' . ltrim($album_data['artwork_path'], '/');
                        } elseif (!empty($album_data['artwork_thumbnail_path'])) {
                            $art_url = rtrim($upload['baseurl'], '/') . '/' . ltrim($album_data['artwork_thumbnail_path'], '/');
                        }

                        $product = new Simuze_Album([
                            'id'           => $album_data['id'],
                            'group_id'     => $album_data['group_id']     ?? 0,
                            'title'        => $album_data['album_name']   ?? '',
                            'slug'         => $album_data['album_nicename'] ?? '',
                            'description'  => $album_data['description']  ?? '',
                            'price'        => $album_price,
                            'cover_image'  => $art_url,
                            'release_date' => $album_data['release_date'] ?? $album_data['publication_date'] ?? '',
                            'is_active'      => 1,
                            'is_purchasable' => 1,
                        ]);
                        error_log('Simuze Download Flow: Created Simuze_Album object from md_music_albums data');
                    } else {
                        error_log('Simuze Download Flow: Album not found in md_music_albums table either');
                    }
                }

                if ($product) {
                    $group   = null;
                    if (method_exists($product, 'get_group')) {
                        try {
                            $group = $product->get_group();
                        } catch (Exception $e) {
                            error_log('Simuze Download Flow: Exception in get_group(): ' . $e->getMessage());
                            if (!empty($product->group_id) && class_exists('Simuze_Group')) {
                                $group = Simuze_Group::get($product->group_id);
                            }
                        }
                    } elseif (!empty($product->group_id) && class_exists('Simuze_Group')) {
                        $group = Simuze_Group::get($product->group_id);
                    }

                    $artwork = '';
                    if (!empty($product->cover_image)) {
                        $artwork = $product->cover_image;
                    } elseif ($group && method_exists($group, 'get_avatar_url')) {
                        $artwork = $group->get_avatar_url('medium');
                    }
                } else {
                    error_log('Simuze Download Flow: Product is null after all attempts');
                }
            }

            if (!$product) {
                error_log('Simuze Download Flow: FINAL - Product not found. product_type=' . $product_type . ', product_id=' . $original_product_id . ', upload_id=' . $upload_id_from_request);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'data' => 'Product not found. Please check the product ID and try again.']);
                exit;
            }

            // Get pricing info
            try {
                $auth         = Simuze_Auth::get_instance();
                $current_user = $auth->get_current_user();

                $pricing = method_exists($product, 'get_final_price')
                    ? $product->get_final_price($current_user ? $current_user->id : null)
                    : ['final_price' => $product->price ?? 0, 'discount' => 0, 'discount_type' => null];

                $has_purchased = false;
                if ($current_user && class_exists('Simuze_Purchase_Flow')) {
                    try {
                        $has_purchased = Simuze_Purchase_Flow::has_purchased($current_user->id, $product_type, $product_id);
                    } catch (Exception $e) {
                        error_log('Simuze Download Flow: Exception checking purchase status: ' . $e->getMessage());
                    }
                }
            } catch (Exception $e) {
                error_log('Simuze Download Flow: Exception getting pricing info: ' . $e->getMessage());
                $pricing       = ['final_price' => $product->price ?? 0, 'discount' => 0, 'discount_type' => null];
                $has_purchased = false;
            }

            // Determine base_price from upload table (authoritative for songs)
            $base_price = 0;

            if ($product_type === 'song') {
                if ($upload_id_for_price && $upload_id_for_price > 0) {
                    $upload_row = md_db_row("SELECT id, song_name, price FROM md_music_uploads WHERE id = ?", [$upload_id_for_price]);
                    if ($upload_row) {
                        $upload_price = $upload_row['price'];
                        error_log('Simuze Download Flow: Checking upload table - upload_id=' . $upload_id_for_price . ', song_name=' . ($upload_row['song_name'] ?? 'N/A') . ', upload_price=' . var_export($upload_price, true));
                        if ($upload_price !== null && $upload_price !== '' && is_numeric($upload_price)) {
                            $base_price      = floatval($upload_price);
                            $product->price  = $base_price;
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

                if ($base_price <= 0) {
                    error_log('Simuze Download Flow: base_price is 0 or not found, setting to default 1.00');
                    $base_price     = 1.00;
                    $product->price = $base_price;
                }
            } else {
                $base_price = floatval($product->price ?? 0);
                if ($base_price <= 0) {
                    $base_price     = 10.00;
                    $product->price = $base_price;
                }
            }

            if ($product_type === 'song') {
                $product->download_price_min     = $base_price;
                $product->download_price_default = $base_price;
            }

            $price_min     = $this->get_product_price_min($product, $base_price);
            $price_default = max($price_min, $base_price);

            error_log('Simuze Download Flow: Product price info - product_id=' . $product_id . ', upload_id=' . ($upload_id_for_price ?? 'N/A') . ', base_price=' . $base_price . ', price_min=' . $price_min . ', price_default=' . $price_default);

            $product_title = $product->title ?? $product->album_name ?? 'Untitled';
            $artist_name   = $group ? ($group->name ?? '') : ($product->artist_name ?? '');

            error_log('Simuze Download Flow: Sending response - title=' . $product_title . ', artist=' . $artist_name . ', price_min=' . $price_min . ', price_default=' . $price_default);

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'data' => [
                'product_type'       => $product_type,
                'product_id'         => $product_id,
                'title'              => $product_title,
                'artist'             => $artist_name,
                'artist_id'          => $group ? $group->id : null,
                'artwork'            => $artwork,
                'price_min'          => $price_min,
                'price_default'      => $price_default,
                'base_price'         => $base_price,
                'final_price'        => $pricing['final_price'],
                'is_free'            => $base_price <= 0,
                'has_purchased'      => $has_purchased,
                'product_type_label' => $product_type === 'song' ? 'Track' : 'Album',
            ]]);
            exit;

        } catch (Exception $e) {
            error_log('Simuze Download Flow: Exception in ajax_get_product_info - ' . $e->getMessage());
            error_log('Simuze Download Flow: Stack trace - ' . $e->getTraceAsString());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'data' => 'An error occurred: ' . $e->getMessage()]);
            exit;
        } catch (Error $e) {
            error_log('Simuze Download Flow: Fatal error in ajax_get_product_info - ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'data' => 'A fatal error occurred: ' . $e->getMessage()]);
            exit;
        }
    }

    // -------------------------------------------------------------------------
    // Helpers: temporary Song from upload record
    // -------------------------------------------------------------------------

    private function get_or_create_song_from_upload($upload_id) {
        $upload = md_db_row("SELECT * FROM md_music_uploads WHERE id = ?", [$upload_id]);
        if (!$upload) return null;

        $upload_price = (isset($upload['price']) && $upload['price'] !== null && $upload['price'] !== '' && is_numeric($upload['price']) && floatval($upload['price']) > 0)
            ? floatval($upload['price'])
            : 1.00;

        $slug = preg_replace('/[^a-z0-9-]+/', '-', strtolower($upload['song_name'] ?? 'track-' . $upload_id));
        $slug = trim($slug, '-');

        return new Simuze_Song([
            'id'                    => 0,
            'upload_id'             => $upload_id,
            'group_id'              => isset($upload['group_id']) ? (int) $upload['group_id'] : 0,
            'title'                 => $upload['song_name']  ?? $upload['file_name'] ?? 'Unknown Track',
            'slug'                  => $slug,
            'description'           => '',
            'price'                 => $upload_price,
            'download_price_min'    => $upload_price,
            'download_price_default' => $upload_price,
            'file_path'             => $upload['file_path'] ?? '',
            'file_size'             => $upload['file_size'] ?? 0,
            'duration'              => $upload['duration']  ?? 0,
            'artwork_url'           => '',
            'genre'                 => $upload['genre']     ?? '',
            'license'               => $upload['license']   ?? '',
            'is_active'             => 1,
            'is_purchasable'        => 1,
            'download_count'        => 0,
            'created_at'            => $upload['created_at'] ?? date('Y-m-d H:i:s'),
            'updated_at'            => date('Y-m-d H:i:s'),
        ]);
    }

    private function get_product_price_min($product, $base_price_override = null) {
        if ($base_price_override !== null && $base_price_override > 0) return floatval($base_price_override);
        if (isset($product->download_price_min) && $product->download_price_min > 0) return floatval($product->download_price_min);
        $base = floatval($product->price ?? 0);
        if ($base > 0) return $base;
        $pricing = $product->get_final_price();
        return max(0, $pricing['final_price']);
    }

    private function get_product_price_default($product, $base_price_override = null) {
        if ($base_price_override !== null && $base_price_override > 0) return floatval($base_price_override);
        if (isset($product->download_price_default) && $product->download_price_default > 0) return floatval($product->download_price_default);
        $base = floatval($product->price ?? 0);
        if ($base > 0) return $base;
        $is_album = (get_class($product) === 'Simuze_Album' || isset($product->album_name));
        return $is_album ? 10.00 : 1.00;
    }

    // -------------------------------------------------------------------------
    // AJAX: Create download order with custom price
    // -------------------------------------------------------------------------

    public function ajax_create_download_order() {
        if (!md_verify_csrf($_POST['nonce'] ?? '')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'data' => 'Security check failed']);
            exit;
        }

        $product_type   = md_sanitize_text($_POST['product_type']   ?? '');
        $product_id     = (int) ($_POST['product_id']               ?? 0);
        $custom_price   = (float) ($_POST['custom_price']           ?? 0);
        $email          = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $payment_method = md_sanitize_text($_POST['payment_method'] ?? 'paypal');

        if ($product_type === 'track') $product_type = 'song';

        if (!in_array($product_type, ['song', 'album'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'data' => 'Invalid product type']);
            exit;
        }

        $original_product_id = $product_id;
        $product             = null;

        if ($product_type === 'song') {
            $product = Simuze_Song::get($product_id);

            if (!$product) {
                $song_data = md_db_row("SELECT * FROM simuze_songs WHERE upload_id = ? AND deleted_at IS NULL", [$product_id]);
                if ($song_data) {
                    $product    = new Simuze_Song($song_data);
                    $product_id = $product->id;
                }
            }

            if (!$product) {
                $product = $this->get_or_create_song_from_upload($original_product_id);
                if ($product && $product->id === 0) $product_id = $original_product_id;
            }
        } else {
            $product = Simuze_Album::get($product_id);
        }

        if (!$product) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'data' => 'Product not found']);
            exit;
        }

        $price_min = $this->get_product_price_min($product);
        if ($custom_price < $price_min) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'data' => sprintf('Minimum price is €%s', number_format($price_min, 2))]);
            exit;
        }

        $auth         = Simuze_Auth::get_instance();
        $current_user = $auth->get_current_user();

        if ($custom_price <= 0 && $price_min > 0) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'data' => sprintf('Minimum price is €%s. Free downloads are not allowed.', number_format($price_min, 2))]);
            exit;
        }

        if ($custom_price <= 0 && $price_min <= 0) {
            return $this->handle_free_download($product_type, $product_id, $current_user, $email);
        }

        if (!$current_user && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'data' => 'Valid email address required for guest checkout']);
            exit;
        }

        $api_url = md_db_var("SELECT option_value FROM settings WHERE option_name = 'simezu_api_url' LIMIT 1") ?: '';
        $redirect = $api_url ? rtrim($api_url, '/') . '/checkout?product_type=' . urlencode($product_type) . '&product_id=' . $product_id . '&price=' . $custom_price . ($current_user ? '&buyer_id=' . $current_user->id : '&email=' . urlencode($email)) : '';

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => [
            'redirect_url' => $redirect,
            'message'      => 'Checkout runs via SIMEZU.',
        ]]);
        exit;
    }

    // -------------------------------------------------------------------------
    // Free download handler
    // -------------------------------------------------------------------------

    private function handle_free_download($product_type, $product_id, $current_user, $email) {
        $product = $product_type === 'song' ? Simuze_Song::get($product_id) : Simuze_Album::get($product_id);

        if (!$product) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'data' => 'Product not found']);
            exit;
        }

        if ($current_user) {
            try {
                md_db_exec(
                    "INSERT INTO simuze_download_grants (user_id, product_type, product_id, simezu_reference, created_at)
                     VALUES (?, ?, ?, 'free', NOW())
                     ON DUPLICATE KEY UPDATE simezu_reference = VALUES(simezu_reference)",
                    [$current_user->id, $product_type, $product_id]
                );
            } catch (\Throwable $e) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'data' => 'Failed to grant download']);
                exit;
            }
            $download_url = md_home_url('/download/' . $product_type . '/' . $product_id);
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'data' => [
                'is_free'      => true,
                'download_url' => $download_url,
            ]]);
            exit;
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => [
            'is_free'  => true,
            'message'  => 'Log in to access your free download.',
            'login_url' => md_home_url('/profile'),
        ]]);
        exit;
    }

    // -------------------------------------------------------------------------
    // AJAX: Get download formats
    // -------------------------------------------------------------------------

    public function ajax_get_download_formats() {
        if (!md_verify_csrf($_POST['nonce'] ?? '')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'data' => 'Security check failed']);
            exit;
        }

        $product_type = md_sanitize_text($_POST['product_type'] ?? '');
        $product_id   = (int) ($_POST['product_id'] ?? 0);
        if (!in_array($product_type, ['song', 'album']) || !$product_id) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'data' => 'Invalid request']);
            exit;
        }

        $auth = Simuze_Auth::get_instance();
        $user = $auth->get_current_user();
        if (!$user) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'data' => 'Login required']);
            exit;
        }
        $has_grant = (int) md_db_var("SELECT COUNT(*) FROM simuze_download_grants WHERE user_id = ? AND product_type = ? AND product_id = ?", [$user->id, $product_type, $product_id]) > 0;
        if (!$has_grant) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'data' => 'No download access']);
            exit;
        }

        $formats = $this->get_available_formats($product_type, $product_id);
        $product = $product_type === 'song' ? Simuze_Song::get($product_id) : Simuze_Album::get($product_id);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => [
            'product'             => $product ? $product->to_array() : null,
            'formats'             => $formats,
            'downloads_remaining' => 'unlimited',
        ]]);
        exit;
    }

    // -------------------------------------------------------------------------
    // Format helpers
    // -------------------------------------------------------------------------

    private function get_available_formats($product_type, $product_id) {
        $available             = [];
        $available['mp3_320']  = self::FORMATS['mp3_320'];
        $available['mp3_320']['available'] = true;
        $available['mp3_320']['size']      = $this->estimate_file_size($product_type, $product_id, 'mp3_320');

        foreach (['flac', 'wav', 'aiff'] as $format) {
            if ($this->check_format_exists($product_type, $product_id, $format)) {
                $available[$format]             = self::FORMATS[$format];
                $available[$format]['available'] = true;
                $available[$format]['size']      = $this->estimate_file_size($product_type, $product_id, $format);
            }
        }

        return $available;
    }

    private function check_format_exists($product_type, $product_id, $format) {
        return $format === 'mp3_320';
    }

    private function estimate_file_size($product_type, $product_id, $format) {
        $duration = 240;
        if ($product_type === 'song') {
            $product = Simuze_Song::get($product_id);
            if ($product && $product->duration) $duration = $product->duration;
        }

        $bitrates = ['mp3_320' => 320, 'flac' => 1000, 'wav' => 1411, 'aiff' => 1411];
        $bitrate  = $bitrates[$format] ?? 320;
        return $this->format_file_size(($bitrate * $duration / 8) * 1024);
    }

    private function format_file_size($bytes) {
        if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 1) . ' GB';
        if ($bytes >= 1048576)    return number_format($bytes / 1048576, 1) . ' MB';
        if ($bytes >= 1024)       return number_format($bytes / 1024, 1) . ' KB';
        return $bytes . ' bytes';
    }

    // -------------------------------------------------------------------------
    // AJAX: Process actual file download
    // -------------------------------------------------------------------------

    public function ajax_process_download() {
        $product_type = md_sanitize_text($_GET['product_type'] ?? $_POST['product_type'] ?? '');
        $product_id   = (int) ($_GET['product_id'] ?? $_POST['product_id'] ?? 0);
        $format       = md_sanitize_text($_GET['format'] ?? $_POST['format'] ?? 'mp3_320');

        if (!in_array($product_type, ['song', 'album']) || !$product_id) die('Invalid download request');

        $auth = Simuze_Auth::get_instance();
        $user = $auth->get_current_user();
        if (!$user) die('Login required');
        $has_grant = (int) md_db_var("SELECT COUNT(*) FROM simuze_download_grants WHERE user_id = ? AND product_type = ? AND product_id = ?", [$user->id, $product_type, $product_id]) > 0;
        if (!$has_grant) die('No download access');

        $product = $product_type === 'song' ? Simuze_Song::get($product_id) : Simuze_Album::get($product_id);
        if (!$product) die('Product not found');

        $file_path = $product->get_file_path();
        if (!$file_path || !file_exists($file_path)) die('File not found');

        $ext      = self::FORMATS[$format]['extension'] ?? 'mp3';
        $mime     = self::FORMATS[$format]['mime']      ?? 'audio/mpeg';
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $product->title) . '.' . $ext;

        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($file_path));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');

        readfile($file_path);
        exit;
    }

    // -------------------------------------------------------------------------
    // Download page routing
    // -------------------------------------------------------------------------

    public function handle_download_page() {
        $uri = $_SERVER['REQUEST_URI'];

        if (preg_match('#/download/success/?#', $uri)) {
            $this->render_download_success_page();
            exit;
        }

        if (preg_match('#/download/access/?#', $uri)) {
            $this->render_download_access_page();
            exit;
        }
    }

    private function render_download_success_page() {
        if (defined('SIMUZE_COMMUNITY_PLUGIN_DIR') && file_exists(SIMUZE_COMMUNITY_PLUGIN_DIR . 'templates/download-success.php')) {
            $order = null;
            include SIMUZE_COMMUNITY_PLUGIN_DIR . 'templates/download-success.php';
        } else {
            header('Content-Type: text/html; charset=utf-8');
            echo '<p>Payment is processed via SIMEZU. Your downloads are available in your account.</p><p><a href="' . htmlspecialchars(md_home_url('/profile'), ENT_QUOTES) . '">Go to profile</a></p>';
        }
    }

    private function render_download_access_page() {
        $auth = Simuze_Auth::get_instance();
        $user = $auth->get_current_user();
        if (!$user) {
            header('Location: ' . md_home_url('/profile'));
            exit;
        }
        if (defined('SIMUZE_COMMUNITY_PLUGIN_DIR') && file_exists(SIMUZE_COMMUNITY_PLUGIN_DIR . 'templates/download-access.php')) {
            $download_token = null;
            $product = null;
            $formats = [];
            include SIMUZE_COMMUNITY_PLUGIN_DIR . 'templates/download-access.php';
        } else {
            header('Location: ' . md_home_url('/profile'));
            exit;
        }
    }

    // -------------------------------------------------------------------------
    // Email notifications
    // -------------------------------------------------------------------------

    public function send_download_email($email, $product, $token, $amount) {
        $subject = 'Your download: ' . $product->title;
        $group   = method_exists($product, 'get_group') ? $product->get_group() : null;
        $url     = md_home_url('/profile');
        $message = sprintf(
            "Thank you for your purchase!\n\nProduct: %s\nArtist: %s\nAmount: €%s\n\nAccess your downloads in your account: %s\n\nEnjoy your music!\nThe Simuze Team",
            $product->title,
            $group ? $group->name : 'Unknown Artist',
            number_format($amount, 2),
            $url
        );
        mail($email, $subject, $message, 'Content-Type: text/plain; charset=UTF-8' . "\r\nFrom: Simuze <noreply@simuze.com>");
    }

    public function send_order_emails($order) {
    }

    private function send_seller_notification($item, $order) {
    }

    // -------------------------------------------------------------------------
    // Modal
    // -------------------------------------------------------------------------

    public function render_download_modal() {
        if (defined('SIMUZE_COMMUNITY_PLUGIN_DIR') && file_exists(SIMUZE_COMMUNITY_PLUGIN_DIR . 'templates/download-modal.php')) {
            include SIMUZE_COMMUNITY_PLUGIN_DIR . 'templates/download-modal.php';
        }
    }

    // -------------------------------------------------------------------------
    // Public static helpers
    // -------------------------------------------------------------------------

    public static function get_user_purchases($user_id, $args = []) {
        $limit  = (int) ($args['limit'] ?? 50);
        $offset = (int) ($args['offset'] ?? 0);
        $rows   = md_db_query("SELECT * FROM simuze_download_grants WHERE user_id = ? ORDER BY created_at DESC LIMIT " . max(1, min(100, $limit)) . " OFFSET " . $offset, [$user_id]);
        return $rows;
    }

    public static function get_group_sales($group_id, $args = []) {
        return [];
    }
}

// Initialize
Simuze_Download_Flow::get_instance();
