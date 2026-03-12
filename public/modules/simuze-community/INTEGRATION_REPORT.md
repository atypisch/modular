# Simuze Community - Simezu Auth Integration Report

## Overzicht

Dit document beschrijft de volledige integratie tussen **Simuze** (WordPress community platform) en **Simezu** (externe authenticatie server). De integratie maakt het mogelijk om gebruikers te authenticeren via Simezu terwijl alle community-functionaliteit in Simuze draait.

## Architectuur

```
┌─────────────────────────────────────────────────────────────────┐
│                         SIMEZU (Auth Server)                     │
│                     http://localhost/simezu                      │
│                                                                  │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐              │
│  │   Users     │  │   Roles     │  │   Sessions  │              │
│  │   Table     │  │   Table     │  │   Table     │              │
│  └─────────────┘  └─────────────┘  └─────────────┘              │
│                                                                  │
│  API Endpoints:                                                  │
│  - POST /auth/login      → Returns JWT token                    │
│  - POST /auth/register   → Creates user                         │
│  - GET  /auth/me         → Returns user data                    │
│  - POST /auth/logout     → Invalidates session                  │
└─────────────────────────────────────────────────────────────────┘
                              │
                              │ JWT Token (via Cookie)
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                    SIMUZE (WordPress Community)                  │
│                    http://localhost/simuze                       │
│                                                                  │
│  ┌─────────────────────────────────────────────────────────────┐│
│  │                  simuze-community Plugin                     ││
│  │                                                              ││
│  │  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐       ││
│  │  │ Simuze_Auth  │  │ Simuze_User  │  │ Simuze_Group │       ││
│  │  │ (JWT verify) │  │ (local copy) │  │ (community)  │       ││
│  │  └──────────────┘  └──────────────┘  └──────────────┘       ││
│  │                                                              ││
│  │  Database Tables:                                            ││
│  │  - wp_simuze_users (simezu_id → local mapping)              ││
│  │  - wp_simuze_profiles                                        ││
│  │  - wp_simuze_groups                                          ││
│  │  - wp_simuze_follows                                         ││
│  │  - wp_simuze_friends                                         ││
│  │  - wp_simuze_messages                                        ││
│  │  - wp_simuze_activity                                        ││
│  └─────────────────────────────────────────────────────────────┘│
└─────────────────────────────────────────────────────────────────┘
```

## Authenticatie Flow

### 1. Login Flow

```
1. User klikt op "Login" in Simuze
2. Redirect naar Simezu login pagina: http://localhost/simezu/login.php?redirect=...
3. User logt in op Simezu
4. Simezu zet JWT token in cookie (accessToken)
5. Redirect terug naar Simuze met ?logged_in=1
6. Simuze leest cookie, verifieert JWT lokaal
7. User wordt aangemaakt/bijgewerkt in wp_simuze_users
8. Redirect naar /profile/ dashboard
```

### 2. Token Verificatie

De JWT token wordt op twee manieren geverifieerd:

**Lokaal (primair):**
```php
// Simuze_Auth::verify_jwt_locally()
- Decode JWT header + payload
- Verify HMAC-SHA256 signature met shared secret
- Check expiration
- Return user data
```

**Via API (fallback):**
```php
// Simuze_Auth::verify_token_via_api()
GET http://localhost/simezu/api.php/auth/me
Authorization: Bearer {token}
```

### 3. Cookie Structuur

| Cookie Name | Beschrijving | Expiry |
|-------------|--------------|--------|
| `accessToken` | JWT access token (shared met Simezu) | 24 uur |
| `refreshToken` | Refresh token voor token renewal | 90 dagen |
| `simuze_jwt_token` | Legacy cookie (backwards compat) | 30 dagen |

## Geïmplementeerde Features

### User Profile (`/profile/`)

| Tab | Functionaliteit |
|-----|-----------------|
| **Dashboard** | Welkomstbericht, stats (groups, friends, followers, following), quick actions |
| **Playlists** | Bestaande playlist functionaliteit (behouden) |
| **Upload** | Music upload naar groups |
| **Activity** | Persoonlijke activity feed |
| **Friends** | Friend requests, accept/deny, unfriend |
| **Groups** | Member groups, following groups, invitations |
| **Messages** | Personal messaging system |
| **Settings** | Profile settings, avatar upload |

