<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/session.php';
requireLogin();

$message = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValid()) {
        denyCsrf();
    }
    $action = $_POST['action'] ?? null;

    if ($action === 'language') {
        $lang = $_POST['lang'] ?? null;
        if (is_string($lang) && in_array($lang, ALLOWED_LANGS, true)) {
            setLanguageCookie($lang);
        }
        redirect('/dashboard.php');
    }

    if ($action === 'create_request') {
        if ($_SESSION['role'] !== 'resident') {      // создавать заявки может только жилец
            http_response_code(403);
            exit('Forbidden');
        }
        $title = $_POST['title'] ?? null;
        $text  = $_POST['text'] ?? null;
        $title = is_string($title) ? trim($title) : '';
        $text  = is_string($text) ? trim($text) : '';

        if ($title !== '' && $text !== '' && mb_strlen($title) <= 100 && mb_strlen($text) <= 500) {
            createRequest($_SESSION['user_id'], (string) $_SESSION['email'], $title, $text);
            redirect('/dashboard.php?created=1');
        }
        $error = t('invalid');
    }
}

if (isset($_GET['created'])) {
    $message = t('created');
}

$isResident = $_SESSION['role'] === 'resident';
$mine = $isResident
    ? array_values(array_filter(
        loadRequests(),
        fn (array $r): bool => $r['user_id'] === $_SESSION['user_id']
    ))
    : [];

pageHead(t('title'));
?>
<h1><?= h(t('title')) ?></h1>
<p><?= h(t('user')) ?>: <?= h((string) $_SESSION['email']) ?></p>
<p><?= h(t('role')) ?>: <?= h((string) $_SESSION['role']) ?></p>

<form method="post" class="inline">
  <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
  <input type="hidden" name="action" value="language">
  <label><?= h(t('language')) ?>
    <select name="lang">
      <?php foreach (ALLOWED_LANGS as $l): ?>
        <option value="<?= h($l) ?>"<?= $l === currentLang() ? ' selected' : '' ?>><?= h($l) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <button type="submit"><?= h(t('save')) ?></button>
</form>

<form method="post" action="/logout.php" class="inline">
  <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
  <button type="submit"><?= h(t('sign_out')) ?></button>
</form>

<?php if ($message !== ''): ?><p class="ok"><?= h($message) ?></p><?php endif; ?>
<?php if ($error !== ''): ?><p class="err"><?= h($error) ?></p><?php endif; ?>

<?php if ($isResident): ?>
  <h2><?= h(t('new_request')) ?></h2>
  <form method="post">
    <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
    <input type="hidden" name="action" value="create_request">
    <label><?= h(t('req_title')) ?>
      <input name="title" maxlength="100" required>
    </label>
    <label><?= h(t('req_text')) ?>
      <textarea name="text" maxlength="500" rows="4" required></textarea>
    </label>
    <button type="submit"><?= h(t('create')) ?></button>
  </form>

  <h2><?= h(t('my_requests')) ?></h2>
  <?php if ($mine === []): ?>
    <p><?= h(t('none')) ?></p>
  <?php else: ?>
    <table>
      <tr><th>#</th><th><?= h(t('req_title')) ?></th><th><?= h(t('status')) ?></th></tr>
      <?php foreach ($mine as $r): ?>
        <tr>
          <td><?= (int) $r['id'] ?></td>
          <td><?= h($r['title']) ?><br><small><?= h($r['text']) ?></small></td>
          <td><?= h(t('st_' . $r['status'])) ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
<?php else: ?>
  <p><a href="/manager.php"><?= h(t('manager_panel')) ?></a></p>
<?php endif; ?>
</body></html>
