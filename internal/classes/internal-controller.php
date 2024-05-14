<?php
require_once('config.php');
require_once('pass.php');

class InternalController
{
    private function getLineWithString($fileName, $str): String {
        $lines = file($fileName);
        foreach ($lines as $lineNumber => $line) {
            if (strpos($line, $str) !== false) {
                return $line;
            }
        }
        return "";
    }

    private function checkID($id): bool
    {
        if (!isset($id) || empty($id) || strlen($id)!=36) return false;
        return true;
    }

    function verify($f3, $args): void
    {
        if (!$this->checkID($args['id'])) return;
        $id=$args['id'];
        $studentline=$this->getLineWithString(STUDENTS_CVS, $id);
        if( $studentline !== "") {
            // valid student ID
            $data = str_getcsv($studentline, ";");
            $resp = array("id" => $data[2], "lastname" => $data[3], "firstname" => $data[4]);
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
        $id=$args['id'];
        if (!$this->checkID($id)) return;
        $pass   = new CStudentPass($f3, $id);
        $passID = $pass->getPassID();
        $data = array("id" => $passID, "apple" => $pass->getAppleURL(), "google" => $pass->getGoogleURL(), "pdf" => $pass->getPDFURL());
        header("Content-Type: application/json");
        echo json_encode($data);
    }
}