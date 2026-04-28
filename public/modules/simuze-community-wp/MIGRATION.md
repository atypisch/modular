# BuddyPress naar Simuze Community Migratie

## Stap 1: Deactiveer BuddyPress

1. Ga naar **Plugins** in WordPress admin
2. Deactiveer **BuddyPress**
3. (Optioneel) Verwijder BuddyPress plugin

## Stap 2: Activeer Simuze Community

1. Activeer de **Simuze Community** plugin
2. Database tabellen worden automatisch aangemaakt

## Stap 3: Update Theme Code

### Vervang BuddyPress functie calls

Zoek en vervang in theme bestanden:

#### User functies

```php
// OLD (BuddyPress)
bp_get_current_user_id()
bp_loggedin_user_id()
bp_displayed_user_id()
bp_core_get_user_displayname($user_id)
bp_core_fetch_avatar()

// NEW (Simuze)
Simuze_Helpers::get_current_user()->id
Simuze_Helpers::get_current_user()->id
$user->id
$user->display_name
$user->get_avatar_url()
```

#### Group functies

```php
// OLD (BuddyPress)
bp_get_current_group_id()
bp_get_group_name()
groups_get_groups()
groups_get_group_members()
bp_groups_get_group_type()

// NEW (Simuze)
$group->id
$group->name
Simuze_Group::get_by_slug($slug)
$group->get_members()
$group->get_group_type()
```

#### Activity functies

```php
// OLD (BuddyPress)
bp_activity_add()
bp_activity_get()

// NEW (Simuze)
Simuze_Activity::record_user_activity()
Simuze_Activity::get_user_feed()
```

## Stap 4: Update Templates

Vervang BuddyPress templates met Simuze shortcodes:

### User Profile

```php
// OLD
bp_core_load_template('members/single/home');

// NEW
echo do_shortcode('[simuze_profile username="' . $username . '"]');
```

### Group Page

```php
// OLD
bp_core_load_template('groups/single/home');

// NEW
echo do_shortcode('[simuze_group slug="' . $slug . '"]');
```

## Stap 5: Update Permalinks

Voeg rewrite rules toe in `functions.php`:

```php
add_action('init', function() {
    add_rewrite_rule('^user/([^/]+)/?$', 'index.php?simuze_user=$matches[1]', 'top');
    add_rewrite_rule('^group/([^/]+)/?$', 'index.php?simuze_group=$matches[1]', 'top');
});

add_filter('query_vars', function($vars) {
    $vars[] = 'simuze_user';
    $vars[] = 'simuze_group';
    return $vars;
});
```

## Stap 6: Test

1. Test user login via externe auth
2. Test group functionaliteit
3. Test activity feed
4. Test messaging
5. Test discography (bestaande functionaliteit moet blijven werken)

## Notities

- **Geen data migratie**: BuddyPress data wordt NIET gemigreerd. Start schoon.
- **Externe auth**: Alle users komen nu via Simezu External Auth API
- **Backwards compatibility**: Voeg fallback functies toe indien nodig











