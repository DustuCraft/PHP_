<?php
declare(strict_types=1);

/**
 * Учебное хранилище. В реальной системе — база данных
 * (пользователи с хешами паролей, таблица заявок).
 * Пароли здесь только в виде хешей; открытые пароли в код не включаются.
 */

const USERS = [
    'resident@atu.edu.kz' => [
        'id'   => 101,
        'role' => 'resident',
        'hash' => '$2y$10$nv7jPa5T3vibYk1SRkRCQOsD8g44mng9BUndCbImjc9ArDyZZ3PlK',
    ],
    'manager@atu.edu.kz' => [
        'id'   => 201,
        'role' => 'manager',
        'hash' => '$2y$10$e6vDB3Y/jXz1DTHOF4zlh.yMr3E/fHfSzNljbDLEGlY26oNyzRJtW',
    ],
];

/** Хеш-«заглушка»: выравнивает время ответа для несуществующего e-mail. */
const DUMMY_HASH = '$2y$10$nv7jPa5T3vibYk1SRkRCQOsD8g44mng9BUndCbImjc9ArDyZZ3PlK';

const REQUEST_STATUSES = ['new', 'in_progress', 'done', 'rejected'];
const REQUESTS_FILE = __DIR__ . '/../storage/requests.json';

function authenticate(string $email, string $password): ?array
{
    $user = USERS[$email] ?? null;
    $ok = password_verify($password, $user['hash'] ?? DUMMY_HASH);
    return ($user !== null && $ok) ? $user : null;
}

/** Атомарное чтение-изменение-запись JSON-файла под эксклюзивной блокировкой. */
function mutateRequests(callable $fn): mixed
{
    $fh = fopen(REQUESTS_FILE, 'c+');
    if ($fh === false) {
        throw new RuntimeException('Storage unavailable');
    }
    flock($fh, LOCK_EX);
    $raw  = stream_get_contents($fh);
    $list = ($raw !== false && $raw !== '') ? json_decode($raw, true, 8, JSON_THROW_ON_ERROR) : [];
    $result = $fn($list);
    ftruncate($fh, 0);
    rewind($fh);
    fwrite($fh, json_encode($list, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    fflush($fh);
    flock($fh, LOCK_UN);
    fclose($fh);
    return $result;
}

function loadRequests(): array
{
    return mutateRequests(function (array &$list): array {
        return $list;
    });
}

function createRequest(int $userId, string $email, string $title, string $text): void
{
    mutateRequests(function (array &$list) use ($userId, $email, $title, $text): void {
        $list[] = [
            'id'      => count($list) + 1,
            'user_id' => $userId,
            'email'   => $email,
            'title'   => $title,
            'text'    => $text,
            'status'  => 'new',
            'created' => date('Y-m-d H:i'),
        ];
    });
}

function setRequestStatus(int $requestId, string $status): bool
{
    if (!in_array($status, REQUEST_STATUSES, true)) {
        return false;
    }
    return (bool) mutateRequests(function (array &$list) use ($requestId, $status): bool {
        foreach ($list as &$row) {
            if ($row['id'] === $requestId) {
                $row['status'] = $status;
                return true;
            }
        }
        return false;
    });
}
