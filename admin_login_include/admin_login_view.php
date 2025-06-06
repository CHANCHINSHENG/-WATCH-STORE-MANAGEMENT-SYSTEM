<?php

    function viewerror(){
        if(isset($_SESSION['errors_details'])){
            $errors=$_SESSION['errors_details'];

            foreach ($errors as $error) {
                echo "<div class='error-message'>" . $error . "</div>";
            }
            unset($_SESSION['errors_details']);
        }else if(isset($_GET['login'])){
            echo "You Are Login succesfully";
        }
    }