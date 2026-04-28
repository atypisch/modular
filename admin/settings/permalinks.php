<?php
/**
 * Permalink Settings
 * Configure per-post-type URL structure and regenerate .htaccess
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!defined('ADMIN_PATH')) {
    require_once dirname(__DIR__) . '/config/config.php';
}
require_once dirname(__DIR__) . '/bootstrap.php';
require_once CORE_PATH . '/functions/auth.php';
require_once CORE_PATH . '/functions/permalinks.php';

if (!is_logged_in()) {
    header('Location: ' . ADMIN_URL . '/auth/login.php');
    exit;
}

$page_title     = 'Permalink Settings';
$settings_active = true;
$db = $GLOBALS['db'];

// Load all post types
$post_types = [];
try {
    $stmt = $db->query("SELECT slug, name FROM post_types WHERE deleted_at IS NULL ORDER BY name ASC");
    $post_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Permalink settings: ' . $e->getMessage());
}

// Load primary taxonomy per post type (for {category} preview)
$type_taxonomies = [];
try {
    $stmt = $db->query("
        SELECT pt.slug AS post_type_slug, tx.slug AS taxonomy_slug
        FROM post_type_taxonomies ptt
        INNER JOIN post_types pt ON ptt.post_type_id = pt.id
        INNER JOIN taxonomies tx ON ptt.taxonomy_id = tx.id
        WHERE pt.deleted_at IS NULL
        GROUP BY pt.slug, tx.slug
        ORDER BY pt.slug, tx.id
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (!isset($type_taxonomies[$row['post_type_slug']])) {
            $type_taxonomies[$row['post_type_slug']] = $row['taxonomy_slug'];
        }
    }
} catch (PDOException $e) {
    // table may not exist in all installs — silent fail
}

$current_structures = get_permalink_structures();
$message = $message_type = '';

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $message      = 'Invalid request.';
        $message_type = 'error';
    } else {
        $structures = [];
        foreach ($post_types as $pt) {
            $slug   = $pt['slug'];
            $preset = $_POST['preset_' . $slug] ?? 'slug_only';
            $custom = trim($_POST['custom_' . $slug] ?? '');

            if ($preset === 'custom' && $custom !== '') {
                // Sanitise: only allow letters, digits, hyphens, underscores, slashes, and tokens
                $custom = preg_replace('#[^a-z0-9/_{}:-]#i', '', $custom);
                // Ensure it starts with /
                $structures[$slug] = '/' . ltrim($custom, '/');
            } else {
                $structures[$slug] = preset_to_structure($slug, $preset, $type_taxonomies);
            }
        }

        if (save_permalink_structures($structures)) {
            $message      = 'Permalink settings saved. .htaccess updated.';
            $message_type = 'success';
            $current_structures = $structures;
        } else {
            $message      = 'Settings saved but .htaccess could not be written. Check file permissions.';
            $message_type = 'warning';
        }
    }
}

/**
 * Map a preset name to a structure string.
 */
function preset_to_structure(string $post_type, string $preset, array $type_taxonomies): string {
    switch ($preset) {
        case 'type_slug':   return '/{post_type}/{slug}';
        case 'cat_slug':    return '/{category}/{slug}';
        case 'year_slug':   return '/{year}/{slug}';
        default:            return '/{slug}';
    }
}

/**
 * Detect which preset a stored structure matches, or 'custom'.
 */
function structure_to_preset(string $structure): string {
    switch ($structure) {
        case '/{slug}':             return 'slug_only';
        case '/{post_type}/{slug}': return 'type_slug';
        case '/{category}/{slug}':  return 'cat_slug';
        case '/{year}/{slug}':      return 'year_slug';
        default:                    return 'custom';
    }
}

/**
 * Build a human-readable preview URL for a structure.
 */
function structure_preview(string $post_type, string $structure, array $type_taxonomies): string {
    $base     = defined('SITE_URL') ? rtrim(SITE_URL, '/') : 'https://example.com';
    $category = $type_taxonomies[$post_type] ?? 'category';
    $preview  = str_replace(
        ['{slug}', '{post_type}', '{category}', '{year}'],
        ['sample-' . $post_type, $post_type, $category, date('Y')],
        ltrim($structure, '/')
    );
    return $base . '/' . $preview;
}

