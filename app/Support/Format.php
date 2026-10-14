<?php

namespace App\Support;

class Format
{
    public static function manYen(int $yen): string
    {
        return number_format((int) round($yen / 10000)).'万円';
    }

    public static function salaryRange(int $min, int $max): string
    {
        return '年収 '.self::manYen($min).'〜'.self::manYen($max);
    }
}
