<?php
function insertCategory($pdo, $categoryName) {
    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM 04_category WHERE LOWER(CategoryName) = LOWER(?)");
    $checkStmt->execute([$categoryName]);
    $count = $checkStmt->fetchColumn();

    if ($count > 0) {
        return "❌ Category already exists.";
    }

    $stmt = $pdo->prepare("INSERT INTO 04_category (CategoryName) VALUES (?)");
    if ($stmt->execute([$categoryName])) {
        return "✅ Category added successfully!";
    } else {
        return "❌ Failed to add category.";
    }
}
