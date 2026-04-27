<?php

abstract class CStudentBase {
    protected string $ID = '';
    protected string $birthday = '';
    protected string $firstname = '';
    protected string $lastname = '';
    protected bool $valid = false;

    abstract protected function http_get(string $url): string|false;
    abstract protected function verify_url(): string;
    abstract protected function register_url(): string;
    protected function log(string $msg): void {}

    function __construct($newID) {
        $this->ID = $this->sanitizeUUID($newID);
        $response = $this->http_get($this->verify_url() . $this->ID);
        if (!$response) { $this->valid = false; return; }
        $data = json_decode($response, true);
        if (!$data || !isset($data['id']) || empty($data['id']) || $data['id'] == 0) {
            $this->valid = false; return;
        }
        $this->valid = true;
        $this->firstname = $data['firstname'];
        $this->lastname = $data['lastname'];
        $this->birthday = $data['birthday'];
    }

    private function sanitizeUUID(string $string): string {
        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $string))
            return $string;
        return '';
    }

    public function get_bday(): string {
        $response = $this->http_get($this->verify_url() . $this->ID);
        if (!$response) return '';
        $data = json_decode($response, true);
        if (!$data || !isset($data['id']) || empty($data['id']) || $data['id'] == 0) return '';
        if (!isset($data['birthday']) || empty($data['birthday'])) return '';
        $this->valid = true;
        return $data['birthday'];
    }

    public function is_valid(): bool { return $this->valid; }
    public function get_short_id(): string { return substr($this->ID, -4); }
    public function get_birthday(): string { return $this->birthday; }
    public function get_firstname(): string { return substr($this->firstname, 0, 1); }
    public function get_lastname(): string { return substr($this->lastname, 0, 1); }

    public function register_pass(bool $isTeacher = false): string {
        $url = $this->register_url() . $this->ID . ($isTeacher ? '?type=teacher' : '');
        $response = $this->http_get($url);
        $this->log("fetched: $url");
        $this->log("result: " . print_r($response, true));
        if (!$response) return '';
        return $response;
    }
}
