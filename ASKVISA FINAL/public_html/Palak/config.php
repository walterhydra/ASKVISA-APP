<?php
// config.php
define('ENVIRONMENT', 'local');

if (ENVIRONMENT === 'local') {
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'visa_test');
    define('DB_USER', 'root');
    define('DB_PASS', '');
} else {
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'u261509590_askvisa_group');
    define('DB_USER', 'u261509590_askvisa');
    define('DB_PASS', 'a+3oYU3>JflH');
}
