<?php
require_once plugin_dir_path(__FILE__) . '../common/controller-base.php';
require_once plugin_dir_path(__FILE__) . 'student.php';

class WPController extends ControllerBase {
    protected function getParam(string $name): string {
        // Wildcard route param (*) is stored as sid_path
        if ($name === '*') return (string)(get_query_var('sid_path', ''));
        return (string)(get_query_var("sid_$name", ''));
    }
    protected function getPost(string $name): string {
        return sanitize_text_field(wp_unslash($_POST[$name] ?? ''));
    }
    protected function getConfig(string $key): mixed {
        return match ($key) {
            'require_birthday' => (bool) get_option('sid_require_birthday'),
            'wallet_apple'     => (bool) get_option('sid_wallet_apple', '1'),
            'wallet_google'    => (bool) get_option('sid_wallet_google', '1'),
            'bday_hmac_secret' => (string) get_option('sid_bday_hmac_secret', ''),
            'school'           => (string) get_option('sid_school', ''),
            'apple_pass_url'   => rtrim(get_option('sid_internal_server', ''), '/') . '/internal/apple/',
            default            => null,
        };
    }
    protected function renderTemplate(string $name, array $vars): void {
        extract($vars, EXTR_SKIP);
        get_header();
        include SID_PLUGIN_DIR . "templates/$name.php";
        get_footer();
    }
    protected function redirect(string $url): never {
        wp_redirect($url);
        exit();
    }
    protected function sendBinary(string $data, string $contentType, string $filename): never {
        header("Content-Type: $contentType");
        header('Content-Disposition: attachment; filename="' . addslashes($filename) . '"');
        echo $data;
        exit();
    }
    protected function makeStudent(string $id): CStudent {
        return new CStudent($id);
    }
    protected function isTeacher(): bool {
        return get_query_var('sid_cardtype', '') === get_option('sid_prefix_teacher', 'LID');
    }
    // WP adds its own query vars (sid_action, sid_path, …) to QUERY_STRING after rewrite.
    // Only forward the token that Apple Wallet actually needs.
    protected function getProxyQueryString(): string {
        $token = sanitize_text_field(wp_unslash($_GET['token'] ?? ''));
        return $token !== '' ? 'token=' . rawurlencode($token) : '';
    }

    protected function controller_http_get(string $url): string|false {
        $result = wp_remote_get($url, ['timeout' => 30]);
        return is_wp_error($result) ? false : wp_remote_retrieve_body($result);
    }
}
