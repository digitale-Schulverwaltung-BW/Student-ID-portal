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
    protected String $pdfURL;

    protected CStudent $student;

    function __construct(CStudent $student) {
        $this->f3 = Base::instance();
        $this->student=$student;
        $this->appleURL="";
        $this->googleURL="";
        $this->pdfURL="";

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
                    $logger->write("TIMEOUT getting pass info, resetting: ".print_r($result, true));
                    $this->passID=""; 
                } 
            } else {
                $url='https://cloud.kortpress.io/rest/v1/pass/'.$this->passID;
                $options = array( 
                    'method' => 'GET', 'follow_redirects' => TRUE,
                    'header' => [ 'Content-Type: application/json', 'Authorization: Bearer '. KORTPRESS_TOKEN ]
                );
                $result = \Web::instance()->request($url, $options);
                if (!isset($result) || empty($result) || (!isset($result['body'])))
                {
                    $logger->write("ERROR getting pass info: ".print_r($result, true));
                } else {
                    $data = json_decode($result['body'], true);
                    $this->extractURLs($data);
                }
            }
        }
    }

    function extractURLs(array $data): void
    {
        if (isset($data['urls']['platforms']['APPLE']))  $this->appleURL=$data['urls']['platforms']['APPLE'];
        if (isset($data['urls']['platforms']['GOOGLE'])) $this->googleURL=$data['urls']['platforms']['GOOGLE']; 
        if (isset($data['urls']['platforms']['PDF']))    $this->pdfURL=$data['urls']['platforms']['PDF'];
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
        $url='https://cloud.kortpress.io/rest/v1/pass/'.$this->passID;
        $options = array( 'method' => 'GET', 'follow_redirects' => TRUE,
            'header' => [ 'Content-Type: application/json', 'Authorization: Bearer '. KORTPRESS_TOKEN ]
        );
        $logger = new Log('update.log');
        $result = \Web::instance()->request($url, $options);
        if (!isset($result) || empty($result) || (!isset($result['body'])))
        {
            $logger->write("ERROR getting missing pass: ".print_r($result, true));
            return [];
        }
        $logger->write("Getting missing pass from Kortpress: ".print_r($result, true));
        $data=json_decode($result['body'], true);
        $cdate=$data['details']['createdDate'];
        $vdate=$data['details']['balance'];
        // last sanity check before DB update
        if (($this->passID!=$data['details']['serialNumber']) || 
            ($this->student->getID()!=$data['details']['thirdPartyId']) ||
            (empty($this->passID)) || (empty($this->student->getID()))||
            (empty($cdate)) || (empty($vdate))) 
            {
                $logger->write("ERROR sanity check failed getting missing pass: ".print_r($result, true));
                return "";
        }
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
        $template=new Template;
        if (empty($this->passID))
             $url='https://cloud.kortpress.io/rest/v1/pass?templateId='.KORTPRESS_TEMPLATE_ID;
        else $url='https://cloud.kortpress.io/rest/v1/pass?templateId='.KORTPRESS_TEMPLATE_ID.'&serialNumber='.$this->passID;
        $this->f3->mset(array('id'=>$this->student->getID(),
                        'pass_name'=>$this->student->getLogin(),
                        'short_id'=>substr($this->student->getID(),-4),
                        'valid_short'=>$this->validShort(),
                        'valid_long'=>$this->validLong(),
                        'verify_base'=> VERIFY_BASE_URL,
                        'school'=>SCHOOL,
                        'school_url'=>SCHOOL_URL,
                        'img_base_url'=>IMG_BASE_URL,
                        'birthday'=> $this->student->getBirthday(), 
                        'firstname'=> $this->student->getFirstname(), 
                        'lastname'=> $this->student->getLastname(),
                        'apple' => KORTPRESS_USE_APPLE,
                        'google' => KORTPRESS_USE_GOOGLE,
                        'pdf' => KORTPRESS_USE_PDF
                    ));
        $postdata=$template->render('templates/pass.txt', 'application/json');
        $options = array(
            'method' => 'POST', 'follow_redirects' => TRUE, 'content' => $postdata,
            'header' => [ 'Content-Type: application/json', 'Authorization: Bearer '. KORTPRESS_TOKEN
            ]
        );
        $result = \Web::instance()->request($url, $options);
        if (!isset($result) || empty($result) || !isset($result['body'])) {
            $logger->write("ERROR deploy result: $result, postdata: $postdata");
            return "";
        }
        $data = json_decode($result['body'], true);
        
        if ((!isset($data['details']) || (!isset($data['details']['serialNumber']))) || empty($data['details']['serialNumber'])) {
             $logger->write("ERROR extracting deploy data: ".$postdata);
             $logger->write("...result body leading to ERROR: ".print_r($result, true));
             return "";
        } else $logger->write("INFO: successful deploy: ".$this->student->getID().'=>'.$data['details']['serialNumber']);

        $this->extractURLs($data);
        if (empty($this->passID))
        {
            $this->passID=$data['details']['serialNumber'];
            $this->db->exec('UPDATE passes SET passID="'.$this->passID.'", created="'.date('Y-m-d').'", valid="'.$this->validShort().'" WHERE studID="'.$this->student->getID().'"');
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

    function getPDFURL(): String
    {
        return $this->pdfURL;
    }
}
?>