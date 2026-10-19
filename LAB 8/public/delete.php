<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/functions.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method Not Allowed'); }
$id = $_POST['id'] ?? '';
if (!is_string($id) || !validAvatarId($id)) { header('Location: index.php?error=delete'); exit; }
try { deleteAvatar($id); header('Location: index.php?deleted=1'); }
catch (Throwable $e) { error_log($e->__toString()); header('Location: index.php?error=delete'); }
exit;
