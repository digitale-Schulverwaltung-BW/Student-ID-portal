<?php
  /* Edit this file and save it as config.php to your internal server directory */
  /* local configuration */
  define('STUDENTS_CVS', '/path/to/your/student-csv.csv');
  define('PASS_DB', '<absolute path, writable for php>/passes.sqlite'); // outside web root!
  define('KORTPRESS_TOKEN', '<your-API-token>');
  define('KORTPRESS_TEMPLATE_ID', 0000);
  define('KORTPRESS_USE_APPLE', 1);  // set to 1 to offer the respective pass type
  define('KORTPRESS_USE_GOOGLE', 1);
  define('KORTPRESS_USE_PDF', 1);

  define('VERIFY_BASE_URL', 'https://www.example.com/ID/v/'); // this points to your external-folder. Trailing / 
  define('SCHOOL', 'Musterschule Musterstadt');
  define('SCHOOLYEAR_START', 9); // last month of validity
  define('IMG_BASE_URL', 'https://www.example.com/ID/templates/');
  define('SCHOOL_URL', 'https://www.example.com/');
  
  // CSV structure. Specify the positions of the relevant fields here.
  // Example: we have
  // login;shortname;idnumber;lastname;firstname;email;Klasse;birthday;Austrittsdatum;Eintrittsdatum
  //
  define('CSV_ID', 2);
  define('CSV_LOGIN', 0);  // unique login identifier used to identify passes in backend
  define('CSV_LAST', 3);
  define('CSV_FIRST', 4);
  define('CSV_CLASS', 6);
  define('CSV_BIRTHDAY', 7);
  
  define('invalid_wait', 3); //seconds to wait after an invalid UUID request for rate limiting
  ?>