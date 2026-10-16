<?php
declare(strict_types=1);

/** Cookie «lang» — единственная несекретная настройка. Разрешённый список. */
const ALLOWED_LANGS = ['ru', 'kk', 'en'];

function currentLang(): string
{
    $lang = $_COOKIE['lang'] ?? 'ru';
    return (is_string($lang) && in_array($lang, ALLOWED_LANGS, true)) ? $lang : 'ru';
}

function setLanguageCookie(string $lang): void
{
    setcookie('lang', $lang, [
        'expires'  => time() + 2592000,
        'path'     => '/',
        'secure'   => isHttps(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function t(string $key): string
{
    static $tr = [
        'ru' => [
            'title' => 'Общежитие', 'login' => 'Вход', 'email' => 'E-mail', 'password' => 'Пароль',
            'sign_in' => 'Войти', 'sign_out' => 'Выйти', 'bad_login' => 'Неверный e-mail или пароль.',
            'expired' => 'Сеанс завершён из-за бездействия.', 'user' => 'Пользователь', 'role' => 'Роль',
            'language' => 'Язык', 'save' => 'Сохранить', 'new_request' => 'Новая заявка',
            'req_title' => 'Тема', 'req_text' => 'Описание', 'create' => 'Создать',
            'my_requests' => 'Мои заявки', 'all_requests' => 'Все заявки', 'status' => 'Статус',
            'none' => 'Заявок нет.', 'created' => 'Заявка создана.', 'updated' => 'Статус обновлён.',
            'invalid' => 'Проверьте поля: тема до 100, описание до 500 символов.',
            'forbidden' => 'Доступ запрещён.', 'manager_panel' => 'Панель менеджера', 'back' => 'В кабинет',
            'author' => 'Автор', 'st_new' => 'Новая', 'st_in_progress' => 'В работе',
            'st_done' => 'Выполнена', 'st_rejected' => 'Отклонена',
        ],
        'kk' => [
            'title' => 'Жатақхана', 'login' => 'Кіру', 'email' => 'E-mail', 'password' => 'Құпиясөз',
            'sign_in' => 'Кіру', 'sign_out' => 'Шығу', 'bad_login' => 'E-mail немесе құпиясөз қате.',
            'expired' => 'Белсенділік болмағандықтан сеанс аяқталды.', 'user' => 'Пайдаланушы', 'role' => 'Рөл',
            'language' => 'Тіл', 'save' => 'Сақтау', 'new_request' => 'Жаңа өтінім',
            'req_title' => 'Тақырып', 'req_text' => 'Сипаттама', 'create' => 'Құру',
            'my_requests' => 'Менің өтінімдерім', 'all_requests' => 'Барлық өтінімдер', 'status' => 'Күйі',
            'none' => 'Өтінімдер жоқ.', 'created' => 'Өтінім құрылды.', 'updated' => 'Күйі жаңартылды.',
            'invalid' => 'Өрістерді тексеріңіз: тақырып 100, сипаттама 500 таңбаға дейін.',
            'forbidden' => 'Қолжетімділік тыйым салынған.', 'manager_panel' => 'Менеджер панелі', 'back' => 'Кабинетке',
            'author' => 'Авторы', 'st_new' => 'Жаңа', 'st_in_progress' => 'Орындалуда',
            'st_done' => 'Орындалды', 'st_rejected' => 'Қабылданбады',
        ],
        'en' => [
            'title' => 'Dormitory', 'login' => 'Sign in', 'email' => 'E-mail', 'password' => 'Password',
            'sign_in' => 'Sign in', 'sign_out' => 'Sign out', 'bad_login' => 'Invalid e-mail or password.',
            'expired' => 'Session ended due to inactivity.', 'user' => 'User', 'role' => 'Role',
            'language' => 'Language', 'save' => 'Save', 'new_request' => 'New request',
            'req_title' => 'Subject', 'req_text' => 'Description', 'create' => 'Create',
            'my_requests' => 'My requests', 'all_requests' => 'All requests', 'status' => 'Status',
            'none' => 'No requests.', 'created' => 'Request created.', 'updated' => 'Status updated.',
            'invalid' => 'Check the fields: subject up to 100, description up to 500 characters.',
            'forbidden' => 'Access denied.', 'manager_panel' => 'Manager panel', 'back' => 'Back to dashboard',
            'author' => 'Author', 'st_new' => 'New', 'st_in_progress' => 'In progress',
            'st_done' => 'Done', 'st_rejected' => 'Rejected',
        ],
    ];
    return $tr[currentLang()][$key] ?? $key;
}

function pageHead(string $title): void
{
    echo '<!doctype html><html lang="' . h(currentLang()) . '"><head><meta charset="UTF-8">'
       . '<meta name="viewport" content="width=device-width, initial-scale=1">'
       . '<title>' . h($title) . '</title>'
       . '<style>body{font-family:system-ui,sans-serif;max-width:720px;margin:2rem auto;padding:0 1rem}'
       . 'label{display:block;margin:.6rem 0}input,textarea,select{width:100%;padding:.4rem;box-sizing:border-box}'
       . 'button{padding:.4rem .9rem;margin-top:.4rem}table{border-collapse:collapse;width:100%}'
       . 'td,th{border:1px solid #ccc;padding:.4rem;text-align:left}.err{color:#b00020}.ok{color:#116611}'
       . 'form.inline{display:inline}form.inline select{width:auto}</style></head><body>';
}
