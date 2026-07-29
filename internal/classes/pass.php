<?php

require_once('config.php');
require_once('student.php');
require_once('utility.php');

class CStudentPass extends Utility {

    protected Base $f3;
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
        $this->passID="";

        // Lookup existing pass via API (replaces SQLite query)
        $logger = new Log('deploy.log');
        $data = $this->walletApiRequest(
            WALLET_API_BASE . '/passes/by-external-id/' . $student->getID()
        );
        if ($data !== null && !empty($data['id'])) {
            $this->passID = $data['id'];
            $logger->write("DEBUG: ".$this->passID);
            // Fetch full pass data with fresh download links
            $fullData = $this->walletApiRequest(
                WALLET_API_BASE . '/passes/' . $this->passID
            );
            if ($fullData !== null) {
                $this->extractURLs($fullData);
            } else {
                $logger->write("ERROR getting pass info for passID: " . $this->passID);
            }
        }
    }

    function extractURLs(array $data): void
    {
        if (!empty($data['apple_pass_url']))  $this->appleURL=$data['apple_pass_url'];
        if (!empty($data['google_save_url'])) $this->googleURL=$data['google_save_url'];
    }

    function getPassID(): String
    {
        if ($this->passID!="") return $this->passID;
        return $this->registerPass();
    }

    function registerPass(): String
    {
        $logger = new Log('deploy.log');
        if ($this->student->getID()=="") return "";

        $url = WALLET_API_BASE . '/passes';
        $postdata = json_encode([
            'theme_id' => ($this->isTeacher && defined('WALLET_THEME_ID_TEACHER'))
                ? WALLET_THEME_ID_TEACHER
                : WALLET_THEME_ID,
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
            $logger->write("ERROR deploy failed (null response) for student: " . $this->student->getID());
            return "";
        }

        if (!isset($data['id']) || empty($data['id'])) {
             $logger->write("ERROR extracting deploy data for student: " . $this->student->getID());
             $logger->write("...response body: ".print_r($data, true));
             return "";
        } else $logger->write("INFO: successful deploy: ".$this->student->getID().'=>'.$data['id']);

        if (!empty($data['apple_error'])) $logger->write("WARNING: Apple error: ".print_r($data['apple_error'], true));
        if (!empty($data['google_error'])) $logger->write("WARNING: Google error: ".print_r($data['google_error'], true));

        $this->extractURLs($data);
        $this->passID=$data['id'];
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
