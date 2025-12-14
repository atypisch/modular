<?php
/**
 * WordPress Importer
 * Imports WordPress export files
 */

namespace Modular;

class WPImporter {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Import WordPress XML export
     */
    public function import($xmlFile) {
        if (!file_exists($xmlFile)) {
            throw new \Exception('Import file not found');
        }
        
        $xml = simplexml_load_file($xmlFile);
        if (!$xml) {
            throw new \Exception('Invalid XML file');
        }
        
        $results = [
            'users' => 0,
            'posts' => 0,
            'media' => 0,
            'errors' => []
        ];
        
        // Import users
        foreach ($xml->channel->children('wp', true)->author as $author) {
            try {
                $this->importUser($author);
                $results['users']++;
            } catch (\Exception $e) {
                $results['errors'][] = 'User import error: ' . $e->getMessage();
            }
        }
        
        // Import posts
        foreach ($xml->channel->item as $item) {
            try {
                $this->importPost($item);
                $results['posts']++;
            } catch (\Exception $e) {
                $results['errors'][] = 'Post import error: ' . $e->getMessage();
            }
        }
        
        return $results;
    }
    
    private function importUser($author) {
        $username = (string)$author->author_login;
        $email = (string)$author->author_email;
        $displayName = (string)$author->author_display_name;
        
        // Check if user exists
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);
        if ($stmt->fetch()) {
            return; // User already exists
        }
        
        // Create user
        $nameParts = explode(' ', $displayName, 2);
        $firstName = $nameParts[0] ?? '';
        $lastName = $nameParts[1] ?? '';
        
        $stmt = $this->db->prepare("
            INSERT INTO users (username, email, first_name, last_name, role, created_at, updated_at)
            VALUES (?, ?, ?, ?, 'user', NOW(), NOW())
        ");
        $stmt->execute([$username, $email, $firstName, $lastName]);
    }
    
    private function importPost($item) {
        $title = (string)$item->title;
        $content = (string)$item->children('content', true)->encoded;
        $excerpt = (string)$item->children('excerpt', true)->encoded;
        $postType = (string)$item->children('wp', true)->post_type;
        $status = (string)$item->children('wp', true)->status;
        $postDate = (string)$item->children('wp', true)->post_date;
        
        // Map WordPress post types
        $postTypeMap = [
            'post' => 'post',
            'page' => 'page'
        ];
        
        $mappedType = $postTypeMap[$postType] ?? 'post';
        
        // Get or create post type
        $postTypeId = $this->getOrCreatePostType($mappedType);
        
        // Map status
        $statusMap = [
            'publish' => 'published',
            'draft' => 'draft',
            'private' => 'draft'
        ];
        $mappedStatus = $statusMap[$status] ?? 'draft';
        
        // Create slug
        $slug = (string)$item->children('wp', true)->post_name;
        if (empty($slug)) {
            $slug = $this->slugify($title);
        }
        
        // Get author
        $authorLogin = (string)$item->children('dc', true)->creator;
        $authorId = $this->getUserIdByUsername($authorLogin) ?? 1;
        
        // Create post
        $componentData = [
            [
                'type' => 'wysiwyg',
                'data' => ['content' => $content]
            ]
        ];
        
        $stmt = $this->db->prepare("
            INSERT INTO posts 
            (post_type_id, slug, title, excerpt, status, component_data, 
             author_id, created_by, updated_by, published_at, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        $publishedAt = ($mappedStatus === 'published' && $postDate) ? $postDate : null;
        
        $stmt->execute([
            $postTypeId, $slug, $title, $excerpt, $mappedStatus, 
            json_encode($componentData), $authorId, $authorId, $authorId, $publishedAt
        ]);
    }
    
    private function getOrCreatePostType($slug) {
        $stmt = $this->db->prepare("SELECT id FROM post_types WHERE slug = ?");
        $stmt->execute([$slug]);
        $result = $stmt->fetch();
        
        if ($result) {
            return $result['id'];
        }
        
        // Create post type
        $name = ucfirst($slug);
        $stmt = $this->db->prepare("
            INSERT INTO post_types 
            (name, slug, public, show_in_menu, created_by, updated_by, created_at, updated_at)
            VALUES (?, ?, 1, 1, 1, 1, NOW(), NOW())
        ");
        $stmt->execute([$name, $slug]);
        
        return $this->db->lastInsertId();
    }
    
    private function getUserIdByUsername($username) {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $result = $stmt->fetch();
        return $result ? $result['id'] : null;
    }
    
    private function slugify($text) {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9-]/', '-', $text);
        $text = preg_replace('/-+/', '-', $text);
        return trim($text, '-');
    }
}

