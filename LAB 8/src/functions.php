<?php
declare(strict_types=1);

const MAX_FILE_SIZE = 2 * 1024 * 1024;
const DATA_FILE = __DIR__ . '/../var/data/avatars.json';
const UPLOAD_DIR = __DIR__ . '/../var/uploads';

function h(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function uploadedFile(string $field): array {
    $file = $_FILES[$field] ?? null;
    if (!is_array($file) || is_array($file['error'] ?? null)) {
        throw new RuntimeException('Некорректное поле файла.');
    }
    $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($error !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Файл не загружен. Код: ' . (string)$error);
    }
    if (!isset($file['size'], $file['tmp_name'], $file['name'])
        || !is_int($file['size']) || $file['size'] < 1
        || $file['size'] > MAX_FILE_SIZE) {
        throw new RuntimeException('Размер файла должен быть от 1 байта до 2 МиБ.');
    }
    if (!is_string($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('Источник не является HTTP-загрузкой.');
    }
    return $file;
}

function validateImage(string $path): array {
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($path);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
    if (!is_string($mime) || !isset($allowed[$mime])) {
        throw new RuntimeException('Разрешены только JPEG и PNG.');
    }
    $imageInfo = @getimagesize($path);
    if ($imageInfo === false || !isset($imageInfo[0], $imageInfo[1], $imageInfo['mime'])) {
        throw new RuntimeException('Не удалось определить параметры изображения.');
    }
    if ($imageInfo['mime'] !== $mime) {
        throw new RuntimeException('Тип изображения не совпадает с MIME.');
    }
    $width = (int)$imageInfo[0];
    $height = (int)$imageInfo[1];
    if ($width < 1 || $height < 1 || $width > 2000 || $height > 2000) {
        throw new RuntimeException('Разрешение не должно превышать 2000×2000 пикселей.');
    }
    return [$mime, $allowed[$mime], $width, $height];
}

function withMetadataLock(callable $callback): mixed {
    $lock = fopen(DATA_FILE . '.lock', 'c');
    if ($lock === false) throw new RuntimeException('Не удалось открыть блокировку JSON.');
    if (!flock($lock, LOCK_EX)) {
        fclose($lock);
        throw new RuntimeException('Не удалось заблокировать JSON-реестр.');
    }
    try {
        $json = is_file(DATA_FILE) ? file_get_contents(DATA_FILE) : '[]';
        if ($json === false) throw new RuntimeException('Не удалось прочитать JSON-реестр.');
        $items = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($items)) throw new RuntimeException('Неверная структура JSON-реестра.');
        return $callback($items);
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

function saveMetadataArray(array $items): void {
    $newJson = json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    $tmp = DATA_FILE . '.' . bin2hex(random_bytes(6)) . '.tmp';
    if (file_put_contents($tmp, $newJson, LOCK_EX) === false || !rename($tmp, DATA_FILE)) {
        @unlink($tmp);
        throw new RuntimeException('Не удалось обновить JSON-реестр.');
    }
}

function appendMetadata(array $item): void {
    withMetadataLock(function (array $items) use ($item): void {
        $items[] = $item;
        saveMetadataArray($items);
    });
}

function loadMetadata(): array {
    if (!is_file(DATA_FILE)) return [];
    $json = file_get_contents(DATA_FILE);
    if ($json === false) throw new RuntimeException('Не удалось прочитать JSON-реестр.');
    $items = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($items)) throw new RuntimeException('Неверная структура JSON-реестра.');
    return $items;
}

function validAvatarId(string $id): bool {
    return (bool)preg_match('/\A[a-f0-9]{32}\z/', $id);
}

function renameAvatar(string $id, string $newName): void {
    $newName = trim($newName);
    if ($newName === '' || mb_strlen($newName, 'UTF-8') > 100) {
        throw new RuntimeException('Название должно содержать от 1 до 100 символов.');
    }
    withMetadataLock(function (array $items) use ($id, $newName): void {
        $found = false;
        foreach ($items as &$item) {
            if (($item['id'] ?? '') === $id) {
                $item['display_name'] = $newName;
                $found = true;
                break;
            }
        }
        unset($item);
        if (!$found) throw new RuntimeException('Аватар не найден.');
        saveMetadataArray($items);
    });
}

function deleteAvatar(string $id): void {
    withMetadataLock(function (array $items) use ($id): void {
        $target = null;
        $newItems = [];
        foreach ($items as $item) {
            if (($item['id'] ?? '') === $id) $target = $item;
            else $newItems[] = $item;
        }
        if (!is_array($target)) throw new RuntimeException('Аватар не найден.');
        $storedName = (string)($target['stored_name'] ?? '');
        if (!preg_match('/\A[a-f0-9]{32}\.(jpg|png)\z/', $storedName)) {
            throw new RuntimeException('Некорректное имя файла в реестре.');
        }
        $path = UPLOAD_DIR . '/' . $storedName;
        $temporaryPath = null;
        if (is_file($path)) {
            $temporaryPath = UPLOAD_DIR . '/.delete-' . bin2hex(random_bytes(8));
            if (!rename($path, $temporaryPath)) throw new RuntimeException('Не удалось подготовить файл к удалению.');
        }
        try {
            saveMetadataArray($newItems);
        } catch (Throwable $e) {
            if ($temporaryPath !== null && is_file($temporaryPath)) @rename($temporaryPath, $path);
            throw $e;
        }
        if ($temporaryPath !== null && is_file($temporaryPath) && !unlink($temporaryPath)) {
            error_log('Не удалось удалить временный файл аватара: ' . basename($temporaryPath));
        }
    });
}
