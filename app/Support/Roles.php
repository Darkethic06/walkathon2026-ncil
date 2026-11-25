<?php

namespace App\Support;

/**
 * Admin panel roles (spatie/laravel-permission).
 *
 *  SUPER-ADMIN  everything, including managing sub-users
 *  EDITOR       everything except managing sub-users
 *  SUB-USER     event-day volunteer: registration list (limited fields),
 *               QR scan and "mark attended" only
 */
final class Roles
{
    const SUPER_ADMIN = 'SUPER-ADMIN';

    const EDITOR = 'EDITOR';

    const SUB_USER = 'SUB-USER';

    /** Can see every registration field, support tickets, undo attendance. */
    const ADMINS = [self::SUPER_ADMIN, self::EDITOR];

    /** Can log in to the admin panel at all. */
    const PANEL = [self::SUPER_ADMIN, self::EDITOR, self::SUB_USER];

    /** "A|B|C" for the `role:` route middleware. */
    public static function middleware(array $roles): string
    {
        return 'role:'.implode('|', $roles);
    }
}
