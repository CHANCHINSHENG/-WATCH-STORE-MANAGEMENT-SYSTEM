<?php

function getusers(object $pdo, string $username) {
    $query = "SELECT AdminID,Admin_Username,Admin_Password FROM 01_ADMIN WHERE Admin_Username  = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$username]);

    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result;
}

