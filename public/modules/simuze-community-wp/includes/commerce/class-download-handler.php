<?php
/**
 * Download Handler for secure file downloads
 */

if (!defined('ABSPATH')) {
    exit;
}

class Simuze_Download_Handler {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'register_rewrite_rules'));
        add_action('template_redirect', array($this, 'handle_download'));
        add_filter('query_vars', array($this, 'add_query_vars'));
    }
    
    /**
     * Register rewrite rules for downloads
     */
    public function register_rewrite_rules() {
        add_rewrite_rule(
            '^download/(song|album)/([0-9]+)/?$',
            'index.php?simuze_download=1&download_type=$matches[1]&download_id=$matches[2]',
            'top'
        );
    }
    
    /**
     * Add query vars
     */
    public function add_query_vars($vars) {
        $vars[] = 'simuze_download';
        $vars[] = 'download_type';
        $vars[] = 'download_id';
        return $vars;
    }
    
    /**
     * Handle download request
     */
    public function handle_download() {
        if (!get_query_var('simuze_download')) {
            return;
        }
        
        $type = get_query_var('download_type');
        $id = get_query_var('download_id');
        $token_string = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : '';
        
        if (empty($token_string)) {
            $this->send_error('Missing download token', 400);
            return;
        }
        
        // Validate token
        $token = Simuze_Download_Token::get_by_token($token_string);
        
        if (!$token) {
            $this->send_error('Invalid download token', 403);
            return;
        }
        
        if (!$token->is_valid()) {
            $this->send_error('Download token has expired or reached its limit', 403);
            return;
        }
        
        // Verify token matches the requested product
        if ($token->product_type !== $type || $token->product_id != $id) {
            $this->send_error('Token does not match requested product', 403);
            return;
        }
        
        // Get product
        $product = $token->get_product();
        
        if (!$product) {
            $this->send_error('Product not found', 404);
            return;
        }
        
        // Handle download based on type
        if ($type === 'song') {
            $this->download_song($product, $token);
        } elseif ($type === 'album') {
            $this->download_album($product, $token);
        }
    }
    
    /**
     * Download single song
     */
    private function download_song($song, $token) {
        $file_path = $song->get_file_path();
        
        if (!$file_path || !file_exists($file_path)) {
            $this->send_error('File not found', 404);
            return;
        }
        
        // Convert to MP3 if needed (always deliver MP3 for compatibility)
        $converter = Simuze_Audio_Converter::get_instance();
        $download_path = $converter->get_mp3_version($file_path, 'high');
        
        // Record download
        $token->record_download();
        
        // Send file as MP3
        $this->send_file($download_path, $song->title . '.mp3');
    }
    
    /**
     * Download album as zip
     */
    private function download_album($album, $token) {
        $songs = $album->get_songs();
        
        if (empty($songs)) {
            $this->send_error('Album has no songs', 404);
            return;
        }
        
        // Create temporary zip file
        $zip_filename = sanitize_file_name($album->title) . '.zip';
        $temp_file = wp_tempnam($zip_filename);
        
        $zip = new ZipArchive();
        if ($zip->open($temp_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->send_error('Failed to create download package', 500);
            return;
        }
        
        foreach ($songs as $index => $song) {
            $file_path = $song->get_file_path();
            if ($file_path && file_exists($file_path)) {
                $track_number = str_pad($index + 1, 2, '0', STR_PAD_LEFT);
                $extension = pathinfo($file_path, PATHINFO_EXTENSION);
                $filename = $track_number . ' - ' . sanitize_file_name($song->title) . '.' . $extension;
                $zip->addFile($file_path, $filename);
            }
        }
        
        // Add album cover if exists
        if ($album->cover_image) {
            $cover_path = $this->url_to_path($album->cover_image);
            if ($cover_path && file_exists($cover_path)) {
                $extension = pathinfo($cover_path, PATHINFO_EXTENSION);
                $zip->addFile($cover_path, 'cover.' . $extension);
            }
        }
        
        $zip->close();
        
        // Record download
        $token->record_download();
        
        // Send zip file
        $this->send_file($temp_file, $zip_filename, true);
    }
    
    /**
     * Send file to browser
     */
    private function send_file($file_path, $filename, $delete_after = false) {
        if (!file_exists($file_path)) {
            $this->send_error('File not found', 404);
            return;
        }
        
        $file_size = filesize($file_path);
        $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        
        // Get mime type
        $mime_types = array(
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'flac' => 'audio/flac',
            'aac' => 'audio/aac',
            'm4a' => 'audio/mp4',
            'ogg' => 'audio/ogg',
            'zip' => 'application/zip',
        );
        
        $mime_type = $mime_types[$extension] ?? 'application/octet-stream';
        
        // Sanitize filename
        $safe_filename = sanitize_file_name($filename);
        if (pathinfo($safe_filename, PATHINFO_EXTENSION) !== $extension) {
            $safe_filename .= '.' . $extension;
        }
        
        // Clear output buffer
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        // Send headers
        header('Content-Type: ' . $mime_type);
        header('Content-Disposition: attachment; filename="' . $safe_filename . '"');
        header('Content-Length: ' . $file_size);
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: private, no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Read file in chunks to handle large files
        $handle = fopen($file_path, 'rb');
        while (!feof($handle)) {
            echo fread($handle, 8192);
            flush();
        }
        fclose($handle);
        
        // Delete temp file if needed
        if ($delete_after && file_exists($file_path)) {
            unlink($file_path);
        }
        
        exit;
    }
    
    /**
     * Send error response
     */
    private function send_error($message, $status_code = 400) {
        status_header($status_code);
        
        if (wp_is_json_request()) {
            header('Content-Type: application/json');
            echo json_encode(array('error' => $message));
        } else {
            get_header();
            echo '<div class="simuze-error"><h1>Download Error</h1><p>' . esc_html($message) . '</p></div>';
            get_footer();
        }
        
        exit;
    }
    
    /**
     * Convert URL to file path
     */
    private function url_to_path($url) {
        $upload_dir = wp_upload_dir();
        
        if (strpos($url, $upload_dir['baseurl']) !== false) {
            return str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $url);
        }
        
        return null;
    }
}

