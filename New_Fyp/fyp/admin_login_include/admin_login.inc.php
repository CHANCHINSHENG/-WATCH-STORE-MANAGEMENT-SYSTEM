<?php

    if($_SERVER['REQUEST_METHOD']==="POST"){
        $username=$_POST["Admin_Username"];
        $pwd=$_POST["Admin_Password"];
        try{
            require_once 'config_session.php';
            require_once 'admin_login_con.php';
            require_once 'admin_login_view.php';
            require_once 'admin_login_model.php';
            require_once 'db.php';

            $errors=[];
            $result=getusers($pdo,$username);


            if(emptyerrors($username,$pwd)){
                $errors['empty_errors']="PLEASE fill out the blank!";
            }else if(verifyusername($result)){
                $errors['errors_username']="Invalid username!";
            }else if(verifypassword($pwd,$result['Admin_Password'])){
                $errors['errors_password']="Invalid password!";
            }

            if($errors){
                $_SESSION['errors_details']=$errors;

                header("Location: ../admin_login.php");
                exit();
            }

            $_SESSION['admin_id']=$result['Admin_Username'];
            header("Location: ../admin_dashboard.php?login=success");

            $pdo=null;
            $stmt=null;
            die();

        }catch(PDOException $th){
            die("The error syntax: ".$th->getMessage());
        }
    }else{
        header("Location: ../admin_login.php");
        die();
    }