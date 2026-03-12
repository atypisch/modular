<?php
/**
 * SimuzeKernel – primary app layer when ACTIVE_THEME === 'simuze'.
 * Handles high-priority routing for Simuze-specific flows before core router.
 *
 * NOTE: This currently delegates to existing simuze-community `public_route`
 * handlers (which already wrap theme header/footer for their pages).
 * Further phases can extend this kernel to own more routes.
 */

if (!class_exists('SimuzeKernel')) {
    class SimuzeKernel
    {
        /**
         * Handle the current request.
         *
         * @param array $server Superglobal $_SERVER
         * @return string|null Full HTML response or null to let core continue.
         */
        public static function handle(array $server)
        {
            if (!defined('ACTIVE_THEME') || ACTIVE_THEME !== 'simuze') {
                return null;
            }
            if (!function_exists('apply_filters')) {
                return null;
            }

            // Ensure the simuze-community module (and its public_route handlers) is loaded.
            if (defined('MODULES_PATH')) {
                $moduleFile = MODULES_PATH . '/simuze-community/module.php';
                if (is_file($moduleFile)) {
                    require_once $moduleFile;
                }
            }

            // Initialize auth context (SIMEZU-based) for all Simuze requests
            $currentUser = null;
            if (class_exists('Simuze_Auth')) {
                try {
                    $auth = Simuze_Auth::get_instance();
                    $currentUser = $auth->get_current_user();
                } catch (\Throwable $e) {
                    $currentUser = null;
                }
            }
            $GLOBALS['current_user']   = $currentUser;
            $GLOBALS['is_logged_in']   = $currentUser !== null;
            $GLOBALS['is_own_profile'] = false;
            $GLOBALS['user_can_edit']  = false;

            // Normalise path info
            $pathInfo = isset($server['PATH_INFO']) ? trim($server['PATH_INFO'], '/') : '';
            if ($pathInfo === '' && isset($server['REQUEST_URI'])) {
                $req = explode('?', $server['REQUEST_URI'], 2)[0];
                $base = defined('BASE_PATH') ? rtrim(BASE_PATH, '/') : '';
                if ($base !== '' && strpos($req, $base) === 0) {
                    $pathInfo = trim(substr($req, strlen($base)), '/');
                } elseif ($base === '' && $req !== '') {
                    $pathInfo = trim($req, '/');
                }
            }

            // Redirect if path has trailing }} (e.g. from Simezu dashboardUrl placeholder bug)
            if ($pathInfo !== '' && (substr($pathInfo, -2) === '}}' || strpos($pathInfo, '}}') !== false)) {
                $cleanPath = preg_replace('/\}\}+$/', '', $pathInfo);
                $cleanPath = str_replace('}}', '', $cleanPath);
                if (function_exists('home_url')) {
                    header('Location: ' . home_url('/' . $cleanPath), true, 302);
                    exit;
                }
            }

            // Simuze community routes get first chance (include music for /music/album/{slug})
            $communityPrefixes = ['profile', 'user', 'dashboard', 'group', 'groups', 'messages', 'discography', 'activity', 'friends', 'music'];
            $firstSegment = $pathInfo !== '' ? trim(explode('/', $pathInfo)[0], '/') : '';

            // Compute basic profile context flags
            if ($firstSegment === 'user') {
                $parts = explode('/', $pathInfo);
                $username = $parts[1] ?? '';
                if ($currentUser && $username !== '' && isset($currentUser->username)) {
                    $GLOBALS['is_own_profile'] = (strcasecmp($currentUser->username, $username) === 0);
                }
            } elseif ($firstSegment === 'profile' || $firstSegment === 'dashboard') {
                // /profile and /dashboard are always "own profile" when logged in
                $GLOBALS['is_own_profile'] = $currentUser !== null;
            }
            $GLOBALS['user_can_edit'] = $GLOBALS['is_own_profile'];

            // Centralized auth middleware — routes that always require authentication.
            // Per-template gates still exist as defence-in-depth; this avoids rendering
            // protected templates at all when no valid session is present.
            $alwaysAuthRoutes = ['dashboard', 'messages', 'friends'];
            $profileAlwaysAuth = ($firstSegment === 'profile');
            $needsAuth = in_array($firstSegment, $alwaysAuthRoutes, true) || $profileAlwaysAuth;

            if ($needsAuth && !$GLOBALS['is_logged_in']) {
                if (function_exists('simezu_require_login')) {
                    simezu_require_login();
                }
                return '';
            }

            if ($firstSegment !== '' && in_array($firstSegment, $communityPrefixes, true)) {
                $response = apply_filters('public_route', null, $pathInfo);
                if ($response !== null && $response !== '') {
                    return $response;
                }
            }

            // No Simuze-specific handling; let core router continue.
            return null;
        }
    }
}