### Group Page (`/group/{slug}/`)

| Tab | Functionaliteit |
|-----|-----------------|
| **Dashboard** | Group info, recent activity, members preview |
| **Music** | Discography met sub-tabs: Overview, Albums, Singles, Scheduled, Upload |
| **Members** | Ledenlijst met roles (admin, mod, member) |
| **Activity** | Group activity feed |
| **Invites** | (Admin) Invite management |
| **Settings** | (Admin) Group settings |

### Social Features

#### Follow System
```php
Simuze_Follow::follow($user_id, 'user', $target_id)
Simuze_Follow::follow($user_id, 'group', $group_id)
Simuze_Follow::unfollow(...)
Simuze_Follow::is_following(...)
Simuze_Follow::count_followers(...)
Simuze_Follow::count_following(...)
```

#### Friends System
```php
Simuze_Friend::send_request($user_id, $friend_id)
Simuze_Friend::accept_request(...)
Simuze_Friend::reject_request(...)
Simuze_Friend::unfriend(...)
Simuze_Friend::are_friends(...)
Simuze_Friend::get_pending_requests(...)
```

#### @Mentions System
```javascript
// Automatisch geactiveerd op elementen met:
// data-mentions="true" of class="mentions-enabled"

SimuzeMentions.parseMentions(text)  // Returns array of usernames
SimuzeMentions.linkifyMentions(text) // Returns HTML with links
```

## Database Schema

### wp_simuze_users
```sql
CREATE TABLE wp_simuze_users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    simezu_id VARCHAR(255) NOT NULL UNIQUE,  -- Link naar Simezu
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    display_name VARCHAR(255),
    avatar_url VARCHAR(500),
    status VARCHAR(20) DEFAULT 'active',
    created_at DATETIME,
    updated_at DATETIME,
    deleted_at DATETIME
);
```

### wp_simuze_follows
```sql
CREATE TABLE wp_simuze_follows (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    follower_id BIGINT UNSIGNED NOT NULL,
    followable_type VARCHAR(20) NOT NULL,  -- 'user' of 'group'
    followable_id BIGINT UNSIGNED NOT NULL,
    created_at DATETIME
);
```

### wp_simuze_friends
```sql
CREATE TABLE wp_simuze_friends (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    friend_id BIGINT UNSIGNED NOT NULL,
    status VARCHAR(20) DEFAULT 'pending',  -- pending, accepted
    requested_by BIGINT UNSIGNED,
    created_at DATETIME,
    updated_at DATETIME
);
```

## Configuratie

### WordPress Admin → Simuze Community → Settings

| Setting | Default | Beschrijving |
|---------|---------|--------------|
| Simezu Auth API URL | `http://localhost/simezu/api.php/auth` | API endpoint |
| Simezu Login URL | `http://localhost/simezu/login.php` | Login pagina |
| Simezu Register URL | `http://localhost/simezu/register.php` | Registratie pagina |
| JWT Secret | `simezu-auth-secret-key-...` | Shared secret voor JWT verificatie |

### Simezu .env
```
JWT_SECRET=simezu-auth-secret-key-change-in-production-2024
```

## REST API Endpoints

### Users
```
GET    /wp-json/simuze/v1/me              - Current user
GET    /wp-json/simuze/v1/users           - List users
GET    /wp-json/simuze/v1/users/{id}      - Get user
PUT    /wp-json/simuze/v1/users/{id}      - Update user
POST   /wp-json/simuze/v1/users/{id}/avatar - Upload avatar
GET    /wp-json/simuze/v1/users/search    - Search users (@mentions)
```

