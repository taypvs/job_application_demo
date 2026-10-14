<?php

namespace App\Services\Ai;

class PromptLibrary
{
    public function system(string $key): string
    {
        return (string) config("prompts.{$key}.system");
    }

    /**
     * @param  array<string, scalar|null>  $vars
     */
    public function user(string $key, array $vars = []): string
    {
        $template = (string) config("prompts.{$key}.user");
        $replace = [];

        foreach ($vars as $name => $value) {
            $replace['{{'.$name.'}}'] = (string) $value;
        }

        return strtr($template, $replace);
    }
}
