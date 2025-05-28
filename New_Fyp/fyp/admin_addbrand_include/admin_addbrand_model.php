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

function uploadBrandImage(?array $file): string {
    if ($file && $file['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) return '';

        $uploadDir = '../uploads/brand_picture/';
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

        $newName = uniqid('brand_', true) . '.' . $ext;
        $path = $uploadDir . $newName;

        return move_uploaded_file($file['tmp_name'], $path) ? $path : '';
    }
    return '';
}
