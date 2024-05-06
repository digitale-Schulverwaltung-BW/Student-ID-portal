<?php

class CStudent
{
    protected $ID="";
    // URL to request
    //protected $url = 'http://192.168.255.2:8080/ID/internal-verify.php?id='+$id;
    protected $url = 'http://10.16.193.229/ID/internal-verify.php?id=';

    function __construct($newID) {
        $this->ID = $this->sanitizeUUID($newID);
        $this->url=$this->url.$this->ID;
    }

    private function sanitizeUUID($string) {
        // Remove any characters that are not hex digits or slashes
        $sanitizedString = preg_replace('/[^-0-9A-F]/i', '', $string);
        return $sanitizedString;
    }

    public function is_valid() {
        return $this->intern_validity();
    }

    protected function intern_validity() {
        $response = file_get_contents($this->url);
        if (is_null($response)) return FALSE;
        $data = json_decode($response, true); // Passing true as the second argument to decode JSON as associative array
        if (is_null($data)) return FALSE;
        if ($data['id']==0) return FALSE;
        return TRUE;
    }
    public function get_short_id() {
        return(substr($this->ID,-4));
    }
}