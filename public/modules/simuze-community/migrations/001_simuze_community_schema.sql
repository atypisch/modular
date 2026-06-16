-- Simuze Community module tables (installed on module activation)
SET FOREIGN_KEY_CHECKS = 0;

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

-- -----------------------------------------------------------------------------
-- Simuze content types (registered in core post_types / taxonomies)
-- -----------------------------------------------------------------------------

SET @supports = '["title","editor"]';

INSERT IGNORE INTO post_types (name, slug, description, public, show_in_menu, supports, created_by, updated_by, created_at, updated_at) VALUES
('Album', 'album', 'Music album', 1, 1, @supports, 1, 1, NOW(), NOW()),
('Track', 'track', 'Music track', 1, 1, @supports, 1, 1, NOW(), NOW()),
('Playlist', 'playlist', 'Playlist', 1, 1, @supports, 1, 1, NOW(), NOW()),
('Event', 'event', 'Event', 1, 1, @supports, 1, 1, NOW(), NOW());

INSERT IGNORE INTO taxonomies (name, slug, description, hierarchical, public, show_in_menu, show_in_rest, created_by, updated_by, created_at, updated_at) VALUES
('Genre', 'genre', 'Music genre for tracks', 0, 1, 1, 1, 1, 1, NOW(), NOW());

INSERT IGNORE INTO post_type_taxonomies (post_type_id, taxonomy_id)
SELECT pt.id, t.id FROM post_types pt CROSS JOIN taxonomies t WHERE pt.slug = 'track' AND t.slug = 'genre';

UPDATE post_types SET `schema` = '{"fields":[{"type":"relationship","name":"album","key":"album","label":"Album","instructions":"Album this track belongs to.","required":false,"post_type":["album"],"return_format":"id","max":1}]}', updated_at = NOW() WHERE slug = 'track';

UPDATE post_types SET `schema` = '{"fields":[{"type":"relationship","name":"tracks","key":"tracks","label":"Tracks","instructions":"Tracks in this playlist.","required":false,"post_type":["track"],"return_format":"id"}]}', updated_at = NOW() WHERE slug = 'playlist';

-- =============================================================================
-- Events / Shows system (Phase A)
-- =============================================================================

