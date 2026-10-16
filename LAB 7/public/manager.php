<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/session.php';
requireRole('manager');          // вход + роль; иначе 403 или редирект

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValid()) {
        denyCsrf();
    }
    $id     = filter_var($_POST['request_id'] ?? null, FILTER_VALIDATE_INT);
    $status = $_POST['status'] ?? null;

    if (is_int($id) && is_string($status) && setRequestStatus($id, $status)) {
        redirect('/manager.php?updated=1');
    }
    http_response_code(400);
    exit('Bad request');
}

$requests = loadRequests();

pageHead(t('manager_panel'));
?>
<h1><?= h(t('manager_panel')) ?></h1>
<p><a href="/dashboard.php"><?= h(t('back')) ?></a></p>
<?php if (isset($_GET['updated'])): ?><p class="ok"><?= h(t('updated')) ?></p><?php endif; ?>

<h2><?= h(t('all_requests')) ?></h2>
<?php if ($requests === []): ?>
  <p><?= h(t('none')) ?></p>
<?php else: ?>
  <table>
    <tr><th>#</th><th><?= h(t('author')) ?></th><th><?= h(t('req_title')) ?></th><th><?= h(t('status')) ?></th></tr>
    <?php foreach ($requests as $r): ?>
      <tr>
        <td><?= (int) $r['id'] ?></td>
        <td><?= h($r['email']) ?></td>
        <td><?= h($r['title']) ?><br><small><?= h($r['text']) ?></small></td>
        <td>
          <form method="post" class="inline">
            <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="request_id" value="<?= (int) $r['id'] ?>">
            <select name="status">
              <?php foreach (REQUEST_STATUSES as $s): ?>
                <option value="<?= h($s) ?>"<?= $s === $r['status'] ? ' selected' : '' ?>><?= h(t('st_' . $s)) ?></option>
              <?php endforeach; ?>
            </select>
            <button type="submit"><?= h(t('save')) ?></button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>
</body></html>
