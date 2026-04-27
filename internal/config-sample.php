<?php
  /* Edit this file and save it as config.php to your internal server directory */
  /* local configuration */
  define('STUDENTS_CSV', '/path/to/your/student-csv.csv');
  define('BLACKLIST', ''); // these IDs are not allowed to register
  define('WALLET_API_BASE', 'https://verwaltung-wallet.hhs.karlsruhe.de/v1');
  define('WALLET_API_KEY', '<your-tenant-API-key>');
  define('WALLET_THEME_ID', '<your-theme-uuid>');
  define('WALLET_USE_APPLE', 1);  // set to 1 to offer the respective pass type
  define('WALLET_USE_GOOGLE', 1);

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
  define('CSV_EXITD', 8);

  // Email-to-ID lookup (GET /ID/@email) — uses STUDENTS_CSV and CSV_ID defined above
  define('CSV_EMAIL_COL', 5);  // column index of email address in the student CSV

  define('invalid_wait', 3); //seconds to wait after an invalid UUID request for rate limiting

  // Admin access control (both are optional; leave empty to disable the respective check)
  // ADMIN_CIDR: comma-separated IPv4 CIDRs allowed to reach /admin, e.g. '10.0.0.0/8,192.168.1.0/24'
  // ADMIN_BLOCK_IP: comma-separated IPv4 IPs/CIDRs always denied (e.g. the external server's IP)
  define('ADMIN_CIDR', '');
  define('ADMIN_BLOCK_IP', '');
  ?>
