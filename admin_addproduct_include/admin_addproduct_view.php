<?php
function displayFormMessages() {
    if (isset($_SESSION['success'])) {
        echo "<div class='message success'>" . $_SESSION['success'] . "</div>";
        unset($_SESSION['success']);
    }

    if (isset($_SESSION['error_signup'])) {
        foreach ($_SESSION['error_signup'] as $msg) {
            echo "<div class='message error'>" . $msg . "</div>";
        }
        unset($_SESSION['error_signup']);
    }
}
