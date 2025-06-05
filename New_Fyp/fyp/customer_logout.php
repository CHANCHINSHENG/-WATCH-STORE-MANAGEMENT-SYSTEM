<?php
session_start();
session_destroy();
header("Location: customermainpage.php");
exit();
?>
