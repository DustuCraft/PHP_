<?php

declare(strict_types=1);

use App\Domain\{Club, ClubCode, Curator, Student};
use function App\printUserCard;

require dirname(__DIR__) . '/src/bootstrap.php';

$isCli = PHP_SAPI === 'cli';

if ($isCli && DIRECTORY_SEPARATOR === '\\' && function_exists('shell_exec')) {
    @shell_exec('chcp 65001 >NUL');
}

$curatorIt = new Curator(1, 'Анна Ким', 'a.kim@atu.edu.kz', 'кафедра информационных технологий');
$curatorLang = new Curator(2, 'Ольга Смирнова', 'o.smirnova@atu.edu.kz', 'кафедра языков');
$curatorSport = new Curator(3, 'Данияр Ахметов', 'd.akhmetov@atu.edu.kz', 'кафедра физической культуры');

$clubs = [
    new Club(new ClubCode('ACT-01'), 'Активисты', $curatorIt, 20),
    new Club(new ClubCode('ENG-02'), 'Английский клуб', $curatorLang, 15),
    new Club(new ClubCode('CHN-03'), 'Китайский клуб', $curatorLang, 12),
    new Club(new ClubCode('FBL-04'), 'Футбол', $curatorSport, 18),
    new Club(new ClubCode('VBL-05'), 'Волейбол', $curatorSport, 16),
    new Club(new ClubCode('DBT-06'), 'Дебатный клуб', $curatorIt, 10),
    new Club(new ClubCode('CYB-07'), 'Киберспорт', $curatorIt, 14),
];

$aliya = new Student(10, 'Алия Нур', 'aliya@example.kz', 'ИС', 'ИС-31');
$daniyar = new Student(11, 'Данияр Серик', 'daniyar@example.kz', 'ИС', 'ИС-31');
$madina = new Student(12, 'Мадина Оспан', 'madina@example.kz', 'ПО', 'ПО-32');
$timur = new Student(13, 'Тимур Ахмет', 'timur@example.kz', 'ИС', 'ИС-32');

$students = [$aliya, $daniyar, $madina, $timur];

$byId = [];
foreach ($students as $student) {
    $byId[$student->id] = $student;
}

$clubs[0]->join($aliya);
$clubs[1]->join($aliya);
$clubs[1]->join($daniyar);
$clubs[2]->join($madina);
$clubs[3]->join($daniyar);
$clubs[4]->join($timur);
$clubs[5]->join($madina);
$clubs[6]->join($timur);

$errors = [];

try {
    $clubs[0]->join($aliya);
} catch (\DomainException $e) {
    $errors[] = ['title' => 'Повторное вступление', 'message' => $e->getMessage()];
}

$miniClub = new Club(new ClubCode('TST-99'), 'Пробный клуб', $curatorIt, 1);
$miniClub->join($aliya);

try {
    $miniClub->join($timur);
} catch (\DomainException $e) {
    $errors[] = ['title' => 'Лимит участников', 'message' => $e->getMessage()];
}

$users = [$curatorIt, $curatorLang, $curatorSport, $aliya, $daniyar, $madina, $timur];

$membershipCount = 0;
$clubMembers = [];
$totalFreePlaces = 0;

foreach ($clubs as $club) {
    $names = [];
    foreach ($club->memberships() as $membership) {
        $names[] = $byId[$membership->studentId]->fullName();
        $membershipCount++;
    }

    $clubMembers[$club->code->value] = $names;
    $totalFreePlaces += $club->freePlaces();
}

if ($isCli) {
    echo "=== Пользователи ===\n";
    foreach ($users as $user) {
        printUserCard($user);
    }

    echo "\n=== Клубы ===\n";
    foreach ($clubs as $club) {
        $names = $clubMembers[$club->code->value];

        printf(
            "%s | %s | куратор: %s | участников: %d/%d | свободно: %d\n",
            $club->code->value,
            $club->title,
            $club->curator->fullName(),
            count($names),
            $club->capacity(),
            $club->freePlaces(),
        );

        if ($names !== []) {
            echo '   участники: ' . implode(', ', $names) . "\n";
        }
    }

    echo "\n=== Проверка ограничений ===\n";
    foreach ($errors as $error) {
        echo $error['title'] . ': ' . $error['message'] . "\n";
    }

    printf(
        "\nИтого: клубов — %d, студентов — %d, членств — %d.\n",
        count($clubs),
        count($students),
        $membershipCount,
    );

    exit(0);
}

header('Content-Type: text/html; charset=utf-8');

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

require dirname(__DIR__) . '/templates/clubs.php';
