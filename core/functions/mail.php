<?php
/**
 * Simple centralized mail helper for Modular CMS.
 *
 * Uses PHP's mail() by default; transport details and templates live in `settings`.
 */

if (!function_exists('mail_get_setting')) {
    function mail_get_setting(string $key, $default = null) {
        if (!isset($GLOBALS['db'])) {
            return $default;
        }
        $db = $GLOBALS['db'];
        try {
            $stmt = $db->prepare("SELECT option_value FROM settings WHERE option_name = ? LIMIT 1");
            $stmt->execute([$key]);
            $value = $stmt->fetchColumn();
            return $value !== false ? $value : $default;
        } catch (\Exception $e) {
            return $default;
        }
    }
}

if (!function_exists('mail_render_template')) {
    /**
     * Very small template renderer: replaces {{var}} tokens in the text.
     */
    function mail_render_template(string $text, array $vars): string {
        foreach ($vars as $key => $value) {
            $text = str_replace('{{' . $key . '}}', (string) $value, $text);
        }
        return $text;
    }
}

if (!function_exists('mail_send_template')) {
    /**
     * Send an email for a logical template key, e.g. 'password_reset'.
     *
     * Settings used:
     *  - mail_from_name
     *  - mail_from_address
     *  - email_template.{key}.subject
     *  - email_template.{key}.body
     */
    function mail_send_template(string $templateKey, string $to, array $vars = []): bool {
        $fromName = mail_get_setting('mail_from_name', 'Modular CMS');
        $fromAddress = mail_get_setting('mail_from_address', 'noreply@example.com');

        $subjectTemplate = mail_get_setting('email_template.' . $templateKey . '.subject', '');
        $bodyTemplate = mail_get_setting('email_template.' . $templateKey . '.body', '');

        if ($subjectTemplate === '' || $bodyTemplate === '') {
            // Fallback: simple generic message
            $subjectTemplate = $vars['subject_fallback'] ?? ucfirst(str_replace('_', ' ', $templateKey));
            $bodyTemplate = $vars['body_fallback'] ?? '';
        }

        $subject = mail_render_template($subjectTemplate, $vars);
        $body = mail_render_template($bodyTemplate, $vars);

        $headers = [];
        $headers[] = 'From: ' . sprintf('"%s" <%s>', addslashes($fromName), $fromAddress);
        $headers[] = 'Reply-To: ' . $fromAddress;
        $headers[] = 'X-Mailer: PHP/' . phpversion();

        return mail($to, $subject, $body, implode("\r\n", $headers));
    }
}

