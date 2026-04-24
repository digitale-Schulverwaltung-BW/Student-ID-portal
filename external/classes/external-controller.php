<?php
require_once __DIR__ . '/../../common/controller-base.php';
require_once 'student.php';

class ExternalController extends ControllerBase {
    private Base $f3;
    private Template $template;

    function __construct() {
        $this->f3       = Base::instance();
        $this->template = new Template;
    }

    protected function getParam(string $name): string {
        return (string)($this->f3->get("PARAMS.$name") ?? '');
    }
    protected function getPost(string $name): string {
        return (string)($this->f3->get("POST.$name") ?? '');
    }
    protected function getConfig(string $key): mixed {
        return match ($key) {
            'require_birthday' => require_birthday,
            'wallet_apple'     => WALLET_USE_APPLE,
            'wallet_google'    => WALLET_USE_GOOGLE,
            'bday_hmac_secret' => BDAY_HMAC_SECRET,
            'school'           => school,
            'apple_pass_url'   => apple_pass_url,
            default            => null,
        };
    }
    protected function renderTemplate(string $name, array $vars): void {
        foreach ($vars as $k => $v) $this->f3->set($k, $v);
        echo $this->template->render("templates/$name.html");
    }
    protected function redirect(string $url): never {
        header("Location: $url");
        exit();
    }
    protected function sendBinary(string $data, string $contentType, string $filename): never {
        header("Content-Type: $contentType");
        header("Content-Disposition: attachment; filename=\"$filename\"");
        echo $data;
        exit();
    }
    protected function makeStudent(string $id): CStudent {
        return new CStudent($id);
    }
    protected function isTeacher(): bool {
        return str_contains($_SERVER['REQUEST_URI'], '/LID/');
    }
    protected function controller_http_get(string $url): string|false {
        return @file_get_contents($url);
    }
}
