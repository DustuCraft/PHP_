<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/session.php';

if (isset($_SESSION['user_id'])) {
    redirect('/dashboard.php');
}

$error = '';
$email = '';
$_SESSION['login_csrf'] ??= bin2hex(random_bytes(32));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailValue    = $_POST['email'] ?? null;
    $passwordValue = $_POST['password'] ?? null;

    if (is_string($emailValue) && is_string($passwordValue) && csrfValid('login_csrf')) {
        $email = strtolower(trim($emailValue));
        $user  = authenticate($email, $passwordValue);
        if ($user !== null) {
            loginUser($user['id'], $email, $user['role']);
            redirect('/dashboard.php');
        }
    }
    // Единое сообщение: не раскрываем, какое именно поле неверно.
    $error = t('bad_login');
}

pageHead(t('login'));
?>
<h1><?= h(t('title')) ?></h1>
<?php if (isset($_GET['expired'])): ?>
  <p class="err"><?= h(t('expired')) ?></p>
<?php endif; ?>
<?php if ($error !== ''): ?>
  <p class="err"><?= h($error) ?></p>
<?php endif; ?>
<form method="post" action="/login.php">
  <input type="hidden" name="csrf_token" value="<?= h($_SESSION['login_csrf']) ?>">
  <label><?= h(t('email')) ?>
    <input name="email" type="email" required value="<?= h($email) ?>">
  </label>
  <label><?= h(t('password')) ?>
    <input name="password" type="password" required>
  </label>
  <button type="submit"><?= h(t('sign_in')) ?></button>
</form>
</body></html>