### Groups
```
GET    /wp-json/simuze/v1/groups          - List groups
POST   /wp-json/simuze/v1/groups          - Create group
GET    /wp-json/simuze/v1/groups/{id}     - Get group
PUT    /wp-json/simuze/v1/groups/{id}     - Update group
POST   /wp-json/simuze/v1/groups/{id}/join - Join group
POST   /wp-json/simuze/v1/groups/{id}/leave - Leave group
POST   /wp-json/simuze/v1/groups/{id}/invite - Invite user
```

### Follows
```
POST   /wp-json/simuze/v1/follow          - Follow user/group
DELETE /wp-json/simuze/v1/unfollow        - Unfollow user/group
```

### Friends
```
POST   /wp-json/simuze/v1/friends/request - Send friend request
POST   /wp-json/simuze/v1/friends/accept  - Accept request
POST   /wp-json/simuze/v1/friends/reject  - Reject request
DELETE /wp-json/simuze/v1/friends/{id}    - Unfriend
```

## JavaScript Globals

```javascript
// Auth status
window.SimuzeAuth.isLoggedIn     // Boolean
window.SimuzeAuth.currentUser    // User object of null
window.SimuzeAuth.login()        // Redirect to login
window.SimuzeAuth.logout()       // Logout and redirect
window.SimuzeAuth.goToProfile()  // Go to profile

// API
window.simuzeAPI.request(endpoint, method, data)

// Mentions
window.SimuzeMentions.parseMentions(text)
window.SimuzeMentions.linkifyMentions(text)

// Config
window.simuzeCommunity.apiUrl
window.simuzeCommunity.nonce
window.simuzeCommunity.currentUser
window.simuzeCommunity.loginUrl
window.simuzeCommunity.registerUrl
```

## PHP Globals

```php
// Current user
$GLOBALS['simuze_current_user']  // Simuze_User object of null

// Auth helper
Simuze_Auth::get_instance()->get_current_user()
Simuze_Auth::get_instance()->is_logged_in()
Simuze_Auth::get_instance()->is_admin()
Simuze_Auth::get_instance()->get_login_url()
Simuze_Auth::get_instance()->get_register_url()
```

## Fixes Toegepast

### Simezu RoleService sort_order Error
**Probleem:** `Unknown column 'sort_order' in 'order clause'`

**Oplossing:** De `RoleService::getAll()` methode checkt nu of de `sort_order` kolom bestaat voordat deze wordt gebruikt:

```php
private function ensureSortOrderColumn() {
    $columns = $this->db->fetchAll("SHOW COLUMNS FROM roles LIKE 'sort_order'");
    if (empty($columns)) {
        // Add column if missing
        $this->db->query("ALTER TABLE roles ADD COLUMN sort_order INT NOT NULL DEFAULT 0");
    }
}
```

## Schaalbaarheid (Miljoenen Gebruikers)

De architectuur is ontworpen voor grote schaal:

1. **Externe Auth**: Simezu handelt alle authenticatie af, Simuze doet alleen lokale JWT verificatie
2. **Caching**: JWT tokens worden lokaal geverifieerd zonder API calls
3. **Database Indexen**: Alle foreign keys en lookup velden zijn geïndexeerd
4. **Soft Deletes**: Gebruikers worden niet fysiek verwijderd
5. **Pagination**: Alle list endpoints ondersteunen pagination

## Testing Checklist

- [ ] Login via Simezu → redirect naar Simuze profile
- [ ] Logout → cookies worden gewist
- [ ] Profile dashboard toont correcte stats
- [ ] Follow user werkt
- [ ] Follow group werkt
- [ ] Friend request versturen
- [ ] Friend request accepteren
- [ ] @mentions autocomplete werkt
- [ ] Group join/leave werkt
- [ ] Group invitations werken
- [ ] Music upload naar group werkt
- [ ] Avatar upload werkt

## Volgende Stappen

1. **Real-time Notifications**: WebSocket integratie voor live updates
2. **Email Notifications**: Transactionele emails voor friend requests, mentions, etc.
3. **Rate Limiting**: API rate limiting voor spam preventie
4. **Activity Aggregation**: Efficiëntere activity feed queries
5. **Search**: Full-text search voor users, groups, music

---

*Gegenereerd: <?php echo date('Y-m-d H:i:s'); ?>*











