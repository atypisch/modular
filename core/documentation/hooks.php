<?php
return [
    'title' => 'Hooks',
    'body' => <<<'MD'
Modular uses a WordPress-style hooks system (actions and filters).

**Actions:** `do_action('name', $arg1, $arg2)` – run callbacks.  
**Filters:** `apply_filters('name', $value, $arg1)` – return filtered value.

**Common actions:**
- `init` – after bootstrap
- `module_activate`, `module_deactivate` – module lifecycle
- `admin_menu_items` – filter to add admin menu items

**Common filters:**
- `admin_menu_items` – filter menu items
- `public_route` – filter public route response
- `public_fixed_routes` – filter fixed route map

**Register:** `add_action('hook_name', callable)` or `add_filter('hook_name', callable, priority?, arity?)`.
MD
,
];
