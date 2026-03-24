<?php
 /* Edit this file and save it as config.php to your external server directory 
    External server configuration
*/

define('school',          'Musterschule Musterstadt');
// set this to TRUE if you want the students to authenticate themselves with their birthday:
define('require_birthday', TRUE);

define('WALLET_USE_APPLE', 1);  // set to 1 to offer the respective pass type
define('WALLET_USE_GOOGLE', 1);

// internal URLs to request
define('internal_server', '<your internal server URL>');

// set to false on production
define('logging', false); 

// no configuration needed below
define('verify_url',      internal_server.'/ID/internal/verify/');
define('register_url',    internal_server.'/ID/internal/register/');
define('lookup_url',      internal_server.'/ID/internal/lookup/');
define('apple_pass_url',  internal_server.'/ID/internal/apple/');

?>