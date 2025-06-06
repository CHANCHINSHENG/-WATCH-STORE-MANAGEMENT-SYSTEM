<?php
function setSuccess(string $message) {
    $_SESSION['success'] = $message;
}

function setError(string $message) {
    $_SESSION['error_signup'][] = $message;
}

function redirectBack() {
    header("Location: ../admin_layout.php?page=admin_add_category");
    exit();
}

function displayFormMessages() {
    if (isset($_SESSION['success'])) {
        echo "<div class='message success'>" . htmlspecialchars($_SESSION['success']) . "</div>";
        unset($_SESSION['success']);
    }

    if (isset($_SESSION['error_signup'])) {
        foreach ($_SESSION['error_signup'] as $msg) {
            echo "<div class='message error'>" . htmlspecialchars($msg) . "</div>";
        }
        unset($_SESSION['error_signup']);
    }
}
