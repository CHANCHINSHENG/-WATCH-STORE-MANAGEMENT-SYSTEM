<?php
function displayFormMessages() {
    if (isset($_SESSION['success'])) {
        echo "<div class='message success'>" . $_SESSION['success'] . "</div>";
        
        unset($_SESSION['success']);
    }

    if (isset($_SESSION['error_signup']) && is_array($_SESSION['error_signup'])) {
        foreach ($_SESSION['error_signup'] as $error) {
            echo "<div class='message error'>" . htmlspecialchars($error) . "</div>";
        }
        unset($_SESSION['error_signup']);
    }
}
