<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/functions.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method Not Allowed'); }
$storedPath = null;
try {
    $file = uploadedFile('avatar');
    [$mime, $extension, $width, $height] = validateImage($file['tmp_name']);
    $id = bin2hex(random_bytes(16));
    $storedName = $id . '.' . $extension;
    $storedPath = UPLOAD_DIR . '/' . $storedName;
    if (!is_dir(UPLOAD_DIR) && !mkdir(UPLOAD_DIR, 0750, true) && !is_dir(UPLOAD_DIR)) throw new RuntimeException('Не удалось создать каталог загрузок.');
    if (!move_uploaded_file($file['tmp_name'], $storedPath)) throw new RuntimeException('Не удалось сохранить файл.');
    $size = filesize($storedPath); $sha256 = hash_file('sha256', $storedPath);
    if ($size === false || $sha256 === false) throw new RuntimeException('Не удалось прочитать сохранённый файл.');
    $originalName = basename((string)$file['name']);
    $displayName = trim((string)($_POST['display_name'] ?? ''));
    if ($displayName === '') $displayName = $originalName;
    if (mb_strlen($displayName, 'UTF-8') > 100) throw new RuntimeException('Название не должно превышать 100 символов.');
    $studentId = trim((string)($_POST['student_id'] ?? ''));
    if (mb_strlen($studentId, 'UTF-8') > 50) throw new RuntimeException('ID студента не должен превышать 50 символов.');
    appendMetadata([
        'id' => $id, 'student_id' => $studentId, 'display_name' => $displayName,
        'original_name' => $originalName, 'stored_name' => $storedName, 'mime' => $mime,
        'size' => $size, 'width' => $width, 'height' => $height, 'sha256' => $sha256,
        'uploaded_at' => (new DateTimeImmutable())->format(DATE_ATOM)
    ]);
    header('Location: index.php?ok=1'); exit;
} catch (Throwable $e) {
    if ($storedPath !== null && is_file($storedPath)) @unlink($storedPath);
    error_log($e->__toString()); header('Location: index.php?error=upload'); exit;
}
