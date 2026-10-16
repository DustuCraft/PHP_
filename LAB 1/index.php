<?php
const UNIVERSITY = "Алматинский технологический университет";
$studentName = "Ержанов Ержанат";   // string
$group       = "ИС-24-22";          // string
$course      = 3;                   // int

// Веса компонентов оценки (сумма = 1.0)
const W_LECTURE  = 0.1;
const W_PRACTICE = 0.15;
const W_LAB      = 0.15;
const W_FINAL    = 0.4;   // итоговый контроль
const W_EXAM     = 0.2;   // экзамен
const W_SUM      = W_LECTURE + W_PRACTICE + W_LAB + W_FINAL + W_EXAM; // 1.0

// Значения по умолчанию (если форма ещё не отправлена)
$gradeLecture  = 85;
$gradePractice = 90;
$gradeLab      = 78;
$gradeFinal    = 80;
$gradeExam     = 88;

$result   = null;
$status   = "";
$mark     = "";
$submitted = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $submitted = true;

    $gradeLecture  = isset($_POST["gradeLecture"])  ? (float)$_POST["gradeLecture"]  : 0;
    $gradePractice = isset($_POST["gradePractice"]) ? (float)$_POST["gradePractice"] : 0;
    $gradeLab      = isset($_POST["gradeLab"])      ? (float)$_POST["gradeLab"]      : 0;
    $gradeFinal    = isset($_POST["gradeFinal"])    ? (float)$_POST["gradeFinal"]    : 0;
    $gradeExam     = isset($_POST["gradeExam"])     ? (float)$_POST["gradeExam"]     : 0;

    // Взвешенная сумма (веса уже дают в сумме 1.0, нормировка не требуется)
    $weightedSum = $gradeLecture  * W_LECTURE
                 + $gradePractice * W_PRACTICE
                 + $gradeLab      * W_LAB
                 + $gradeFinal    * W_FINAL
                 + $gradeExam     * W_EXAM;
    $result = $weightedSum / W_SUM; // деление на 1.0, оставлено для надёжности

    // Статус (успешно/нет)
    if ($result >= 50) {
        $status = "Дисциплина освоена";
    } else {
        $status = "Необходимо повысить результат";
    }

    // Оценка по шкале
    if ($result >= 90) {
        $mark = "5 (Отлично)";
    } elseif ($result >= 75) {
        $mark = "4 (Хорошо)";
    } elseif ($result >= 50) {
        $mark = "3 (Удовлетворительно)";
    } else {
        $mark = "2 (Неудовлетворительно)";
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>LAB 1</title>
<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 30px auto;
    padding: 20px;
    background: #121417;
    color: #e6e6e6;
}
h1, h2 {
    color: #ffffff;
}
.card {
    padding: 25px;
    background: #1e2126;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.4);
    margin-bottom: 20px;
    border: 1px solid #2a2e35;
}
.success { color: #4caf50; }
.warning { color: #ef5350; }
.hint { color: #8b929c; font-size: 13px; margin-top: -6px; }
label { display: block; margin-top: 10px; font-weight: bold; color: #cfd3d8; }
input[type="number"] {
    width: 100%;
    padding: 8px;
    margin-top: 4px;
    border: 1px solid #3a3f47;
    border-radius: 6px;
    box-sizing: border-box;
    background: #14161a;
    color: #e6e6e6;
}
input[type="number"]:focus {
    outline: none;
    border-color: #2f6fed;
}
button {
    margin-top: 18px;
    padding: 10px 20px;
    background: #2f6fed;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 15px;
}
button:hover { background: #1e56c4; }
strong { color: #ffffff; }
</style>
</head>
<body>

    <h1><?= UNIVERSITY ?></h1>

    <div class="card">
        <h2>Сведения о студенте</h2>
        <p>ФИО: <?= $studentName ?></p>
        <p>Группа: <?= $group ?>; курс: <?= $course ?></p>
    </div>

    <div class="card">
        <h2>Ввод оценок</h2>
        <form method="post" action="">
            <label for="gradeLecture">Лекция (0–100),  <?= W_LECTURE ?></label>
            <input type="number" id="gradeLecture" name="gradeLecture" min="0" max="100" step="0.1"
                   value="<?= htmlspecialchars($gradeLecture) ?>" required>

            <label for="gradePractice">Практика (0–100),  <?= W_PRACTICE ?></label>
            <input type="number" id="gradePractice" name="gradePractice" min="0" max="100" step="0.1"
                   value="<?= htmlspecialchars($gradePractice) ?>" required>

            <label for="gradeLab">Лабораторная работа (0–100),  <?= W_LAB ?></label>
            <input type="number" id="gradeLab" name="gradeLab" min="0" max="100" step="0.1"
                   value="<?= htmlspecialchars($gradeLab) ?>" required>

            <label for="gradeFinal">Итоговый контроль (0–100),  <?= W_FINAL ?></label>
            <input type="number" id="gradeFinal" name="gradeFinal" min="0" max="100" step="0.1"
                   value="<?= htmlspecialchars($gradeFinal) ?>" required>

            <label for="gradeExam">Экзамен (0–100),  <?= W_EXAM ?></label>
            <input type="number" id="gradeExam" name="gradeExam" min="0" max="100" step="0.1"
                   value="<?= htmlspecialchars($gradeExam) ?>" required>

            

            <button type="submit">Вычислить результат</button>
        </form>
    </div>

    <?php if ($submitted): ?>
    <div class="card">
        <h2>Результат</h2>
        <p>Взвешенный средний балл: <?= round($result, 2) ?></p>
        <p>Оценка: <strong><?= $mark ?></strong></p>
        <p>Статус: <span class="<?= $result >= 50 ? 'success' : 'warning' ?>"><?= $status ?></span></p>
        <p>Дата формирования: <?= date("d.m.Y") ?></p>
    </div>
    <?php endif; ?>

</body>
</html>