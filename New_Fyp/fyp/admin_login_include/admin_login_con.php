<?php
    function emptyerrors(string $username,string $pwd){
        if(empty($username) || empty($pwd)){
            return true;
        }else{
            return false;
        }
    }

    function verifyusername(bool | array $result){
        if(!$result){
            return true;
        }else{
            return false;
        }
    }
    function verifypassword(string $pwd,string $hashpassword) {
        // If the password does not match the hash, return true (indicating invalid password)
        if (!password_verify($pwd, $hashpassword)) {
            return true;  // Invalid password
        } else {
            return false; // Valid password
        }
    }

    