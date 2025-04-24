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