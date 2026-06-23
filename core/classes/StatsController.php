<?php
/**
 * Stats & Reports API Controller
 * Returns real database statistics for the admin dashboard, analytics and reports.
 */

namespace Modular\API;

class StatsController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /** GET /stats/overview — KPI cards for the dashboard overview tab. */
    public function overview() {
        $posts = $this->db->query("SELECT COUNT(*) FROM posts WHERE deleted_at IS NULL")->fetchColumn();
        $published = $this->db->query("SELECT COUNT(*) FROM posts WHERE deleted_at IS NULL AND status = 'published'")->fetchColumn();
        $drafts = (int) $posts - (int) $published;
        $postTypes = $this->db->query("SELECT COUNT(*) FROM post_types WHERE deleted_at IS NULL")->fetchColumn();
        $components = $this->db->query("SELECT COUNT(*) FROM components WHERE deleted_at IS NULL")->fetchColumn();
        $componentUsage = $this->db->query("SELECT COUNT(*) FROM component_usage")->fetchColumn();
        $media = $this->db->query("SELECT COUNT(*) FROM media WHERE deleted_at IS NULL")->fetchColumn();
        $users = $this->db->query("SELECT COUNT(*) FROM users WHERE deleted_at IS NULL")->fetchColumn();
        $forms = $this->db->query("SELECT COUNT(*) FROM forms")->fetchColumn();
        $submissions = $this->db->query("SELECT COUNT(*) FROM form_submissions")->fetchColumn();

        return [
            'content_items' => (int) $posts,
            'published' => (int) $published,
            'drafts' => $drafts,
            'post_types' => (int) $postTypes,
            'components' => (int) $components,
            'component_usage' => (int) $componentUsage,
            'media' => (int) $media,
            'users' => (int) $users,
            'forms' => (int) $forms,
            'submissions' => (int) $submissions,
        ];
    }

    /** GET /stats/analytics — all data for the analytics dashboard + full analytics page. */
    public function analytics() {
        $range = isset($_GET['range']) ? (int) $_GET['range'] : 14;
        if (!in_array($range, [7, 14, 30, 90], true)) $range = 14;

        // --- Content counts by post type ---
        $byType = $this->db->query("
            SELECT pt.name AS label, COUNT(p.id) AS count
            FROM post_types pt
            LEFT JOIN posts p ON p.post_type_id = pt.id AND p.deleted_at IS NULL
            WHERE pt.deleted_at IS NULL
            GROUP BY pt.id, pt.name ORDER BY count DESC
        ")->fetchAll(\PDO::FETCH_ASSOC);
        $byTypeLabels = array_column($byType, 'label');
        $byTypeData = array_map('intval', array_column($byType, 'count'));

        // --- Posts created over time (the "traffic" proxy: content production per day) ---
        $dailySeries = $this->db->prepare("
            SELECT DATE(created_at) AS day, COUNT(*) AS cnt
            FROM posts WHERE deleted_at IS NULL AND created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            GROUP BY DATE(created_at) ORDER BY day
        ");
        $dailySeries->execute([$range]);
        $dailyRows = $dailySeries->fetchAll(\PDO::FETCH_KEY_PAIR);

        $days = []; $dayCounts = [];
        for ($i = $range - 1; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-{$i} days"));
            $days[] = date('M j', strtotime($d));
            $dayCounts[] = isset($dailyRows[$d]) ? (int) $dailyRows[$d] : 0;
        }

        // --- Top pages: most-viewed (approximated by most recently updated published posts) ---
        $topPages = $this->db->query("
            SELECT p.title, p.slug, pt.slug AS type_slug, p.updated_at
            FROM posts p
            INNER JOIN post_types pt ON pt.id = p.post_type_id
            WHERE p.deleted_at IS NULL AND p.status = 'published'
            ORDER BY p.updated_at DESC LIMIT 10
        ")->fetchAll(\PDO::FETCH_ASSOC);
        $topPagesOut = [];
        foreach ($topPages as $tp) {
            $topPagesOut[] = ['path' => '/' . $tp['type_slug'] . '/' . $tp['slug'], 'title' => $tp['title']];
        }

        // --- Content by status ---
        $byStatus = $this->db->query("
            SELECT status, COUNT(*) AS cnt FROM posts WHERE deleted_at IS NULL GROUP BY status ORDER BY cnt DESC
        ")->fetchAll(\PDO::FETCH_ASSOC);

        // --- Recent activity log ---
        $recentActivity = $this->db->query("
            SELECT al.action, al.model_type, al.description, al.created_at,
                   COALESCE(u.username, u.email) AS user_name
            FROM activity_log al
            LEFT JOIN users u ON u.id = al.user_id
            ORDER BY al.created_at DESC LIMIT 10
        ")->fetchAll(\PDO::FETCH_ASSOC);

        // --- Authors breakdown ---
        $authors = $this->db->query("
            SELECT COALESCE(u.username, u.email, 'Unknown') AS name, COUNT(p.id) AS count
            FROM posts p
            LEFT JOIN users u ON u.id = p.author_id
            WHERE p.deleted_at IS NULL
            GROUP BY p.author_id ORDER BY count DESC LIMIT 10
        ")->fetchAll(\PDO::FETCH_ASSOC);

        // --- Content by month (last 12 months) ---
        $monthly = $this->db->query("
            SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, COUNT(*) AS cnt
            FROM posts WHERE deleted_at IS NULL AND created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY month ORDER BY month
        ")->fetchAll(\PDO::FETCH_ASSOC);
        $monthLabels = array_column($monthly, 'month');
        $monthData = array_map('intval', array_column($monthly, 'cnt'));

        // --- Form submissions over time ---
        $formDaily = $this->db->prepare("
            SELECT DATE(created_at) AS day, COUNT(*) AS cnt
            FROM form_submissions WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            GROUP BY DATE(created_at) ORDER BY day
        ");
        $formDaily->execute([$range]);
        $formDailyRows = $formDaily->fetchAll(\PDO::FETCH_KEY_PAIR);
        $formDayCounts = [];
        for ($i = $range - 1; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-{$i} days"));
            $formDayCounts[] = isset($formDailyRows[$d]) ? (int) $formDailyRows[$d] : 0;
        }

        // --- Media uploads over time ---
        $mediaDaily = $this->db->prepare("
            SELECT DATE(created_at) AS day, COUNT(*) AS cnt
            FROM media WHERE deleted_at IS NULL AND created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            GROUP BY DATE(created_at) ORDER BY day
        ");
        $mediaDaily->execute([$range]);
        $mediaDailyRows = $mediaDaily->fetchAll(\PDO::FETCH_KEY_PAIR);
        $mediaDayCounts = [];
        for ($i = $range - 1; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-{$i} days"));
            $mediaDayCounts[] = isset($mediaDailyRows[$d]) ? (int) $mediaDailyRows[$d] : 0;
        }

        // --- Media by type ---
        $mediaByType = $this->db->query("
            SELECT file_type AS label, COUNT(*) AS count
            FROM media WHERE deleted_at IS NULL
            GROUP BY file_type ORDER BY count DESC LIMIT 6
        ")->fetchAll(\PDO::FETCH_ASSOC);

        // --- Users by role ---
        $usersByRole = $this->db->query("
            SELECT role AS label, COUNT(*) AS count
            FROM users WHERE deleted_at IS NULL
            GROUP BY role ORDER BY count DESC
        ")->fetchAll(\PDO::FETCH_ASSOC);

        // --- Form conversion rates ---
        $formStats = $this->db->query("
            SELECT f.name, f.slug, COUNT(fs.id) AS submissions
            FROM forms f
            LEFT JOIN form_submissions fs ON fs.form_id = f.id
            GROUP BY f.id, f.name, f.slug
            ORDER BY submissions DESC
        ")->fetchAll(\PDO::FETCH_ASSOC);

        // --- Totals for KPIs ---
        $totalPosts = array_sum($dayCounts);
        $totalSubmissions = array_sum($formDayCounts);
        $totalMedia = array_sum($mediaDayCounts);

        // Previous period comparison
        $prevPosts = $this->db->prepare("
            SELECT COUNT(*) FROM posts WHERE deleted_at IS NULL
            AND created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            AND created_at < DATE_SUB(CURDATE(), INTERVAL ? DAY)
        ");
        $prevPosts->execute([$range * 2, $range]);
        $prevPostsCount = (int) $prevPosts->fetchColumn();
        $postsDelta = $prevPostsCount > 0 ? round(($totalPosts - $prevPostsCount) / $prevPostsCount * 100) : 0;

        $prevSubs = $this->db->prepare("
            SELECT COUNT(*) FROM form_submissions
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            AND created_at < DATE_SUB(CURDATE(), INTERVAL ? DAY)
        ");
        $prevSubs->execute([$range * 2, $range]);
        $prevSubsCount = (int) $prevSubs->fetchColumn();
        $subsDelta = $prevSubsCount > 0 ? round(($totalSubmissions - $prevSubsCount) / $prevSubsCount * 100) : 0;

        return [
            'range' => $range,
            'days' => $days,
            'kpis' => [
                ['label' => 'New content', 'value' => $totalPosts, 'delta' => $postsDelta, 'up' => $postsDelta >= 0],
                ['label' => 'Form submissions', 'value' => $totalSubmissions, 'delta' => $subsDelta, 'up' => $subsDelta >= 0],
                ['label' => 'Media uploaded', 'value' => $totalMedia, 'delta' => 0, 'up' => true],
                ['label' => 'Total content', 'value' => (int) $this->db->query("SELECT COUNT(*) FROM posts WHERE deleted_at IS NULL")->fetchColumn(), 'delta' => 0, 'up' => true],
            ],
            'daily_posts' => $dayCounts,
            'daily_submissions' => $formDayCounts,
            'daily_media' => $mediaDayCounts,
            'by_type' => ['labels' => $byTypeLabels, 'data' => $byTypeData],
            'by_status' => $byStatus,
            'top_pages' => $topPagesOut,
            'authors' => $authors,
            'monthly' => ['labels' => $monthLabels, 'data' => $monthData],
            'media_by_type' => ['labels' => array_column($mediaByType, 'label'), 'data' => array_map('intval', array_column($mediaByType, 'count'))],
            'users_by_role' => ['labels' => array_column($usersByRole, 'label'), 'data' => array_map('intval', array_column($usersByRole, 'count'))],
            'form_stats' => $formStats,
            'recent_activity' => $recentActivity,
        ];
    }

    /** GET /stats/reports — data for the reports tab. */
    public function reports() {
        $range = isset($_GET['range']) ? (int) $_GET['range'] : 30;
        if (!in_array($range, [7, 30, 90], true)) $range = 30;
        $reportType = isset($_GET['type']) ? $_GET['type'] : 'production';

        switch ($reportType) {
            case 'production':
                return $this->reportProduction($range);
            case 'cadence':
                return $this->reportCadence($range);
            case 'forms':
                return $this->reportForms($range);
            case 'authors':
                return $this->reportAuthors($range);
            case 'health':
                return $this->reportHealth();
            default:
                return $this->reportProduction($range);
        }
    }

    private function reportProduction($range) {
        $published = $this->db->prepare("
            SELECT COUNT(*) FROM posts WHERE deleted_at IS NULL AND status = 'published'
            AND published_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
        ");
        $published->execute([$range]);
        $publishedCount = (int) $published->fetchColumn();

        $weeks = max(1, round($range / 7));
        $avgPerWeek = round($publishedCount / $weeks, 1);

        $draftsPending = (int) $this->db->query("SELECT COUNT(*) FROM posts WHERE deleted_at IS NULL AND status = 'draft'")->fetchColumn();

        $rows = $this->db->prepare("
            SELECT p.title AS name, pt.name AS type,
                   COALESCE(u.username, u.email) AS author,
                   DATE_FORMAT(COALESCE(p.published_at, p.created_at), '%b %d') AS published,
                   p.status
            FROM posts p
            INNER JOIN post_types pt ON pt.id = p.post_type_id
            LEFT JOIN users u ON u.id = p.author_id
            WHERE p.deleted_at IS NULL AND p.created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            ORDER BY p.created_at DESC LIMIT 25
        ");
        $rows->execute([$range]);
        $breakdown = $rows->fetchAll(\PDO::FETCH_ASSOC);

        return [
            'report' => 'production',
            'range' => $range,
            'kpis' => [
                ['Published', (string) $publishedCount],
                ['Avg / week', (string) $avgPerWeek],
                ['Drafts pending', (string) $draftsPending],
                ['Total content', (string) $this->db->query("SELECT COUNT(*) FROM posts WHERE deleted_at IS NULL")->fetchColumn()],
            ],
            'rows' => $breakdown,
        ];
    }

    private function reportCadence($range) {
        // Publishing cadence per type
        $types = $this->db->query("
            SELECT pt.name, pt.slug,
                   COUNT(p.id) AS total,
                   COUNT(CASE WHEN p.status = 'published' THEN 1 END) AS published
            FROM post_types pt
            LEFT JOIN posts p ON p.post_type_id = pt.id AND p.deleted_at IS NULL
            WHERE pt.deleted_at IS NULL
            GROUP BY pt.id, pt.name, pt.slug ORDER BY total DESC
        ")->fetchAll(\PDO::FETCH_ASSOC);

        $lastPublished = $this->db->query("SELECT MAX(published_at) FROM posts WHERE deleted_at IS NULL AND status = 'published'")->fetchColumn();
        $daysSinceLast = $lastPublished ? (int) ceil((time() - strtotime($lastPublished)) / 86400) : null;

        // Most active type
        $mostActive = !empty($types) ? $types[0]['name'] : '-';
        $leastActive = !empty($types) ? end($types)['name'] : '-';

        $rows = $this->db->prepare("
            SELECT p.title AS name, pt.name AS type,
                   COALESCE(u.username, u.email) AS author,
                   DATE_FORMAT(COALESCE(p.published_at, p.created_at), '%b %d') AS published,
                   p.status
            FROM posts p
            INNER JOIN post_types pt ON pt.id = p.post_type_id
            LEFT JOIN users u ON u.id = p.author_id
            WHERE p.deleted_at IS NULL AND p.status = 'published'
            AND p.published_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            ORDER BY p.published_at DESC LIMIT 25
        ");
        $rows->execute([$range]);

        return [
            'report' => 'cadence',
            'range' => $range,
            'kpis' => [
                ['Days since last', $daysSinceLast !== null ? $daysSinceLast . 'd' : '-'],
                ['Most active type', $mostActive],
                ['Least active type', $leastActive],
                ['Post types', (string) count($types)],
            ],
            'rows' => $rows->fetchAll(\PDO::FETCH_ASSOC),
        ];
    }

    private function reportForms($range) {
        $formStats = $this->db->prepare("
            SELECT f.name, f.slug AS type,
                   COUNT(fs.id) AS submissions,
                   COUNT(CASE WHEN fs.is_spam = 1 THEN 1 END) AS spam
            FROM forms f
            LEFT JOIN form_submissions fs ON fs.form_id = f.id AND fs.created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            GROUP BY f.id, f.name, f.slug ORDER BY submissions DESC
        ");
        $formStats->execute([$range]);
        $forms = $formStats->fetchAll(\PDO::FETCH_ASSOC);

        $totalSubs = array_sum(array_column($forms, 'submissions'));
        $totalSpam = array_sum(array_column($forms, 'spam'));
        $topForm = !empty($forms) ? $forms[0]['name'] : '-';

        $rows = [];
        foreach ($forms as $f) {
            $rows[] = [
                'name' => $f['name'],
                'type' => 'Form',
                'author' => '-',
                'published' => '-',
                'status' => $f['submissions'] . ' submissions',
            ];
        }

        return [
            'report' => 'forms',
            'range' => $range,
            'kpis' => [
                ['Submissions', (string) $totalSubs],
                ['Top form', $topForm],
                ['Spam blocked', (string) $totalSpam],
                ['Total forms', (string) count($forms)],
            ],
            'rows' => $rows,
        ];
    }

    private function reportAuthors($range) {
        $authors = $this->db->prepare("
            SELECT COALESCE(u.username, u.email, 'Unknown') AS name,
                   COUNT(p.id) AS items,
                   COUNT(CASE WHEN p.status = 'published' THEN 1 END) AS published,
                   COUNT(CASE WHEN p.status = 'draft' THEN 1 END) AS drafts
            FROM posts p
            LEFT JOIN users u ON u.id = p.author_id
            WHERE p.deleted_at IS NULL AND p.created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            GROUP BY p.author_id ORDER BY items DESC
        ");
        $authors->execute([$range]);
        $authorRows = $authors->fetchAll(\PDO::FETCH_ASSOC);

        $topAuthor = !empty($authorRows) ? $authorRows[0]['name'] : '-';
        $topItems = !empty($authorRows) ? $authorRows[0]['items'] : 0;
        $contributors = count($authorRows);

        $rows = [];
        foreach ($authorRows as $a) {
            $rows[] = [
                'name' => $a['name'],
                'type' => 'Author',
                'author' => $a['name'],
                'published' => $a['published'] . ' published',
                'status' => $a['items'] . ' items',
            ];
        }

        return [
            'report' => 'authors',
            'range' => $range,
            'kpis' => [
                ['Top author', $topAuthor],
                ['Items', (string) $topItems],
                ['Contributors', (string) $contributors],
                ['Total content', (string) $this->db->query("SELECT COUNT(*) FROM posts WHERE deleted_at IS NULL")->fetchColumn()],
            ],
            'rows' => $rows,
        ];
    }

    private function reportHealth() {
        // Stale content (not updated in > 365 days)
        $stale = (int) $this->db->query("
            SELECT COUNT(*) FROM posts WHERE deleted_at IS NULL AND status = 'published'
            AND updated_at < DATE_SUB(CURDATE(), INTERVAL 365 DAY)
        ")->fetchColumn();

        // Drafts older than 30 days (possibly abandoned)
        $oldDrafts = (int) $this->db->query("
            SELECT COUNT(*) FROM posts WHERE deleted_at IS NULL AND status = 'draft'
            AND updated_at < DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ")->fetchColumn();

        // Posts with no excerpt (missing SEO)
        $missingSeo = (int) $this->db->query("
            SELECT COUNT(*) FROM posts WHERE deleted_at IS NULL AND status = 'published'
            AND (excerpt IS NULL OR excerpt = '')
        ")->fetchColumn();

        // Unused media
        $unusedMedia = (int) $this->db->query("
            SELECT COUNT(*) FROM media m WHERE m.deleted_at IS NULL
            AND m.id NOT IN (SELECT DISTINCT featured_image FROM posts WHERE featured_image IS NOT NULL AND deleted_at IS NULL)
        ")->fetchColumn();

        $rows = $this->db->query("
            SELECT p.title AS name, pt.name AS type,
                   COALESCE(u.username, u.email) AS author,
                   DATE_FORMAT(p.updated_at, '%b %d, %Y') AS published,
                   CASE
                     WHEN p.updated_at < DATE_SUB(CURDATE(), INTERVAL 365 DAY) THEN 'stale'
                     WHEN p.status = 'draft' AND p.updated_at < DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 'abandoned draft'
                     WHEN (p.excerpt IS NULL OR p.excerpt = '') AND p.status = 'published' THEN 'missing excerpt'
                     ELSE p.status
                   END AS status
            FROM posts p
            INNER JOIN post_types pt ON pt.id = p.post_type_id
            LEFT JOIN users u ON u.id = p.author_id
            WHERE p.deleted_at IS NULL AND (
                (p.status = 'published' AND p.updated_at < DATE_SUB(CURDATE(), INTERVAL 365 DAY))
                OR (p.status = 'draft' AND p.updated_at < DATE_SUB(CURDATE(), INTERVAL 30 DAY))
                OR (p.status = 'published' AND (p.excerpt IS NULL OR p.excerpt = ''))
            )
            ORDER BY p.updated_at ASC LIMIT 25
        ")->fetchAll(\PDO::FETCH_ASSOC);

        return [
            'report' => 'health',
            'range' => 0,
            'kpis' => [
                ['Stale (>1y)', (string) $stale],
                ['Missing excerpt', (string) $missingSeo],
                ['Old drafts (>30d)', (string) $oldDrafts],
                ['Unused media', (string) $unusedMedia],
            ],
            'rows' => $rows,
        ];
    }
}
