<?php

    require('config.php');

    if(!isset($_REQUEST['id'])) {
        die("no id");
    }

    function getLineWithString($fileName, $str) {
        $lines = file($fileName);
        foreach ($lines as $lineNumber => $line) {
            if (strpos($line, $str) !== false) {
                return $line;
            }
        }
        return -1;
    }

    if (!isset($_REQUEST['id']) || empty($_REQUEST['id'])) return "";
    $id=$_REQUEST['id'];
    if (strlen($id)!=36) return "";
    $studentline=getLineWithString(STUDENTS_CVS, $id);
    if( $studentline !== -1) {
        // valid student ID
        $data = str_getcsv($studentline, ";");
        $data = array("id" => $data[2], "lastname" => $data[3], "firstname" => $data[4]);
        header("Content-Type: application/json");
        echo json_encode($data);
    } else {
        // invalid student ID
        sleep(invalid_wait);
        $data = array("id" => 0, "lastname" => "", "firstname" => "");
        header("Content-Type: application/json");
        echo json_encode($data);
    }
?>
