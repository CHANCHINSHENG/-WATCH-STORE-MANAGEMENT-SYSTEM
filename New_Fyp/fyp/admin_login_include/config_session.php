<?php
// Enforce secure cookie/session settings
ini_set('session.use_only_cookies', 1);
ini_set('session.use_strict_mode', 1);

// Optionally set session timeout behavior
$timeoutInterval = 30 * 60; // 30 minutes


session_start();

// Session regeneration logic
if (!isset($_SESSION['generation_id'])) {
    session_regenerate_id(true);
    $_SESSION['generation_id'] = time();
} else {
    if (time() - $_SESSION['generation_id'] >= $timeoutInterval) {
        session_regenerate_id(true);
        $_SESSION['generation_id'] = time();
    }
}

