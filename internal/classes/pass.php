<?php
// Ubuntu package: php-sqlite3

require_once('config.php');
require_once('student.php');

class CStudentPass {

    protected String $passID;

    protected String $appleURL;
    protected String $googleURL;
    protected String $pdfURL;

    protected CStudent $stud;

    function __construct($f3, CStudent $student) {
        $this->student=$student;
        $this->appleURL="";
        $this->googleURL="";
        $this->pdfURL="";
        $db=new DB\SQL('sqlite:'.PASS_DB);
        $db->exec('CREATE TABLE IF NOT EXISTS "passes" (
            "studID" VARCHAR PRIMARY KEY NOT NULL, "passID" VARCHAR, "created" DATE
        )');
        $results = $db->exec('SELECT passID FROM passes WHERE studID="'.$student->getID().'"');
        if (empty($results))
            $this->passID="";
        else {
            $this->passID=$results[0]['passID'];
            $url='https://cloud.kortpress.io/rest/v1/pass/'.$this->passID;
            $options = array(
                'method' => 'GET', 'follow_redirects' => TRUE,
                'header' => [
                    'Cookie: INGRESSCOOKIE=e04b23e90c3fcb130cc9f602b360d97f; SESSION=YWM4OTk3MTctZTEzOC00NjMyLTkyMTYtZjBlNDAwMmRmMmIw',
                    'Content-Type: application/json', 'Authorization: Bearer '. KORTPRESS_TOKEN
                ]
            );
            $result = \Web::instance()->request($url, $options);
            if (!isset($result) || empty($result) || (!isset($result['body'])))
            {
                $logger = new Log('deploy.log');
                $logger->write("ERROR getting pass info: ".print_r($result));
            } else {
                $data = json_decode($result['body'], true);
                $this->extractURLs($data);
            }
        }
    }

    function extractURLs($data)
    {
        if (isset($data['urls']['platforms']['APPLE']))  $this->appleURL=$data['urls']['platforms']['APPLE'];
        if (isset($data['urls']['platforms']['GOOGLE'])) $this->googleURL=$data['urls']['platforms']['GOOGLE']; 
        if (isset($data['urls']['platforms']['PDF']))    $this->pdfURL=$data['urls']['platforms']['PDF'];
    }

    function validShort(): String
    {
        $month=date("m");
        $year=date("Y");
        if ($month>8) return "9/".$year+1;
        return "9/$year";
    }

    function validLong(): String
    {
        $month=date("m");
        $year=date("Y");
        if ($month>8) return ($year+1)."-09-30T12:00:00.118Z";
        return "$year.-09-30T12:00:00.118Z";
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
        $f3->mset(array('id'=>$this->studentID,
                        'short_id'=>substr($this->student->getID(),-4),
                        'valid_short'=>$this->validShort(),
                        'valid_long'=>$this->validLong(),
                        'verify_base'=> VERIFY_BASE_URL,
                        'school'=>SCHOOL,
                        'school_url'=>SCHOOL_URL,
                        'img_base_url'=>IMG_BASE_URL,
                        'birthday'=> $this->student->getBirthday(), 
                        'firstname'=> $this->student->getFirstname(), 
                        'lastname'=> $this->student->getLastname() 
                    ));
        $postdata=$template->render('templates/pass.json');
        $options = array(
            'method' => 'POST', 'follow_redirects' => TRUE, 'content' => $postdata,
            'header' => [
                'Cookie: INGRESSCOOKIE=e04b23e90c3fcb130cc9f602b360d97f; SESSION=YWM4OTk3MTctZTEzOC00NjMyLTkyMTYtZjBlNDAwMmRmMmIw',
                'Content-Type: application/json', 'Authorization: Bearer '. KORTPRESS_TOKEN
            ]
        );
            
        $result = \Web::instance()->request($url, $options);
        if (!isset($result) || empty($result) || (!isset($data['details'])|| 
            (!isset($data['details']['serialNumber']))) || empty($data['details']['serialNumber']))
             $logger->write("ERROR deploy data: ".$postdata);
        else $logger->write("INFO: successful deploy: ".$args['id'].'=>'.$data['details']['serialNumber']);

        $data = json_decode($result['body'], true);
        $this->extractURLs($data);
        $this->passID=$data['details']['serialNumber'];
        $db=new DB\SQL('sqlite:'.PASS_DB);
        $db->exec('INSERT INTO passes VALUES ('.$this->studentID.', '.$this->passID.', '.date('Y-m-d').')');
        return $this->passID;
    }

    function getAppleURL()
    {
        return $this->appleURL;
    }

    function getGoogleURL()
    {
        return $this->googleURL;
    }

    function getPDFURL()
    {
        return $this->pdfURL;
    }
}
?>