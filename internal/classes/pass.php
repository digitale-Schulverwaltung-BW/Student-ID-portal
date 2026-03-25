<?php
// Ubuntu package: php-sqlite3

require_once('config.php');
require_once('student.php');
require_once('utility.php');

class CStudentPass extends Utility {

    protected Base $f3;
    protected $db;
    protected String $passID;

    protected String $appleURL;
    protected String $googleURL;

    protected CStudent $student;
    protected bool $isTeacher;

    function __construct(CStudent $student, bool $isTeacher = false) {
        $this->f3 = Base::instance();
        $this->student=$student;
        $this->isTeacher=$isTeacher;
        $this->appleURL="";
        $this->googleURL="";

        $this->db=new DB\SQL('sqlite:'.PASS_DB);
        $this->db->exec('CREATE TABLE IF NOT EXISTS "passes" (
            "studID" VARCHAR PRIMARY KEY NOT NULL, "passID" VARCHAR, "created" DATE, "valid" VARCHAR
        )');
        $logger = new Log('deploy.log');
        $results = $this->db->exec('SELECT passID, created FROM passes WHERE studID="'.$student->getID().'"');
        if (empty($results)) { // create transaction record to prevent double registrations from the impatient
            $this->db->exec('INSERT INTO passes VALUES ("'.$this->student->getID().'", "0000", "'.date(DATE_ATOM).'", "00/0000")');
            $this->passID="";
        } else {
            $logger->write("DEBUG: ".$results[0]['passID']);

            $this->passID=$results[0]['passID'];
            if ($this->passID=="0000")
            {
                $regdate=strtotime($results[0]['created']);
                $delay=time()-$regdate;
                if ($delay>300) // more than 5 minutes ago, reset to allow registration
                {
                    $this->db->exec('UPDATE passes SET passID="0000", created="'.date(DATE_ATOM).'" WHERE studID="'.$student->getID().'"');
                    $logger->write("TIMEOUT getting pass info, resetting."); // not working: .print_r($result, true)
                    $this->passID="";
                }
            } else {
                $data = $this->walletApiRequest(WALLET_API_BASE . '/passes/' . $this->passID);
                if ($data === null)
                {
                    $logger->write("ERROR getting pass info for passID: " . $this->passID);
                } else {
                    $this->extractURLs($data);
                }
            }
        }
    }

    function extractURLs(array $data): void
    {
        if (!empty($data['apple_pass_url']))  $this->appleURL=$data['apple_pass_url'];
        if (!empty($data['google_save_url'])) $this->googleURL=$data['google_save_url'];
    }

    function setPassID(String $ID): String
    {
        $this->db->exec('UPDATE passes SET passID="'.$ID.'" WHERE studID="'.$this->student->getID().'"');
        $this->passID=$ID;
        return $this->passID;
    }
    function refetchPass(String $passID): String
    {
        $this->passID=$passID;
        $logger = new Log('update.log');
        $data = $this->walletApiRequest(WALLET_API_BASE . '/passes/' . $this->passID);
        if ($data === null)
        {
            $logger->write("ERROR getting missing pass: " . $this->passID);
            return "";
        }
        $logger->write("Getting missing pass from API: " . $this->passID);
        // sanity check before DB update
        if (($this->passID != $data['id']) ||
            ($this->student->getID() != $data['external_id']) ||
            (empty($this->passID)) || (empty($this->student->getID())))
            {
                $logger->write("ERROR sanity check failed getting missing pass: " . $this->passID);
                return "";
        }
        $cdate = date('Y-m-d');
        $vdate = $this->validShort();
        $this->db->exec('INSERT INTO passes VALUES ("'.$this->student->getID().'", "'.$this->passID.'", "'.$cdate.'", "'.$vdate.'")');
        return $this->passID;
    }

    function getPassID(): String
    {
        // already registered, return stored passID
        if ($this->passID=="0000") return ""; // registration in progress
        if ($this->passID!="") return $this->passID;
        // not in DB: create pass
        return $this->registerPass();
    }

    function registerPass(): String
    {
        $logger = new Log('deploy.log');
        if ($this->student->getID()=="") return "";

        $url = WALLET_API_BASE . '/passes';
        $postdata = json_encode([
            'theme_id' => WALLET_THEME_ID,
            'external_id' => $this->student->getID(),
            'student' => [
                'first_name' => $this->student->getFirstname(),
                'last_name' => $this->student->getLastname(),
                'short_id' => substr($this->student->getID(), -4),
                'student_shortcode' => substr($this->student->getID(), -4),
                'birthdate' => $this->convertBirthdayToISO($this->student->getBirthday()),
                'valid_from' => date('c'),
                'valid_until' => $this->isTeacher
                    ? $this->teacherValidLong($this->student->getBirthday())
                    : $this->validLong(),
                'school_name' => SCHOOL,
                'school_url' => SCHOOL_URL,
                'verification_url' => VERIFY_BASE_URL // walletStudentID adds . $this->student->getID()
            ]
        ]);

        $data = $this->walletApiRequest($url, 'POST', $postdata);
        if ($data === null) {
            $logger->write("ERROR deploy result, postdata: $postdata");
            return "";
        }

        if (!isset($data['id']) || empty($data['id'])) {
             $logger->write("ERROR extracting deploy data: ".$postdata);
             $logger->write("...result body leading to ERROR: ".print_r($data, true));
             return "";
        } else $logger->write("INFO: successful deploy: ".$this->student->getID().'=>'.$data['id']);

        if (!empty($data['apple_error'])) $logger->write("WARNING: Apple error: ".print_r($data['apple_error'], true));
        if (!empty($data['google_error'])) $logger->write("WARNING: Google error: ".print_r($data['google_error'], true));

        $this->extractURLs($data);
        if (empty($this->passID))
        {
            $this->passID=$data['id'];
            $vdate = $this->isTeacher ? $this->teacherValidShort($this->student->getBirthday()) : $this->validShort();
            $this->db->exec('UPDATE passes SET passID="'.$this->passID.'", created="'.date('Y-m-d').'", valid="'.$vdate.'" WHERE studID="'.$this->student->getID().'"');
        }
        return $this->passID;
    }

    function getAppleURL(): String
    {
        return $this->appleURL;
    }

    function getGoogleURL(): String
    {
        return $this->googleURL;
    }
}
?>
