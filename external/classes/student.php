<?php
require_once('config.php');

// publicly exposed representation of a student.
// provides check if valid, short ID string, birthday and pass registration JSON array
class CStudent
{
    protected String $ID="";
    protected String $birthday="";
    protected String $firstname="";
    protected String $lastname="";
    protected bool $valid=FALSE;

    // constructor. Can only be used with a student ID
    function __construct($newID) {
        $this->ID = $this->sanitizeUUID($newID);
        $response = @file_get_contents(verify_url.$this->ID);
        if (is_null($response) || empty($response)  || $response === false) $this->valid=false; else $this->valid=true;
        $data = json_decode($response, true); // Passing true as the second argument to decode JSON as associative array
        if (is_null($data)) $this->valid=false;
        if (!isset($data['id']) || empty($data['id']) || $data['id']==0) $this->valid=false;
        if ($this->valid) {
            $this->firstname=$data['firstname'];
            $this->lastname=$data['lastname'];
            $this->birthday=$data['birthday'];
        }
    }

    // Remove any characters that are not hex digits or slashes, cut to 36 chars
    private function sanitizeUUID($string): String {
        $sanitizedString = preg_replace('/[^-0-9A-F]/i', '', $string);
        return substr($sanitizedString, 0, 36);
    }

    // get student's birthday from student data file
    public function get_bday() : String{
        $response = @file_get_contents(verify_url.$this->ID);
        if (is_null($response) || empty($response)  || $response === false) return "";
        $data = json_decode($response, true); // Passing true as the second argument to decode JSON as associative array
        if (is_null($data)) return "";
        if (!isset($data['id']) || empty($data['id']) || $data['id']==0) return "";
        if (!isset($data['birthday']) || empty($data['birthday'])) return "";
        $this->valid=TRUE;
        return $data['birthday'];
    }

    // does the obvious
    public function is_valid(): bool {
        if ($this->valid) return true;
        return false;
    }

    // Student ID cards have a 4-digit ID string which can be obtained from here
    public function get_short_id(): String {
        return(substr($this->ID,-4));
    }
    public function get_birthday(): String {
        return($this->birthday);
    }
    public function get_firstname(): String {
        return(substr($this->firstname, 0, 1));
    }
    public function get_lastname(): String {
        return(substr($this->lastname, 0, 1));
    }
    // if pass already issued, retrieve download URLs.
    // if not, register pass and retrieve download URLs.
    // returns JSON containing the fields id, apple, google and pdf.
    public function register_pass(): String {
        $response = @file_get_contents(register_url.$this->ID);
        if (logging) {$logger = new Log('extdeploy.log');
            $logger->write("fetched: ".register_url.$this->ID);
            $logger->write("result: ".print_r($response, true));
        }
        if (is_null($response) || empty($response)  || $response === false) return "";
        return ($response);
    }
}