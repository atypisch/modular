<?php
/**
 * Modular CMS — Build Script
 *
 * Generates a production release ZIP from the current source tree.
 *
 * Usage (CLI):
 *   php core/build/build.php
 *
 * Output:
 *   /release/modular-{version}.zip
 *
 * Steps:
 *   1. Read /core/build/build.json  (single source of truth for versions)
 *   2. Collect /core, /admin, /public
 *   3. Exclude dev files: /docs, /.git, /node_modules, /release
 *   4. Write to /release/modular-{version}/
 *   5. Zip to /release/modular-{version}.zip
 */

declare(strict_types=1);

// Only run from CLI
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This script must be run from the command line.\n");
}

$rootDir   = dirname(__DIR__, 2); // /core/build/build.php → two levels up = project root
$buildFile = __DIR__ . '/build.json'; // /core/build/build.json

// ---- 1. Read build.json -------------------------------------------------
if (!is_readable($buildFile)) {
    fwrite(STDERR, "ERROR: core/build/build.json not found.\n");
    exit(1);
}

$build = json_decode(file_get_contents($buildFile), true);
if (!is_array($build) || empty($build['version'])) {
    fwrite(STDERR, "ERROR: core/build/build.json is invalid or missing 'version'.\n");
    exit(1);
}

$version   = $build['version'];
$buildDate = $build['build_date'] ?? date('Y-m-d');

echo "Building Modular CMS v{$version} ({$buildDate})\n";

// ---- 2. Prepare release directory ---------------------------------------
$releaseDir  = $rootDir . '/release';
$releaseName = "modular-{$version}";
$releaseBase = $releaseDir . '/' . $releaseName;
$releaseZip  = $releaseDir . '/' . $releaseName . '.zip';

if (!is_dir($releaseDir)) {
    mkdir($releaseDir, 0755, true);
}

// Remove stale release folder if it exists
if (is_dir($releaseBase)) {
    remove_dir($releaseBase);
}
mkdir($releaseBase, 0755, true);

// ---- 3. Define include / exclude lists ----------------------------------
$includeDirs = [
    'admin',
    'core',
    'public',
    'build',
    'index.php',
    '.htaccess',
];

$excludePatterns = [
    // directories
    '.git',
    'node_modules',
    'release',
    'docs',
    'scripts',
    // dev artefacts
    '.DS_Store',
    'Thumbs.db',
    '*.log',
    'storage/logs',
    'storage/cache',
    'storage/backups',
    // local config
    'config.local.php',
];

// ---- 4. Copy to release folder ------------------------------------------
foreach ($includeDirs as $item) {
    $src = $rootDir . '/' . $item;
    $dst = $releaseBase . '/' . $item;

    if (is_dir($src)) {
        copy_dir($src, $dst, $excludePatterns);
        echo "  Copied dir:  {$item}/\n";
    } elseif (is_file($src)) {
        copy($src, $dst);
        echo "  Copied file: {$item}\n";
    } else {
        echo "  Skipped (not found): {$item}\n";
    }
}

// ---- 5. Update build_date in the release copy ---------------------------
$releaseBuildFile = $releaseBase . '/core/build/build.json';
if (is_readable($releaseBuildFile)) {
    $releaseBuild = json_decode(file_get_contents($releaseBuildFile), true);
    $releaseBuild['build_date'] = date('Y-m-d');
    file_put_contents($releaseBuildFile, json_encode($releaseBuild, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
}

// ---- 6. Create ZIP -------------------------------------------------------
if (!class_exists('ZipArchive')) {
    fwrite(STDERR, "ERROR: PHP ZipArchive extension is required.\n");
    exit(1);
}

if (file_exists($releaseZip)) {
    unlink($releaseZip);
}

$zip = new ZipArchive();
if ($zip->open($releaseZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    fwrite(STDERR, "ERROR: Could not create ZIP file at {$releaseZip}\n");
    exit(1);
}

add_dir_to_zip($zip, $releaseBase, $releaseName);
$zip->close();

echo "\nBuild complete:\n";
echo "  Folder: {$releaseBase}\n";
echo "  ZIP:    {$releaseZip}\n";

// Clean up release folder (keep only the zip)
remove_dir($releaseBase);

exit(0);

// ---- Helper functions ---------------------------------------------------

/**
 * Recursively copy a directory, skipping excluded paths.
 */
function copy_dir(string $src, string $dst, array $excludePatterns): void {
    if (!is_dir($dst)) {
        mkdir($dst, 0755, true);
    }

    $items = array_diff(scandir($src), ['.', '..']);
    foreach ($items as $item) {
        if (is_excluded($item, $src . '/' . $item, $excludePatterns)) {
            continue;
        }
        $s = $src . '/' . $item;
        $d = $dst . '/' . $item;
        if (is_dir($s)) {
            copy_dir($s, $d, $excludePatterns);
        } else {
            copy($s, $d);
        }
    }
}

/**
 * Check whether a path matches any exclude pattern.
 */
function is_excluded(string $name, string $fullPath, array $excludePatterns): bool {
    foreach ($excludePatterns as $pattern) {
        // Exact name match
        if ($name === $pattern) {
            return true;
        }
        // Glob-style wildcard on name
        if (fnmatch($pattern, $name)) {
            return true;
        }
        // Partial path segment match
        if (strpos($fullPath, DIRECTORY_SEPARATOR . $pattern) !== false) {
            return true;
        }
    }
    return false;
}

/**
 * Recursively add a directory to a ZipArchive.
 */
function add_dir_to_zip(ZipArchive $zip, string $dir, string $zipBase): void {
    $items = array_diff(scandir($dir), ['.', '..']);
    foreach ($items as $item) {
        $fullPath  = $dir . '/' . $item;
        $localPath = $zipBase . '/' . $item;
        if (is_dir($fullPath)) {
            $zip->addEmptyDir($localPath);
            add_dir_to_zip($zip, $fullPath, $localPath);
        } else {
            $zip->addFile($fullPath, $localPath);
        }
    }
}

/**
 * Recursively remove a directory.
 */
function remove_dir(string $dir): void {
    if (!is_dir($dir)) {
        return;
    }
    $items = array_diff(scandir($dir), ['.', '..']);
    foreach ($items as $item) {
        $path = $dir . '/' . $item;
        if (is_dir($path)) {
            remove_dir($path);
        } else {
            unlink($path);
        }
    }
    rmdir($dir);
}
