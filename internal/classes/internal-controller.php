<?php
require_once('config.php');
require_once('pass.php');
require_once('student.php');

class InternalController
{

    function verify($f3, $args): void
    {
        $id=(isset($args['id']))?$args['id']:"";
        $stud=new CStudent($id);
        if( $stud->getID() !== "") {
            // valid student ID
            $resp = array("id" => $stud->getID(), "lastname" => $stud->getLastname(), 
                          "firstname" => $stud->getFirstname(), "birthday" => $stud->getBirthday());
            header("Content-Type: application/json");
            echo json_encode($resp);
        } else {
            // invalid student ID
            sleep(invalid_wait);
            $data = array("id" => 0, "lastname" => "", "firstname" => "");
            header("Content-Type: application/json");
            echo json_encode($data);
        }
    }

    function deploy($f3, $args): void
    {
        $id=(isset($args['id']))?$args['id']:"";
        $stud   = new CStudent($id);
        if ($stud->getID()=="") return;
        $pass   = new CStudentPass($f3, $stud);
        $passID = $pass->getPassID();
        $data = array("id" => $passID, "apple" => $pass->getAppleURL(), "google" => $pass->getGoogleURL(), "pdf" => $pass->getPDFURL());
        header("Content-Type: application/json");
        echo json_encode($data);
    }
}