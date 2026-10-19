<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/functions.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method Not Allowed'); }
$id = $_POST['id'] ?? ''; $newName = $_POST['new_name'] ?? '';
if (!is_string($id) || !validAvatarId($id) || !is_string($newName)) { header('Location: index.php?error=rename'); exit; }
try { renameAvatar($id, $newName); header('Location: index.php?renamed=1'); }
catch (Throwable $e) { error_log($e->__toString()); header('Location: index.php?error=rename'); }
exit;
