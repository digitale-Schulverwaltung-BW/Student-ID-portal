<?php
require_once __DIR__ . '/../../common/student-base.php';
require_once 'config.php';

class CStudent extends CStudentBase {
    protected function http_get(string $url): string|false {
        return @file_get_contents($url);
    }
    protected function log(string $msg): void {
        if (logging) {
            $logger = new Log('extdeploy.log');
            $logger->write($msg);
        }
    }
    protected function verify_url(): string  { return verify_url; }
    protected function register_url(): string { return register_url; }
}
