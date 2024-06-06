<?php
 /* Edit this file and save it as config.php to your external server directory */

define('school',          'Musterschule Musterstadt');
// set this to TRUE if you want the students to authenticate themselves with their birthday:
define('require_birthday', TRUE);

define('KORTPRESS_USE_APPLE', 1);  // set to 1 to offer the respective pass type
define('KORTPRESS_USE_GOOGLE', 1);
define('KORTPRESS_USE_PDF', 0);

// internal URLs to request
define('internal_server', '<your internal server URL>');

define('verify_url',      internal_server.'/ID/internal/verify/');
define('register_url',    internal_server.'/ID/internal/register/');

?>