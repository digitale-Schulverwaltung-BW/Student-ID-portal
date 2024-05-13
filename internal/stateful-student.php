<?php

// Ubuntu package: mongodb-org (see install instructions on mongodb.com)

require_once('issued_passes.php');
$db = new SQLite3('students.db', SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE);

class CStatefulStudent extends CStudent
{
    protected $passID;
    function __construct($newID) {
        parent::__construct($newID);
        $db->query('CREATE TABLE IF NOT EXISTS "passes" (
            "studID" INTEGER PRIMARY KEY NOT NULL,
            "passID" VARCHAR
        )');
        $results = $db->query('SELECT passID from passes WHERE studID='.$this->ID);
        while ($row = $results->fetchArray()) {
            $this->passID=$row['passID'];
            echo $this->passID;
        }
    }
}