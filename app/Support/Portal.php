<?php

namespace App\Support;

class Portal
{
    public const CANDIDATE = 'candidate';

    public const ADMIN = 'admin';

    public static function current(): string
    {
        if (! app()->bound('request')) {
            return self::CANDIDATE;
        }

        return request()->server('PORTAL') === self::ADMIN ? self::ADMIN : self::CANDIDATE;
    }

    public static function isAdmin(): bool
    {
        return self::current() === self::ADMIN;
    }

    public static function isSeparated(): bool
    {
        return app()->bound('request') && request()->server->has('PORTAL');
    }

    public static function sessionCookie(): string
    {
        return self::isAdmin() ? 'admin_session' : 'candidate_session';
    }

    public static function guard(): string
    {
        return self::isAdmin() ? 'admin' : 'web';
    }

    public static function baseUrl(string $portal): string
    {
        $url = $portal === self::ADMIN
            ? config('app.admin_url')
            : config('app.candidate_url');

        return rtrim((string) $url, '/');
    }
}
