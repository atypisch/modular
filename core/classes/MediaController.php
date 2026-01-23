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
        $this->uploadDir = dirname(dirname(dirname(__DIR__))) . '/public/uploads';
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }
    
    public function index() {
        $stmt = $this->db->query("
            SELECT m.*, u.username as uploaded_by_name
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
            SELECT m.*, u.username as uploaded_by_name
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
        
        // Validate file
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];
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
        $fileType = strpos($mimeType, 'image/') === 0 ? 'image' : 'file';
        
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
             variants, title, alt_text, uploaded_by, created_by, updated_by, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        $relativePath = '/modular/public/uploads/' . $filename;
        $variantsJson = json_encode($variants);
        
        $stmt->execute([
            $filename, $relativePath, $fileType, $mimeType, $fileSize, $width, $height,
            $variantsJson, $title, $altText, $userId, $userId, $userId
        ]);
        
        $id = $this->db->lastInsertId();
        return $this->show($id);
    }
    
    private function generateImageVariants($sourcePath, $baseFilename) {
        $variants = [];
        $sizes = [
            'thumb' => [150, 150],
            'small' => [300, 300],
            'medium' => [768, 768],
            'large' => [1200, 1200]
        ];
        
        $extension = pathinfo($baseFilename, PATHINFO_EXTENSION);
        $baseName = pathinfo($baseFilename, PATHINFO_FILENAME);
        
        foreach ($sizes as $name => $dimensions) {
            $variantFilename = $baseName . '_' . $name . '.' . $extension;
            $variantPath = $this->uploadDir . '/' . $variantFilename;
            
            if ($this->resizeImage($sourcePath, $variantPath, $dimensions[0], $dimensions[1])) {
                $variants[$name] = [
                    'filename' => $variantFilename,
                    'path' => '/modular/public/uploads/' . $variantFilename,
                    'width' => $dimensions[0],
                    'height' => $dimensions[1]
                ];
            }
        }
        
        return $variants;
    }
    
    private function resizeImage($source, $destination, $maxWidth, $maxHeight) {
        $imageInfo = getimagesize($source);
        if (!$imageInfo) {
            return false;
        }
        
        $sourceWidth = $imageInfo[0];
        $sourceHeight = $imageInfo[1];
        $mimeType = $imageInfo['mime'];
        
        // Calculate new dimensions
        $ratio = min($maxWidth / $sourceWidth, $maxHeight / $sourceHeight);
        $newWidth = (int)($sourceWidth * $ratio);
        $newHeight = (int)($sourceHeight * $ratio);
        
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
        switch ($mimeType) {
            case 'image/jpeg':
                $result = imagejpeg($destinationImage, $destination, 85);
                break;
            case 'image/png':
                $result = imagepng($destinationImage, $destination, 9);
                break;
            case 'image/gif':
                $result = imagegif($destinationImage, $destination);
                break;
            case 'image/webp':
                $result = imagewebp($destinationImage, $destination, 85);
                break;
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