CREATE TABLE IF NOT EXISTS simuze_cities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    country VARCHAR(100) NOT NULL DEFAULT '',
    province VARCHAR(100) NOT NULL DEFAULT '',
    region VARCHAR(100) NOT NULL DEFAULT '',
    lat DECIMAL(10,7) DEFAULT NULL,
    lng DECIMAL(10,7) DEFAULT NULL,
    UNIQUE KEY uq_slug (slug),
    KEY idx_country (country),
    KEY idx_province (province),
    KEY idx_location (country, province, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS simuze_venues (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    type ENUM('diy_space','rehearsal_space','studio','house','bar','gallery','collective','basement','outdoor','other') NOT NULL DEFAULT 'diy_space',
    city_id INT DEFAULT NULL,
    address TEXT DEFAULT NULL,
    capacity INT UNSIGNED DEFAULT NULL,
    contact TEXT DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    is_public TINYINT(1) NOT NULL DEFAULT 1,
    group_id INT DEFAULT NULL,
    created_by INT NULL DEFAULT NULL,
    owner_email VARCHAR(255) NULL DEFAULT NULL,
    created_at DATETIME NOT NULL,
    deleted_at DATETIME DEFAULT NULL,
    KEY idx_city (city_id),
    KEY idx_group (group_id),
    KEY idx_public (is_public)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS simuze_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    type ENUM('show','diy','house','jam','rehearsal','listening','tour_date','other') NOT NULL DEFAULT 'show',
    organiser_id INT NULL DEFAULT NULL,
    organiser_type ENUM('user','group') NOT NULL DEFAULT 'group',
    city_id INT DEFAULT NULL,
    venue_id INT DEFAULT NULL,
    venue_label VARCHAR(255) DEFAULT NULL,
    address_hidden TINYINT(1) NOT NULL DEFAULT 0,
    event_date DATETIME NOT NULL,
    doors_time TIME DEFAULT NULL,
    capacity INT UNSIGNED DEFAULT NULL,
    genres JSON DEFAULT NULL,
    mood VARCHAR(100) DEFAULT NULL,
    open_slots TINYINT UNSIGNED NOT NULL DEFAULT 0,
    ticket_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    ticket_capacity INT UNSIGNED DEFAULT NULL,
    ticket_currency CHAR(3) NOT NULL DEFAULT 'EUR',
    ticket_sales_enabled TINYINT(1) NOT NULL DEFAULT 0,
    ticket_type ENUM('none','simuze','external') NOT NULL DEFAULT 'none',
    ticket_url VARCHAR(500) DEFAULT NULL,
    header_image_url VARCHAR(500) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    diy_notes TEXT DEFAULT NULL,
    contact_name VARCHAR(255) DEFAULT NULL,
    contact_email VARCHAR(255) DEFAULT NULL,
    status ENUM('draft','open','lineup_set','confirmed','past','cancelled') NOT NULL DEFAULT 'open',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    deleted_at DATETIME DEFAULT NULL,
    UNIQUE KEY uq_slug (slug),
    KEY idx_city (city_id),
    KEY idx_organiser (organiser_type, organiser_id),
    KEY idx_status (status),
    KEY idx_date (event_date),
    KEY idx_ticket_sales (ticket_sales_enabled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS simuze_event_slots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    group_id INT DEFAULT NULL,
    user_id INT DEFAULT NULL,
    slot_type ENUM('headliner','support','opener','host','dj','tbc') NOT NULL DEFAULT 'tbc',
    confirmed TINYINT(1) NOT NULL DEFAULT 0,
    set_time SMALLINT UNSIGNED DEFAULT NULL,
    slot_order TINYINT UNSIGNED NOT NULL DEFAULT 0,
    notes TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL,
    KEY idx_event (event_id),
    KEY idx_group (group_id),
    KEY idx_user (user_id),
    FOREIGN KEY (event_id) REFERENCES simuze_events(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS simuze_show_slot_pool (
    id INT AUTO_INCREMENT PRIMARY KEY,
    band_group_id INT NOT NULL,
    location_level ENUM('country','province','city') NOT NULL,
    country VARCHAR(100) NOT NULL DEFAULT '',
    province VARCHAR(100) NOT NULL DEFAULT '',
    city_id INT DEFAULT NULL,
    from_date DATE DEFAULT NULL,
    to_date DATE DEFAULT NULL,
    created_by_user_id INT NOT NULL,
    status ENUM('active','expired') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    KEY idx_band_group_id (band_group_id),
    KEY idx_status (status),
    KEY idx_location (location_level, country, province, city_id),
    KEY idx_timeframe (from_date, to_date),
    KEY idx_creator (created_by_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS simuze_event_slot_invitations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    slot_id INT NOT NULL,
    band_group_id INT NOT NULL,
    pool_entry_id INT DEFAULT NULL,
    status ENUM('pending','accepted','declined','cancelled') NOT NULL DEFAULT 'pending',
    created_by_user_id INT NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    KEY idx_event_id (event_id),
    KEY idx_slot_id (slot_id),
    KEY idx_band_group_id (band_group_id),
    KEY idx_status (status),
    KEY idx_pool_entry_id (pool_entry_id),
    CONSTRAINT fk_slot_inv_event
        FOREIGN KEY (event_id) REFERENCES simuze_events(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS simuze_event_orders (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    buyer_user_id INT NULL DEFAULT NULL,
    buyer_email VARCHAR(255) DEFAULT NULL,
    quantity SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    currency CHAR(3) NOT NULL DEFAULT 'EUR',
    status ENUM('pending','paid','failed','cancelled','refunded') NOT NULL DEFAULT 'pending',
    external_reference VARCHAR(191) DEFAULT NULL,
    payment_provider VARCHAR(50) NOT NULL DEFAULT 'simezu',
    metadata JSON DEFAULT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    KEY idx_event (event_id),
    KEY idx_buyer_user (buyer_user_id),
    KEY idx_status (status),
    UNIQUE KEY uq_ext_ref (external_reference)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS simuze_event_tickets (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    order_id BIGINT UNSIGNED DEFAULT NULL,
    buyer_user_id INT NULL DEFAULT NULL,
    buyer_email VARCHAR(255) DEFAULT NULL,
    ticket_code VARCHAR(64) NOT NULL,
    qr_token VARCHAR(128) NOT NULL,
    status ENUM('valid','used','cancelled','refunded') NOT NULL DEFAULT 'valid',
    issued_at DATETIME NOT NULL,
    used_at DATETIME DEFAULT NULL,
    metadata JSON DEFAULT NULL,
    KEY idx_event (event_id),
    KEY idx_order (order_id),
    KEY idx_buyer_user (buyer_user_id),
    KEY idx_status (status),
    UNIQUE KEY uq_ticket_code (ticket_code),
    UNIQUE KEY uq_qr_token (qr_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS simuze_activity_posts (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    type         ENUM('open_slot','looking_for_band','musician_wanted','rehearsal_space','general_request') NOT NULL,
    author_type  ENUM('user','group') NOT NULL DEFAULT 'user',
    author_id    INT UNSIGNED NOT NULL,
    title        VARCHAR(255) NOT NULL,
    city_id      INT UNSIGNED NULL,
    city_name    VARCHAR(100) NULL,
    scope        ENUM('group','city','global') NOT NULL DEFAULT 'city',
    body         TEXT NULL,
    status       ENUM('active','closed','deleted') NOT NULL DEFAULT 'active',
    expires_at   DATETIME NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at   DATETIME NULL,
    KEY idx_type (type),
    KEY idx_author (author_type, author_id),
    KEY idx_city (city_id),
    KEY idx_status (status),
    KEY idx_expires (expires_at),
    KEY idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Attendance ("I'm going")
CREATE TABLE IF NOT EXISTS simuze_event_attendance (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at DATETIME NOT NULL,
    UNIQUE KEY uq_event_user (event_id, user_id),
    KEY idx_event (event_id),
    KEY idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Show comments
CREATE TABLE IF NOT EXISTS simuze_event_comments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    user_id INT NOT NULL,
    body TEXT NOT NULL,
    status ENUM('visible','hidden','deleted') NOT NULL DEFAULT 'visible',
    created_at DATETIME NOT NULL,
    deleted_at DATETIME DEFAULT NULL,
    KEY idx_event (event_id),
    KEY idx_user (user_id),
    KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Organiser guestlist (manual adds, free entries)
CREATE TABLE IF NOT EXISTS simuze_event_guestlist (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    added_by_user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) DEFAULT NULL,
    quantity TINYINT UNSIGNED NOT NULL DEFAULT 1,
    note TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL,
    KEY idx_event (event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS simuze_show_invites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    show_id INT NOT NULL,
    artist_group_id INT NOT NULL,
    invited_by_user_id INT NOT NULL,
    status ENUM('pending','accepted','declined','cancelled') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_show (show_id),
    KEY idx_artist (artist_group_id),
    KEY idx_status (status),
    UNIQUE KEY uq_show_artist (show_id, artist_group_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
