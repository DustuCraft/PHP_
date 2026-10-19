<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/functions.php';
try {
    $items = loadMetadata();
    usort($items, fn($a, $b) => strcmp($b['uploaded_at'] ?? '', $a['uploaded_at'] ?? ''));
    $loadError = false;
} catch (Throwable $e) {
    $items = [];
    $loadError = true;
    error_log($e->__toString());
}
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Аватары студентов</title>
<style>
body{font-family:Arial,sans-serif;max-width:1100px;margin:30px auto;padding:0 16px;background:#f4f6f8;color:#222}main{background:#fff;padding:24px;border-radius:12px}form.upload{padding:16px;background:#eef2f5;border-radius:8px;margin-bottom:22px}button{padding:8px 12px;cursor:pointer;border:0;border-radius:5px}table{width:100%;border-collapse:collapse}th,td{text-align:left;padding:10px;border-bottom:1px solid #ddd;overflow-wrap:anywhere}.avatar{width:72px;height:72px;object-fit:cover;border-radius:50%}.message{padding:10px;background:#e8f5e9;margin:12px 0}.error{padding:10px;background:#ffebee;margin:12px 0}.small{font-size:12px;color:#555}.inline-form{display:flex;gap:6px;align-items:center;flex-wrap:wrap}.inline-form input{max-width:150px;padding:7px}.danger{background:#b42318;color:white}.rename{background:#e5e7eb}
</style></head><body><main>
<h1>Реестр аватаров студентов</h1>
<?php if (isset($_GET['ok'])): ?><p class="message">Аватар успешно сохранён.</p><?php endif; ?>
<?php if (isset($_GET['renamed'])): ?><p class="message">Название изменено.</p><?php endif; ?>
<?php if (isset($_GET['deleted'])): ?><p class="message">Аватар удалён.</p><?php endif; ?>
<?php if (isset($_GET['error'])): ?><p class="error">Операция не выполнена. Проверьте данные и попробуйте снова.</p><?php endif; ?>
<?php if ($loadError): ?><p class="error">Не удалось прочитать реестр. Проверьте файл avatars.json.</p><?php endif; ?>
<form class="upload" method="post" action="upload.php" enctype="multipart/form-data">
<label>ID студента (необязательно):<br><input type="text" name="student_id" maxlength="50" placeholder="Например, ИС-24-22-01"></label><p>
<label>Название аватара:<br><input type="text" name="display_name" maxlength="100" placeholder="Например, Ержанат"></label></p>
<label>Выберите JPEG или PNG (до 2 МиБ):<br><input type="file" name="avatar" accept="image/jpeg,image/png,.jpg,.jpeg,.png" required></label>
<p><button type="submit">Загрузить аватар</button></p></form>
<table><tr><th>ID записи</th><th>ID студента</th><th>Аватар</th><th>Название</th><th>Тип</th><th>Размер</th><th>Разрешение</th><th>Действия</th></tr>
<?php foreach ($items as $item): $id = (string)($item['id'] ?? ''); ?>
<tr>
<td><span class="small"><?= h($id) ?></span></td>
<td><?= h((string)($item['student_id'] ?? '—')) ?></td>
<td><img class="avatar" src="download.php?id=<?= h($id) ?>&view=1" alt="Аватар"></td>
<td><?= h((string)($item['display_name'] ?? $item['original_name'] ?? 'Аватар')) ?><br><span class="small"><?= h((string)($item['original_name'] ?? '')) ?></span></td>
<td><?= h((string)($item['mime'] ?? '')) ?></td><td><?= h((string)($item['size'] ?? '')) ?> байт</td>
<td><?= h((string)($item['width'] ?? '')) ?> × <?= h((string)($item['height'] ?? '')) ?></td>
<td><p><a href="download.php?id=<?= h($id) ?>">Скачать</a></p>
<form class="inline-form" method="post" action="rename.php"><input type="hidden" name="id" value="<?= h($id) ?>"><input type="text" name="new_name" maxlength="100" placeholder="Новое название" required><button class="rename" type="submit">Переименовать</button></form>
<form method="post" action="delete.php" onsubmit="return confirm('Точно удалить этот аватар?');"><input type="hidden" name="id" value="<?= h($id) ?>"><button class="danger" type="submit">Удалить</button></form></td>
</tr>
<?php endforeach; ?>
</table></main></body></html>
