<?php
/**
 * Media API Controller
 * Handles media upload, processing, and management
 */

namespace Modular\API;

class MediaController {
    private $db;
    private $uploadDir;
    
    public function __construct($db) {
        $this->db = $db;
        $this->uploadDir = defined('UPLOADS_PATH') ? UPLOADS_PATH : (dirname(dirname(dirname(__DIR__))) . '/public/uploads');
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }
    
    public function index() {
        $stmt = $this->db->query("
            SELECT m.*, COALESCE(u.username, u.email, 'Unknown') as uploaded_by_name
            FROM media m
            LEFT JOIN users u ON m.uploaded_by = u.id
            WHERE m.deleted_at IS NULL
            ORDER BY m.created_at DESC
            LIMIT 100
        ");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    public function show($id) {
        $stmt = $this->db->prepare("
            SELECT m.*, COALESCE(u.username, u.email, 'Unknown') as uploaded_by_name
            FROM media m
            LEFT JOIN users u ON m.uploaded_by = u.id
            WHERE m.id = ? AND m.deleted_at IS NULL
        ");
        $stmt->execute([$id]);
        $media = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$media) {
            return ['error' => 'Media not found', 'code' => 404];
        }
        
        $media['variants'] = json_decode($media['variants'], true);
        $media['metadata'] = json_decode($media['metadata'], true);
        $media['tags'] = json_decode($media['tags'], true);
        
        return $media;
    }
    
    public function upload() {
        $userId = $GLOBALS['current_user']['user_id'] ?? 1;
        
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            return ['error' => 'File upload failed', 'code' => 400];
        }
        
        $file = $_FILES['file'];
        $title = $_POST['title'] ?? basename($file['name']);
        $altText = $_POST['alt_text'] ?? '';
        $folderPath = isset($_POST['folder_path']) ? trim((string)$_POST['folder_path']) : null;
        
        // Validate file (images, PDF, audio for Simuze music)
        $allowedTypes = [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf',
            'audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/x-wav', 'audio/ogg', 'audio/webm', 'audio/flac', 'audio/aac', 'audio/mp4',
        ];
        if (!in_array($file['type'], $allowedTypes)) {
            return ['error' => 'File type not allowed', 'code' => 400];
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $filePath = $this->uploadDir . '/' . $filename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            return ['error' => 'Failed to save file', 'code' => 500];
        }
        
        // Get file info
        $fileSize = filesize($filePath);
        $mimeType = $file['type'];
        $fileType = strpos($mimeType, 'image/') === 0 ? 'image' : (strpos($mimeType, 'audio/') === 0 ? 'audio' : 'file');
        
        $width = null;
        $height = null;
        $variants = [];
        
        // Process image if it's an image
        if ($fileType === 'image') {
            $imageInfo = getimagesize($filePath);
            if ($imageInfo) {
                $width = $imageInfo[0];
                $height = $imageInfo[1];
                
                // Generate variants
                $variants = $this->generateImageVariants($filePath, $filename);
            }
        }
        
        // Save to database
        $stmt = $this->db->prepare("
            INSERT INTO media 
            (filename, file_path, file_type, mime_type, file_size, width, height, 
             variants, title, alt_text, folder_path, uploaded_by, created_by, updated_by, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        $relativePath = (defined('UPLOADS_WEB_PATH') ? rtrim(UPLOADS_WEB_PATH, '/') . '/' : '/public/uploads/') . $filename;
        $variantsJson = json_encode($variants);
        
        $stmt->execute([
            $filename, $relativePath, $fileType, $mimeType, $fileSize, $width, $height,
            $variantsJson, $title, $altText, $folderPath ?: null, $userId, $userId, $userId
        ]);
        
        $id = $this->db->lastInsertId();
        return $this->show($id);
    }
    
    private function generateImageVariants($sourcePath, $baseFilename) {
        $variants = [];

        // Load dynamic image size configuration
        if (!function_exists('media_get_image_settings')) {
            require_once dirname(__DIR__) . '/functions/media-settings.php';
        }
        $settings = media_get_image_settings();
        $sizes = $settings['sizes'] ?? [
            'thumb' => ['width' => 150, 'height' => 150, 'crop' => true],
            'small' => ['width' => 300, 'height' => 300, 'crop' => false],
            'medium' => ['width' => 768, 'height' => 0, 'crop' => false],
            'large' => ['width' => 1200, 'height' => 0, 'crop' => false],
        ];
        $format = $settings['format'] ?? 'original';
        $quality = (int)($settings['quality'] ?? 85);
        
        $extension = pathinfo($baseFilename, PATHINFO_EXTENSION);
        $baseName = pathinfo($baseFilename, PATHINFO_FILENAME);
        
        foreach ($sizes as $name => $cfg) {
            $maxW = (int)($cfg['width'] ?? 0);
            $maxH = (int)($cfg['height'] ?? 0);
            $crop = !empty($cfg['crop']);

            $variantFilename = $baseName . '_' . $name . '.' . $extension;
            $variantPath = $this->uploadDir . '/' . $variantFilename;
            
            if ($this->resizeImage($sourcePath, $variantPath, $maxW, $maxH, $crop, $format, $quality)) {
                $variants[$name] = [
                    'filename' => $variantFilename,
                    'path' => (defined('UPLOADS_WEB_PATH') ? rtrim(UPLOADS_WEB_PATH, '/') . '/' : '/public/uploads/') . $variantFilename,
                    'width' => $maxW,
                    'height' => $maxH
                ];
            }
        }
        
        return $variants;
    }
    
    private function resizeImage($source, $destination, $maxWidth, $maxHeight, $crop = false, $targetFormat = 'original', $quality = 85) {
        $imageInfo = getimagesize($source);
        if (!$imageInfo) {
            return false;
        }
        
        $sourceWidth = $imageInfo[0];
        $sourceHeight = $imageInfo[1];
        $mimeType = $imageInfo['mime'];

        if ($maxWidth <= 0 && $maxHeight <= 0) {
            $maxWidth = $sourceWidth;
            $maxHeight = $sourceHeight;
        } elseif ($maxWidth <= 0) {
            $maxWidth = (int)round($sourceWidth * ($maxHeight / $sourceHeight));
        } elseif ($maxHeight <= 0) {
            $maxHeight = (int)round($sourceHeight * ($maxWidth / $sourceWidth));
        }

        // Calculate new dimensions
        if ($crop) {
            $targetRatio = $maxWidth / $maxHeight;
            $sourceRatio = $sourceWidth / $sourceHeight;
            if ($sourceRatio > $targetRatio) {
                // Source is wider
                $newHeight = $maxHeight;
                $newWidth = (int)round($maxHeight * $sourceRatio);
            } else {
                $newWidth = $maxWidth;
                $newHeight = (int)round($maxWidth / $sourceRatio);
            }
        } else {
            $ratio = min($maxWidth / $sourceWidth, $maxHeight / $sourceHeight);
            $newWidth = (int)($sourceWidth * $ratio);
            $newHeight = (int)($sourceHeight * $ratio);
        }
        
        // Create image resource
        switch ($mimeType) {
            case 'image/jpeg':
                $sourceImage = imagecreatefromjpeg($source);
                break;
            case 'image/png':
                $sourceImage = imagecreatefrompng($source);
                break;
            case 'image/gif':
                $sourceImage = imagecreatefromgif($source);
                break;
            case 'image/webp':
                $sourceImage = imagecreatefromwebp($source);
                break;
            default:
                return false;
        }
        
        if (!$sourceImage) {
            return false;
        }
        
        // Create resized image
        $destinationImage = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preserve transparency for PNG/GIF
        if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
            imagealphablending($destinationImage, false);
            imagesavealpha($destinationImage, true);
            $transparent = imagecolorallocatealpha($destinationImage, 255, 255, 255, 127);
            imagefilledrectangle($destinationImage, 0, 0, $newWidth, $newHeight, $transparent);
        }
        
        imagecopyresampled($destinationImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $sourceWidth, $sourceHeight);
        
        // Save resized image
        $result = false;
        $fmt = $targetFormat === 'original' ? $mimeType : ('image/' . $targetFormat);
        if ($fmt === 'image/jpeg' || $fmt === 'image/jpg') {
            $result = imagejpeg($destinationImage, $destination, $quality);
        } elseif ($fmt === 'image/png') {
            // For PNG, invert quality: 0 (no compression) - 9 (max). Map 10-100 to 0-9.
            $pngQuality = (int)round((100 - max(10, min(100, $quality))) / 10);
            $result = imagepng($destinationImage, $destination, $pngQuality);
        } elseif ($fmt === 'image/gif') {
            $result = imagegif($destinationImage, $destination);
        } elseif ($fmt === 'image/webp') {
            $result = imagewebp($destinationImage, $destination, $quality);
        }
        
        imagedestroy($sourceImage);
        imagedestroy($destinationImage);
        
        return $result;
    }
    
    public function delete($id) {
        $userId = $GLOBALS['current_user']['user_id'] ?? 1;
        
        $media = $this->show($id);
        if (isset($media['error'])) {
            return $media;
        }
        
        // Soft delete
        $stmt = $this->db->prepare("
            UPDATE media 
            SET deleted_at = NOW(), updated_by = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$userId, $id]);
        
        return ['message' => 'Media deleted'];
    }
}


