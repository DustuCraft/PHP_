<?php
declare(strict_types=1);

/* Не показывать ошибки PHP пользователю, писать в лог */
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

/* ---------- Справочники (allow-list) ---------- */

$roles = [
    'listener'  => 'Слушатель',
    'speaker'   => 'Докладчик',
    'volunteer' => 'Волонтёр',
];

$sections = [
    'web'  => 'Веб-разработка',
    'db'   => 'Базы данных',
    'sec'  => 'Информационная безопасность',
    'ai'   => 'Искусственный интеллект',
];

$formats = [
    'onsite' => 'Очно',
    'online' => 'Онлайн',
];

/* ---------- Вспомогательные функции ---------- */

/* Экранирование при выводе (защита от XSS) */
function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/* Безопасно получить строковое поле из POST (поле может отсутствовать или быть массивом) */
function postString(string $key): ?string
{
    if (!isset($_POST[$key]) || !is_string($_POST[$key])) {
        return null;
    }
    return trim($_POST[$key]);
}

/* ---------- Отдельные функции валидации ---------- */
/* Каждая возвращает строку с ошибкой или null, если всё верно. */

function validateFullName(?string $v): ?string
{
    if ($v === null || $v === '') return 'Укажите ФИО.';
    $len = mb_strlen($v);
    if ($len < 5 || $len > 100) return 'ФИО должно содержать от 5 до 100 символов.';
    if (!preg_match('/^[\p{L}\' -]+$/u', $v)) return 'ФИО может содержать только буквы, пробелы, дефис и апостроф.';
    if (count(preg_split('/\s+/u', $v, -1, PREG_SPLIT_NO_EMPTY)) < 2) return 'Укажите минимум фамилию и имя.';
    return null;
}

function validateEmail(?string $v): ?string
{
    if ($v === null || $v === '') return 'Укажите e-mail.';
    if (mb_strlen($v) > 254) return 'E-mail слишком длинный (максимум 254 символа).';
    if (filter_var($v, FILTER_VALIDATE_EMAIL) === false) return 'Неверный формат e-mail.';
    return null;
}

/* Универсальная строгая проверка значения по allow-list */
function validateChoice(?string $v, array $allowed, string $emptyMsg, string $invalidMsg): ?string
{
    if ($v === null || $v === '') return $emptyMsg;
    if (!in_array($v, array_keys($allowed), true)) return $invalidMsg;
    return null;
}

function validateRole(?string $v, array $allowed): ?string
{
    return validateChoice($v, $allowed, 'Выберите роль.', 'Выбрана недопустимая роль.');
}

function validateSection(?string $v, array $allowed): ?string
{
    return validateChoice($v, $allowed, 'Выберите секцию.', 'Выбрана недопустимая секция.');
}

function validateFormat(?string $v, array $allowed): ?string
{
    return validateChoice($v, $allowed, 'Выберите формат участия.', 'Выбран недопустимый формат участия.');
}

function validateConsent(?string $v): ?string
{
    if ($v !== '1') return 'Необходимо подтвердить согласие на обработку данных.';
    return null;
}

/* ---------- Обработка запроса ---------- */

$old = ['fullname' => '', 'email' => '', 'role' => '', 'section' => '', 'format' => '', 'consent' => ''];
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'fullname' => postString('fullname'),
        'email'    => postString('email'),
        'role'     => postString('role'),
        'section'  => postString('section'),
        'format'   => postString('format'),
        'consent'  => postString('consent'),
    ];

    /* Повторное заполнение формы (только строки) */
    foreach ($data as $k => $v) {
        $old[$k] = $v ?? '';
    }

    /* Ошибки хранятся по ключам полей */
    $checks = [
        'fullname' => validateFullName($data['fullname']),
        'email'    => validateEmail($data['email']),
        'role'     => validateRole($data['role'], $roles),
        'section'  => validateSection($data['section'], $sections),
        'format'   => validateFormat($data['format'], $formats),
        'consent'  => validateConsent($data['consent']),
    ];
    $errors = array_filter($checks, static fn($e) => $e !== null);
    $success = $errors === [];
}

