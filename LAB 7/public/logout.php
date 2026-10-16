<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method Not Allowed');
}

if (!csrfValid()) {
    denyCsrf();
}

logoutUser();
redirect('/login.php');
