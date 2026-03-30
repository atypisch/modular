<?php
/**
 * LocationAvatar — generates and caches permanent avatar images for location groups.
 *
 * - Country groups → flag image (50 % opacity on black background)
 * - Province / city groups → dark Carto Dark Matter map-tile composite (matches Network map)
 *
 * Generated files are stored in /public/uploads/simuze-groups/ and the URL is
 * written back to simuze_groups.avatar_url so the image is never regenerated.
 */

if (!defined('SIMUZE_MODULAR_RUN') && !defined('ABSPATH')) {
    exit;
}

class LocationAvatar {

    const SIZE = 320; // output image width/height in pixels

    // -----------------------------------------------------------------------
    // Public API
    // -----------------------------------------------------------------------

    /**
     * Return the avatar URL for a location group.
     * Generates and saves the image if it does not yet exist on disk.
     *
     * @param  array  $group  Row from simuze_groups (with type_slug key added by JOIN).
     * @return string         Absolute URL to the avatar image (may be empty on failure).
     */
    public static function get_or_generate(array $group): string
    {
        $type = $group['type_slug'] ?? '';

        if ($type === 'country') {
            return self::country_avatar($group);
        }

        if (in_array($type, ['province', 'city'], true)) {
            return self::map_avatar($group);
        }

        return '';
    }

    // -----------------------------------------------------------------------
    // Internal helpers
    // -----------------------------------------------------------------------

    /**
     * Build the upload directory path (creates it if needed).
     */
    private static function avatar_dir(): string
    {
        $dir = rtrim(PUBLIC_PATH, '/') . '/uploads/simuze-groups/';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        return $dir;
    }

    /**
     * Build the base URL for the upload directory.
     */
    private static function avatar_base_url(): string
    {
        return rtrim(md_home_url('/public/uploads/simuze-groups'), '/') . '/';
    }

    // -----------------------------------------------------------------------
    // Country flag avatar
    // -----------------------------------------------------------------------

    /**
     * Create a 320×320 flag image at 50 % opacity on a black background.
     */
    private static function country_avatar(array $group): string
    {
        $id       = (int) $group['id'];
        $filename = "location_country_{$id}.jpg";
        $path     = self::avatar_dir() . $filename;
        $url      = self::avatar_base_url() . $filename;

        // Already generated
        if (is_file($path)) {
            return $url;
        }

        $iso = self::resolve_iso($group);
        if (!$iso) {
            return '';
        }

        // Fetch flag PNG from flagcdn.com
        $flag_data = self::http_get("https://flagcdn.com/w320/{$iso}.png");
        if (!$flag_data) {
            return '';
        }

        $flag_img = @imagecreatefromstring($flag_data);
        if (!$flag_img) {
            return '';
        }

        $size   = self::SIZE;
        $canvas = imagecreatetruecolor($size, $size);

        // Black background
        $black = imagecolorallocate($canvas, 0, 0, 0);
        imagefill($canvas, 0, 0, $black);

        // Scale flag to fill the canvas
        $fw = imagesx($flag_img);
        $fh = imagesy($flag_img);
        $flag_scaled = imagecreatetruecolor($size, $size);
        imagecopyresampled($flag_scaled, $flag_img, 0, 0, 0, 0, $size, $size, $fw, $fh);
        imagedestroy($flag_img);

        // Blend at 50 % opacity onto black canvas
        imagecopymerge($canvas, $flag_scaled, 0, 0, 0, 0, $size, $size, 50);
        imagedestroy($flag_scaled);

        $ok = imagejpeg($canvas, $path, 88);
        imagedestroy($canvas);

        if (!$ok) {
            return '';
        }

        // Persist URL to DB
        self::update_db($id, $url);

        return $url;
    }

    // -----------------------------------------------------------------------
    // OSM map tile avatar (province / city)
    // -----------------------------------------------------------------------

    /** Carto Dark Matter — same basemap as public/themes/simuze/template_network.php Leaflet layer. */
    private const MAP_TILE_BASE = 'https://a.basemaps.cartocdn.com/dark_all';

