-- =============================================================================
-- Modular CMS + Simuze Community – full schema including Simuze (phpMyAdmin)
--
-- Use this when you want Modular core AND Simuze Community (music, groups,
-- messages, activity, etc.). Fresh install: run entire file.
-- Existing install: run only sections 4–7 (genre, location, track/playlist
--   schema, Simuze tables) or section 7 only to add Simuze Community tables.
-- =============================================================================

-- -----------------------------------------------------------------------------
-- 1. Core tables
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(255) DEFAULT NULL,
    last_name VARCHAR(255) DEFAULT NULL,
    role VARCHAR(50) NOT NULL DEFAULT 'user',
    avatar VARCHAR(255) DEFAULT NULL,
    bio TEXT,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    email_verified TINYINT(1) NOT NULL DEFAULT 0,
    email_verification_token VARCHAR(255) DEFAULT NULL,
    reset_password_token VARCHAR(255) DEFAULT NULL,
    reset_password_expires DATETIME DEFAULT NULL,
    two_factor_secret VARCHAR(255) DEFAULT NULL,
    two_factor_enabled TINYINT(1) NOT NULL DEFAULT 0,
    last_login DATETIME DEFAULT NULL,
    last_ip VARCHAR(45) DEFAULT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    deleted_at DATETIME DEFAULT NULL,
    INDEX idx_email (email),
    INDEX idx_status (status),
    INDEX idx_deleted (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    display_name VARCHAR(255) NOT NULL,
    description TEXT,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS capabilities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS role_capabilities (
    role_id INT NOT NULL,
    capability_id INT NOT NULL,
    PRIMARY KEY (role_id, capability_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (capability_id) REFERENCES capabilities(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_roles (
    user_id INT NOT NULL,
    role_id INT NOT NULL,
    PRIMARY KEY (user_id, role_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS post_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    icon VARCHAR(50) DEFAULT 'file',
    supports TEXT NOT NULL,
    public TINYINT(1) NOT NULL DEFAULT 1,
    show_in_menu TINYINT(1) NOT NULL DEFAULT 1,
    menu_position INT DEFAULT NULL,
    menu_icon VARCHAR(50) DEFAULT NULL,
    hierarchical TINYINT(1) NOT NULL DEFAULT 0,
    has_archive TINYINT(1) NOT NULL DEFAULT 1,
    exclude_from_search TINYINT(1) NOT NULL DEFAULT 0,
    publicly_queryable TINYINT(1) NOT NULL DEFAULT 1,
    show_in_rest TINYINT(1) NOT NULL DEFAULT 1,
    rest_base VARCHAR(50) DEFAULT NULL,
    capability_type VARCHAR(50) NOT NULL DEFAULT 'post',
    settings JSON,
    mode VARCHAR(20) NOT NULL DEFAULT 'fixed',
    schema JSON NULL,
    created_by INT NOT NULL,
    updated_by INT NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    deleted_at DATETIME DEFAULT NULL,
    INDEX idx_slug (slug),
    INDEX idx_deleted (deleted_at),
    INDEX idx_created_by (created_by),
    INDEX idx_updated_by (updated_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_type_id INT NOT NULL,
    parent_id INT DEFAULT NULL,
    slug VARCHAR(255) NOT NULL,
    title VARCHAR(255) NOT NULL,
    excerpt TEXT,
    status VARCHAR(20) NOT NULL DEFAULT 'draft',
    password VARCHAR(255) DEFAULT NULL,
    comment_status VARCHAR(20) NOT NULL DEFAULT 'open',
    ping_status VARCHAR(20) NOT NULL DEFAULT 'open',
    visibility VARCHAR(20) NOT NULL DEFAULT 'public',
    featured_image INT DEFAULT NULL,
    template VARCHAR(255) DEFAULT NULL,
    `order` INT NOT NULL DEFAULT 0,
    component_data JSON,
    meta_data JSON,
    data JSON NULL,
    layout JSON NULL,
    author_id INT NOT NULL,
    created_by INT NOT NULL,
    updated_by INT NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    published_at DATETIME DEFAULT NULL,
    deleted_at DATETIME DEFAULT NULL,
    INDEX idx_post_type (post_type_id),
    INDEX idx_status (status),
    INDEX idx_slug (slug),
    INDEX idx_published (published_at),
    INDEX idx_deleted (deleted_at),
    INDEX idx_author (author_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS component_definitions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_type_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    type VARCHAR(50) NOT NULL,
    schema JSON NOT NULL,
    is_fixed TINYINT(1) NOT NULL DEFAULT 1,
    is_flexible TINYINT(1) NOT NULL DEFAULT 0,
    `order` INT NOT NULL DEFAULT 0,
    group_name VARCHAR(255) DEFAULT NULL,
    access_control JSON,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_post_type (post_type_id),
    INDEX idx_type (type),
    UNIQUE KEY unique_slug (post_type_id, slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS media (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(100) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    file_size INT NOT NULL,
    width INT DEFAULT NULL,
    height INT DEFAULT NULL,
    variants JSON,
    metadata JSON,
    title VARCHAR(255) DEFAULT NULL,
    caption TEXT,
    description TEXT,
    alt_text VARCHAR(255) DEFAULT NULL,
    credits VARCHAR(255) DEFAULT NULL,
    folder_path VARCHAR(500) DEFAULT NULL,
    tags JSON,
    uploaded_by INT NOT NULL,
    created_by INT NOT NULL,
    updated_by INT NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    archived_at DATETIME DEFAULT NULL,
    deleted_at DATETIME DEFAULT NULL,
    INDEX idx_file_type (file_type),
    INDEX idx_uploaded_by (uploaded_by),
    INDEX idx_created_by (created_by),
    INDEX idx_updated_by (updated_by),
    INDEX idx_deleted (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS relationships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    left_id INT NOT NULL,
    right_id INT NOT NULL,
    relation_type VARCHAR(50) NOT NULL,
    left_type VARCHAR(50) NOT NULL,
    right_type VARCHAR(50) NOT NULL,
    `order` INT DEFAULT 0,
    meta JSON,
    created_at DATETIME NOT NULL,
    INDEX idx_left (left_id, left_type),
    INDEX idx_right (right_id, right_type),
    INDEX idx_type (relation_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS taxonomies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    hierarchical TINYINT(1) NOT NULL DEFAULT 0,
    public TINYINT(1) NOT NULL DEFAULT 1,
    show_in_menu TINYINT(1) NOT NULL DEFAULT 1,
    show_in_rest TINYINT(1) NOT NULL DEFAULT 1,
    rest_base VARCHAR(50) DEFAULT NULL,
    created_by INT NOT NULL,
    updated_by INT NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    deleted_at DATETIME DEFAULT NULL,
    INDEX idx_slug (slug),
    INDEX idx_created_by (created_by),
    INDEX idx_updated_by (updated_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS terms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    taxonomy_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    description TEXT,
    parent_id INT DEFAULT NULL,
    `order` INT NOT NULL DEFAULT 0,
    created_by INT NOT NULL,
    updated_by INT NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    deleted_at DATETIME DEFAULT NULL,
    INDEX idx_taxonomy (taxonomy_id),
    INDEX idx_parent (parent_id),
    INDEX idx_slug (slug),
    INDEX idx_created_by (created_by),
    INDEX idx_updated_by (updated_by),
    UNIQUE KEY unique_taxonomy_slug (taxonomy_id, slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS post_terms (
    post_id INT NOT NULL,
    term_id INT NOT NULL,
    PRIMARY KEY (post_id, term_id),
    INDEX idx_post_id (post_id),
    INDEX idx_term_id (term_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS post_type_taxonomies (
    post_type_id INT NOT NULL,
    taxonomy_id INT NOT NULL,
    PRIMARY KEY (post_type_id, taxonomy_id),
    INDEX idx_post_type_id (post_type_id),
    INDEX idx_taxonomy_id (taxonomy_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS forms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    fields JSON NOT NULL,
    settings JSON,
    spam_protection JSON,
    created_by INT NOT NULL,
    updated_by INT NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_slug (slug),
    INDEX idx_created_by (created_by),
    INDEX idx_updated_by (updated_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS form_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    form_id INT NOT NULL,
    data JSON NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    spam_score DECIMAL(3,2) DEFAULT 0,
    is_spam TINYINT(1) NOT NULL DEFAULT 0,
    status VARCHAR(20) NOT NULL DEFAULT 'new',
    created_at DATETIME NOT NULL,
    INDEX idx_form (form_id),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS actions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    trigger_type VARCHAR(50) NOT NULL,
    trigger_config JSON,
    action_type VARCHAR(50) NOT NULL,
    action_config JSON NOT NULL,
    enabled TINYINT(1) NOT NULL DEFAULT 1,
    priority INT NOT NULL DEFAULT 10,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_trigger (trigger_type),
    INDEX idx_enabled (enabled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS action_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    action_id INT NOT NULL,
    payload JSON NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    attempts INT NOT NULL DEFAULT 0,
    max_attempts INT NOT NULL DEFAULT 3,
    error_message TEXT,
    scheduled_at DATETIME NOT NULL,
    executed_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_action_id (action_id),
    INDEX idx_status (status),
    INDEX idx_scheduled (scheduled_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    option_name VARCHAR(255) NOT NULL UNIQUE,
    option_value LONGTEXT NOT NULL,
    autoload TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_autoload (autoload)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS post_revisions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    component_data JSON,
    meta_data JSON,
    created_by INT NOT NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_post (post_id),
    INDEX idx_created (created_at),
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    model_type VARCHAR(255) NOT NULL,
    model_id INT NOT NULL,
    description TEXT NOT NULL,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(255) DEFAULT NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_user (user_id),
    INDEX idx_model (model_type, model_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS schema_migrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    version VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    executed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS modules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    slug VARCHAR(255) NOT NULL UNIQUE,
    version VARCHAR(50) NOT NULL DEFAULT '1.0.0',
    description TEXT,
    author VARCHAR(255),
    author_url VARCHAR(500),
    module_url VARCHAR(500),
    text_domain VARCHAR(100),
    requires_php VARCHAR(20),
    requires_modular VARCHAR(20),
    active TINYINT(1) NOT NULL DEFAULT 0,
    settings JSON,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_active (active),
    INDEX idx_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 2. Default roles and capabilities
-- -----------------------------------------------------------------------------

INSERT IGNORE INTO roles (name, display_name, description, created_at, updated_at) VALUES
('admin', 'Administrator', 'Full system access', NOW(), NOW()),
('editor', 'Editor', 'Can create and edit content', NOW(), NOW()),
('author', 'Author', 'Can create and edit own content', NOW(), NOW()),
('user', 'User', 'Basic user access', NOW(), NOW());

INSERT IGNORE INTO capabilities (name) VALUES
('post:create'), ('post:edit:own'), ('post:edit:any'), ('post:delete:own'), ('post:delete:any'),
('post:publish'), ('media:upload'), ('media:edit'), ('media:delete'),
('user:create'), ('user:edit'), ('user:delete'), ('user:promote'),
('plugin:activate'), ('plugin:deactivate'), ('settings:edit'), ('theme:edit');

INSERT IGNORE INTO role_capabilities (role_id, capability_id)
SELECT r.id, c.id FROM roles r CROSS JOIN capabilities c WHERE r.name = 'admin';

-- -----------------------------------------------------------------------------
-- 3. Post types (core + Simuze)
-- -----------------------------------------------------------------------------

SET @supports = '[\"title\",\"editor\"]';

INSERT IGNORE INTO post_types (name, slug, description, public, show_in_menu, supports, created_by, updated_by, created_at, updated_at) VALUES
('Post', 'post', 'Blog post', 1, 1, @supports, 1, 1, NOW(), NOW()),
('Page', 'page', 'Static page', 1, 1, @supports, 1, 1, NOW(), NOW()),
('Album', 'album', 'Music album', 1, 1, @supports, 1, 1, NOW(), NOW()),
('Track', 'track', 'Music track', 1, 1, @supports, 1, 1, NOW(), NOW()),
('Playlist', 'playlist', 'Playlist', 1, 1, @supports, 1, 1, NOW(), NOW()),
('Event', 'event', 'Event', 1, 1, @supports, 1, 1, NOW(), NOW());

-- -----------------------------------------------------------------------------
-- 4. Genre taxonomy + link to track
-- -----------------------------------------------------------------------------

INSERT IGNORE INTO taxonomies (name, slug, description, hierarchical, public, show_in_menu, show_in_rest, created_by, updated_by, created_at, updated_at) VALUES
('Genre', 'genre', 'Music genre for tracks', 0, 1, 1, 1, 1, 1, NOW(), NOW());

INSERT IGNORE INTO post_type_taxonomies (post_type_id, taxonomy_id)
SELECT pt.id, t.id FROM post_types pt CROSS JOIN taxonomies t WHERE pt.slug = 'track' AND t.slug = 'genre';

-- -----------------------------------------------------------------------------
-- 5. Location taxonomies (country, province, city)
-- -----------------------------------------------------------------------------

INSERT IGNORE INTO taxonomies (name, slug, description, hierarchical, public, show_in_menu, show_in_rest, created_by, updated_by, created_at, updated_at) VALUES
('Country', 'country', 'Country (location)', 0, 1, 1, 1, 1, 1, NOW(), NOW()),
('Province', 'province', 'Province / region', 0, 1, 1, 1, 1, 1, NOW(), NOW()),
('City', 'city', 'City (location)', 0, 1, 1, 1, 1, 1, NOW(), NOW());

-- -----------------------------------------------------------------------------
-- 6. Track and playlist relation fields (schema JSON)
-- -----------------------------------------------------------------------------

UPDATE post_types SET schema = '{\"fields\":[{\"type\":\"relationship\",\"name\":\"album\",\"key\":\"album\",\"label\":\"Album\",\"instructions\":\"Album this track belongs to.\",\"required\":false,\"post_type\":[\"album\"],\"return_format\":\"id\",\"max\":1}]}', updated_at = NOW() WHERE slug = 'track';

UPDATE post_types SET schema = '{\"fields\":[{\"type\":\"relationship\",\"name\":\"tracks\",\"key\":\"tracks\",\"label\":\"Tracks\",\"instructions\":\"Tracks in this playlist.\",\"required\":false,\"post_type\":[\"track\"],\"return_format\":\"id\"}]}', updated_at = NOW() WHERE slug = 'playlist';

-- -----------------------------------------------------------------------------
-- 7. Simuze Community module tables
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS simuze_groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    description TEXT,
    group_type VARCHAR(100) NOT NULL DEFAULT 'artist',
    owner_id INT NOT NULL,
    unique_handle VARCHAR(255) DEFAULT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    deleted_at DATETIME DEFAULT NULL,
    UNIQUE KEY uq_slug (slug),
    KEY idx_owner (owner_id),
    KEY idx_type (group_type),
    KEY idx_deleted (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS simuze_group_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL,
    user_id INT NOT NULL,
    role VARCHAR(50) NOT NULL DEFAULT 'member',
    created_at DATETIME NOT NULL,
    UNIQUE KEY uq_group_user (group_id, user_id),
    KEY idx_user (user_id),
    FOREIGN KEY (group_id) REFERENCES simuze_groups(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS simuze_group_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    icon VARCHAR(100) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME DEFAULT NULL,
    UNIQUE KEY uq_slug (slug),
    KEY idx_deleted (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS simuze_conversations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL DEFAULT 'direct',
    title VARCHAR(255) DEFAULT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    KEY idx_type (type),
    KEY idx_updated (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS simuze_conversation_participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    user_id INT NOT NULL,
    role VARCHAR(50) NOT NULL DEFAULT 'member',
    joined_at DATETIME NOT NULL,
    last_read_at DATETIME DEFAULT NULL,
    UNIQUE KEY uq_conv_user (conversation_id, user_id),
    KEY idx_user (user_id),
    FOREIGN KEY (conversation_id) REFERENCES simuze_conversations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS simuze_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    user_id INT NOT NULL,
    body TEXT NOT NULL,
    created_at DATETIME NOT NULL,
    edited_at DATETIME DEFAULT NULL,
    KEY idx_conversation (conversation_id),
    KEY idx_created (created_at),
    FOREIGN KEY (conversation_id) REFERENCES simuze_conversations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS simuze_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reviewer_id INT NOT NULL,
    content_type VARCHAR(50) NOT NULL,
    content_id INT UNSIGNED NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    notes TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    KEY idx_reviewer (reviewer_id),
    KEY idx_content (content_type, content_id),
    KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS simuze_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reporter_id INT NOT NULL,
    content_type VARCHAR(50) NOT NULL,
    content_id INT UNSIGNED NOT NULL,
    report_type VARCHAR(50) NOT NULL,
    comment TEXT DEFAULT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    reviewed_by INT UNSIGNED DEFAULT NULL,
    reviewed_at DATETIME DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    KEY idx_reporter (reporter_id),
    KEY idx_content (content_type, content_id),
    KEY idx_status (status),
    KEY idx_reviewed_by (reviewed_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS simuze_activity (
    id INT AUTO_INCREMENT PRIMARY KEY,
    author_type VARCHAR(20) NOT NULL DEFAULT 'user',
    author_id INT UNSIGNED NOT NULL,
    content TEXT NOT NULL,
    extra TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL,
    KEY idx_author (author_type, author_id),
    KEY idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- End of full_schema_simuze.sql
-- =============================================================================
