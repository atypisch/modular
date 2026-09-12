<?php
/**
 * Shared helpers for Atelier auth pages (login / register / forgot / reset).
 */

if (!function_exists('md_auth_url')) {
    function md_auth_url(string $file): string
    {
        $base = defined('ADMIN_URL') ? rtrim((string) ADMIN_URL, '/') : '/modular/admin';
        return $base . '/auth/' . ltrim($file, '/');
    }
}

if (!function_exists('md_auth_registration_allowed')) {
    function md_auth_registration_allowed(): bool
    {
        if (!isset($GLOBALS['db'])) return false;
        try {
            $stmt = $GLOBALS['db']->prepare("SELECT option_value FROM settings WHERE option_name = 'allow_registration' LIMIT 1");
            $stmt->execute();
            return in_array((string) $stmt->fetchColumn(), ['1', 'true', 'on', 'yes'], true);
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('md_auth_flash')) {
    /** Render and clear session flash messages. */
    function md_auth_flash(): string
    {
        $html = '';
        if (!empty($_SESSION['error'])) {
            $html .= '<div class="alert alert-error">' . htmlspecialchars((string) $_SESSION['error']) . '</div>';
            unset($_SESSION['error']);
        }
        if (!empty($_SESSION['success'])) {
            $html .= '<div class="alert alert-success">' . htmlspecialchars((string) $_SESSION['success']) . '</div>';
            unset($_SESSION['success']);
        }
        return $html;
    }
}

if (!function_exists('md_auth_mark_svg')) {
    function md_auth_mark_svg(string $gradId, string $from, string $to): string
    {
        return '<svg viewBox="0 0 86 78" aria-hidden="true"><defs><linearGradient id="' . htmlspecialchars($gradId) . '" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="' . htmlspecialchars($from) . '"/><stop offset="1" stop-color="' . htmlspecialchars($to) . '"/></linearGradient></defs><path d="M1.44,75.812l8.08,-48.479l7.927,15.092l-5.336,33.386l-10.671,0Zm69.516,-74.852l2.744,16.617l-31.252,59.912l-31.404,-60.065l2.744,-16.464l28.66,55.186l28.508,-55.186Zm4.269,26.069l8.08,48.783l-10.671,0l-5.336,-33.539l7.927,-15.245Z" fill="url(#' . htmlspecialchars($gradId) . ')"/></svg>';
    }
}

if (!function_exists('md_auth_icon')) {
    function md_auth_icon(string $name): string
    {
        $paths = [
            'layers' => '<path d="m12 2 9 5-9 5-9-5 9-5Z"/><path d="m3 12 9 5 9-5"/><path d="m3 17 9 5 9-5"/>',
            'webhook' => '<path d="M18 16.98h-5.99c-1.1 0-1.95.94-2.48 1.9A4 4 0 0 1 2 17c.01-.7.2-1.4.57-2"/><path d="m6 17 3.13-5.78c.53-.97.1-2.18-.5-3.1a4 4 0 1 1 6.89-4.06"/><path d="m12 6 3.13 5.73C15.66 12.7 16.9 13 18 13a4 4 0 0 1 0 8"/>',
            'lock' => '<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
            'eye' => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>',
            'mail' => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m2 7 10 6 10-6"/>',
            'chevLeft' => '<path d="m15 6-6 6 6 6"/>',
        ];
        $inner = $paths[$name] ?? '<circle cx="12" cy="12" r="9"/>';
        return '<svg viewBox="0 0 24 24" width="100%" height="100%" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $inner . '</svg>';
    }
}

if (!function_exists('md_auth_shell_open')) {
    /** Open the two-panel auth shell (brand + form card). */
    function md_auth_shell_open(): string
    {
        $year = date('Y');
        return '
  <div class="auth">
    <aside class="auth-brand">
      <div class="auth-brand-top">
        <span class="auth-mark">' . md_auth_mark_svg('mkBrand', '#FCFBF8', '#C0552F') . '</span>
        <span class="auth-word">Modular</span>
      </div>
      <div class="auth-brand-body">
        <div class="auth-eyebrow">Content platform</div>
        <h1 class="auth-headline">Model your content. Keep it calm.</h1>
        <p class="auth-sub">One composable CMS for your site and your API — fixed where it should be, modular where it counts.</p>
        <div class="auth-points">
          <div class="auth-point"><span class="auth-point-dot">' . md_auth_icon('layers') . '</span> Composable field groups &amp; components</div>
          <div class="auth-point"><span class="auth-point-dot">' . md_auth_icon('webhook') . '</span> Themed and headless from one source</div>
          <div class="auth-point"><span class="auth-point-dot">' . md_auth_icon('lock') . '</span> EU-hosted, role-based access</div>
        </div>
      </div>
      <div class="auth-foot"><span>&copy; ' . $year . ' Atypisch</span></div>
    </aside>
    <main class="auth-panel">
      <div class="auth-card">
        <div class="auth-mobilebrand">
          <span class="auth-mark">' . md_auth_mark_svg('mkMobile', '#221F1A', '#C0552F') . '</span>
          <span class="auth-word">Modular</span>
        </div>';
    }
}

if (!function_exists('md_auth_shell_close')) {
    function md_auth_shell_close(): string
    {
        return '
      </div>
    </main>
  </div>
  <script>
  document.addEventListener("click", function (e) {
    var t = e.target.closest("[data-toggle]");
    if (!t) return;
    var inp = document.getElementById(t.getAttribute("data-toggle"));
    if (!inp) return;
    inp.type = inp.type === "password" ? "text" : "password";
  });
  </script>';
    }
}
