# Modular CMS – Project Structure & Design Rules

## 🔁 Overview
Modular is a custom CMS framework designed for performance, scalability, and modular expansion. It avoids dependencies like Composer, keeping the structure clean and simple.

/ ├── .htaccess 
├── index.php 
├── /admin/ 
├── /core/ 
├── /public/ 
├── /docs/ (this folder)

---

## 📁 Folder Structure

### /admin/
Handles CMS UI and internal logic. Includes authentication, configuration, setup, and CMS template files.

/admin/ 
├── /assets/ 
│   ├── /css/ 
│   ├── /js/ 
│   ├── /fonts/ 
│   └── /images/ 
├── /auth/     # Login, registration, password logic 
├── /config/   # config.php for DB connection and app settings 
├── /setup/    # Two-step install wizard (DB + admin user) 
├── /templates/ # CMS templates: header.php, footer.php, etc.

---

### /core/
The engine of the CMS. Handles routing, modules, frontend rendering, core libraries, JSON/HTML output, filters, hooks, etc.

/core/ 
├── /router/    # Slug routing, clean URL logic 
├── /modules/   # Global module handler + module functions 
├── /frontend/  # Output logic (HTML/JSON renderer) 
├── /hooks/     # Hook/filter system (actions, filters) 
├── /libraries/ # Utility libraries (e.g., mailer, cache, parser) 
├── /functions/ # Shared functions (auth, media, validation, etc.) 
└── init.php    # Main core initializer (autoloads all of above)

---

### /public/
Frontend-facing files only. No logic or sensitive data.

/public/ 
├── /themes/   # Frontend themes 
│   └── [theme]/ # Each theme in own folder 
│       ├── css/ 
│       ├── js/ 
│       └── views/ # .php templates for frontend output 
├── /modules/  # Module public output (each in own folder) 
├── /uploads/  # Media uploads, temp, logs, cache, etc. 
│   ├── /media/ 
│   ├── /logs/ 
│   ├── /cache/ 
│   └── /tmp/

---

## ⚙️ CMS Setup Flow
Simple two-step install when `config.php` is missing or empty:

1. **Step 1**: Enter DB credentials → saves to `/admin/config/config.php`.
2. **Step 2**: Create admin user (email, password) → logs in → goes to Dashboard.

---

## 🔐 Auth System
- Registration & login with password strength validation.
- Token-based password reset.
- Email confirmation required for registration.
- JWT (planned).
- Admin user has access to the CMS backend (`/admin/`).

---

## 🧱 CMS Sections (Admin)
- Dashboard (with widgets)
- Add Type (custom post types / modules)
- Media
- Users
- Settings

Each section has an `index.php` and its own route.

---

## 🧭 Routing
- Clean URLs: `example.com/page-name`
- Routes stored in DB using a `slug` field.
- Router resolves slug → loads page/module/template
- Admin routing is handled internally (`/admin/?page=users`, etc.)

---

## 🎨 Theme & CSS Rules
- All frontend CSS is loaded through `header.php`
- No inline styles or external CSS
- All styles must live inside `/public/themes/[theme]/css/`
- Header must include all required CSS for the page

---

## 🔧 Customization Philosophy
Every part of Modular can be overridden or extended:
- Custom modules live in `/public/modules/[modulename]/`
- Themes are fully self-contained
- Modules and routes are pluggable
- Hooks and filters allow extension without core modification

---

## 📈 Scaling Vision
Modular is built with potential scale in mind:
- Multi-user architecture with session isolation
- Separation of admin logic and frontend logic
- Potential for millions of users
- Minimal dependencies
- Frontend caching and module-level lazy loading (planned)

---

## 📦 Notes
- No Composer
- No external PHP dependencies
- JS libraries can be included manually and versioned





