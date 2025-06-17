<?php
ini_set('session.use_only_cookies', 1);
ini_set('session.use_strict_mode', 1);

$timeoutInterval = 30 * 60; 

session_name("admin_session");
session_start();

if (!isset($_SESSION['generation_id'])) {
    session_regenerate_id(true);
    $_SESSION['generation_id'] = time();
} else {
    if (time() - $_SESSION['generation_id'] >= $timeoutInterval) {
        session_regenerate_id(true);
        $_SESSION['generation_id'] = time();
    }
}

