<?php
define('SITE_NAME', 'Swim Data');

define('DB_HOST', 'Localhost'); 
define('DB_USER', 'tbrozek1'); //host username
define('DB_PASS', 'tbrozek1'); //host pw
define('DB_NAME', 'athleticsRecruitingDB'); //database name

if(session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>