    /**
     * Stitch a 3×3 map tile grid into a dark square avatar (Carto dark_all, B/W-style).
     */
    private static function map_avatar(array $group): string
    {
        $id       = (int) $group['id'];
        $type     = $group['type_slug'] ?? 'city';
        // Suffix bumps cache when tile source/style changes (was plain OSM color tiles).
        $filename = "location_{$type}_{$id}_carto_dark.jpg";
        $path     = self::avatar_dir() . $filename;
        $url      = self::avatar_base_url() . $filename;

        if (is_file($path)) {
            return $url;
        }

        $lat = (float) ($group['lat'] ?? 0);
        $lng = (float) ($group['lng'] ?? 0);

        if (!$lat && !$lng) {
            return '';
        }

        $zoom   = ($type === 'province') ? 9 : 12;
        $tile_x = self::lng_to_tile_x($lng, $zoom);
        $tile_y = self::lat_to_tile_y($lat, $zoom);

        // Build 3×3 grid: tiles from (tx-1, ty-1) to (tx+1, ty+1)
        $tile_size    = 256;
        $grid         = 3;
        $canvas_size  = $tile_size * $grid;    // 768
        $canvas       = imagecreatetruecolor($canvas_size, $canvas_size);

        $max_tile = (int) (2 ** $zoom) - 1;
        $success  = false;

        for ($dy = 0; $dy < $grid; $dy++) {
            for ($dx = 0; $dx < $grid; $dx++) {
                $tx = max(0, min($max_tile, $tile_x - 1 + $dx));
                $ty = max(0, min($max_tile, $tile_y - 1 + $dy));

                $tile_url  = self::MAP_TILE_BASE . "/{$zoom}/{$tx}/{$ty}.png";
                $tile_data = self::http_get($tile_url, ['User-Agent: Simuze/1.0 (https://simuze.com)']);

                if (!$tile_data) {
                    continue;
                }

                $tile_img = @imagecreatefromstring($tile_data);
                if (!$tile_img) {
                    continue;
                }

                imagecopy($canvas, $tile_img, $dx * $tile_size, $dy * $tile_size, 0, 0, $tile_size, $tile_size);
                imagedestroy($tile_img);
                $success = true;
            }
        }

        if (!$success) {
            imagedestroy($canvas);
            return '';
        }

        // Strong monochrome read (map is already dark; this matches Simuze B/W map aesthetic).
        if (function_exists('imagefilter')) {
            @imagefilter($canvas, IMG_FILTER_GRAYSCALE);
        }

        // Dark overlay: fill black rectangle at ~50 % alpha (imagefilter GD approach)
        // GD alpha: 0 = fully opaque, 127 = fully transparent
        $overlay = imagecolorallocatealpha($canvas, 0, 0, 0, 64); // ~50% dark
        imagefilledrectangle($canvas, 0, 0, $canvas_size - 1, $canvas_size - 1, $overlay);

        // Resize to 320×320
        $out = imagecreatetruecolor(self::SIZE, self::SIZE);
        imagecopyresampled($out, $canvas, 0, 0, 0, 0, self::SIZE, self::SIZE, $canvas_size, $canvas_size);
        imagedestroy($canvas);

        $ok = imagejpeg($out, $path, 88);
        imagedestroy($out);

        if (!$ok) {
            return '';
        }

        self::update_db($id, $url);

        return $url;
    }

    // -----------------------------------------------------------------------
    // OSM tile math (Web Mercator)
    // -----------------------------------------------------------------------

    private static function lng_to_tile_x(float $lng, int $zoom): int
    {
        return (int) floor(($lng + 180) / 360 * (2 ** $zoom));
    }

    private static function lat_to_tile_y(float $lat, int $zoom): int
    {
        $lat_rad = deg2rad($lat);
        return (int) floor((1 - log(tan($lat_rad) + 1 / cos($lat_rad)) / M_PI) / 2 * (2 ** $zoom));
    }

