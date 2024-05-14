<?php
// Ubuntu package: php-sqlite3

require_once('config.php');

class CStudentPass {

    protected $studentID;
    protected $passID;

    protected $appleURL;
    protected $googleURL;
    protected $pdfURL;

    function __construct($f3, $studentID) {
        $this->studentID=$studentID;
        $db=new DB\SQL('sqlite:'.PASS_DB);
        $db->exec('CREATE TABLE IF NOT EXISTS "passes" (
            "studID" VARCHAR PRIMARY KEY NOT NULL,
            "passID" VARCHAR,
            "created" DATE
        )');
        $results = $db->exec('SELECT passID FROM passes WHERE studID="'.$studentID.'"');
        if (empty($results))
            $this->passID="";
        else {
            $this->passID=$results[0]['passID'];
            $url='https://cloud.kortpress.io/rest/v1/pass/'.$this->passID;
            $options = array(
                'method' => 'GET',
                'follow_redirects' => TRUE,
                'header' => [
                    'Cookie: INGRESSCOOKIE=e04b23e90c3fcb130cc9f602b360d97f; SESSION=YWM4OTk3MTctZTEzOC00NjMyLTkyMTYtZjBlNDAwMmRmMmIw',
                    'Content-Type: application/json',
                    'Authorization: Bearer '. KORTPRESS_TOKEN
                ]
            );
            $result = \Web::instance()->request($url, $options);
            if (!isset($result) || empty($result) || (!isset($result['body'])))
            {
                $logger = new Log('deploy.log');
                $logger->write("ERROR getting pass info: ".print_r($result));
            } else {
                $data = json_decode($result['body'], true);
                if (isset($data['urls']['platforms']['APPLE'])) $this->appleURL=$data['urls']['platforms']['APPLE'];
                if (isset($data['urls']['platforms']['GOOGLE'])) $this->googleURL=$data['urls']['platforms']['GOOGLE'];
                if (isset($data['urls']['platforms']['PDF'])) $this->PDFURL=$data['urls']['platforms']['PDF'];
            }
        }

    }
    function getPassID(): String
    {   
        // already registered, return stored passID
        if ($this->passID!="") return $this->passID;
        
        // not in DB: create pass
        $logger = new Log('deploy.log');
        if (!$this->checkID($this->studentID)) return "";
        $template=new Template;
        $url='https://cloud.kortpress.io/rest/v1/pass?templateId='.KORTPRESS_TEMPLATE_ID;
        $f3->mset(array('id'=>$this->studentID,
                        'short_id'=>substr($this->studentID,-4)));
        $postdata=$template->render('templates/pass.json');
        $options = array(
            'method' => 'POST',
            'follow_redirects' => TRUE,
            'content' => $postdata,
            'header' => [
                'Cookie: INGRESSCOOKIE=e04b23e90c3fcb130cc9f602b360d97f; SESSION=YWM4OTk3MTctZTEzOC00NjMyLTkyMTYtZjBlNDAwMmRmMmIw',
                'Content-Type: application/json',
                'Authorization: Bearer '. KORTPRESS_TOKEN
            ]
        );
            
        $result = \Web::instance()->request($url, $options);
        if (!isset($result) || empty($result) || (!isset($data['details'])|| 
            (!isset($data['details']['serialNumber']))) || empty($data['details']['serialNumber']))
             $logger->write("ERROR deploy data: ".$postdata);
        else $logger->write("INFO: successful deploy: ".$args['id'].'=>'.$data['details']['serialNumber']);

        $data = json_decode($result['body'], true);
        $this->appleURL=$data['urls']['platforms']['APPLE'];
        $this->googleURL=$data['urls']['platforms']['GOOGLE'];
        $this->pdfURL=$data['urls']['platforms']['PDF'];
        $this->passID=$data['details']['serialNumber'];
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