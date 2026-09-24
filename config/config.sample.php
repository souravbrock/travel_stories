<?php
// Travel Stories shared config. Copy to config.php and fill values.
// config.php is gitignored on server (keep secrets out of GitHub).
return [
  'app_name' => 'Travel Stories',
  'app_url'  => getenv('APP_URL') ?: 'https://tstory.reddevils.co.in',
  'env'      => getenv('APP_ENV') ?: 'production', // production | dev

  'db' => [
    'host' => getenv('DB_HOST') ?: 'localhost',
    'name' => getenv('DB_NAME') ?: 'reddevil_tstory',
    'user' => getenv('DB_USER') ?: 'reddevil_tstory',
    'pass' => getenv('DB_PASS') ?: 'CHANGE_ME',
    'charset' => 'utf8mb4',
  ],

  // Registration + general communication go from no-reply; all booking mail from booking.
  // Uses PHP mail() by default; set use_smtp=true to use SMTP via sockets (no composer needed).
  'mail' => [
    'from_noreply' => 'Travel Stories <no-reply@tstory.reddevils.co.in>',
    'from_booking' => 'Travel Stories Bookings <booking@tstory.reddevils.co.in>',
    'notify_booking' => 'booking@tstory.reddevils.co.in', // lead inbox for platform-managed bookings
    'use_smtp' => false,
    'smtp_host' => 'mail.reddevils.co.in',
    'smtp_port' => 465,
    'smtp_user' => 'no-reply@tstory.reddevils.co.in',
    'smtp_pass' => 'CHANGE_ME',
    'smtp_secure' => 'ssl', // ssl | tls
  ],

  'otp' => [
    'length' => 5,
    'ttl_minutes' => 15,
    'max_attempts' => 5,
  ],

  // Token sessions (simple HMAC tokens, no JWT lib needed)
  'auth' => [
    'secret' => getenv('AUTH_SECRET') ?: 'CHANGE_ME_32CHARS_MIN',
    'token_days' => 30,
  ],

  'uploads_dir' => __DIR__ . '/../public/uploads',
  'uploads_url' => '/uploads',
];
