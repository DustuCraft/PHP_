<?php

declare(strict_types=1);

namespace App;

use App\Domain\User;

function printUserCard(User $user): void
{
    printf(
        "#%d | %s | %s | %s\n",
        $user->id,
        $user->fullName(),
        $user->roleLabel(),
        $user->email,
    );
}
