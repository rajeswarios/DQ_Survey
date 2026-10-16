<?php
// Change the password before making the survey public.
return ['admin_password' => 'admin@angler',
  // Email settings. Leave 'host' empty to disable emailing.
  'smtp' => [
    'host'       => 'smtp.gmail.com',            // e.g. smtp.gmail.com or mail.yourdomain.com
    'port'       => 587,           // 587 (tls) or 465 (ssl)
    'encryption' => 'tls',         // 'tls', 'ssl' or '' for none
    'username'   => 'anglertech25@gmail.com',
    'password'   => 'zvtb qkcl yvww vjgk',
    'from_email' => 'anglertech25@gmail.com',            // usually the same as username
    'from_name'  => 'DQ Readiness Assessment',
    'admin_copy' => 'jayanthra@angleritech.com',            // optional: also send each summary to this address
  ],
];
