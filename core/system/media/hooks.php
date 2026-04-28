<?php
/**
 * System Module: Media — Hook registrations
 */

use Modular\Hooks;

// Validate mime type on upload
Hooks::addFilter('upload_allowed_mime_types', function ($types) {
    if (!is_array($types)) {
        $types = [];
    }
    // Merge default safe mime types if not already set
    $defaults = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
        'application/pdf',
        'video/mp4', 'video/webm',
        'audio/mpeg', 'audio/ogg', 'audio/wav',
    ];
    foreach ($defaults as $type) {
        if (!in_array($type, $types, true)) {
            $types[] = $type;
        }
    }
    return $types;
});
