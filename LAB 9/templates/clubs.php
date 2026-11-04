<?php
if (!isset($clubs, $users, $errors, $clubMembers, $membershipCount, $students, $totalFreePlaces)) {
    http_response_code(404);
    exit('Not Found');
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Студенческие клубы — Лабораторная работа №9</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="./" aria-label="Almaty Technological University">
            <img class="brand-logo" src="assets/logo.png" alt="Almaty Technological University">
        </a>
        <div class="brand-divider" aria-hidden="true"></div>
        <div class="brand-text">
            <span class="eyebrow">Лабораторная работа №9 · вариант 6</span>
            <h1>Студенческие клубы</h1>
        </div>
    </div>
</header>

<main class="container">
    <section class="hero">
        <div>
            <p class="hero-kicker">PHP 8.4 · ООП · предметная область</p>
            <h2>Учёт клубов, кураторов и членства</h2>
            <p class="hero-text">
                Лимит участников и уникальное членство проверяются до изменения состояния.
                Ниже — объекты, бизнес-правила и полиморфный вывод через общий контракт User.
            </p>
        </div>
        <ul class="hero-tags">
            <li>Инкапсуляция</li>
            <li>Наследование</li>
            <li>Полиморфизм</li>
            <li>Исключения</li>
        </ul>
    </section>

    <section class="stats" aria-label="Сводка">
        <div class="stat">
            <span class="stat-value"><?= count($clubs) ?></span>
            <span class="stat-label">клубов</span>
        </div>
        <div class="stat">
            <span class="stat-value"><?= count($students) ?></span>
            <span class="stat-label">студентов</span>
        </div>
        <div class="stat">
            <span class="stat-value"><?= $membershipCount ?></span>
            <span class="stat-label">членств</span>
        </div>
        <div class="stat">
            <span class="stat-value"><?= $totalFreePlaces ?></span>
            <span class="stat-label">свободных мест</span>
        </div>
    </section>

    <section class="section">
        <div class="section-head">
            <h2>Клубы</h2>
            <p>Семь направлений: от активистов до киберспорта</p>
        </div>

        <div class="club-grid">
            <?php foreach ($clubs as $club): ?>
                <?php
                $names = $clubMembers[$club->code->value];
                $taken = count($names);
                $capacity = $club->capacity();
                $percent = $capacity > 0 ? (int) round($taken / $capacity * 100) : 0;
                ?>
                <article class="club-card<?= $percent >= 100 ? ' is-full' : '' ?>">
                    <div class="club-head">
                        <span class="club-code"><?= h($club->code->value) ?></span>
                        <span class="club-free"><?= $club->freePlaces() ?> свободно</span>
                    </div>

                    <h3><?= h($club->title) ?></h3>
                    <p class="club-curator">Куратор: <span><?= h($club->curator->fullName()) ?></span></p>

                    <div class="capacity">
                        <div class="capacity-bar" role="img" aria-label="Занято <?= $taken ?> из <?= $capacity ?>">
                            <span style="width: <?= $percent ?>%"></span>
                        </div>
                        <div class="capacity-meta">
                            <span><strong><?= $taken ?></strong> / <?= $capacity ?> участников</span>
                            <span><?= $club->freePlaces() ?> мест</span>
                        </div>
                    </div>

                    <div class="members">
                        <?php if ($names === []): ?>
                            <span class="muted">Пока нет участников</span>
                        <?php else: ?>
                            <?php foreach ($names as $name): ?>
                                <span class="chip"><?= h($name) ?></span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="section">
        <div class="section-head">
            <h2>Пользователи</h2>
            <p>Карточки выводятся через общий контракт User</p>
        </div>

        <div class="table-wrap">
            <table class="users-table">
                <thead>
                <tr>
                    <th>#</th>
                    <th>ФИО</th>
                    <th>Роль</th>
                    <th>Email</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td class="mono"><?= $user->id ?></td>
                        <td class="name"><?= h($user->fullName()) ?></td>
                        <td><span class="role"><?= h($user->roleLabel()) ?></span></td>
                        <td><a href="mailto:<?= h($user->email) ?>"><?= h($user->email) ?></a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="section">
        <div class="section-head">
            <h2>Проверка ограничений</h2>
            <p>Ошибки ожидаемы: инварианты не дали объекту перейти в недопустимое состояние</p>
        </div>

        <div class="alerts">
            <?php foreach ($errors as $error): ?>
                <article class="alert">
                    <span class="alert-icon" aria-hidden="true">!</span>
                    <div>
                        <h3><?= h($error['title']) ?></h3>
                        <p><?= h($error['message']) ?></p>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</main>

<footer class="site-footer">
    <div class="container footer-inner">
        <span>Лабораторная работа №9 · «Студенческие клубы»</span>
        <span class="mono">PHP <?= h(PHP_VERSION) ?></span>
    </div>
</footer>
</body>
</html>
