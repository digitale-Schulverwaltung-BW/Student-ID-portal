<?php
require_once('config.php');
require_once('pass.php');

class CStudent
{
    protected String $ID;
    protected String $firstname;
    protected String $lastname;
    protected String $birthday;
    protected String $login;

    function __construct($id) {
        if (!isset($id) || empty($id) || strlen($id)!=36)
        {
            $this->ID="";
        } else {
            $this->ID = $this->sanitizeUUID($id);
            $studentline=$this->getLineWithString(STUDENTS_CVS, $this->ID);
            if ($studentline=="" && defined('DEMO_CVS')) 
                $studentline=$this->getLineWithString(DEMO_CVS, $this->ID);
            if ($this->getLineWithString(BLACKLIST, $this->ID)!="")
                $studentline="";
            if( $studentline !== "") {
                // valid student ID
                $data = str_getcsv($studentline, ";");
                $this->lastname=$data[CSV_LAST];
                $this->firstname=$data[CSV_FIRST];
                $this->birthday=$data[CSV_BIRTHDAY];
                $this->login=$data[CSV_LOGIN];
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

    // try to look up a student from backend DB with a human-readable login name
    public function lookupStudent(String $username)
    {
        $studentline=$this->getLineWithString(STUDENTS_CVS, $username);
        if ($studentline=="" && defined('DEMO_CVS')) 
            $studentline=$this->getLineWithString(DEMO_CVS, $username);
        if( $studentline !== "") {
            // valid student ID
            $data = str_getcsv($studentline, ";");
            $this->lastname=$data[CSV_LAST];
            $this->firstname=$data[CSV_FIRST];
            $this->birthday=$data[CSV_BIRTHDAY];
            $this->ID=$data[CSV_ID];
            $this->login=$data[CSV_LOGIN];
        } else {
            $this->ID="";
        }
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
    public function getLogin(): String
    {
        return $this->login;
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
    public function reissuePass(): bool
    {
        $pass=new CStudentPass(Base::instance(), $this);
        return ($pass->registerPass()!="");
    }

}

?>