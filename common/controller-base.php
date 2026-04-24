<?php

abstract class ControllerBase {

    private ?array $cardVars = null;

    // --- Abstract: environment-specific glue ---

    // URL path param (id, hash, deploy, * for wildcard)
    abstract protected function getParam(string $name): string;
    // POST field
    abstract protected function getPost(string $name): string;
    // Config value; keys: require_birthday, wallet_apple, wallet_google,
    //   bday_hmac_secret, school, apple_pass_url
    abstract protected function getConfig(string $key): mixed;
    // Render a named template (verify, register, register-auth) with variables
    abstract protected function renderTemplate(string $name, array $vars): void;
    // HTTP redirect and exit
    abstract protected function redirect(string $url): never;
    // Send binary data with content-type header and exit
    abstract protected function sendBinary(string $data, string $contentType, string $filename): never;
    // Instantiate a student by ID
    abstract protected function makeStudent(string $id): CStudentBase;
    // True when handling a teacher card (/LID/ prefix)
    abstract protected function isTeacher(): bool;
    // HTTP GET for apple proxy forwarding
    abstract protected function controller_http_get(string $url): string|false;

    // --- Shared helpers ---

    protected function getCardVars(): array {
        if ($this->cardVars === null) $this->cardVars = $this->setCardTypeVars();
        return $this->cardVars;
    }

    protected function setCardTypeVars(): array {
        if ($this->isTeacher()) {
            return [
                'card_type'      => 'Lehrkraft-Nachweis',
                'card_type_fr'   => "carte d'enseignant valide",
                'card_type_en'   => 'valid teacher ID',
                'person_label'   => 'Lehrkraft',
                'person_desc'    => 'Die Lehrkraft',
                'role_desc'      => 'ist im aktuellen Schuljahr an der',
                'person_invalid' => 'keine Lehrkraft',
            ];
        }
        return [
            'card_type'      => 'Schülerausweis',
            'card_type_fr'   => "carte d'étudiant valide",
            'card_type_en'   => 'student ID',
            'person_label'   => 'Schüler/in',
            'person_desc'    => 'Die Schülerin bzw. der Schüler',
            'role_desc'      => 'besucht im aktuellen Schuljahr die',
            'person_invalid' => 'keine Schülerin bzw. Schüler',
        ];
    }

    protected function exit_if_invalid(CStudentBase $stud): void {
        if (!$stud->is_valid()) {
            $cardVars = $this->getCardVars();
            $this->renderTemplate('verify', array_merge($cardVars, [
                'valid'   => false,
                'card_ID' => $stud->get_short_id(),
                'title'   => 'Ungültiger ' . $cardVars['card_type'],
            ]));
            exit();
        }
    }

    protected function is_valid_var(mixed $var): bool {
        return isset($var) && $var !== 'null' && !empty($var);
    }

    protected function exit_with_error(string $message): never {
        $cardVars = $this->getCardVars();
        $this->renderTemplate('register', array_merge($cardVars, [
            'valid'        => true,
            'deploy_error' => $message,
        ]));
        exit();
    }

    // --- Route handlers ---

    public function v(): void {
        $stud = $this->makeStudent($this->getParam('id'));
        $this->exit_if_invalid($stud);
        $cardVars = $this->getCardVars();
        $this->renderTemplate('verify', array_merge($cardVars, [
            'title'    => 'Gültiger ' . $cardVars['card_type'],
            'valid'    => true,
            'card_ID'  => $stud->get_short_id(),
            'school'   => $this->getConfig('school'),
            'birthday' => $stud->get_birthday(),
            'fn'       => $stud->get_firstname(),
            'sn'       => $stud->get_lastname(),
        ]));
    }

    public function r(): void {
        $stud = $this->makeStudent($this->getParam('id'));
        $cardVars = $this->getCardVars();
        if (!$stud->is_valid()) {
            $this->renderTemplate('register', array_merge($cardVars, ['valid' => false]));
            exit();
        }
        if ($this->getConfig('require_birthday')) {
            $this->renderTemplate('register-auth', array_merge($cardVars, [
                'auth'    => $_SERVER['REQUEST_URI'],
                'shortid' => $stud->get_short_id(),
            ]));
            exit();
        }
        $this->authReg();
    }

