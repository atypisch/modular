# Claude Code — Project Rules (simuze-mdlr)

This file is read automatically on every Claude Code session. All rules below are binding.
When a new coding rule or constraint is established during a session, add it here under the relevant section.

---

## Project Stack

- **Framework**: Modular PHP — no WordPress, no WP functions
- **Database**: `mdlr-simuze` — credentials: root/root, MAMP mysql at `/Applications/MAMP/Library/bin/mysql80/bin/mysql`
- **PHP helpers**: always use `md_db_*()`, `md_home_url()`, `md_asset_url()`, `md_esc_html/attr/url()`, `md_sanitize_text()` — never use WP equivalents
- **Auth**: JWT cookie, handled via `core/functions/simezu_auth.php`
- **Theme**: `public/themes/simuze/` — active theme constant `ACTIVE_THEME = 'simuze'`
- **Module**: `public/modules/simuze-community/` — loaded via `module.php`

---

## Database & Schema

- ONE schema file only: `core/migrations/full_schema_simuze.sql`
- No separate migration files — all schema changes go into the single schema file
- All `CREATE TABLE` statements must use `IF NOT EXISTS`
- Schema must be safely re-runnable without data loss or errors (idempotent)
- Schema must be directly paste-able into phpMyAdmin

---

## File & Folder Structure

- Do not create new folders without explicit user approval
- Do not create new files unless strictly required — always prefer editing an existing file
- Stick to the existing file and folder structure

---

## Coding Rules

- Implement only what was explicitly asked — no extra features, no unrequested improvements
- Do not change designs, layouts or CSS unless explicitly asked
- Do not add comments, docstrings or type annotations to code you did not change
- Prefer targeted edits (diffs) over full file rewrites
- No backwards-compatibility shims, unused exports, or removed-code comments
- No error handling for scenarios that cannot happen
- No abstractions for one-time operations

---

## Self-Updating

When a new rule or recurring constraint is established during a session (e.g. "always use X", "never do Y"), add it to the relevant section above so it applies to all future sessions.
