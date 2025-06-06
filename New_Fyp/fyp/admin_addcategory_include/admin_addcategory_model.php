<?php
function insertCategory(object $pdo, string $categoryName): string {
    try {
        $query = "INSERT INTO 04_category (CategoryName) VALUES (?)";
        $stmt = $pdo->prepare($query);

        if (!$stmt) {
            return "❌ Failed to prepare statement.";
        }

        $stmt->execute([$categoryName]);
        return "✅ Category added successfully!";
    } catch (PDOException $e) {
        return "❌ Database error: " . $e->getMessage();
    }
}
