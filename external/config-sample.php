<?php
 /* Edit this file and save it as config.php to your external server directory */
// internal URLs to request
define('internal_server', '<your internal server URL>');
define('verify_url',      internal_server.'/ID/internal/internal-verify.php?id=');
define('register_url',    internal_server.'/ID/internal/internal-register.php?id=');

// set this to TRUE if you want the students to authenticate themselves with their birthday:
define('require_birthday', TRUE);
?>