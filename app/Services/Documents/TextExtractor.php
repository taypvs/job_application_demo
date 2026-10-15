<?php

namespace App\Services\Documents;

use Illuminate\Support\Facades\Http;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;
use Smalot\PdfParser\Parser;
use ZipArchive;

class TextExtractor
{
    public function fromFile(string $path, string $extension): string
    {
        $extension = strtolower($extension);
        $text = match ($extension) {
            'pdf' => $this->pdf($path),
            'docx' => $this->docx($path),
            'txt' => $this->plain($path),
            'xlsx', 'xls' => $this->excel($path),
            'doc' => throw new RuntimeException(__('messages.files.doc_unsupported')),
            default => throw new RuntimeException(__('messages.files.unsupported')),
        };

        return $this->clean($text);
    }

    public function fromUrl(string $url): string
    {
        try {
            $response = Http::withHeaders(['User-Agent' => 'KanriBot/1.0'])
                ->timeout(20)
                ->get($url)
                ->throw();
        } catch (\Throwable $e) {
            report($e);
            throw new RuntimeException(__('messages.files.url_failed'));
        }

        $html = mb_substr($response->body(), 0, 1_000_000);
        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', ' ', $html) ?? $html;
        $html = preg_replace('/<style\b[^>]*>.*?<\/style>/is', ' ', $html) ?? $html;
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return $this->clean($text);
    }

    private function pdf(string $path): string
    {
        try {
            return (new Parser)->parseFile($path)->getText();
        } catch (\Throwable $e) {
            report($e);
            throw new RuntimeException(__('messages.files.pdf_failed'));
        }
    }

    private function docx(string $path): string
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw new RuntimeException(__('messages.files.word_open_failed'));
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if ($xml === false) {
            throw new RuntimeException(__('messages.files.word_empty'));
        }

        $xml = str_replace(['</w:p>', '</w:tr>', '<w:br/>', '<w:tab/>'], ["\n", "\n", "\n", "\t"], $xml);

        return html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private function excel(string $path): string
    {
        try {
            $spreadsheet = IOFactory::load($path);
        } catch (\Throwable $e) {
            report($e);
            throw new RuntimeException(__('messages.files.excel_failed'));
        }

        $sections = [];

        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $highestRow = $sheet->getHighestDataRow();
            $highestColumn = $sheet->getHighestDataColumn();
            $rows = $sheet->rangeToArray(
                'A1:'.$highestColumn.$highestRow,
                null,
                true,
                true,
            );

            $lines = [];
            foreach ($rows as $row) {
                $cells = array_map(
                    fn ($value) => trim(str_replace(["\r\n", "\r", "\n", "\t"], ' ', (string) ($value ?? ''))),
                    $row,
                );

                while ($cells !== [] && end($cells) === '') {
                    array_pop($cells);
                }

                if ($cells === [] || implode('', $cells) === '') {
                    continue;
                }

                $lines[] = implode("\t", $cells);
            }

            if ($lines === []) {
                continue;
            }

            $sections[] = '【'.$sheet->getTitle().'】'."\n".implode("\n", $lines);
        }

        $spreadsheet->disconnectWorksheets();

        return implode("\n\n", $sections);
    }

    private function plain(string $path): string
    {
        $text = file_get_contents($path);
        if ($text === false) {
            throw new RuntimeException(__('messages.files.text_failed'));
        }

        if (! mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'SJIS-win, EUC-JP, UTF-8');
        }

        return $text;
    }

    private function clean(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace("/[ \t]+\n/u", "\n", $text) ?? $text;
        $text = preg_replace("/\n{3,}/u", "\n\n", $text) ?? $text;

        return trim(mb_substr($text, 0, 200_000));
    }
}
