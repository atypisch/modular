-- Create users table
CREATE TABLE IF NOT EXISTS `users` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `username` varchar(255) NOT NULL,
    `email` varchar(255) NOT NULL,
    `password` varchar(255) NOT NULL,
    `first_name` varchar(255) DEFAULT NULL,
    `last_name` varchar(255) DEFAULT NULL,
    `role` varchar(50) NOT NULL DEFAULT 'editor',
    `avatar` varchar(255) DEFAULT NULL,
    `bio` text,
    `status` varchar(20) NOT NULL DEFAULT 'active',
    `email_verified` tinyint(1) NOT NULL DEFAULT '0',
    `email_verification_token` varchar(255) DEFAULT NULL,
    `reset_password_token` varchar(255) DEFAULT NULL,
    `reset_password_expires` datetime DEFAULT NULL,
    `last_login` datetime DEFAULT NULL,
    `last_ip` varchar(45) DEFAULT NULL,
    `created_at` datetime NOT NULL,
    `updated_at` datetime NOT NULL,
    `deleted_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `username` (`username`),
    UNIQUE KEY `email` (`email`),
    KEY `status` (`status`),
    KEY `role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create user_sessions table for better session management
CREATE TABLE IF NOT EXISTS `user_sessions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `token` varchar(255) NOT NULL,
    `ip_address` varchar(45) NOT NULL,
    `user_agent` varchar(255) NOT NULL,
    `last_activity` datetime NOT NULL,
    `expires_at` datetime NOT NULL,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    KEY `token` (`token`),
    CONSTRAINT `user_sessions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create post_types table with additional fields
CREATE TABLE IF NOT EXISTS `post_types` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `slug` varchar(255) NOT NULL,
    `description` text,
    `icon` varchar(50) DEFAULT 'file',
    `supports` text NOT NULL DEFAULT '["title","editor","thumbnail"]',
    `public` tinyint(1) NOT NULL DEFAULT '1',
    `show_in_menu` tinyint(1) NOT NULL DEFAULT '1',
    `menu_position` int(11) DEFAULT NULL,
    `menu_icon` varchar(50) DEFAULT NULL,
    `hierarchical` tinyint(1) NOT NULL DEFAULT '0',
    `has_archive` tinyint(1) NOT NULL DEFAULT '1',
    `exclude_from_search` tinyint(1) NOT NULL DEFAULT '0',
    `publicly_queryable` tinyint(1) NOT NULL DEFAULT '1',
    `show_in_rest` tinyint(1) NOT NULL DEFAULT '1',
    `rest_base` varchar(50) DEFAULT NULL,
    `capability_type` varchar(50) NOT NULL DEFAULT 'post',
    `created_by` int(11) NOT NULL,
    `updated_by` int(11) NOT NULL,
    `created_at` datetime NOT NULL,
    `updated_at` datetime NOT NULL,
    `deleted_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `slug` (`slug`),
    KEY `created_by` (`created_by`),
    KEY `updated_by` (`updated_by`),
    CONSTRAINT `post_types_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
    CONSTRAINT `post_types_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create fields table for custom fields with additional configuration options
CREATE TABLE IF NOT EXISTS `fields` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `post_type_id` int(11) NOT NULL,
    `name` varchar(255) NOT NULL,
    `slug` varchar(255) NOT NULL,
    `type` varchar(50) NOT NULL,
    `description` text,
    `placeholder` varchar(255) DEFAULT NULL,
    `help_text` text,
    `required` tinyint(1) NOT NULL DEFAULT '0',
    `searchable` tinyint(1) NOT NULL DEFAULT '1',
    `filterable` tinyint(1) NOT NULL DEFAULT '0',
    `show_in_list` tinyint(1) NOT NULL DEFAULT '0',
    `options` text,
    `default_value` text,
    `validation_rules` text,
    `conditional_logic` text,
    `wrapper_class` varchar(255) DEFAULT NULL,
    `input_class` varchar(255) DEFAULT NULL,
    `order` int(11) NOT NULL DEFAULT '0',
    `group` varchar(255) DEFAULT NULL,
    `tab` varchar(255) DEFAULT NULL,
    `created_by` int(11) NOT NULL,
    `updated_by` int(11) NOT NULL,
    `created_at` datetime NOT NULL,
    `updated_at` datetime NOT NULL,
    `deleted_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `post_type_id` (`post_type_id`),
    KEY `created_by` (`created_by`),
    KEY `updated_by` (`updated_by`),
    CONSTRAINT `fields_post_type_id_foreign` FOREIGN KEY (`post_type_id`) REFERENCES `post_types` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fields_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
    CONSTRAINT `fields_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create field_groups table for organizing fields
CREATE TABLE IF NOT EXISTS `field_groups` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `description` text,
    `position` varchar(20) DEFAULT 'normal',
    `style` varchar(20) DEFAULT 'default',
    `order` int(11) NOT NULL DEFAULT '0',
    `created_by` int(11) NOT NULL,
    `updated_by` int(11) NOT NULL,
    `created_at` datetime NOT NULL,
    `updated_at` datetime NOT NULL,
    PRIMARY KEY (`id`),
    KEY `created_by` (`created_by`),
    KEY `updated_by` (`updated_by`),
    CONSTRAINT `field_groups_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
    CONSTRAINT `field_groups_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create posts table with improved metadata
CREATE TABLE IF NOT EXISTS `posts` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `post_type_id` int(11) NOT NULL,
    `parent_id` int(11) DEFAULT NULL,
    `title` varchar(255) NOT NULL,
    `slug` varchar(255) NOT NULL,
    `content` longtext,
    `excerpt` text,
    `status` varchar(20) NOT NULL DEFAULT 'draft',
    `password` varchar(255) DEFAULT NULL,
    `comment_status` varchar(20) NOT NULL DEFAULT 'open',
    `ping_status` varchar(20) NOT NULL DEFAULT 'open',
    `visibility` varchar(20) NOT NULL DEFAULT 'public',
    `featured_image` int(11) DEFAULT NULL,
    `template` varchar(255) DEFAULT NULL,
    `order` int(11) NOT NULL DEFAULT '0',
    `author_id` int(11) NOT NULL,
    `created_by` int(11) NOT NULL,
    `updated_by` int(11) NOT NULL,
    `created_at` datetime NOT NULL,
    `updated_at` datetime NOT NULL,
    `published_at` datetime DEFAULT NULL,
    `deleted_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `post_type_id` (`post_type_id`),
    KEY `parent_id` (`parent_id`),
    KEY `author_id` (`author_id`),
    KEY `created_by` (`created_by`),
    KEY `updated_by` (`updated_by`),
    KEY `status` (`status`),
    KEY `slug` (`slug`),
    CONSTRAINT `posts_post_type_id_foreign` FOREIGN KEY (`post_type_id`) REFERENCES `post_types` (`id`),
    CONSTRAINT `posts_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `posts` (`id`) ON DELETE SET NULL,
    CONSTRAINT `posts_author_id_foreign` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`),
    CONSTRAINT `posts_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
    CONSTRAINT `posts_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create post_meta table for custom field values
