<?php
return [
    'title' => 'Theme development',
    'body' => <<<'MD'
**Location:** `public/themes/your-theme/`

**Required files:** `header.php`, `footer.php`, `index.php`, `single.php`, `archive.php`, `404.php`.

**Optional:** `single-{post_type}.php`, `archive-{post_type}.php`, `search.php`, `author.php`, `documentation.php`.

**Template hierarchy:** Single: `single-{type}.php` → `single.php`. Archive: `archive-{type}.php` → `archive.php`.

**Design system (default theme):** CSS variables in `style.css` (--gap, --radius, --shadow, --font-sans). Use `.container`, `.row`, `.col-*`, `.card`, `.btn`. Sticky header, mobile hamburger, slide-in nav.

**Security:** Always use `e()` for dynamic output. Do not echo raw user content without escaping.
MD
,
];