    // -----------------------------------------------------------------------
    // ISO code resolution
    // -----------------------------------------------------------------------

    private static function resolve_iso(array $group): string
    {
        // 1. Check settings JSON field
        if (!empty($group['settings'])) {
            $settings = is_string($group['settings']) ? json_decode($group['settings'], true) : $group['settings'];
            if (!empty($settings['iso_code'])) {
                return strtolower(trim($settings['iso_code']));
            }
        }

        // 2. Normalise group name and look up in static map
        $name = strtolower(trim($group['name'] ?? ''));
        $map  = self::iso_map();

        if (isset($map[$name])) {
            return $map[$name];
        }

        // Partial match (e.g. "The Netherlands" → "netherlands")
        foreach ($map as $key => $iso) {
            if (str_contains($name, $key) || str_contains($key, $name)) {
                return $iso;
            }
        }

        return '';
    }

    private static function iso_map(): array
    {
        return [
            'afghanistan'            => 'af',
            'albania'                => 'al',
            'algeria'                => 'dz',
            'argentina'              => 'ar',
            'australia'              => 'au',
            'austria'                => 'at',
            'belgium'                => 'be',
            'brazil'                 => 'br',
            'canada'                 => 'ca',
            'chile'                  => 'cl',
            'china'                  => 'cn',
            'colombia'               => 'co',
            'croatia'                => 'hr',
            'czech republic'         => 'cz',
            'denmark'                => 'dk',
            'egypt'                  => 'eg',
            'finland'                => 'fi',
            'france'                 => 'fr',
            'germany'                => 'de',
            'greece'                 => 'gr',
            'hungary'                => 'hu',
            'india'                  => 'in',
            'indonesia'              => 'id',
            'ireland'                => 'ie',
            'israel'                 => 'il',
            'italy'                  => 'it',
            'japan'                  => 'jp',
            'mexico'                 => 'mx',
            'netherlands'            => 'nl',
            'the netherlands'        => 'nl',
            'new zealand'            => 'nz',
            'norway'                 => 'no',
            'pakistan'               => 'pk',
            'peru'                   => 'pe',
            'philippines'            => 'ph',
            'poland'                 => 'pl',
            'portugal'               => 'pt',
            'romania'                => 'ro',
            'russia'                 => 'ru',
            'saudi arabia'           => 'sa',
            'south africa'           => 'za',
            'south korea'            => 'kr',
            'spain'                  => 'es',
            'sweden'                 => 'se',
            'switzerland'            => 'ch',
            'turkey'                 => 'tr',
            'ukraine'                => 'ua',
            'united arab emirates'   => 'ae',
            'united kingdom'         => 'gb',
            'united states'          => 'us',
            'usa'                    => 'us',
            'uk'                     => 'gb',
        ];
    }

    // -----------------------------------------------------------------------
    // DB update
    // -----------------------------------------------------------------------

    private static function update_db(int $group_id, string $url): void
    {
        if (function_exists('md_db_update')) {
            md_db_update('simuze_groups', ['avatar_url' => $url], ['id' => $group_id]);
        }
    }

    // -----------------------------------------------------------------------
    // HTTP helper
    // -----------------------------------------------------------------------

    /**
     * Simple HTTP GET using file_get_contents with optional headers.
     * Returns raw body string or false on failure.
     */
    private static function http_get(string $url, array $headers = []): string|false
    {
        $default_headers = ['User-Agent: Simuze/1.0 (+https://simuze.com)'];
        $all_headers     = array_merge($default_headers, $headers);

        $context = stream_context_create([
            'http' => [
                'method'     => 'GET',
                'header'     => implode("\r\n", $all_headers),
                'timeout'    => 8,
                'ignore_errors' => true,
            ],
            'ssl' => [
                'verify_peer'      => true,
                'verify_peer_name' => true,
            ],
        ]);

        $data = @file_get_contents($url, false, $context);
        return ($data !== false && strlen($data) > 100) ? $data : false;
    }
}