CREATE TABLE IF NOT EXISTS `post_meta` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `post_id` int(11) NOT NULL,
    `field_id` int(11) NOT NULL,
    `value` longtext,
    `created_at` datetime NOT NULL,
    `updated_at` datetime NOT NULL,
    PRIMARY KEY (`id`),
    KEY `post_id` (`post_id`),
    KEY `field_id` (`field_id`),
    CONSTRAINT `post_meta_post_id_foreign` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
    CONSTRAINT `post_meta_field_id_foreign` FOREIGN KEY (`field_id`) REFERENCES `fields` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create post_revisions table
CREATE TABLE IF NOT EXISTS `post_revisions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `post_id` int(11) NOT NULL,
    `title` varchar(255) NOT NULL,
    `content` longtext,
    `meta_data` longtext,
    `created_by` int(11) NOT NULL,
    `created_at` datetime NOT NULL,
    PRIMARY KEY (`id`),
    KEY `post_id` (`post_id`),
    KEY `created_by` (`created_by`),
    CONSTRAINT `post_revisions_post_id_foreign` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
    CONSTRAINT `post_revisions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create taxonomies table
CREATE TABLE IF NOT EXISTS `taxonomies` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `slug` varchar(255) NOT NULL,
    `description` text,
    `hierarchical` tinyint(1) NOT NULL DEFAULT '0',
    `public` tinyint(1) NOT NULL DEFAULT '1',
    `show_in_menu` tinyint(1) NOT NULL DEFAULT '1',
    `show_in_rest` tinyint(1) NOT NULL DEFAULT '1',
    `rest_base` varchar(50) DEFAULT NULL,
    `created_by` int(11) NOT NULL,
    `updated_by` int(11) NOT NULL,
    `created_at` datetime NOT NULL,
    `updated_at` datetime NOT NULL,
    `deleted_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `slug` (`slug`),
    KEY `created_by` (`created_by`),
    KEY `updated_by` (`updated_by`),
    CONSTRAINT `taxonomies_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
    CONSTRAINT `taxonomies_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create post_type_taxonomies table for linking post types to taxonomies
CREATE TABLE IF NOT EXISTS `post_type_taxonomies` (
    `post_type_id` int(11) NOT NULL,
    `taxonomy_id` int(11) NOT NULL,
    PRIMARY KEY (`post_type_id`, `taxonomy_id`),
    KEY `taxonomy_id` (`taxonomy_id`),
    CONSTRAINT `post_type_taxonomies_post_type_id_foreign` FOREIGN KEY (`post_type_id`) REFERENCES `post_types` (`id`) ON DELETE CASCADE,
    CONSTRAINT `post_type_taxonomies_taxonomy_id_foreign` FOREIGN KEY (`taxonomy_id`) REFERENCES `taxonomies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create terms table
