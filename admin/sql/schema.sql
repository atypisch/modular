-- =============================================================================
-- Modular CMS + Simuze Community – single schema (phpMyAdmin-safe)
--
-- Auth, subscriptions and payments run via external SIMEZU; no internal
-- payment/subscription/payout tables. Use IF NOT EXISTS – idempotent.
-- =============================================================================

-- -----------------------------------------------------------------------------
-- 1. Core tables
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(255) NOT NULL UNIQUE,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `first_name` VARCHAR(255) DEFAULT NULL,
    `last_name` VARCHAR(255) DEFAULT NULL,
    `role` VARCHAR(50) NOT NULL DEFAULT 'user',
    `avatar` VARCHAR(255) DEFAULT NULL,
    `bio` TEXT,
    `status` VARCHAR(20) NOT NULL DEFAULT 'active',
    `email_verified` TINYINT(1) NOT NULL DEFAULT 0,
    `email_verification_token` VARCHAR(255) DEFAULT NULL,
    `reset_password_token` VARCHAR(255) DEFAULT NULL,
    `reset_password_expires` DATETIME DEFAULT NULL,
    `two_factor_secret` VARCHAR(255) DEFAULT NULL,
    `two_factor_enabled` TINYINT(1) NOT NULL DEFAULT 0,
    `last_login` DATETIME DEFAULT NULL,
    `last_ip` VARCHAR(45) DEFAULT NULL,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    `deleted_at` DATETIME DEFAULT NULL,
    INDEX `idx_email` (`email`),
    INDEX `idx_status` (`status`),
    INDEX `idx_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `roles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL UNIQUE,
    `display_name` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `capabilities` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `description` TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `role_capabilities` (
    `role_id` INT NOT NULL,
    `capability_id` INT NOT NULL,
    PRIMARY KEY (`role_id`, `capability_id`),
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`capability_id`) REFERENCES `capabilities`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_roles` (
    `user_id` INT NOT NULL,
    `role_id` INT NOT NULL,
    PRIMARY KEY (`user_id`, `role_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `post_types` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL UNIQUE,
    `description` TEXT,
    `icon` VARCHAR(50) DEFAULT 'file',
    `supports` TEXT NOT NULL,
    `public` TINYINT(1) NOT NULL DEFAULT 1,
    `show_in_menu` TINYINT(1) NOT NULL DEFAULT 1,
    `menu_position` INT DEFAULT NULL,
    `menu_icon` VARCHAR(50) DEFAULT NULL,
    `hierarchical` TINYINT(1) NOT NULL DEFAULT 0,
    `has_archive` TINYINT(1) NOT NULL DEFAULT 1,
    `exclude_from_search` TINYINT(1) NOT NULL DEFAULT 0,
    `publicly_queryable` TINYINT(1) NOT NULL DEFAULT 1,
    `show_in_rest` TINYINT(1) NOT NULL DEFAULT 1,
    `rest_base` VARCHAR(50) DEFAULT NULL,
    `capability_type` VARCHAR(50) NOT NULL DEFAULT 'post',
    `settings` JSON,
    `mode` VARCHAR(20) NOT NULL DEFAULT 'fixed',
    `schema` JSON NULL,
    `created_by` INT NOT NULL,
    `updated_by` INT NOT NULL,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    `deleted_at` DATETIME DEFAULT NULL,
    INDEX `idx_slug` (`slug`),
    INDEX `idx_deleted` (`deleted_at`),
    INDEX `idx_created_by` (`created_by`),
    INDEX `idx_updated_by` (`updated_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `posts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `post_type_id` INT NOT NULL,
    `parent_id` INT DEFAULT NULL,
    `slug` VARCHAR(255) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `excerpt` TEXT,
    `status` VARCHAR(20) NOT NULL DEFAULT 'draft',
    `password` VARCHAR(255) DEFAULT NULL,
    `comment_status` VARCHAR(20) NOT NULL DEFAULT 'open',
    `ping_status` VARCHAR(20) NOT NULL DEFAULT 'open',
    `visibility` VARCHAR(20) NOT NULL DEFAULT 'public',
    `featured_image` INT DEFAULT NULL,
    `template` VARCHAR(255) DEFAULT NULL,
    `order` INT NOT NULL DEFAULT 0,
    `component_data` JSON,
    `meta_data` JSON,
    `data` JSON NULL,
    `layout` JSON NULL,
    `author_id` INT NOT NULL,
    `created_by` INT NOT NULL,
    `updated_by` INT NOT NULL,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    `published_at` DATETIME DEFAULT NULL,
    `deleted_at` DATETIME DEFAULT NULL,
    INDEX `idx_post_type` (`post_type_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_slug` (`slug`),
    INDEX `idx_published` (`published_at`),
    INDEX `idx_deleted` (`deleted_at`),
    INDEX `idx_author` (`author_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `component_definitions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `post_type_id` INT NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL,
    `type` VARCHAR(50) NOT NULL,
    `schema` JSON NOT NULL,
    `is_fixed` TINYINT(1) NOT NULL DEFAULT 1,
    `is_flexible` TINYINT(1) NOT NULL DEFAULT 0,
    `order` INT NOT NULL DEFAULT 0,
    `group_name` VARCHAR(255) DEFAULT NULL,
    `access_control` JSON,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    INDEX `idx_post_type` (`post_type_id`),
    INDEX `idx_type` (`type`),
    UNIQUE KEY `unique_slug` (`post_type_id`, `slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `media` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `filename` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(500) NOT NULL,
    `file_type` VARCHAR(100) NOT NULL,
    `mime_type` VARCHAR(100) NOT NULL,
    `file_size` INT NOT NULL,
    `width` INT DEFAULT NULL,
    `height` INT DEFAULT NULL,
    `variants` JSON,
    `metadata` JSON,
    `title` VARCHAR(255) DEFAULT NULL,
    `caption` TEXT,
    `description` TEXT,
    `alt_text` VARCHAR(255) DEFAULT NULL,
    `credits` VARCHAR(255) DEFAULT NULL,
    `folder_path` VARCHAR(500) DEFAULT NULL,
    `tags` JSON,
    `uploaded_by` INT NOT NULL,
    `created_by` INT NOT NULL,
    `updated_by` INT NOT NULL,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    `archived_at` DATETIME DEFAULT NULL,
    `deleted_at` DATETIME DEFAULT NULL,
    INDEX `idx_file_type` (`file_type`),
    INDEX `idx_uploaded_by` (`uploaded_by`),
    INDEX `idx_created_by` (`created_by`),
    INDEX `idx_updated_by` (`updated_by`),
    INDEX `idx_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `relationships` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `left_id` INT NOT NULL,
    `right_id` INT NOT NULL,
    `relation_type` VARCHAR(50) NOT NULL,
    `left_type` VARCHAR(50) NOT NULL,
    `right_type` VARCHAR(50) NOT NULL,
    `order` INT DEFAULT 0,
    `meta` JSON,
    `created_at` DATETIME NOT NULL,
    INDEX `idx_left` (`left_id`, `left_type`),
    INDEX `idx_right` (`right_id`, `right_type`),
    INDEX `idx_type` (`relation_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `taxonomies` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL UNIQUE,
    `description` TEXT,
    `hierarchical` TINYINT(1) NOT NULL DEFAULT 0,
    `public` TINYINT(1) NOT NULL DEFAULT 1,
    `show_in_menu` TINYINT(1) NOT NULL DEFAULT 1,
    `show_in_rest` TINYINT(1) NOT NULL DEFAULT 1,
    `rest_base` VARCHAR(50) DEFAULT NULL,
    `created_by` INT NOT NULL,
    `updated_by` INT NOT NULL,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    `deleted_at` DATETIME DEFAULT NULL,
    INDEX `idx_slug` (`slug`),
    INDEX `idx_created_by` (`created_by`),
    INDEX `idx_updated_by` (`updated_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `terms` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `taxonomy_id` INT NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `parent_id` INT DEFAULT NULL,
    `order` INT NOT NULL DEFAULT 0,
    `created_by` INT NOT NULL,
    `updated_by` INT NOT NULL,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    `deleted_at` DATETIME DEFAULT NULL,
    INDEX `idx_taxonomy` (`taxonomy_id`),
    INDEX `idx_parent` (`parent_id`),
    INDEX `idx_slug` (`slug`),
    INDEX `idx_created_by` (`created_by`),
    INDEX `idx_updated_by` (`updated_by`),
    UNIQUE KEY `unique_taxonomy_slug` (`taxonomy_id`, `slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `post_terms` (
    `post_id` INT NOT NULL,
    `term_id` INT NOT NULL,
    PRIMARY KEY (`post_id`, `term_id`),
    INDEX `idx_post_id` (`post_id`),
    INDEX `idx_term_id` (`term_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `post_type_taxonomies` (
    `post_type_id` INT NOT NULL,
    `taxonomy_id` INT NOT NULL,
    PRIMARY KEY (`post_type_id`, `taxonomy_id`),
    INDEX `idx_post_type_id` (`post_type_id`),
    INDEX `idx_taxonomy_id` (`taxonomy_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `forms` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL UNIQUE,
    `description` TEXT,
    `fields` JSON NOT NULL,
    `settings` JSON,
    `spam_protection` JSON,
    `created_by` INT NOT NULL,
    `updated_by` INT NOT NULL,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    INDEX `idx_slug` (`slug`),
    INDEX `idx_created_by` (`created_by`),
    INDEX `idx_updated_by` (`updated_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `form_submissions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `form_id` INT NOT NULL,
    `data` JSON NOT NULL,
    `ip_address` VARCHAR(45),
    `user_agent` TEXT,
    `spam_score` DECIMAL(3,2) DEFAULT 0,
    `is_spam` TINYINT(1) NOT NULL DEFAULT 0,
    `status` VARCHAR(20) NOT NULL DEFAULT 'new',
    `created_at` DATETIME NOT NULL,
    INDEX `idx_form` (`form_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `actions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `trigger_type` VARCHAR(50) NOT NULL,
    `trigger_config` JSON,
    `action_type` VARCHAR(50) NOT NULL,
    `action_config` JSON NOT NULL,
    `enabled` TINYINT(1) NOT NULL DEFAULT 1,
    `priority` INT NOT NULL DEFAULT 10,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    INDEX `idx_trigger` (`trigger_type`),
    INDEX `idx_enabled` (`enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `action_queue` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `action_id` INT NOT NULL,
    `payload` JSON NOT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
    `attempts` INT NOT NULL DEFAULT 0,
    `max_attempts` INT NOT NULL DEFAULT 3,
    `error_message` TEXT,
    `scheduled_at` DATETIME NOT NULL,
    `executed_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL,
    INDEX `idx_action_id` (`action_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_scheduled` (`scheduled_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `option_name` VARCHAR(255) NOT NULL UNIQUE,
    `option_value` LONGTEXT NOT NULL,
    `autoload` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    INDEX `idx_autoload` (`autoload`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `post_revisions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `post_id` INT NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `component_data` JSON,
    `meta_data` JSON,
    `created_by` INT NOT NULL,
    `created_at` DATETIME NOT NULL,
    INDEX `idx_post` (`post_id`),
    INDEX `idx_created` (`created_at`),
    INDEX `idx_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `activity_log` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `action` VARCHAR(50) NOT NULL,
    `model_type` VARCHAR(255) NOT NULL,
    `model_id` INT NOT NULL,
    `description` TEXT NOT NULL,
    `old_values` JSON,
    `new_values` JSON,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME NOT NULL,
    INDEX `idx_user` (`user_id`),
    INDEX `idx_model` (`model_type`, `model_id`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `schema_migrations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `version` VARCHAR(50) NOT NULL UNIQUE,
    `name` VARCHAR(255) NOT NULL,
    `executed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `modules` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL UNIQUE,
    `slug` VARCHAR(255) NOT NULL UNIQUE,
    `version` VARCHAR(50) NOT NULL DEFAULT '1.0.0',
    `description` TEXT,
    `author` VARCHAR(255),
    `author_url` VARCHAR(500),
    `module_url` VARCHAR(500),
    `text_domain` VARCHAR(100),
    `requires_php` VARCHAR(20),
    `requires_modular` VARCHAR(20),
    `active` TINYINT(1) NOT NULL DEFAULT 0,
    `settings` JSON,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    INDEX `idx_active` (`active`),
    INDEX `idx_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 2. Default roles and capabilities
-- -----------------------------------------------------------------------------

INSERT IGNORE INTO `roles` (`name`, `display_name`, `description`, `created_at`, `updated_at`) VALUES
('admin', 'Administrator', 'Full system access', NOW(), NOW()),
('editor', 'Editor', 'Can create and edit content', NOW(), NOW()),
('author', 'Author', 'Can create and edit own content', NOW(), NOW()),
('user', 'User', 'Basic user access', NOW(), NOW());

INSERT IGNORE INTO `capabilities` (`name`) VALUES
('post:create'), ('post:edit:own'), ('post:edit:any'), ('post:delete:own'), ('post:delete:any'),
('post:publish'), ('media:upload'), ('media:edit'), ('media:delete'),
('user:create'), ('user:edit'), ('user:delete'), ('user:promote'),
('plugin:activate'), ('plugin:deactivate'), ('settings:edit'), ('theme:edit');

INSERT IGNORE INTO `role_capabilities` (`role_id`, `capability_id`)
SELECT r.id, c.id FROM `roles` r CROSS JOIN `capabilities` c WHERE r.name = 'admin';

-- -----------------------------------------------------------------------------
-- 3. Post types (core + Simuze)
-- -----------------------------------------------------------------------------

SET @supports = '[\"title\",\"editor\"]';

INSERT IGNORE INTO `post_types` (`name`, `slug`, `description`, `public`, `show_in_menu`, `supports`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
('Post', 'post', 'Blog post', 1, 1, @supports, 1, 1, NOW(), NOW()),
('Page', 'page', 'Static page', 1, 1, @supports, 1, 1, NOW(), NOW()),
('Album', 'album', 'Music album', 1, 1, @supports, 1, 1, NOW(), NOW()),
('Track', 'track', 'Music track', 1, 1, @supports, 1, 1, NOW(), NOW()),
('Playlist', 'playlist', 'Playlist', 1, 1, @supports, 1, 1, NOW(), NOW()),
('Event', 'event', 'Event', 1, 1, @supports, 1, 1, NOW(), NOW());

-- -----------------------------------------------------------------------------
-- 4. Genre taxonomy + link to track
-- -----------------------------------------------------------------------------

INSERT IGNORE INTO `taxonomies` (`name`, `slug`, `description`, `hierarchical`, `public`, `show_in_menu`, `show_in_rest`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
('Genre', 'genre', 'Music genre for tracks', 0, 1, 1, 1, 1, 1, NOW(), NOW());

INSERT IGNORE INTO `post_type_taxonomies` (`post_type_id`, `taxonomy_id`)
SELECT pt.id, t.id FROM `post_types` pt CROSS JOIN `taxonomies` t WHERE pt.slug = 'track' AND t.slug = 'genre';

-- -----------------------------------------------------------------------------
-- 5. Track, album, playlist schema (JSON)
-- -----------------------------------------------------------------------------

UPDATE `post_types` SET `schema` = '{\"fields\":[{\"type\":\"relationship\",\"name\":\"album\",\"key\":\"album\",\"label\":\"Album\",\"instructions\":\"Album this track belongs to.\",\"required\":false,\"post_type\":[\"album\"],\"return_format\":\"id\",\"max\":1},{\"type\":\"date\",\"name\":\"release_date\",\"key\":\"release_date\",\"label\":\"Release date\"},{\"type\":\"number\",\"name\":\"duration_seconds\",\"key\":\"duration_seconds\",\"label\":\"Duration (seconds)\"},{\"type\":\"text\",\"name\":\"isrc\",\"key\":\"isrc\",\"label\":\"ISRC\"},{\"type\":\"image\",\"name\":\"cover_image\",\"key\":\"cover_image\",\"label\":\"Cover image\"},{\"type\":\"number\",\"name\":\"track_number\",\"key\":\"track_number\",\"label\":\"Track number\"}]}', `updated_at` = NOW() WHERE slug = 'track';

UPDATE `post_types` SET `schema` = '{\"fields\":[{\"type\":\"image\",\"name\":\"cover_image\",\"key\":\"cover_image\",\"label\":\"Cover image\"},{\"type\":\"date\",\"name\":\"release_date\",\"key\":\"release_date\",\"label\":\"Release date\"}]}', `updated_at` = NOW() WHERE slug = 'album';

UPDATE `post_types` SET `schema` = '{\"fields\":[{\"type\":\"relationship\",\"name\":\"tracks\",\"key\":\"tracks\",\"label\":\"Tracks\",\"instructions\":\"Tracks in this playlist.\",\"required\":false,\"post_type\":[\"track\"],\"return_format\":\"id\"}]}', `updated_at` = NOW() WHERE slug = 'playlist';

-- -----------------------------------------------------------------------------
-- 6. Simuze Community tables (auth/subscriptions/payments via SIMEZU – no internal payment tables)
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `simuze_users` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `simezu_id` VARCHAR(255) NOT NULL,
    `username` VARCHAR(100) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `display_name` VARCHAR(255) DEFAULT NULL,
    `avatar_url` VARCHAR(500) DEFAULT NULL,
    `header_url` VARCHAR(500) DEFAULT NULL,
    `status` VARCHAR(20) DEFAULT 'active',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `simezu_id` (`simezu_id`),
    UNIQUE KEY `username` (`username`),
    UNIQUE KEY `email` (`email`),
    KEY `status` (`status`),
    KEY `deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `simuze_profiles` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `bio` TEXT DEFAULT NULL,
    `location` VARCHAR(255) DEFAULT NULL,
    `city` VARCHAR(100) DEFAULT NULL,
    `province` VARCHAR(100) DEFAULT NULL,
    `country` VARCHAR(100) DEFAULT NULL,
    `geo_id` VARCHAR(50) DEFAULT NULL,
    `lat` DECIMAL(10,8) DEFAULT NULL,
    `lng` DECIMAL(11,8) DEFAULT NULL,
    `website` VARCHAR(255) DEFAULT NULL,
    `social_links` LONGTEXT DEFAULT NULL,
    `settings` LONGTEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `user_id` (`user_id`),
    KEY `location` (`city`, `province`, `country`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `simuze_group_types` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL,
    `icon` VARCHAR(100) DEFAULT NULL,
    `description` TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `slug` (`slug`),
    KEY `deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `simuze_groups` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL,
    `unique_name` VARCHAR(255) DEFAULT NULL,
    `unique_handle` VARCHAR(64) DEFAULT NULL,
    `description` TEXT DEFAULT NULL,
    `group_type_id` BIGINT UNSIGNED DEFAULT NULL,
    `creator_id` BIGINT UNSIGNED NOT NULL,
    `avatar_url` VARCHAR(500) DEFAULT NULL,
    `header_url` VARCHAR(500) DEFAULT NULL,
    `status` VARCHAR(20) DEFAULT 'public',
    `city` VARCHAR(100) DEFAULT NULL,
    `province` VARCHAR(100) DEFAULT NULL,
    `country` VARCHAR(100) DEFAULT NULL,
    `geo_id` VARCHAR(50) DEFAULT NULL,
    `lat` DECIMAL(10,8) DEFAULT NULL,
    `lng` DECIMAL(11,8) DEFAULT NULL,
    `settings` LONGTEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `slug` (`slug`),
    UNIQUE KEY `unique_name` (`unique_name`),
    UNIQUE KEY `unique_handle` (`unique_handle`),
    KEY `group_type_id` (`group_type_id`),
    KEY `creator_id` (`creator_id`),
    KEY `status` (`status`),
    KEY `location` (`city`, `province`, `country`),
    KEY `deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `simuze_group_members` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `group_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `role` VARCHAR(20) DEFAULT 'member',
    `status` VARCHAR(20) DEFAULT 'active',
    `invited_by` BIGINT UNSIGNED DEFAULT NULL,
    `joined_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `group_user` (`group_id`, `user_id`),
    KEY `user_id` (`user_id`),
    KEY `role` (`role`),
    KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `simuze_group_invitations` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `group_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `invited_by` BIGINT UNSIGNED NOT NULL,
    `message` TEXT DEFAULT NULL,
    `status` VARCHAR(20) DEFAULT 'pending',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `group_id` (`group_id`),
    KEY `user_id` (`user_id`),
    KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `simuze_group_activity` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `group_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `action` VARCHAR(50) NOT NULL,
    `content` TEXT DEFAULT NULL,
    `component` VARCHAR(50) DEFAULT NULL,
    `item_id` BIGINT UNSIGNED DEFAULT NULL,
    `meta` LONGTEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `group_id` (`group_id`),
    KEY `user_id` (`user_id`),
    KEY `action` (`action`),
    KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `simuze_user_activity` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `action` VARCHAR(50) NOT NULL,
    `content` TEXT DEFAULT NULL,
    `component` VARCHAR(50) DEFAULT NULL,
    `item_id` BIGINT UNSIGNED DEFAULT NULL,
    `meta` LONGTEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    KEY `action` (`action`),
    KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `simuze_messages` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `thread_id` VARCHAR(100) DEFAULT NULL,
    `sender_id` BIGINT UNSIGNED DEFAULT NULL,
    `recipient_id` BIGINT UNSIGNED DEFAULT NULL,
    `group_id` BIGINT UNSIGNED DEFAULT NULL,
    `subject` VARCHAR(255) DEFAULT NULL,
    `message` TEXT NOT NULL,
    `mentions` TEXT DEFAULT NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `thread_id` (`thread_id`),
    KEY `sender_id` (`sender_id`),
    KEY `recipient_id` (`recipient_id`),
    KEY `group_id` (`group_id`),
    KEY `is_read` (`is_read`),
    KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `simuze_conversations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `type` VARCHAR(50) NOT NULL DEFAULT 'direct',
    `title` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    KEY `idx_type` (`type`),
    KEY `idx_updated` (`updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `simuze_conversation_participants` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `conversation_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `role` VARCHAR(50) NOT NULL DEFAULT 'member',
    `joined_at` DATETIME NOT NULL,
    `last_read_at` DATETIME DEFAULT NULL,
    UNIQUE KEY `uq_conv_user` (`conversation_id`, `user_id`),
    KEY `idx_user` (`user_id`),
    FOREIGN KEY (`conversation_id`) REFERENCES `simuze_conversations`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `simuze_notifications` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `type` VARCHAR(50) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `link` VARCHAR(500) DEFAULT NULL,
    `meta` LONGTEXT DEFAULT NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    `read_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    KEY `type` (`type`),
    KEY `is_read` (`is_read`),
    KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `simuze_reviews` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reviewer_id` BIGINT UNSIGNED NOT NULL,
    `content_type` VARCHAR(50) NOT NULL,
    `content_id` BIGINT UNSIGNED NOT NULL,
    `status` VARCHAR(20) DEFAULT 'pending',
    `notes` TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `reviewer_id` (`reviewer_id`),
    KEY `content` (`content_type`, `content_id`),
    KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `simuze_group_handle_aliases` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `group_id` BIGINT UNSIGNED NOT NULL,
    `old_handle` VARCHAR(64) NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `old_handle` (`old_handle`),
    KEY `group_id` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `simuze_reports` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reporter_id` BIGINT UNSIGNED NOT NULL,
    `content_type` VARCHAR(50) NOT NULL,
    `content_id` BIGINT UNSIGNED NOT NULL,
    `report_type` VARCHAR(50) NOT NULL,
    `comment` TEXT DEFAULT NULL,
    `status` VARCHAR(20) DEFAULT 'pending',
    `reviewed_by` BIGINT UNSIGNED DEFAULT NULL,
    `reviewed_at` DATETIME DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `reporter_id` (`reporter_id`),
    KEY `content` (`content_type`, `content_id`),
    KEY `status` (`status`),
    KEY `reviewed_by` (`reviewed_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `simuze_follows` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `follower_id` BIGINT UNSIGNED NOT NULL,
    `followable_type` VARCHAR(50) NOT NULL,
    `followable_id` BIGINT UNSIGNED NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `follow` (`follower_id`, `followable_type`, `followable_id`),
    KEY `follower_id` (`follower_id`),
    KEY `followable` (`followable_type`, `followable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `simuze_friends` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `friend_id` BIGINT UNSIGNED NOT NULL,
    `status` VARCHAR(20) DEFAULT 'pending',
    `requested_by` BIGINT UNSIGNED NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `friendship` (`user_id`, `friend_id`),
    KEY `user_id` (`user_id`),
    KEY `friend_id` (`friend_id`),
    KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `simuze_avatar_cache_user` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` VARCHAR(255) NOT NULL,
    `thumb_url` VARCHAR(500) DEFAULT NULL,
    `medium_url` VARCHAR(500) DEFAULT NULL,
    `full_url` VARCHAR(500) DEFAULT NULL,
    `metadata` LONGTEXT DEFAULT NULL,
    `cached_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `expires_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `user_lookup` (`user_id`),
    KEY `expires_at` (`expires_at`),
    KEY `cached_at` (`cached_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `simuze_avatar_cache_group` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `group_id` BIGINT UNSIGNED NOT NULL,
    `thumb_url` VARCHAR(500) DEFAULT NULL,
    `medium_url` VARCHAR(500) DEFAULT NULL,
    `full_url` VARCHAR(500) DEFAULT NULL,
    `metadata` LONGTEXT DEFAULT NULL,
    `cached_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `group_lookup` (`group_id`),
    KEY `cached_at` (`cached_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `simuze_label_artists` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `label_group_id` BIGINT UNSIGNED NOT NULL,
    `artist_group_id` BIGINT UNSIGNED NOT NULL,
    `status` ENUM('pending','accepted','rejected','revoked') DEFAULT 'pending',
    `message` TEXT DEFAULT NULL,
    `invited_by` BIGINT UNSIGNED NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `label_artist` (`label_group_id`, `artist_group_id`),
    KEY `label_group_id` (`label_group_id`),
    KEY `artist_group_id` (`artist_group_id`),
    KEY `status` (`status`),
    KEY `invited_by` (`invited_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `simuze_webhooks` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `provider` VARCHAR(100) NOT NULL,
    `event_type` VARCHAR(100) NOT NULL,
    `external_reference` VARCHAR(255) NOT NULL,
    `payload` LONGTEXT NOT NULL,
    `status` VARCHAR(20) DEFAULT 'received',
    `error_message` TEXT DEFAULT NULL,
    `processed_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `external_reference` (`external_reference`),
    KEY `provider` (`provider`),
    KEY `event_type` (`event_type`),
    KEY `status` (`status`),
    KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `simuze_activity` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `author_type` VARCHAR(20) NOT NULL DEFAULT 'user',
    `author_id` INT UNSIGNED NOT NULL,
    `content` TEXT NOT NULL,
    `extra` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL,
    KEY `idx_author` (`author_type`, `author_id`),
    KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default group types (idempotent)
INSERT IGNORE INTO `simuze_group_types` (`name`, `slug`, `icon`) VALUES
('Artist', 'artist', 'music'),
('Label', 'label', 'tag'),
('Venue', 'venue', 'map-marker'),
('City', 'city', 'city'),
('Province', 'province', 'map'),
('Country', 'country', 'globe');

-- -----------------------------------------------------------------------------
-- 7. Demo seed (idempotent)
-- -----------------------------------------------------------------------------

INSERT IGNORE INTO `simuze_users` (`simezu_id`, `username`, `email`, `display_name`, `status`, `created_at`, `updated_at`)
VALUES ('demo-simezu-1', 'demouser', 'demo@simuze.local', 'Demo User', 'active', NOW(), NOW());

INSERT INTO `simuze_profiles` (`user_id`, `bio`, `created_at`, `updated_at`)
SELECT u.id, 'Demo profile for testing.', NOW(), NOW()
FROM `simuze_users` u
WHERE u.simezu_id = 'demo-simezu-1' AND u.deleted_at IS NULL
AND NOT EXISTS (SELECT 1 FROM `simuze_profiles` p WHERE p.user_id = u.id)
LIMIT 1;

INSERT INTO `simuze_groups` (`name`, `slug`, `unique_handle`, `description`, `group_type_id`, `creator_id`, `status`, `created_at`, `updated_at`)
SELECT 'Demo Artist', 'demo-artist', 'demo-artist', 'Demo artist group', gt.id, u.id, 'public', NOW(), NOW()
FROM `simuze_group_types` gt
CROSS JOIN `simuze_users` u
WHERE gt.slug = 'artist' AND u.simezu_id = 'demo-simezu-1' AND u.deleted_at IS NULL
AND NOT EXISTS (SELECT 1 FROM `simuze_groups` g WHERE g.unique_handle = 'demo-artist' AND g.deleted_at IS NULL)
LIMIT 1;

INSERT INTO `simuze_group_members` (`group_id`, `user_id`, `role`, `status`, `joined_at`, `created_at`, `updated_at`)
SELECT g.id, u.id, 'admin', 'active', NOW(), NOW(), NOW()
FROM `simuze_groups` g
CROSS JOIN `simuze_users` u
WHERE g.unique_handle = 'demo-artist' AND g.deleted_at IS NULL
AND u.simezu_id = 'demo-simezu-1' AND u.deleted_at IS NULL
AND NOT EXISTS (SELECT 1 FROM `simuze_group_members` m WHERE m.group_id = g.id AND m.user_id = u.id)
LIMIT 1;

-- -----------------------------------------------------------------------------
-- 8. Music tables (md_ prefix)
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `md_music_uploads` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `artist_id` BIGINT UNSIGNED DEFAULT NULL,
    `group_id` BIGINT UNSIGNED DEFAULT NULL,
    `album_id` BIGINT UNSIGNED DEFAULT NULL,
    `media_id` INT UNSIGNED DEFAULT NULL,
    `file_path` VARCHAR(500) NOT NULL DEFAULT '',
    `original_file_path` VARCHAR(255) NOT NULL DEFAULT '',
    `mp3_file_path` VARCHAR(255) DEFAULT NULL,
    `mime_type` VARCHAR(100) NOT NULL DEFAULT 'audio/mpeg',
    `file_size` INT UNSIGNED NOT NULL DEFAULT 0,
    `song_name` VARCHAR(255) NOT NULL DEFAULT '',
    `license` VARCHAR(255) NOT NULL DEFAULT '',
    `genre` VARCHAR(255) NOT NULL DEFAULT '',
    `mood_slow_fast` INT DEFAULT 0,
    `mood_quiet_loud` INT DEFAULT 0,
    `mood_static_dynamic` INT DEFAULT 0,
    `mood_sad_happy` INT DEFAULT 0,
    `mood_mellow_aggressive` INT DEFAULT 0,
    `tags` TEXT DEFAULT NULL,
    `status` ENUM('draft','published','under_review','rejected','scheduled','trash') DEFAULT 'draft',
    `release_date` DATE DEFAULT NULL,
    `price` DECIMAL(10,2) DEFAULT NULL,
    `currency` VARCHAR(3) DEFAULT 'EUR',
    `is_free` TINYINT(1) DEFAULT 0,
    `virus_scan_status` ENUM('pending','clean','infected','error') DEFAULT 'pending',
    `virus_scan_date` DATETIME DEFAULT NULL,
    `uploaded_by` INT UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    `deleted_at` DATETIME DEFAULT NULL,
    INDEX `idx_artist_id` (`artist_id`),
    INDEX `idx_group_id` (`group_id`),
    INDEX `idx_album_id` (`album_id`),
    INDEX `idx_media_id` (`media_id`),
    INDEX `idx_uploaded_by` (`uploaded_by`),
    INDEX `idx_status` (`status`),
    INDEX `idx_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `md_music_reports` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `reporter_id` INT UNSIGNED NOT NULL,
    `content_type` VARCHAR(50) NOT NULL,
    `content_id` INT UNSIGNED NOT NULL,
    `report_type` VARCHAR(50) NOT NULL,
    `comment` TEXT DEFAULT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
    `reviewed_by` INT UNSIGNED DEFAULT NULL,
    `reviewed_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    INDEX `idx_reporter` (`reporter_id`),
    INDEX `idx_content` (`content_type`, `content_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `md_music_reviews` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `reviewer_id` INT UNSIGNED NOT NULL,
    `content_type` VARCHAR(50) NOT NULL,
    `content_id` INT UNSIGNED NOT NULL,
    `rating` TINYINT UNSIGNED DEFAULT NULL,
    `body` TEXT DEFAULT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    INDEX `idx_reviewer` (`reviewer_id`),
    INDEX `idx_content` (`content_type`, `content_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `md_music_review_comments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `review_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `body` TEXT NOT NULL,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    `deleted_at` DATETIME DEFAULT NULL,
    INDEX `idx_review` (`review_id`),
    INDEX `idx_user` (`user_id`),
    INDEX `idx_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `md_music_albums` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `artist_id` BIGINT UNSIGNED DEFAULT NULL,
    `group_id` BIGINT UNSIGNED DEFAULT NULL,
    `post_id` INT UNSIGNED DEFAULT NULL,
    `album_name` VARCHAR(255) NOT NULL DEFAULT '',
    `album_nicename` VARCHAR(255) NOT NULL DEFAULT '',
    `description` TEXT DEFAULT NULL,
    `publication_date` DATE DEFAULT NULL,
    `release_date` DATE DEFAULT NULL,
    `artwork_path` VARCHAR(255) DEFAULT NULL,
    `artwork_thumbnail_path` VARCHAR(255) DEFAULT NULL,
    `is_album` TINYINT(1) DEFAULT 1,
    `status` ENUM('draft','published','under_review','rejected','scheduled','trash') DEFAULT 'draft',
    `license` VARCHAR(255) DEFAULT 'cc-by',
    `price` DECIMAL(10,2) DEFAULT NULL,
    `currency` VARCHAR(3) DEFAULT 'EUR',
    `is_free` TINYINT(1) DEFAULT 0,
    `created_by` INT UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    `deleted_at` DATETIME DEFAULT NULL,
    INDEX `idx_artist_id` (`artist_id`),
    INDEX `idx_group_id` (`group_id`),
    INDEX `idx_post_id` (`post_id`),
    INDEX `idx_created_by` (`created_by`),
    INDEX `idx_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `md_music_playlists` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `post_id` INT UNSIGNED DEFAULT NULL,
    `created_by` INT UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    `deleted_at` DATETIME DEFAULT NULL,
    `name` VARCHAR(255) NOT NULL DEFAULT '',
    `description` TEXT DEFAULT NULL,
    `slug` VARCHAR(255) DEFAULT NULL,
    `visibility` ENUM('public','private') DEFAULT 'public',
    INDEX `idx_post_id` (`post_id`),
    INDEX `idx_created_by` (`created_by`),
    INDEX `idx_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `md_music_playlist_tracks` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `playlist_id` INT UNSIGNED NOT NULL,
    `upload_id` INT UNSIGNED NOT NULL,
    `position` INT DEFAULT 0,
    `added_at` DATETIME NOT NULL,
    INDEX `idx_playlist_id` (`playlist_id`),
    INDEX `idx_upload_id` (`upload_id`),
    UNIQUE INDEX `idx_unique_pt` (`playlist_id`, `upload_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 9. Default Simuze pages (idempotent)
-- -----------------------------------------------------------------------------

INSERT INTO `posts` (`post_type_id`, `parent_id`, `slug`, `title`, `excerpt`, `status`, `template`, `component_data`, `author_id`, `created_by`, `updated_by`, `created_at`, `updated_at`, `published_at`)
SELECT pt.id, NULL, 'home', 'Home', '', 'published', NULL, '{\"content\":\"<p>Welcome to Simuze. Discover music, connect with the network. Edit this page in Modular admin.</p>\"}', 1, 1, 1, NOW(), NOW(), NOW()
FROM `post_types` pt
WHERE pt.slug = 'page' AND NOT EXISTS (SELECT 1 FROM `posts` p WHERE p.post_type_id = pt.id AND p.slug = 'home' AND p.deleted_at IS NULL)
LIMIT 1;

INSERT INTO `posts` (`post_type_id`, `parent_id`, `slug`, `title`, `excerpt`, `status`, `template`, `component_data`, `author_id`, `created_by`, `updated_by`, `created_at`, `updated_at`, `published_at`)
SELECT pt.id, NULL, 'about', 'About Simuze', '', 'published', 'template_about', '{\"content\":\"<p>Simuze connects music creators and listeners worldwide.</p>\"}', 1, 1, 1, NOW(), NOW(), NOW()
FROM `post_types` pt
WHERE pt.slug = 'page' AND NOT EXISTS (SELECT 1 FROM `posts` p WHERE p.post_type_id = pt.id AND p.slug = 'about' AND p.deleted_at IS NULL)
LIMIT 1;

INSERT INTO `posts` (`post_type_id`, `parent_id`, `slug`, `title`, `excerpt`, `status`, `template`, `component_data`, `author_id`, `created_by`, `updated_by`, `created_at`, `updated_at`, `published_at`)
SELECT pt.id, NULL, 'weblog', 'Weblog', '', 'published', 'template_weblog', '{\"content\":\"<p>Latest news and updates.</p>\"}', 1, 1, 1, NOW(), NOW(), NOW()
FROM `post_types` pt
WHERE pt.slug = 'page' AND NOT EXISTS (SELECT 1 FROM `posts` p WHERE p.post_type_id = pt.id AND p.slug = 'weblog' AND p.deleted_at IS NULL)
LIMIT 1;

INSERT INTO `posts` (`post_type_id`, `parent_id`, `slug`, `title`, `excerpt`, `status`, `template`, `component_data`, `author_id`, `created_by`, `updated_by`, `created_at`, `updated_at`, `published_at`)
SELECT pt.id, NULL, 'howto', 'How it Works', '', 'published', 'template_howto', '{\"content\":\"<p>Learn how Simuze works.</p>\"}', 1, 1, 1, NOW(), NOW(), NOW()
FROM `post_types` pt
WHERE pt.slug = 'page' AND NOT EXISTS (SELECT 1 FROM `posts` p WHERE p.post_type_id = pt.id AND p.slug = 'howto' AND p.deleted_at IS NULL)
LIMIT 1;

-- -----------------------------------------------------------------------------
-- 10. Simuze download grants (SIMEZU confirmation only; no local orders)
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `simuze_download_grants` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `product_type` ENUM('song','album') NOT NULL,
    `product_id` BIGINT UNSIGNED NOT NULL,
    `simezu_reference` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `user_product` (`user_id`, `product_type`, `product_id`),
    KEY `user_id` (`user_id`),
    KEY `product` (`product_type`, `product_id`),
    KEY `simezu_reference` (`simezu_reference`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 11. Simuze favorites
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `simuze_favorites` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `content_type` ENUM('music','group','other','album','playlist') NOT NULL,
    `content_id` BIGINT UNSIGNED NOT NULL,
    `created_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `user_content` (`user_id`, `content_type`, `content_id`),
    KEY `user_id` (`user_id`),
    KEY `content_type` (`content_type`),
    KEY `content_id` (`content_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
