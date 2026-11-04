<?php

declare(strict_types=1);

use App\Domain\{Club, ClubCode, Curator, Membership, Student};
use function App\printUserCard;

require dirname(__DIR__) . '/src/bootstrap.php';

if (PHP_SAPI === 'cli' && DIRECTORY_SEPARATOR === '\\' && function_exists('shell_exec')) {
    @shell_exec('chcp 65001 >NUL');
}

$passed = 0;
$failed = 0;

function ok(string $name): void
{
    global $passed;
    $passed++;
    echo "OK   {$name}\n";
}

function fail(string $name, string $reason): void
{
    global $failed;
    $failed++;
    echo "FAIL {$name}: {$reason}\n";
}

function check(string $name, callable $action): void
{
    try {
        $action();
        ok($name);
    } catch (\Throwable $e) {
        fail($name, $e->getMessage());
    }
}

function expectException(string $expected, callable $action, string $name): void
{
    try {
        $action();
        fail($name, "ожидалось {$expected}, исключение не выброшено");
    } catch (\Throwable $e) {
        if ($e instanceof $expected) {
            ok($name);
        } else {
            fail($name, "ожидалось {$expected}, получено " . $e::class . ': ' . $e->getMessage());
        }
    }
}

function makeCurator(): Curator
{
    return new Curator(1, 'Анна Ким', 'a.kim@atu.edu.kz', 'ИТ');
}

function makeStudent(int $id = 10, string $name = 'Алия Нур'): Student
{
    return new Student($id, $name, "student{$id}@example.kz", 'ИС', 'ИС-31');
}

function makeClub(int $capacity = 2): Club
{
    return new Club(new ClubCode('TST-01'), 'Тестовый клуб', makeCurator(), $capacity);
}

echo "Проверка бизнес-правил (вариант 6 «Студенческие клубы»)\n\n";

check('Создание Student и Curator', function (): void {
    $student = makeStudent();
    $curator = makeCurator();

    if (!str_contains($student->roleLabel(), 'Студент')) {
        throw new RuntimeException('Неверная подпись роли студента.');
    }

    if (!str_contains($curator->roleLabel(), 'Куратор')) {
        throw new RuntimeException('Неверная подпись роли куратора.');
    }
});

expectException(\InvalidArgumentException::class, function (): void {
    new Student(10, '  ', 'aliya@example.kz', 'ИС', 'ИС-31');
}, 'Пустое имя');

expectException(\InvalidArgumentException::class, function (): void {
    new Student(10, 'Алия Нур', 'bad-email', 'ИС', 'ИС-31');
}, 'Некорректный email');

expectException(\InvalidArgumentException::class, function (): void {
    new Club(new ClubCode('TST-01'), 'Тестовый клуб', makeCurator(), 0);
}, 'Нулевой лимит участников');

expectException(\InvalidArgumentException::class, function (): void {
    new ClubCode('club-1');
}, 'Неверный формат кода клуба');

check('Вступление в клуб уменьшает число свободных мест', function (): void {
    $club = makeClub(2);
    $student = makeStudent();
    $membership = $club->join($student);

    if ($membership->studentId !== $student->id) {
        throw new RuntimeException('Членство указывает на другого студента.');
    }

    if ($club->freePlaces() !== 1) {
        throw new RuntimeException('Свободных мест должно остаться 1.');
    }

    if (!$club->hasMember($student)) {
        throw new RuntimeException('Студент не найден среди участников.');
    }
});

expectException(\DomainException::class, function (): void {
    $club = makeClub(2);
    $student = makeStudent();
    $club->join($student);
    $club->join($student);
}, 'Повторное вступление запрещено');

expectException(\DomainException::class, function (): void {
    $club = makeClub(1);
    $club->join(makeStudent(10));
    $club->join(makeStudent(11));
}, 'Вступление сверх лимита');

expectException(\InvalidArgumentException::class, function (): void {
    new Membership(10, new ClubCode('TST-01'), new \DateTimeImmutable('+1 day'));
}, 'Дата вступления в будущем');

expectException(\Error::class, function (): void {
    $student = makeStudent();
    $student->id = 99;
}, 'ID нельзя изменить после создания');

check('Полиморфный вывод без проверки типа', function (): void {
    $student = makeStudent();
    $curator = makeCurator();

    ob_start();
    printUserCard($student);
    printUserCard($curator);
    $output = (string) ob_get_clean();

    if (!str_contains($output, 'Студент') || !str_contains($output, 'Куратор')) {
        throw new RuntimeException('Подписи ролей не различаются.');
    }
});

check('Коллекция членств защищена от прямой записи', function (): void {
    $club = makeClub(2);
    $club->join(makeStudent());

    $copy = $club->memberships();
    $copy[] = 'лишний элемент';

    if (count($club->memberships()) !== 1) {
        throw new RuntimeException('Внутренняя коллекция изменилась извне.');
    }
});

check('Студент без членства не считается участником', function (): void {
    $club = makeClub(2);
    $club->join(makeStudent(10));

    if ($club->hasMember(makeStudent(11))) {
        throw new RuntimeException('Посторонний студент найден в клубе.');
    }
});

echo "\nПройдено: {$passed}, провалено: {$failed}\n";
exit($failed === 0 ? 0 : 1);
