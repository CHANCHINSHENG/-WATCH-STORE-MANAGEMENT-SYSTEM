<?php
function insertdetail(object $pdo, string $brandName, string $imagePath) {
    try {
        $query = "INSERT INTO 03_brand (BrandName, BrandImage) VALUES (?, ?)";
        $stmt = $pdo->prepare($query);

        if (!$stmt) {
            return "❌ Failed to prepare statement.";
        }

        $stmt->execute([$brandName, $imagePath]);
        return "✅ Brand added successfully!";
    } catch (PDOException $e) {
        return "❌ Database error: " . $e->getMessage();
    }
}

function uploadBrandImage($file) {
    if ($file && $file['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'brand_picture/';
        $filename = basename($file['name']);
        $targetPath = $uploadDir . $filename;

        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return $targetPath; 
        }
    }

    return null;
}

