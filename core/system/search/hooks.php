<?php
/**
 * System Module: Search — Hook registrations
 */

use Modular\Hooks;

// Index content after a post is saved
Hooks::addAction('post_saved', function ($postId, $postData) {
    // Search indexing can be extended via the search_index_post action
    do_action('search_index_post', $postId, $postData);
}, 10, 2);

// Remove from index when post is deleted
Hooks::addAction('post_deleted', function ($postId) {
    do_action('search_deindex_post', $postId);
});