/* Единый шаблон вывода ошибки рядом с полем */
function fieldError(array $errors, string $key): string
{
    if (!isset($errors[$key])) return '';
    return '<p class="error" id="err-' . h($key) . '">' . h($errors[$key]) . '</p>';
}

function ariaAttrs(array $errors, string $key): string
{
    return isset($errors[$key]) ? ' aria-invalid="true" aria-describedby="err-' . h($key) . '"' : '';
}

/* Вывод <select> по справочнику */
function renderSelect(string $name, string $label, array $options, string $selected, array $errors): string
{
    $html  = '<label for="' . h($name) . '">' . h($label) . '</label>';
    $html .= '<select id="' . h($name) . '" name="' . h($name) . '" required' . ariaAttrs($errors, $name) . '>';
    $html .= '<option value="">— выберите —</option>';
    foreach ($options as $value => $title) {
        $sel = ((string)$value === $selected) ? ' selected' : '';
        $html .= '<option value="' . h((string)$value) . '"' . $sel . '>' . h($title) . '</option>';
    }
    $html .= '</select>';
    return $html . fieldError($errors, $name);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Регистрация на конференцию</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 520px; margin: 2rem auto; padding: 0 1rem; }
        label { display: block; margin-top: 1rem; font-weight: 600; }
        input[type=text], input[type=email], select { width: 100%; padding: .5rem; box-sizing: border-box; }
        .error { color: #b00020; margin: .25rem 0 0; font-size: .9rem; }
        [aria-invalid=true] { border: 2px solid #b00020; }
        .summary { background: #fdecea; border-left: 4px solid #b00020; padding: .5rem 1rem; margin-bottom: 1rem; }
        .ok { background: #e6f4ea; border-left: 4px solid #1e7e34; padding: .5rem 1rem; margin-bottom: 1rem; }
        button { margin-top: 1.5rem; padding: .6rem 1.4rem; }
    </style>
</head>
<body>
<h1>Регистрация на конференцию</h1>

<?php if ($success): ?>
    <div class="ok" role="status">
        <?= h($old['fullname']) ?>, вы зарегистрированы.
        Роль: <?= h($roles[$old['role']]) ?>;
        секция: <?= h($sections[$old['section']]) ?>;
        формат: <?= h($formats[$old['format']]) ?>.
        Подтверждение будет отправлено на <?= h($old['email']) ?>.
    </div>
<?php elseif ($errors): ?>
    <div class="summary" role="alert">
        Исправьте ошибки (<?= count($errors) ?>):
        <ul>
            <?php foreach ($errors as $key => $msg): ?>
                <li><a href="#<?= h($key) ?>"><?= h($msg) ?></a></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="post" action="" novalidate>
    <label for="fullname">ФИО</label>
    <input type="text" id="fullname" name="fullname" maxlength="100" required
           value="<?= h($old['fullname']) ?>"<?= ariaAttrs($errors, 'fullname') ?>>
    <?= fieldError($errors, 'fullname') ?>

    <label for="email">E-mail</label>
    <input type="email" id="email" name="email" maxlength="254" required
           value="<?= h($old['email']) ?>"<?= ariaAttrs($errors, 'email') ?>>
    <?= fieldError($errors, 'email') ?>

    <?= renderSelect('role', 'Роль', $roles, $old['role'], $errors) ?>
    <?= renderSelect('section', 'Секция', $sections, $old['section'], $errors) ?>
    <?= renderSelect('format', 'Формат участия', $formats, $old['format'], $errors) ?>

    <label>
        <input type="checkbox" id="consent" name="consent" value="1"
               <?= $old['consent'] === '1' ? 'checked' : '' ?><?= ariaAttrs($errors, 'consent') ?>>
        Согласен на обработку данных
    </label>
    <?= fieldError($errors, 'consent') ?>

    <button type="submit">Зарегистрироваться</button>
</form>
</body>
</html>