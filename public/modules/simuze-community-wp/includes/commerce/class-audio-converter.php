<?php
/**
 * Audio Converter class
 * Converts audio files to MP3 for downloads
 */

if (!defined('ABSPATH')) {
    exit;
}

class Simuze_Audio_Converter {
    
    private static $instance = null;
    private $ffmpeg_path;
    private $cache_dir;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Try to find ffmpeg
        $this->ffmpeg_path = $this->find_ffmpeg();
        
        // Set up cache directory for converted files
        $upload_dir = wp_upload_dir();
        $this->cache_dir = $upload_dir['basedir'] . '/simuze-audio-cache';
        
        if (!is_dir($this->cache_dir)) {
            wp_mkdir_p($this->cache_dir);
            
            // Add .htaccess to prevent direct access
            file_put_contents($this->cache_dir . '/.htaccess', 'Deny from all');
        }
    }
    
    /**
     * Find ffmpeg binary
     */
    private function find_ffmpeg() {
        // Check common locations
        $paths = array(
            '/usr/bin/ffmpeg',
            '/usr/local/bin/ffmpeg',
            '/opt/local/bin/ffmpeg',
            '/opt/homebrew/bin/ffmpeg', // macOS Homebrew
            'C:\\ffmpeg\\bin\\ffmpeg.exe', // Windows
        );
        
        // Check if configured in options
        $configured = get_option('simuze_ffmpeg_path', '');
        if ($configured && file_exists($configured)) {
            return $configured;
        }
        
        foreach ($paths as $path) {
            if (file_exists($path) && is_executable($path)) {
                return $path;
            }
        }
        
        // Try to find via shell
        $which = shell_exec('which ffmpeg 2>/dev/null');
        if ($which) {
            $path = trim($which);
            if (file_exists($path)) {
                return $path;
            }
        }
        
        return null;
    }
    
    /**
     * Check if ffmpeg is available
     */
    public function is_available() {
        return $this->ffmpeg_path !== null;
    }
    
    /**
     * Get MP3 version of audio file
     * Returns path to MP3 file (cached if already converted)
     */
    public function get_mp3_version($source_path, $quality = 'high') {
        if (!file_exists($source_path)) {
            return null;
        }
        
        $extension = strtolower(pathinfo($source_path, PATHINFO_EXTENSION));
        
        // If already MP3, return original
        if ($extension === 'mp3') {
            return $source_path;
        }
        
        // Check cache
        $cache_key = md5($source_path . $quality . filemtime($source_path));
        $cache_file = $this->cache_dir . '/' . $cache_key . '.mp3';
        
        if (file_exists($cache_file)) {
            return $cache_file;
        }
        
        // Convert if ffmpeg available
        if ($this->is_available()) {
            $converted = $this->convert_to_mp3($source_path, $cache_file, $quality);
            if ($converted) {
                return $cache_file;
            }
        }
        
        // Fallback: return original if conversion failed
        return $source_path;
    }
    
    /**
     * Convert audio file to MP3
     */
    private function convert_to_mp3($source, $destination, $quality = 'high') {
        if (!$this->ffmpeg_path) {
            return false;
        }
        
        // Quality presets
        $bitrates = array(
            'low' => '128k',
            'medium' => '192k',
            'high' => '320k',
        );
        
        $bitrate = $bitrates[$quality] ?? '320k';
        
        // Build ffmpeg command
        $command = sprintf(
            '%s -i %s -codec:a libmp3lame -b:a %s -y %s 2>&1',
            escapeshellcmd($this->ffmpeg_path),
            escapeshellarg($source),
            $bitrate,
            escapeshellarg($destination)
        );
        
        exec($command, $output, $return_code);
        
        if ($return_code === 0 && file_exists($destination)) {
            return true;
        }
        
        error_log('Simuze Audio Converter: FFmpeg conversion failed - ' . implode("\n", $output));
        return false;
    }
    
    /**
     * Clean old cache files
     */
    public function clean_cache($max_age_days = 30) {
        $max_age = $max_age_days * 24 * 60 * 60;
        $now = time();
        
        $files = glob($this->cache_dir . '/*.mp3');
        $deleted = 0;
        
        foreach ($files as $file) {
            if ($now - filemtime($file) > $max_age) {
                unlink($file);
                $deleted++;
            }
        }
        
        return $deleted;
    }
    
    /**
     * Get cache directory size
     */
    public function get_cache_size() {
        $size = 0;
        $files = glob($this->cache_dir . '/*.mp3');
        
        foreach ($files as $file) {
            $size += filesize($file);
        }
        
        return $size;
    }
}











