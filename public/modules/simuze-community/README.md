# Simuze Community Plugin

Volledige custom community systeem ter vervanging van BuddyPress.

## Installatie

1. Upload de `simuze-community` folder naar `/wp-content/plugins/`
2. Activeer de plugin via het WordPress admin menu
3. De database tabellen worden automatisch aangemaakt bij activatie

## Configuratie

### Externe Auth API

Ga naar **Simuze > Settings** en configureer:
- **Auth API URL**: URL naar de Simezu External Auth API (default: `https://api.simezu.com/auth`)
- **GEO API URL**: URL naar de GEO API voor locatie data

## Gebruik

### Shortcodes

- `[simuze_profile username=""]` - Toon user profiel pagina
- `[simuze_group slug=""]` - Toon group pagina
- `[simuze_messages]` - Toon messages inbox
- `[simuze_discography group_id=""]` - Toon discography
- `[simuze_activity]` - Toon activity feed
- `[simuze_friends]` - Toon friends lijst
- `[simuze_groups group_type=""]` - Toon groups lijst

### REST API

Alle endpoints zijn beschikbaar onder `/wp-json/simuze/v1/`:

- `GET /me` - Huidige user
- `GET /users` - Users lijst
- `GET /users/{id}` - User details
- `GET /users/search?query=xxx` - Zoek users (voor mentions)
- `GET /groups` - Groups lijst
- `POST /groups` - Maak group
- `GET /groups/{id}` - Group details
- `PUT /groups/{id}` - Update group
- `GET /groups/{id}/members` - Group members
- `POST /groups/{id}/join` - Join group
- `POST /groups/{id}/leave` - Leave group
- `GET /activity` - Activity feed
- `GET /messages` - Messages
- `POST /messages` - Send message
- `POST /follow` - Follow user/group
- `POST /unfollow` - Unfollow user/group

## Database Structuur

Alle tabellen gebruiken het prefix `simuze_`:

- `simuze_users` - Users
- `simuze_profiles` - User profiles
- `simuze_groups` - Groups
- `simuze_group_types` - Group types
- `simuze_group_members` - Group memberships
- `simuze_group_invitations` - Group invitations
- `simuze_group_activity` - Group activity
- `simuze_user_activity` - User activity
- `simuze_messages` - Messages
- `simuze_reviews` - Reviews
- `simuze_reports` - Reports
- `simuze_discography` - Discography albums
- `simuze_discography_items` - Discography tracks
- `simuze_follows` - Follow relationships
- `simuze_friends` - Friend relationships

## BuddyPress Vervanging

### Verwijderen BuddyPress

1. **Deactiveer BuddyPress plugin** via WordPress admin
2. **Verwijder BuddyPress** (optioneel, maar aanbevolen)
3. **Update theme code** - Vervang alle `bp_*` functie calls met Simuze equivalenten

### Fallback Functies

Voor backwards compatibility kunnen fallback functies worden toegevoegd in `includes/class-buddypress-fallback.php`:

```php
// Example fallback
if (!function_exists('bp_get_current_user_id')) {
    function bp_get_current_user_id() {
        $user = Simuze_Helpers::get_current_user();
        return $user ? $user->id : 0;
    }
}
```

## Development

### Bestandsstructuur

```
simuze-community/
├── simuze-community.php      # Main plugin file
├── includes/
│   ├── class-database.php    # Database management
│   ├── class-auth.php         # External auth
│   ├── class-user.php         # User management
│   ├── class-group.php        # Group management
│   ├── class-activity.php     # Activity system
│   ├── class-message.php      # Messaging
│   ├── class-friend.php       # Friends
│   ├── class-follow.php       # Follows
│   ├── class-review.php       # Reviews
│   ├── class-report.php       # Reports
│   ├── class-geo.php          # GEO location
│   ├── class-helpers.php      # Helper functions
│   ├── class-shortcodes.php   # Shortcodes
│   ├── class-templates.php    # Template loader
│   └── api/
│       └── class-rest-api.php # REST API
├── admin/
│   ├── class-admin.php        # Admin interface
│   └── views/                 # Admin views
├── templates/                 # Frontend templates
├── assets/
│   ├── css/                   # Stylesheets
│   └── js/                    # JavaScript
└── languages/                 # Translations
```

## TODO

- [ ] Complete activity feed aggregator
- [ ] Full messaging system with threads
- [ ] Mentions system (@username)
- [ ] Notifications system
- [ ] Frontend templates
- [ ] Admin UI voor group types
- [ ] Review queue UI
- [ ] Reports queue UI
- [ ] Rate limiting & anti-spam
- [ ] Feature flags
- [ ] BuddyPress fallback functions
- [ ] Migration scripts (indien nodig)

## Support

Voor vragen of issues, contact Atypisch - Marten Timan.











