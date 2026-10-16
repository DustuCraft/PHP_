#!/usr/bin/env bash
# Автотест матрицы T1–T12 (+ сценарии для варианта 4).
# Запуск из каталога lab7:  bash tests/run_tests.sh
set -u
cd "$(dirname "$0")/.."

PORT=8099
BASE="http://localhost:$PORT"
SESS_DIR="$(mktemp -d)"
JAR_R="$(mktemp)"; JAR_M="$(mktemp)"
echo '[]' > storage/requests.json

php -d session.save_path="$SESS_DIR" -S "localhost:$PORT" -t public >/dev/null 2>&1 &
SERVER_PID=$!
trap 'kill $SERVER_PID 2>/dev/null; rm -rf "$SESS_DIR" "$JAR_R" "$JAR_M"' EXIT
sleep 1

PASS=0; FAIL=0
check() { # name expected actual
  if [ "$2" = "$3" ]; then PASS=$((PASS+1)); printf 'PASS  %-58s ожидалось=%s факт=%s\n' "$1" "$2" "$3"
  else FAIL=$((FAIL+1)); printf 'FAIL  %-58s ожидалось=%s факт=%s\n' "$1" "$2" "$3"; fi
}
code()  { curl -s -o /dev/null -w '%{http_code}' "$@"; }
token() { sed -n 's/.*name="csrf_token" value="\([0-9a-f]*\)".*/\1/p' | head -1; }
sid()   { awk '$6=="DORMSESSION"{print $7}' "$1" | tail -1; }

login() { # jar email password
  local t; t=$(curl -s -c "$1" -b "$1" "$BASE/login.php" | token)
  curl -s -o /dev/null -w '%{http_code}' -c "$1" -b "$1" \
       --data-urlencode "csrf_token=$t" --data-urlencode "email=$2" --data-urlencode "password=$3" "$BASE/login.php"
}

# T1
check "T1  защищённая страница без входа -> редирект" 302 "$(code "$BASE/dashboard.php")"
loc=$(curl -s -D - -o /dev/null "$BASE/dashboard.php" | tr -d '\r' | awk -F': ' 'tolower($1)=="location"{print $2}')
check "T1  Location = /login.php" "/login.php" "$loc"

# T2
r=$(login "$JAR_R" resident@atu.edu.kz WrongPass1!)
check "T2  неверный пароль: форма повторно (200)" 200 "$r"
check "T2  после отказа состояние входа не создано" 302 "$(code -b "$JAR_R" "$BASE/dashboard.php")"
r=$(login "$JAR_R" nobody@atu.edu.kz Resident123!)
check "T2  неизвестный e-mail: тот же ответ (200)" 200 "$r"

# T3
sid_before=$(sid "$JAR_R")
r=$(login "$JAR_R" resident@atu.edu.kz 'Resident123!')
check "T3  корректный вход -> редирект" 302 "$r"
sid_after=$(sid "$JAR_R")
[ -n "$sid_after" ] && [ "$sid_before" != "$sid_after" ] && d=changed || d=same
check "T3  ID сессии изменён после входа" changed "$d"
check "T3  кабинет открыт" 200 "$(code -b "$JAR_R" "$BASE/dashboard.php")"

# T4
check "T4  обновление страницы сохраняет вход" 200 "$(code -b "$JAR_R" "$BASE/dashboard.php")"

# T5
check "T5  жилец открывает manager.php -> 403" 403 "$(code -b "$JAR_R" "$BASE/manager.php")"

# T6
html=$(curl -s -b "$JAR_R" -b "lang=<script>x" "$BASE/login.php")
curl -s -b "lang=evil" "$BASE/login.php" | grep -q '<html lang="ru"' && v=ru || v=other
check "T6  подменённая cookie lang -> значение по умолчанию" ru "$v"
curl -s -b "lang=kk" "$BASE/login.php" | grep -q '<html lang="kk"' && v=kk || v=other
check "T6  допустимое значение lang=kk принято" kk "$v"

# T7, T8
T=$(curl -s -b "$JAR_R" "$BASE/dashboard.php" | token)
check "T7  POST без CSRF-токена -> 403" 403 "$(code -b "$JAR_R" -d 'action=language&lang=en' "$BASE/dashboard.php")"
check "T8  POST с изменённым токеном -> 403" 403 "$(code -b "$JAR_R" -d "csrf_token=${T}00&action=language&lang=en" "$BASE/dashboard.php")"
check "T7/8 состояние входа не изменилось" 200 "$(code -b "$JAR_R" "$BASE/dashboard.php")"

# Вариант 4: жилец создаёт заявку
check "V4  жилец создаёт заявку -> редирект" 302 "$(code -b "$JAR_R" --data-urlencode "csrf_token=$T" -d 'action=create_request' --data-urlencode 'title=Сломан кран' --data-urlencode 'text=<b>Комната 214</b>, течёт вода' "$BASE/dashboard.php")"
curl -s -b "$JAR_R" "$BASE/dashboard.php" | grep -q '&lt;b&gt;Комната 214' && v=escaped || v=raw
check "V4  вывод заявки экранирован (XSS)" escaped "$v"
check "V4  пустая заявка отклонена (200 + ошибка)" 200 "$(code -b "$JAR_R" --data-urlencode "csrf_token=$T" -d 'action=create_request&title=&text=' "$BASE/dashboard.php")"

