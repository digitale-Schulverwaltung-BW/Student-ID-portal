<?php
require_once plugin_dir_path(__FILE__) . '../common/student-base.php';

class CStudent extends CStudentBase {
    protected function http_get(string $url): string|false {
        $result = wp_remote_get($url, ['timeout' => 30]);
        return is_wp_error($result) ? false : wp_remote_retrieve_body($result);
    }
    protected function log(string $msg): void {
        error_log('[StudentID] ' . $msg);
    }
    protected function verify_url(): string {
        return rtrim(get_option('sid_internal_server', ''), '/') . '/internal/verify/';
    }
    protected function register_url(): string {
        return rtrim(get_option('sid_internal_server', ''), '/') . '/internal/register/';
    }
}
