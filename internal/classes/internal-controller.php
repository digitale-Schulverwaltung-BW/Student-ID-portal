<?php
require_once('config.php');
require_once('pass.php');
require_once('student.php');

class InternalController
{
    // route handler for verify. Returns JSON array with student name+birthday
    function verify($f3, $args): void
    {
        $id=(isset($args['id']))?$args['id']:"";
        $stud=new CStudent($id);
        if (($stud->getID() !== "")/* && (!$stud->isBlacklisted())*/){
            // valid student ID
            $resp = array("id" => $stud->getID(), 
                          "lastname" => $stud->getLastname(), 
                          "firstname" => $stud->getFirstname(), 
                          "birthday" => $stud->getBirthday());
        } else {
            // invalid student ID
            sleep(invalid_wait); // rate limiting to keep brute forcing ID attempts at bay
            $resp = array("id" => 0, "lastname" => "", "firstname" => "");
        }
        header("Content-Type: application/json");
        echo json_encode($resp);
    }

    // route handler for deploy. Will create a pass if not already registered
    // or retrieve the remote download URLs for existing passes. Returns JSON
    // array with passID and the download URLs.
    function deploy($f3, $args): void
    {
        $id=(isset($args['id']))?$args['id']:"";
        $stud = new CStudent($id);
        if ($stud->isBlacklisted()) {
            $data = array("error" => "Fehler: bereits Papierausweis ausgestellt, kein digitaler Ausweis möglich.");
            header("Content-Type: application/json");
            echo json_encode($data);
            return;
        }
        if ($stud->getID()=="") return;
        $pass   = new CStudentPass($stud);
        $passID = $pass->getPassID();
        if (!empty($passID))
        {
            $data = array("id" => $passID,
                           "apple" => $pass->getAppleURL(),
                           "google" => $pass->getGoogleURL());
            header("Content-Type: application/json");
            echo json_encode($data);
        }
        else {
            header("Content-Type: application/json");
            echo "";
        }
    }

    // route handler for verify. Returns JSON array with student name+birthday
    function lookup($f3, $args): void
    {
        $login=(isset($args['login']))?$args['login']:"";
        $stud   = new CStudent("");
        $stud.lookup($login);
        echo $stud->getID();
    }

}