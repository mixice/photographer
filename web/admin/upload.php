<?php
require_once('../db.php');

header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// detect type from referer
$upload_dir = 'uploads/tmp/';

if (isset($_FILES['file'])) {
    $file = $_FILES['file'];
} else {
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded']);
    exit;
}

$allowed_extensions = array('jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp');
$file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!in_array($file_extension, $allowed_extensions)) {
    echo json_encode(['error' => 'Only image files are allowed to be uploaded']);
    exit;
}

$max_file_size = 10 * 1024 * 1024;
if ($file['size'] > $max_file_size) {
    echo json_encode(['error' => 'The file is too large. The maximum allowed size is 10MB']);
    exit;
}

$upload_path = $_SERVER['DOCUMENT_ROOT'] . '/' . $upload_dir;
if (!is_dir($upload_path)) mkdir($upload_path, 0755, true);

if (!isValidImageFile($file['tmp_name'])) {
    echo json_encode(['error' => 'Invalid image file']);
    exit;
}

$filename = date('YmdHis') . rand(100, 999) . '.' . $file_extension;
$filepath = $upload_path . $filename;

if (move_uploaded_file($file['tmp_name'], $filepath)) {
    echo json_encode(['location' => '/' . $upload_dir . $filename]);
} else {
    echo json_encode(['error' => 'Upload failed, please try again']);
}
?>