<?php
    $dsn="mysql:host=localhost;dbname=watch_store_db";
    $dbusername="root";
    $dbpassword="";

    try {
        $pdo=new PDO($dsn,$dbusername,$dbpassword);

        $pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);

    } catch (PDOException $th) {
        echo "The connection failed: ".$th->getMessage();
    }