require_once ADMIN_PATH . '/templates/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Permalink Settings</h1>
        <p>Configure the URL structure for each post type. Changes are saved to the database and applied immediately.</p>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo htmlspecialchars($message_type); ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($post_types)): ?>
        <div class="card"><div class="card-body"><p>No post types found.</p></div></div>
    <?php else: ?>

    <form method="POST">
        <?php echo csrf_field(); ?>

        <div class="card">
            <div class="card-header"><h2>Post Type URL Structures</h2></div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th style="width:140px">Post type</th>
                            <th>Structure</th>
                            <th>Preview</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($post_types as $pt):
                        $slug      = $pt['slug'];
                        $label     = $pt['name'];
                        $stored    = $current_structures[$slug] ?? '/{slug}';
                        $preset    = structure_to_preset($stored);
                        $hasTax    = isset($type_taxonomies[$slug]);
                        $previewUrl = structure_preview($slug, $stored, $type_taxonomies);
                    ?>
                        <tr>
                            <td class="align-middle"><strong><?php echo htmlspecialchars($label); ?></strong></td>
                            <td>
                                <div class="permalink-options" data-slug="<?php echo htmlspecialchars($slug); ?>">

                                    <label class="permalink-option">
                                        <input type="radio" name="preset_<?php echo $slug; ?>" value="slug_only"
                                               <?php echo $preset === 'slug_only' ? 'checked' : ''; ?>>
                                        <code>/{slug}</code>
                                        <span class="text-muted small ml-1">— plain slug</span>
                                    </label>

                                    <label class="permalink-option">
                                        <input type="radio" name="preset_<?php echo $slug; ?>" value="type_slug"
                                               <?php echo $preset === 'type_slug' ? 'checked' : ''; ?>>
                                        <code>/<?php echo htmlspecialchars($slug); ?>/{slug}</code>
                                        <span class="text-muted small ml-1">— post type prefix</span>
                                    </label>

                                    <?php if ($hasTax): ?>
                                    <label class="permalink-option">
                                        <input type="radio" name="preset_<?php echo $slug; ?>" value="cat_slug"
                                               <?php echo $preset === 'cat_slug' ? 'checked' : ''; ?>>
                                        <code>/{<?php echo htmlspecialchars($type_taxonomies[$slug]); ?>}/{slug}</code>
                                        <span class="text-muted small ml-1">— taxonomy + slug</span>
                                    </label>
                                    <?php endif; ?>

                                    <label class="permalink-option">
                                        <input type="radio" name="preset_<?php echo $slug; ?>" value="year_slug"
                                               <?php echo $preset === 'year_slug' ? 'checked' : ''; ?>>
                                        <code>/{year}/{slug}</code>
                                        <span class="text-muted small ml-1">— year prefix</span>
                                    </label>

                                    <label class="permalink-option">
                                        <input type="radio" name="preset_<?php echo $slug; ?>" value="custom"
                                               <?php echo $preset === 'custom' ? 'checked' : ''; ?>>
                                        Custom:
                                        <input type="text" name="custom_<?php echo $slug; ?>"
                                               class="form-control form-control-sm d-inline-block permalink-custom-input"
                                               style="width:220px; vertical-align:middle;"
                                               placeholder="/{slug}"
                                               value="<?php echo htmlspecialchars($preset === 'custom' ? $stored : ''); ?>"
                                               onfocus="this.closest('.permalink-options').querySelector('[value=custom]').checked=true">
                                        <span class="text-muted small ml-1">tokens: {slug} {post_type} {category} {year}</span>
                                    </label>
                                </div>
                            </td>
                            <td class="align-middle">
                                <code class="permalink-preview text-muted small" data-slug="<?php echo htmlspecialchars($slug); ?>">
                                    <?php echo htmlspecialchars($previewUrl); ?>
                                </code>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header"><h2>Current .htaccess</h2></div>
            <div class="card-body">
                <p class="text-muted small">This block is automatically written to <code><?php echo htmlspecialchars(defined('MODULAR_PATH') ? MODULAR_PATH . '/.htaccess' : '.htaccess'); ?></code> when you save.</p>
                <pre class="bg-light p-3" style="font-size:0.82rem"><?php
                    $basePath = defined('BASE_PATH') ? BASE_PATH : '/modular';
                    echo htmlspecialchars(build_htaccess($basePath));
                ?></pre>
            </div>
        </div>

        <div class="form-actions mt-3">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Save &amp; Update .htaccess
            </button>
        </div>
    </form>
    <?php endif; ?>
</div>

<style>
.permalink-options { display: flex; flex-direction: column; gap: 6px; }
.permalink-option  { display: flex; align-items: center; gap: 6px; margin: 0; cursor: pointer; }
.permalink-option input[type=radio] { margin: 0; flex-shrink: 0; }
</style>

<?php require_once ADMIN_PATH . '/templates/footer.php'; ?>
