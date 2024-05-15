<?php
require_once('config.php');

class CStudent
{
    protected $ID;
    protected $firstname;
    protected $lastname;
    protected $birthday;

    function __construct($id) {
        if (!isset($id) || empty($id) || strlen($id)!=36)
        {
            $this->ID="";
        } else {
            $this->ID = $this->sanitizeUUID($id);
            $studentline=$this->getLineWithString(STUDENTS_CVS, $id);
            if( $studentline !== "") {
                // valid student ID
                $data = str_getcsv($studentline, ";");
                $this->lastname=$data[CSV_LAST];
                $this->firstname=$data[CSV_FIRST];
                $this->birthday=$data[CSV_BIRTHDAY];
            } else {
                $this->ID="";
            }
        }
    }

    private function sanitizeUUID($string) {
        // Remove any characters that are not hex digits or slashes
        $sanitizedString = preg_replace('/[^-0-9A-F]/i', '', $string);
        return substr($sanitizedString, 0, 36);
    }

    private function getLineWithString($fileName, $str): String {
        $lines = file($fileName);
        foreach ($lines as $lineNumber => $line) {
            if (strpos($line, $str) !== false) {
                return $line;
            }
        }
        return "";
    }

    public function getID(): String
    {
        return $this->ID;
    }
    public function getFirstname(): String
    {
        return $this->firstname;
    }
    public function getLastname(): String
    {
        return $this->lastname;
    }
    public function getBirthday(): String
    {
        return $this->birthday;
    }
}

?>