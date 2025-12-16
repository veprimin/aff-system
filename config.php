<?php
// Global configuration

define('DB_HOST', 'localhost');
define('DB_NAME', 'mycatime_introduce');
define('DB_USER', 'mycatime_introduce');
define('DB_PASS', '32569801@Vmp');

define('CLIENT_SLUG', 'clinicsecret');

// Payout rules
define('PAYOUT_SEMA_MONTHLY', 70);
define('PAYOUT_TIRZ_MONTHLY', 100);
define('PAYOUT_3MO_PACK', 200);

// Start session
session_name('clinicsecret_portal');
session_start();
