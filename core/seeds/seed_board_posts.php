<?php
/**
 * Seed script: insert 20 test board entries.
 *
 * Usage (from project root):
 *   php core/seeds/seed_board_posts.php
 *
 * Requires the Modular bootstrap so that ActivityPostRepository is available.
 */

define('SIMUZE_MODULAR_RUN', true);

$root = dirname(__DIR__, 2);

require_once $root . '/admin/config/config.php';
require_once $root . '/core/functions/database.php';
if (is_file($root . '/core/functions/common.php')) {
    require_once $root . '/core/functions/common.php';
}

$inc = $root . '/public/modules/simuze-community/includes/class-activity-post-repository.php';
if (is_file($inc)) {
    require_once $inc;
}
if (!class_exists('ActivityPostRepository')) {
    echo "ActivityPostRepository not available.\n";
    exit(1);
}

$simuze_group_id = function_exists('simuze_default_group_id') ? simuze_default_group_id() : null;
if (!$simuze_group_id) {
    echo "Could not resolve or create the default 'simuze' group.\n";
    exit(1);
}
echo "Using simuze group ID: {$simuze_group_id}\n";

$posts = [
    ['type' => 'musician_wanted',  'title' => 'Drummer needed for rock band in Amsterdam',           'city_name' => 'Amsterdam',  'body' => 'We rehearse weekly on Thursdays. Steady gig schedule.'],
    ['type' => 'open_slot',        'title' => 'Open slot at De Melkweg — March 29',                  'city_name' => 'Amsterdam',  'body' => 'One 30-min support slot available.'],
    ['type' => 'rehearsal_space',   'title' => 'Shared rehearsal room in Rotterdam',                  'city_name' => 'Rotterdam',  'body' => 'Available Tue/Thu evenings. Looking to share costs.'],
    ['type' => 'looking_for_band', 'title' => 'Singer-guitarist looking for band',                   'city_name' => 'Utrecht',    'body' => 'Indie/alternative. Experienced, own equipment.'],
    ['type' => 'general_request',  'title' => 'Recommendations for studio recording',                'city_name' => 'Amsterdam',  'body' => 'Budget around EUR 1500 for a 5-track EP.'],
    ['type' => 'musician_wanted',  'title' => 'Bass player for jazz trio',                           'city_name' => 'The Hague',  'body' => 'Jazz standards and originals. Reliable player needed.'],
    ['type' => 'open_slot',        'title' => 'Festival slot — Nijmegen DIY fest',                   'city_name' => 'Nijmegen',   'body' => 'Punk/indie preferred. One band slot open.'],
    ['type' => 'rehearsal_space',   'title' => 'Practice space near centrum',                        'city_name' => 'Amsterdam',  'body' => 'Drum kit included. 2x per week possible.'],
    ['type' => 'looking_for_band', 'title' => 'Keyboardist seeking synth-pop band',                  'city_name' => 'Eindhoven',  'body' => 'Classically trained, into synth pop and electronic.'],
    ['type' => 'general_request',  'title' => 'Where to find vintage amps in NL?',                   'city_name' => null,         'body' => 'Looking for a Vox AC30. Willing to travel.'],
    ['type' => 'musician_wanted',  'title' => 'Violinist for folk project',                          'city_name' => 'Amsterdam',  'body' => 'Acoustic folk — originals + covers.'],
    ['type' => 'open_slot',        'title' => 'House show in Groningen — one opener needed',         'city_name' => 'Groningen',  'body' => 'Intimate house show. 25 min acoustic set.'],
    ['type' => 'rehearsal_space',   'title' => 'Soundproofed basement studio Haarlem',               'city_name' => 'Haarlem',    'body' => 'Great for loud bands. Available weekends.'],
    ['type' => 'looking_for_band', 'title' => 'Electronic producer wants live band',                 'city_name' => 'Amsterdam',  'body' => 'I make electronic music, want to add live drums + bass.'],
    ['type' => 'musician_wanted',  'title' => 'Multi-instrumentalist for paid sessions',             'city_name' => 'Rotterdam',  'body' => 'Flexible studio sessions. Paid work.'],
    ['type' => 'general_request',  'title' => 'Best open mics in Amsterdam?',                        'city_name' => 'Amsterdam',  'body' => 'New in town, want to meet other musicians.'],
    ['type' => 'open_slot',        'title' => 'Support slot at Paradiso',                            'city_name' => 'Amsterdam',  'body' => 'Opening for touring band. Need established act.'],
    ['type' => 'rehearsal_space',   'title' => 'Jam room available in Leiden',                       'city_name' => 'Leiden',     'body' => 'Casual jam space. Bring your own gear.'],
    ['type' => 'looking_for_band', 'title' => 'Guitarist looking for metal band',                    'city_name' => 'Arnhem',     'body' => 'Thrash/death metal. Can also do vocals.'],
    ['type' => 'musician_wanted',  'title' => 'Horn section for soul band — spring gigs',            'city_name' => 'Amsterdam',  'body' => 'Need trumpet and sax. Gigs coming up.'],
];

$created = 0;
foreach ($posts as $i => $post) {
    $expires = [7, 14, 30][$i % 3];

    $id = ActivityPostRepository::create([
        'type'            => $post['type'],
        'author_type'     => 'group',
        'author_id'       => $simuze_group_id,
        'title'           => $post['title'],
        'city_name'       => $post['city_name'],
        'body'            => $post['body'],
        'expires_in_days' => $expires,
    ]);

    if ($id) {
        $created++;
        echo "  [{$created}] Created: {$post['title']}\n";
    } else {
        echo "  [!] Failed: {$post['title']}\n";
    }
}

echo "\nDone. {$created}/" . count($posts) . " board posts seeded.\n";
