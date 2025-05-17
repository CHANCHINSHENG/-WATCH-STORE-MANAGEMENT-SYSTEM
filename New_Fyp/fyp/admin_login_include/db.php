<?php
    $dsn="mysql:host=localhost;dbname=e-fashion";
    $dbusername="root";
    $dbpassword="";

    try {
        //code...
        $pdo=new PDO($dsn,$dbusername,$dbpassword);

        $pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);

    } catch (PDOException $th) {
        //throw $th;    
        echo "The connection failed: ".$th->getMessage();
    }


