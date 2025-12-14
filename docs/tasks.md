# Modular CMS – Tasks



##  Phase 1 Tasks

### 1.  Two-Step CMS Setup Flow (in `/admin/setup/`)
- check for errors and Directory dependencies. make sure directories never link to a root but include the right flder structure. I am running a test on http://localhost/modular
- If modular is not installed, show a message and a button 'install modular' in our tailwind template deisgn that will link to our setup page.
- Setup Step 1: Enter DB credentials and write to `/admin/config/config.php`
- Setup Step 2: Create first (admin) user
- After setup: Auto-login and redirect to `/admin/index.php` (Dashboard)

### 2.  Upgrade Auth System
- Add registration email confirmation flow:
  - Generate secure token
  - Send confirmation email with clickable link
  - Validate token on click and activate user
- Store temporary registration data until confirmation
- Add token expiry and resend option

### 3.  CMS Template + Admin Design System
- All CMS CSS must be loaded from `/admin/assets/css/`
- CSS is included via `header.php`
- Create base layout template with:
  - Top bar with logo & user menu
  - Sidebar with navigation (Dashboard, Add Type, Media, Users, Settings)
  - Dashboard page with placeholder widgets
- Include a pre-install message with link to `/admin/setup/` if not configured yet

## 🧠 Rules and Considerations

- Keep all URLs clean via slug-based routing system in `/core/router/`
- Admin users land on `/admin/index.php` (Dashboard) after login
- Avoid unnecessary libraries or bloat (no Composer)
- Use PHP `include_once` for modular page rendering
- Use tokenized URLs for setup and confirmation flows
- Ensure all CSS/JS is loaded from `/admin/assets/` only

## 📦 Deliverables per Task
1. Files for `/admin/setup/` and setup UI flow
2. Updated `/admin/auth/` logic for confirmation and secure token use
3. Base admin template and minimal dashboard structure
4. Include CSS from `/admin/assets/css/` via `templates/header.php`

# Modular CMS Tasks

## Completed Features
- ✓ Two-Step CMS Setup Flow
- ✓ User Authentication System
- ✓ Base Admin Template Design

## Post Type System Development

### Task 1: Create new Post Types (MODULAR-POST-TYPE-CREATE)
- [ ] Create database table for storing post type definitions
- [ ] Implement Post Type class with basic attributes:
  - Name (e.g., "Article")
  - Nicename/slug (e.g., "article")
  - Single name (e.g., "Article")
  - Plural name (e.g., "Articles")
- [ ] Create admin interface for managing post types
- [ ] Implement CRUD operations for post types
- [ ] Add validation and sanitization

### Task 2: Add Taxonomies (MODULAR-TAXONOMY-CREATE)
- [ ] Create database table for taxonomy definitions
- [ ] Implement Taxonomy class with attributes:
  - Name
  - Slug
  - Hierarchical flag (true/false)
  - Associated post types
- [ ] Create admin interface for managing taxonomies
- [ ] Implement CRUD operations for taxonomies
- [ ] Add term management interface

### Task 3: Add Custom Fields (MODULAR-CUSTOM-FIELD-CREATE)
- [ ] Create database table for custom field definitions
- [ ] Implement CustomField class with attributes:
  - Field name
  - Field type
  - Field options
  - Associated post type
- [ ] Create admin interface for managing custom fields
- [ ] Implement basic field types:
  - Text
  - Textarea
  - WYSIWYG
  - Select
  - Radio
  - Checkbox
  - File upload
- [ ] Add validation per field type
- [ ] Implement field rendering system

### Task 4: Add Post Type Relations (MODULAR-RELATION-CREATE)
- [ ] Create database table for post type relations
- [ ] Implement Relation class with attributes:
  - Source post type
  - Target post type
  - Relation type (one-to-one, one-to-many, many-to-many)
- [ ] Create admin interface for managing relations
- [ ] Implement relation data storage
- [ ] Add relation query/fetch functionality

## Implementation Timeline
- Task 1: 2 days
- Task 2: 3 days
- Task 3: 4 days
- Task 4: 5 days

Total estimated time: 14 days

## Next Steps
1. Database schema design for all new tables
2. Core classes implementation
3. Admin interface development
4. Testing and validation