CREATE TABLE IF NOT EXISTS `terms` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `taxonomy_id` int(11) NOT NULL,
    `name` varchar(255) NOT NULL,
    `slug` varchar(255) NOT NULL,
    `description` text,
    `parent_id` int(11) DEFAULT NULL,
    `order` int(11) NOT NULL DEFAULT '0',
    `created_by` int(11) NOT NULL,
    `updated_by` int(11) NOT NULL,
    `created_at` datetime NOT NULL,
    `updated_at` datetime NOT NULL,
    `deleted_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `taxonomy_id` (`taxonomy_id`),
    KEY `parent_id` (`parent_id`),
    KEY `created_by` (`created_by`),
    KEY `updated_by` (`updated_by`),
    CONSTRAINT `terms_taxonomy_id_foreign` FOREIGN KEY (`taxonomy_id`) REFERENCES `taxonomies` (`id`) ON DELETE CASCADE,
    CONSTRAINT `terms_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `terms` (`id`) ON DELETE SET NULL,
    CONSTRAINT `terms_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
    CONSTRAINT `terms_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create post_terms table for linking posts to terms
CREATE TABLE IF NOT EXISTS `post_terms` (
    `post_id` int(11) NOT NULL,
    `term_id` int(11) NOT NULL,
    PRIMARY KEY (`post_id`, `term_id`),
    KEY `term_id` (`term_id`),
    CONSTRAINT `post_terms_post_id_foreign` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
    CONSTRAINT `post_terms_term_id_foreign` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create media table with enhanced metadata
CREATE TABLE IF NOT EXISTS `media` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `file_name` varchar(255) NOT NULL,
    `file_path` varchar(255) NOT NULL,
    `file_type` varchar(100) NOT NULL,
    `mime_type` varchar(100) NOT NULL,
    `file_size` int(11) NOT NULL,
    `width` int(11) DEFAULT NULL,
    `height` int(11) DEFAULT NULL,
    `title` varchar(255) DEFAULT NULL,
    `caption` text,
    `description` text,
    `alt_text` varchar(255) DEFAULT NULL,
    `uploaded_by` int(11) NOT NULL,
    `created_by` int(11) NOT NULL,
    `updated_by` int(11) NOT NULL,
    `created_at` datetime NOT NULL,
    `updated_at` datetime NOT NULL,
    `deleted_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `uploaded_by` (`uploaded_by`),
    KEY `created_by` (`created_by`),
    KEY `updated_by` (`updated_by`),
    CONSTRAINT `media_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`),
    CONSTRAINT `media_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
    CONSTRAINT `media_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create options table for site settings
CREATE TABLE IF NOT EXISTS `options` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `option_name` varchar(255) NOT NULL,
    `option_value` longtext NOT NULL,
    `autoload` tinyint(1) NOT NULL DEFAULT '1',
    `created_at` datetime NOT NULL,
    `updated_at` datetime NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `option_name` (`option_name`),
    KEY `autoload` (`autoload`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create activity_log table for tracking changes
CREATE TABLE IF NOT EXISTS `activity_log` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `action` varchar(50) NOT NULL,
    `model_type` varchar(255) NOT NULL,
    `model_id` int(11) NOT NULL,
    `description` text NOT NULL,
    `old_values` longtext,
    `new_values` longtext,
    `ip_address` varchar(45) DEFAULT NULL,
    `user_agent` varchar(255) DEFAULT NULL,
    `created_at` datetime NOT NULL,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    KEY `model_type_model_id` (`model_type`, `model_id`),
    CONSTRAINT `activity_log_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
