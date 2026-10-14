<?php

namespace App\Support;

class SkillMatcher
{
    public static function contains(string $haystack, string $skill): bool
    {
        $pattern = '/(?<![a-z0-9+#])'.preg_quote(mb_strtolower($skill), '/').'(?![a-z0-9+#])/iu';

        return preg_match($pattern, mb_strtolower($haystack)) === 1;
    }
}
