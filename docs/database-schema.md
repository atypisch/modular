# Modular CMS Database Schema Checklist

## Core Authentication Tables

### `users` ⚠️ Partially Implemented
```sql
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role_id INT UNSIGNED,
    status ENUM('active', 'inactive', 'pending') DEFAULT 'pending',
    bio TEXT,                         -- ✅ Implemented
    profile_image VARCHAR(255),       -- ✅ Implemented
    created_at TIMESTAMP,             -- ✅ Implemented
    updated_at TIMESTAMP,             -- ✅ Implemented
    last_login TIMESTAMP NULL,
    FOREIGN KEY (role_id) REFERENCES roles(id)
);
```

### `sessions` 🔄 To Implement
```sql
CREATE TABLE IF NOT EXISTS sessions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    ip_address VARCHAR(45),
    user_agent TEXT,
    payload TEXT,
    last_activity TIMESTAMP,
    expires_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### `password_resets` 🔄 To Implement
```sql
CREATE TABLE IF NOT EXISTS password_resets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP,
    INDEX email_index (email)
);
```

### `logs` 🔄 To Implement
```sql
CREATE TABLE IF NOT EXISTS logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    type ENUM('auth', 'content', 'system', 'error') NOT NULL,
    action VARCHAR(50) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
```

## Access Control Tables

### `roles` 🔄 To Implement
```sql
CREATE TABLE IF NOT EXISTS roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    is_system BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### `permissions` 🔄 To Implement
```sql
CREATE TABLE IF NOT EXISTS permissions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    category VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### `role_permissions` 🔄 To Implement
```sql
CREATE TABLE IF NOT EXISTS role_permissions (
    role_id INT UNSIGNED NOT NULL,
    permission_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
);
```

## Content Management Tables

### `types` 🔄 To Implement
```sql
CREATE TABLE IF NOT EXISTS types (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    slug VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    is_public BOOLEAN DEFAULT TRUE,
    supports_revisions BOOLEAN DEFAULT FALSE,
    supports_templates BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### `components` 🔄 To Implement
```sql
CREATE TABLE IF NOT EXISTS components (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    type ENUM('text', 'textarea', 'wysiwyg', 'image', 'gallery', 'file', 'select', 'relation', 'repeater') NOT NULL,
    config JSON,
    validation_rules TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### `type_components` 🔄 To Implement
```sql
CREATE TABLE IF NOT EXISTS type_components (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    type_id INT UNSIGNED NOT NULL,
    component_id INT UNSIGNED NOT NULL,
    label VARCHAR(100) NOT NULL,
    key VARCHAR(50) NOT NULL,
    is_required BOOLEAN DEFAULT FALSE,
    is_repeatable BOOLEAN DEFAULT FALSE,
    sort_order INT UNSIGNED DEFAULT 0,
    config JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (type_id) REFERENCES types(id) ON DELETE CASCADE,
    FOREIGN KEY (component_id) REFERENCES components(id) ON DELETE CASCADE,
    UNIQUE KEY unique_type_key (type_id, key)
);
```

### `type_items` 🔄 To Implement
```sql
CREATE TABLE IF NOT EXISTS type_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    type_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    status ENUM('draft', 'published', 'scheduled', 'archived') DEFAULT 'draft',
    content JSON,
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (type_id) REFERENCES types(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    UNIQUE KEY unique_type_slug (type_id, slug)
);
```

### `taxonomies` 🔄 To Implement
```sql
CREATE TABLE IF NOT EXISTS taxonomies (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    slug VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    is_hierarchical BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### `taxonomy_terms` 🔄 To Implement
```sql
CREATE TABLE IF NOT EXISTS taxonomy_terms (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    taxonomy_id INT UNSIGNED NOT NULL,
    parent_id INT UNSIGNED NULL,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    description TEXT,
    sort_order INT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (taxonomy_id) REFERENCES taxonomies(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES taxonomy_terms(id) ON DELETE SET NULL,
    UNIQUE KEY unique_taxonomy_slug (taxonomy_id, slug)
);
```

### `type_taxonomies` 🔄 To Implement
```sql
CREATE TABLE IF NOT EXISTS type_taxonomies (
    type_id INT UNSIGNED NOT NULL,
    taxonomy_id INT UNSIGNED NOT NULL,
    is_required BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (type_id, taxonomy_id),
    FOREIGN KEY (type_id) REFERENCES types(id) ON DELETE CASCADE,
    FOREIGN KEY (taxonomy_id) REFERENCES taxonomies(id) ON DELETE CASCADE
);
```

### `item_terms` 🔄 To Implement
```sql
CREATE TABLE IF NOT EXISTS item_terms (
    item_id INT UNSIGNED NOT NULL,
    term_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (item_id, term_id),
    FOREIGN KEY (item_id) REFERENCES type_items(id) ON DELETE CASCADE,
    FOREIGN KEY (term_id) REFERENCES taxonomy_terms(id) ON DELETE CASCADE
);
```

## System Tables

### `settings` 🔄 To Implement
```sql
CREATE TABLE IF NOT EXISTS settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    group_key VARCHAR(50) NOT NULL,
    key_name VARCHAR(100) NOT NULL,
    value TEXT,
    is_autoload BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_setting (group_key, key_name)
);
```

### `media` 🔄 To Implement
```sql
CREATE TABLE IF NOT EXISTS media (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    filename VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    size INT UNSIGNED NOT NULL,
    width INT UNSIGNED NULL,
    height INT UNSIGNED NULL,
    alt_text VARCHAR(255),
    caption TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

### `modules` 🔄 To Implement
```sql
CREATE TABLE IF NOT EXISTS modules (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    version VARCHAR(20) NOT NULL,
    is_active BOOLEAN DEFAULT FALSE,
    is_system BOOLEAN DEFAULT FALSE,
    config JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### `navigation` 🔄 To Implement
```sql
CREATE TABLE IF NOT EXISTS navigation (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parent_id INT UNSIGNED NULL,
    title VARCHAR(100) NOT NULL,
    type ENUM('custom', 'type_item', 'taxonomy_term') NOT NULL,
    target_id INT UNSIGNED NULL,
    url VARCHAR(255) NULL,
    sort_order INT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES navigation(id) ON DELETE CASCADE
);
```

### `api_keys` 🔄 To Implement
```sql
CREATE TABLE IF NOT EXISTS api_keys (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    key_hash VARCHAR(255) NOT NULL,
    last_used_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

## Implementation Notes

### Current Status
- ✅ Partially implemented: users table with basic fields
- 🔄 All other tables need to be implemented
- ⚠️ Foreign key constraints need to be carefully managed during implementation

### Implementation Order
1. Core Authentication Tables
2. Access Control Tables
3. Content Management Tables
4. System Tables

### Security Considerations
- All password/token fields use secure hashing
- Timestamps for tracking and security
- Proper indexing for performance
- Careful permission management
- Audit logging capability

### Performance Considerations
- Appropriate field types and lengths
- Strategic indexing
- JSON fields for flexible storage
- Efficient foreign key relationships 