<?php
require_once 'admin_login_include/config_session.php';
session_unset();           
session_destroy();          
header("Location: admin_login.php"); 
exit(); 
