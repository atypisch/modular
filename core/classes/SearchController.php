<?php
/**
 * Search API Controller
 * Handles content search
 */

namespace Modular\API;

class SearchController {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function search() {
        $query = $_GET['q'] ?? '';
        $postType = $_GET['post_type'] ?? null;
        $limit = min((int)($_GET['limit'] ?? 20), 100);
        $offset = (int)($_GET['offset'] ?? 0);
        
        if (empty($query)) {
            return ['error' => 'Search query required', 'code' => 400];
        }
        
        $results = [
            'posts' => [],
            'media' => [],
            'users' => [],
            'total' => 0
        ];
        
        // Search posts
        $sql = "
            SELECT p.*, pt.name as post_type_name, pt.slug as post_type_slug
            FROM posts p
            INNER JOIN post_types pt ON p.post_type_id = pt.id
            WHERE p.deleted_at IS NULL 
            AND p.status = 'published'
            AND (
                p.title LIKE :query 
                OR p.excerpt LIKE :query
                OR p.component_data LIKE :query
            )
        ";
        
        $params = [':query' => '%' . $query . '%'];
        
        if ($postType) {
            $sql .= " AND pt.slug = :post_type";
            $params[':post_type'] = $postType;
        }
        
        $sql .= " ORDER BY p.published_at DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        
        $posts = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        foreach ($posts as &$post) {
            $post['component_data'] = json_decode($post['component_data'], true);
            $post['meta_data'] = json_decode($post['meta_data'], true);
        }
        
        $results['posts'] = $posts;
        $results['total'] = count($posts);
        
        // Search media
        $stmt = $this->db->prepare("
            SELECT * FROM media 
            WHERE deleted_at IS NULL 
            AND (title LIKE :query OR filename LIKE :query OR alt_text LIKE :query)
            LIMIT :limit
        ");
        $stmt->bindValue(':query', '%' . $query . '%');
        $stmt->bindValue(':limit', 10, \PDO::PARAM_INT);
        $stmt->execute();
        $results['media'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        return $results;
    }
    
    public function autocomplete() {
        $query = $_GET['q'] ?? '';
        $type = $_GET['type'] ?? 'posts';
        
        if (empty($query) || strlen($query) < 2) {
            return [];
        }
        
        $results = [];
        
        if ($type === 'posts') {
            $stmt = $this->db->prepare("
                SELECT id, title, slug 
                FROM posts 
                WHERE deleted_at IS NULL 
                AND status = 'published'
                AND title LIKE :query 
                LIMIT 10
            ");
            $stmt->bindValue(':query', '%' . $query . '%');
            $stmt->execute();
            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } elseif ($type === 'media') {
            $stmt = $this->db->prepare("
                SELECT id, title, filename 
                FROM media 
                WHERE deleted_at IS NULL 
                AND (title LIKE :query OR filename LIKE :query)
                LIMIT 10
            ");
            $stmt->bindValue(':query', '%' . $query . '%');
            $stmt->execute();
            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }
        
        return $results;
    }
}


