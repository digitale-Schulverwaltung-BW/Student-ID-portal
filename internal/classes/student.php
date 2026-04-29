<?php
require_once('config.php');
require_once('utility.php');
require_once('pass.php');

class CStudent extends Utility
{
    protected Base $f3;
    protected String $ID;
    protected String $firstname;
    protected String $lastname;
    protected String $birthday;
    protected String $login;
    protected String $class;
    protected String $exitDate;

    function __construct($id, bool $isTeacher = false) {
        $this->exitDate="";
        if (!isset($id) || empty($id) || strlen($id)!=36)
        {
            $this->ID="";
        } else {
            $this->ID = $this->sanitizeUUID($id);
            $csv = ($isTeacher && defined('TEACHERS_CSV')) ? TEACHERS_CSV : STUDENTS_CSV;
            $studentline=$this->getLineWithString($csv, $this->ID);
            if ($studentline=="" && defined('DEMO_CVS'))
                $studentline=$this->getLineWithString(DEMO_CVS, $this->ID);
            if( $studentline !== "") {
                $data = str_getcsv($studentline, ";");
                if ($isTeacher) {
                    $this->lastname  = $data[CSV_T_LAST];
                    $this->firstname = $data[CSV_T_FIRST];
                    $this->birthday  = $data[CSV_T_BIRTHDAY];
                    $this->class     = '';
                    $this->login     = $data[CSV_T_EMAIL] ?? '';
                    $this->exitDate  = '';
                } else {
                    $this->lastname  = $data[CSV_LAST];
                    $this->firstname = $data[CSV_FIRST];
                    $this->birthday  = $data[CSV_BIRTHDAY];
                    $this->class     = $data[CSV_CLASS];
                    $this->login     = $data[CSV_LOGIN];
                    $this->exitDate  = isset($data[CSV_EXITD]) ? trim($data[CSV_EXITD], " \t\n\r\0\x0B\"") : '';
                }
            } else {
                $this->ID="";
            }
        }
    }

    public function isBlacklisted()
    {
        if ((!empty(BLACKLIST)) && $this->getLineWithString(BLACKLIST, $this->ID)!="")
            return true;
        return false;
    }

    private function sanitizeUUID(string $string): string {
        if (strlen($string) === 36 && preg_match('/^[0-9a-f\-]+$/i', $string))
            return $string;
        return '';
    }

    // try to look up a student from backend DB with a human-readable login name
    public function lookupStudent(String $username)
    {
        $this->intLookupStudent($username, false);
    }
    // try to look up a current or former student from backend DB with a human-readable login name
    public function lookupFormerStudent(String $username)
    {
        $this->intLookupStudent($username, true);
    }
    // try to look up a student from backend DB with a human-readable login name
    private function intLookupStudent(String $username, bool $former)
    {
        $studentline=$this->getLineWithString(STUDENTS_CSV, $username);
        if ($studentline=="" && defined('DEMO_CVS'))
            $studentline=$this->getLineWithString(DEMO_CVS, $username);
        if( $studentline !== "") {
            // valid student ID
            $data = str_getcsv($studentline, ";");
            $today=new DateTime();
            $exitd=new DateTime($data[CSV_EXITD]);
            if (!($former) && $today>$exitd)
                $this->ID="";
            else
            {
                $this->lastname=$data[CSV_LAST];
                $this->firstname=$data[CSV_FIRST];
                $this->birthday=$data[CSV_BIRTHDAY];
                $this->ID=$data[CSV_ID];
                $this->login=$data[CSV_LOGIN];
                $this->exitDate=isset($data[CSV_EXITD]) ? trim($data[CSV_EXITD], " \t\n\r\0\x0B\"") : "";
            }
        } else {
            $this->ID="";
        }
    }


    public function getID(): String
    {
        return $this->ID;
    }
    public function getPassID(): String
    {
        $data = $this->walletApiRequest(
            WALLET_API_BASE . '/passes/by-external-id/' . $this->ID
        );
        if ($data === null || empty($data['id'])) return "";
        return $data['id'];
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
    public function getClass(): String
    {
        return $this->class;
    }
    public function getExitDate(): String
    {
        return $this->exitDate;
    }
    public function hasExited(): bool
    {
        if (empty($this->exitDate)) return false;
        $today=new DateTime();
        $exitd=new DateTime($this->exitDate);
        return $today > $exitd;
    }
    public function reissuePass(): bool
    {
        $pass=new CStudentPass($this);
        return ($pass->registerPass()!="");
    }

}

?>