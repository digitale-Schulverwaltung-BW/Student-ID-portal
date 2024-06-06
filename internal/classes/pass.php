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

    protected CStudent $stud;

    function __construct(Base $f3, CStudent $student) {
        $this->f3 = $f3;
        $this->student=$student;
        $this->appleURL="";
        $this->googleURL="";
        $this->pdfURL="";

        $this->db=new DB\SQL('sqlite:'.PASS_DB);
        $this->db->exec('CREATE TABLE IF NOT EXISTS "passes" (
            "studID" VARCHAR PRIMARY KEY NOT NULL, "passID" VARCHAR, "created" DATE
        )');
        $results = $this->db->exec('SELECT passID FROM passes WHERE studID="'.$student->getID().'"');
        if (empty($results))
            $this->passID="";
        else {
            $this->passID=$results[0]['passID'];
            $url='https://cloud.kortpress.io/rest/v1/pass/'.$this->passID;
            $options = array( // ToDo handle fixed PostMan cookies - needed at all?
                'method' => 'GET', 'follow_redirects' => TRUE,
                'header' => [
//                    'Cookie: INGRESSCOOKIE=...
                    'Content-Type: application/json', 'Authorization: Bearer '. KORTPRESS_TOKEN
                ]
            );
            $result = \Web::instance()->request($url, $options);
            if (!isset($result) || empty($result) || (!isset($result['body'])))
            {
                $logger = new Log('deploy.log');
                $logger->write("ERROR getting pass info: ".print_r($result, true));
            } else {
                $data = json_decode($result['body'], true);
                $this->extractURLs($data);
            }
        }
    }

    function extractURLs(array $data): void
    {
        if (isset($data['urls']['platforms']['APPLE']))  $this->appleURL=$data['urls']['platforms']['APPLE'];
        if (isset($data['urls']['platforms']['GOOGLE'])) $this->googleURL=$data['urls']['platforms']['GOOGLE']; 
        if (isset($data['urls']['platforms']['PDF']))    $this->pdfURL=$data['urls']['platforms']['PDF'];
    }



    function getPassID(): String
    {   
        // already registered, return stored passID
        if ($this->passID!="") return $this->passID;
        
        // not in DB: create pass
        $logger = new Log('deploy.log');
        if ($this->student->getID()=="") return "";
        $template=new Template;
        $url='https://cloud.kortpress.io/rest/v1/pass?templateId='.KORTPRESS_TEMPLATE_ID;
        $this->f3->mset(array('id'=>$this->student->getID(),
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
        $postdata=$template->render('templates/pass.txt');
        $options = array(
            'method' => 'POST', 'follow_redirects' => TRUE, 'content' => $postdata,
            'header' => [ // ToDo
//                'Cookie: INGRESSCOOKIE=...
                'Content-Type: application/json', 'Authorization: Bearer '. KORTPRESS_TOKEN
            ]
        );
        $result = \Web::instance()->request($url, $options);
        if (!isset($result) || empty($result) || !isset($result['body'])) {
            $logger->write("ERROR deploy result: $result, postdata: $postdata");
            return "";
        }
        $data = json_decode($result['body'], true);
        
        if ((!isset($data['details']) || (!isset($data['details']['serialNumber']))) || empty($data['details']['serialNumber'])) {
             $logger->write("ERROR extrating deploy data: ".$postdata);
             $logger->write("...result body leading to ERROR: ".print_r($result, true));
             return "";
        } else $logger->write("INFO: successful deploy: ".$this->student->getID().'=>'.$data['details']['serialNumber']);

        $this->extractURLs($data);
        $this->passID=$data['details']['serialNumber'];
        $this->db->exec('INSERT INTO passes VALUES ("'.$this->student->getID().'", "'.$this->passID.'", "'.date('Y-m-d').'")');
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