    public function authReg(): void {
        $stud = $this->makeStudent($this->getParam('id'));
        $cardVars = $this->getCardVars();
        $bday_hash = '';
        if (!$stud->is_valid()) {
            $this->renderTemplate('register', array_merge($cardVars, ['valid' => false]));
            exit();
        }
        if ($this->getConfig('require_birthday')) {
            $bday = str_pad($this->getPost('day'),   2, '0', STR_PAD_LEFT) . '.' .
                    str_pad($this->getPost('month'), 2, '0', STR_PAD_LEFT) . '.' .
                    $this->getPost('year');
            if ($stud->get_bday() !== $bday)
                $this->exit_with_error('Geburtsdatum nicht korrekt. Korrigieren Sie Ihre Eingabe oder wenden Sie sich ggf. an unser Sekretariat.');
            $bday_hash = '/' . hash_hmac('sha256', $bday, $this->getConfig('bday_hmac_secret'));
        }
        $this->renderTemplate('register', array_merge($cardVars, [
            'valid'        => true,
            'base'         => $_SERVER['REQUEST_URI'] . $bday_hash,
            'deploy_error' => '',
            'apple'        => $this->getConfig('wallet_apple'),
            'google'       => $this->getConfig('wallet_google'),
        ]));
    }

    public function deploy(): void {
        if ($this->getConfig('require_birthday'))
            $this->exit_with_error('Fehlende Authentifizierung. Bitte scannen Sie den QR-Code erneut.');
        $this->deploy_pass($this->getParam('id'), $this->getParam('deploy'));
    }

    public function deployAuth(): void {
        $stud = $this->makeStudent($this->getParam('id'));
        $this->exit_if_invalid($stud);
        $expected = hash_hmac('sha256', $stud->get_bday(), $this->getConfig('bday_hmac_secret'));
        if (hash_equals($expected, $this->getParam('hash')))
            $this->deploy_pass($this->getParam('id'), $this->getParam('deploy'));
        else
            $this->exit_with_error('Fehlende Authentifizierung. Bitte scannen Sie den QR-Code erneut.');
    }

    public function appleProxy(): void {
        $path = $this->getParam('*');
        if (!preg_match('#^v1/passes/[0-9a-f\-]+/apple\.pkpass$#i', $path))
            $this->exit_with_error('Ungültiger Download-Link.');
        $url   = $this->getConfig('apple_pass_url') . $path;
        $query = $_SERVER['QUERY_STRING'] ?? '';
        if (!empty($query)) $url .= '?' . $query;
        $pkpass = $this->controller_http_get($url);
        if ($pkpass === false || empty($pkpass))
            $this->exit_with_error('Fehler beim Abruf des Apple Passes.');
        $this->sendBinary($pkpass, 'application/vnd.apple.pkpass', 'studentid.pkpass');
    }

    private function deploy_pass(string $id, string $deploy): void {
        $stud = $this->makeStudent($id);
        $this->exit_if_invalid($stud);
        $pass = $stud->register_pass($this->isTeacher());
        if (!isset($pass) || empty($pass))
            $this->exit_with_error('Fehler beim Abruf der Ausweisdaten.');
        $data = json_decode($pass, true);
        if (isset($data['error'])) $deploy = 'error';
        switch ($deploy) {
            case 'google':
                if ($this->is_valid_var($data['google'] ?? null) && str_starts_with($data['google'], 'https://pay.google.com/'))
                    $this->redirect($data['google']);
                else
                    $this->exit_with_error('Fehler beim Abruf der externen Wallet-Daten.');
                break;
            case 'apple':
                if ($this->is_valid_var($data['apple'] ?? null)) {
                    // Wallet API returns a URL with the proxy path already embedded;
                    // redirect to just the path so the local apple proxy route handles it.
                    $parsed   = parse_url($data['apple']);
                    $proxyUrl = $parsed['path'];
                    if (!empty($parsed['query'])) $proxyUrl .= '?' . $parsed['query'];
                    $this->redirect($proxyUrl);
                } else {
                    $this->exit_with_error('Fehler beim Abruf der externen Wallet-Daten.');
                }
                break;
            default:
                if (!empty($data['error'] ?? ''))
                    $this->exit_with_error($data['error']);
                $this->exit_with_error('Fehler: Unbekannter Wallet-Typ oder Ausweis im gewählten Format nicht verfügbar.');
        }
    }
}
