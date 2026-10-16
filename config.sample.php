<?php
// Copy to config.php (not tracked in git). Change the password before making the survey public.
return ['admin_password' => 'CHANGE-ME',
  // Email settings. Leave 'host' empty to disable emailing.
  'smtp' => [
    'host'       => '',            // e.g. smtp.gmail.com or mail.yourdomain.com
    'port'       => 587,           // 587 (tls) or 465 (ssl)
    'encryption' => 'tls',         // 'tls', 'ssl' or '' for none
    'username'   => '',
    'password'   => '',
    'from_email' => '',            // usually the same as username
    'from_name'  => 'DQ Readiness Assessment',
    'admin_copy' => '',            // optional: also send each summary to this address
  ],
];
