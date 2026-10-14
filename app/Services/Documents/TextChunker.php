<?php

namespace App\Services\Documents;

class TextChunker
{
    /**
     * @return list<string>
     */
    public function chunk(string $text, int $size = 300, int $overlap = 100): array
    {
        $text = trim($text);
        if ($text === '') {
            return [];
        }

        $chunks = [];
        $length = mb_strlen($text);
        $offset = 0;

        while ($offset < $length) {
            $chunks[] = trim(mb_substr($text, $offset, $size));
            if ($offset + $size >= $length) {
                break;
            }
            $offset += max(1, $size - $overlap);
        }

        return array_values(array_filter($chunks, fn (string $chunk) => $chunk !== ''));
    }
}