# Вариант 4: менеджер меняет статус
r=$(login "$JAR_M" manager@atu.edu.kz 'Manager123!')
check "V4  менеджер входит" 302 "$r"
check "V4  менеджер открывает manager.php" 200 "$(code -b "$JAR_M" "$BASE/manager.php")"
TM=$(curl -s -b "$JAR_M" "$BASE/manager.php" | token)
check "V4  менеджер без CSRF -> 403" 403 "$(code -b "$JAR_M" -d 'request_id=1&status=done' "$BASE/manager.php")"
check "V4  недопустимый статус -> 400" 400 "$(code -b "$JAR_M" -d "csrf_token=$TM&request_id=1&status=hacked" "$BASE/manager.php")"
check "V4  менеджер меняет статус -> редирект" 302 "$(code -b "$JAR_M" -d "csrf_token=$TM&request_id=1&status=in_progress" "$BASE/manager.php")"
curl -s -b "$JAR_R" "$BASE/dashboard.php" | grep -q 'В работе' && v=yes || v=no
check "V4  жилец видит новый статус" yes "$v"
check "V4  менеджер не может создавать заявки -> 403" 403 "$(code -b "$JAR_M" --data-urlencode "csrf_token=$TM" -d 'action=create_request&title=a&text=b' "$BASE/dashboard.php")"
check "V4  жилец не меняет статус (POST на manager.php) -> 403" 403 "$(code -b "$JAR_R" -d "csrf_token=$T&request_id=1&status=done" "$BASE/manager.php")"

# T9
check "T9  выход методом GET -> 405" 405 "$(code -b "$JAR_R" "$BASE/logout.php")"
check "T9  сеанс не завершён" 200 "$(code -b "$JAR_R" "$BASE/dashboard.php")"

# T10, T11
OLD=$(sid "$JAR_R")
check "T10 выход POST без токена -> 403" 403 "$(code -b "$JAR_R" -X POST "$BASE/logout.php")"
check "T10 корректный выход -> редирект" 302 "$(code -b "$JAR_R" -c "$JAR_R" -d "csrf_token=$T" "$BASE/logout.php")"
curl -s -D - -o /dev/null -b "DORMSESSION=$OLD" -d "csrf_token=$T" "$BASE/logout.php" >/dev/null
check "T10 серверный файл сессии удалён" 0 "$(ls "$SESS_DIR" | grep -c "sess_$OLD")"
check "T11 старая cookie после выхода -> редирект" 302 "$(code -H "Cookie: DORMSESSION=$OLD" "$BASE/dashboard.php")"
check "T11 доступ к manager.php не восстановлен" 302 "$(code -H "Cookie: DORMSESSION=$OLD" "$BASE/manager.php")"
# strict mode: неизвестный ID не принимается и заменяется новым
sidnew=$(curl -s -D - -o /dev/null -H "Cookie: DORMSESSION=attackerchosenid1234567890abcd" "$BASE/login.php" | tr -d '\r' | sed -n 's/^Set-Cookie: DORMSESSION=\([^;]*\);.*/\1/Ip')
[ -n "$sidnew" ] && [ "$sidnew" != "attackerchosenid1234567890abcd" ] && v=rejected || v=accepted
check "SM  strict mode: навязанный ID отклонён" rejected "$v"

# T12
login "$JAR_R" resident@atu.edu.kz 'Resident123!' >/dev/null
S=$(sid "$JAR_R")
sed -i 's/last_activity|i:[0-9]*;/last_activity|i:1;/' "$SESS_DIR/sess_$S"
loc=$(curl -s -D - -o /dev/null -b "$JAR_R" "$BASE/dashboard.php" | tr -d '\r' | awk -F': ' 'tolower($1)=="location"{print $2}')
check "T12 тайм-аут: редирект с expired=1" "/login.php?expired=1" "$loc"
check "T12 после тайм-аута доступ закрыт" 302 "$(code -H "Cookie: DORMSESSION=$S" "$BASE/dashboard.php")"

# Cookie-атрибуты и отсутствие session_id в HTML
hdr=$(curl -s -D - -o /dev/null "$BASE/login.php" | tr -d '\r' | grep -i '^Set-Cookie: DORMSESSION')
echo "$hdr" | grep -qi 'HttpOnly' && echo "$hdr" | grep -qi 'SameSite=Lax' && v=ok || v=missing
check "CK  у сессионной cookie HttpOnly и SameSite=Lax" ok "$v"
login "$JAR_R" resident@atu.edu.kz 'Resident123!' >/dev/null
S=$(sid "$JAR_R")
curl -s -b "$JAR_R" "$BASE/dashboard.php" | grep -q "$S" && v=leaked || v=hidden
check "CK  session_id не выводится в HTML" hidden "$v"

echo "----"; echo "Пройдено: $PASS, провалено: $FAIL"
echo '[]' > storage/requests.json
[ "$FAIL" -eq 0 ]
