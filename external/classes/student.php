<?php
require_once('config.php');

class CStudent
{
    protected $ID="";
 
    function __construct($newID) {
        $this->ID = $this->sanitizeUUID($newID);
    }

    private function sanitizeUUID($string) {
        // Remove any characters that are not hex digits or slashes
        $sanitizedString = preg_replace('/[^-0-9A-F]/i', '', $string);
        return substr($sanitizedString, 0, 36);
    }

    public function is_valid() {
        $response = @file_get_contents(verify_url.$this->ID);
        if (is_null($response)) return FALSE;
        $data = json_decode($response, true); // Passing true as the second argument to decode JSON as associative array
        if (is_null($data)) return FALSE;
        if (!isset($data['id']) || empty($data['id']) || $data['id']==0) return FALSE;
        return TRUE;
    }

    public function get_short_id() {
        return(substr($this->ID,-4));
    }

    public function register_pass() {
        $response = @file_get_contents(register_url.$this->ID);
        return($response);
    }
}