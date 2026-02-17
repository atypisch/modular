# Capability mapping: WordPress → Modular

Reference for migration and permission checks. Modular uses a flat capability list; roles get a set of capabilities.

## Modular default capabilities (core migration 001)

| Capability | Use |
|------------|-----|
| `post:create` | Create posts (any type) |
| `post:edit:own` | Edit own posts |
| `post:edit:any` | Edit any post |
| `post:delete:own` | Delete own posts |
| `post:delete:any` | Delete any post |
| `post:publish` | Publish / unpublish |
| `media:upload` | Upload media |
| `media:edit` | Edit media |
| `media:delete` | Delete media |
| `user:create` | Create users |
| `user:edit` | Edit users |
| `user:delete` | Delete users |
| `user:promote` | Change roles |
| `plugin:activate` | Activate modules |
| `plugin:deactivate` | Deactivate modules |
| `settings:edit` | Edit settings |
| `theme:edit` | Change theme / appearance |

## WordPress equivalents (common)

| WP capability | Modular equivalent |
|---------------|--------------------|
| `edit_posts` | `post:edit:own` or `post:edit:any` |
| `edit_others_posts` | `post:edit:any` |
| `publish_posts` | `post:publish` |
| `delete_posts` | `post:delete:own` |
| `delete_others_posts` | `post:delete:any` |
| `upload_files` | `media:upload` |
| `edit_users` | `user:edit` |
| `create_users` | `user:create` |
| `manage_options` | `settings:edit` |
| `activate_plugins` | `plugin:activate` |
| `switch_themes` | `theme:edit` |

## Per–post-type capabilities (WP)

WordPress uses `edit_{post_type}`, `delete_{post_type}`, etc. Modular currently uses generic `post:*`; per–post-type checks can be added by:

- Adding capabilities like `album:edit`, `track:create` to the `capabilities` table and assigning them to roles, and
- Checking them in post-type–specific admin or API (e.g. Simuze Community module for groups/albums).

## Applying checks

- Admin UI: before save/delete, resolve current user’s role and check `role_capabilities` for the required capability.
- API: same check in the handler for state-changing requests.
- Module (e.g. simuze-community): group-scoped permissions can layer on top (e.g. “can edit this group” = member + `post:edit:own` or group role).
