<?php
require_once('../db.php');

header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// CSRF protection: verify Origin / Referer / token
$csrf_ok = false;
$server_host = strtolower($_SERVER['SERVER_NAME']);

// 1. Origin header (present on all cross-origin AJAX requests)
if (!empty($_SERVER['HTTP_ORIGIN'])) {
    $origin_host = strtolower(parse_url($_SERVER['HTTP_ORIGIN'], PHP_URL_HOST));
    $csrf_ok = ($origin_host === $server_host);
}

// 2. Referer header fallback (same-origin AJAX typically sends Referer)
if (!$csrf_ok && !empty($_SERVER['HTTP_REFERER'])) {
    $ref_host_full = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
    if ($ref_host_full) {
        $ref_host = strtolower(explode(':', $ref_host_full)[0]);
        $csrf_ok = ($ref_host === $server_host);
    }
}

// 3. X-CSRF-Token header (for programmatic API access)
if (!$csrf_ok && !empty($_SERVER['HTTP_X_CSRF_TOKEN'])) {
    $csrf_ok = validateCsrf($_SERVER['HTTP_X_CSRF_TOKEN']);
}

if (!$csrf_ok) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid request origin']);
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