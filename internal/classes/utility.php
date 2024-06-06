<?php

require_once('config.php');

class Utility{
    // generate validity date String (human readable)
    // adjust end date here
    function validShort(): String
    {
        $month=date("m");
        $year=date("Y");
        if ($month>8) return SCHOOLYEAR_START.$year+1;
        return SCHOOLYEAR_START."/$year";
    }

    // generate validity date String (machine readable).
    // adjust end date here
    function validLong(): String
    {
        $month=date("m");
        $year=date("Y");
        $m=(SCHOOLYEAR_START<10)?"0".SCHOOLYEAR_START:SCHOOLYEAR_START;
        if ($month>8) return ($year+1)."-$m-30T12:00:00.118Z";
        return "$year-$m-30T12:00:00.118Z";
    }
}
?>