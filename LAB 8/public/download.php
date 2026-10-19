<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/functions.php';

$id = $_GET['id'] ?? '';
if (!is_string($id) || !preg_match('/\A[a-f0-9]{32}\z/', $id)) {
    http_response_code(400);
    exit('Bad Request');
}

$item = null;
foreach (loadMetadata() as $candidate) {
    if (($candidate['id'] ?? null) === $id) {
        $item = $candidate;
        break;
    }
}
if (!is_array($item)) {
    http_response_code(404);
    exit('Not Found');
}

$storedName = (string)($item['stored_name'] ?? '');
if (!preg_match('/\A[a-f0-9]{32}\.(jpg|png)\z/', $storedName)) {
    http_response_code(404);
    exit('Not Found');
}
$path = UPLOAD_DIR . '/' . $storedName;
if (!is_file($path)) {
    http_response_code(404);
    exit('Not Found');
}

$mime = ($item['mime'] ?? '') === 'image/png' ? 'image/png' : 'image/jpeg';
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($path));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=3600');

if (isset($_GET['view']) && $_GET['view'] === '1') {
    header('Content-Disposition: inline; filename="avatar.' . ($mime === 'image/png' ? 'png' : 'jpg') . '"');
} else {
    header("Content-Disposition: attachment; filename=\"avatar." . ($mime === 'image/png' ? 'png' : 'jpg') . "\"");
}
readfile($path);
