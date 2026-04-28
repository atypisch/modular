<?php
/**
 * System Module: SEO — Hook registrations
 */

use Modular\Hooks;

// Inject default meta description from post excerpt/content when not explicitly set
Hooks::addFilter('seo_meta_description', function ($description, $post) {
    if (!empty($description)) {
        return $description;
    }
    if (is_array($post) && !empty($post['excerpt'])) {
        return mb_substr(strip_tags($post['excerpt']), 0, 160);
    }
    if (is_array($post) && !empty($post['content'])) {
        return mb_substr(strip_tags($post['content']), 0, 160);
    }
    return $description;
}, 10, 2);
