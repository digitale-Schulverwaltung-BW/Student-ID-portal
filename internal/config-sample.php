<?php
  /* Edit this file and save it as config.php to your internal server directory */
  /* local configuration */
  define('STUDENTS_CVS', '/path/to/your/student-csv.csv');
  define('PASS_DB', '<absolute path, writable for php>/passes.sqlite');
  define('KORTPRESS_TOKEN', '<your-API-token>');
  define('KORTPRESS_TEMPLATE_ID', 0000);
  define('invalid_wait', 3); //seconds to wait after an invalid UUID request for rate limiting
  ?>