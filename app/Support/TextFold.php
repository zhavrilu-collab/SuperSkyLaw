<?php

namespace App\Support;

class TextFold
{
    private const PAIRS = [
        'š' => 's', 'Š' => 's',
        'đ' => 'd', 'Đ' => 'd',
        'č' => 'c', 'Č' => 'c',
        'ć' => 'c', 'Ć' => 'c',
        'ž' => 'z', 'Ž' => 'z',
    ];

    public static function fold(string $value): string
    {
        return mb_strtolower(strtr($value, self::PAIRS));
    }

    public static function expression(string $column): string
    {
        $expression = $column;
        foreach (self::PAIRS as $from => $to) {
            $expression = "REPLACE({$expression}, '{$from}', '{$to}')";
        }

        return "LOWER({$expression})";
    }

    public static function like(string $term): string
    {
        $folded = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], self::fold($term));

        return '%'.$folded.'%';
    }

    public static function highlight(string $text, string $term): string
    {
        $needle = self::fold($term);
        if ($needle === '') {
            return e($text);
        }

        $folded = self::fold($text);
        $length = mb_strlen($needle);
        $cursor = 0;
        $html = '';
        $found = false;
        while (($pos = mb_strpos($folded, $needle, $cursor)) !== false) {
            $found = true;
            $html .= e(mb_substr($text, $cursor, $pos - $cursor));
            $html .= '<mark class="zakoni-pogodak">'.e(mb_substr($text, $pos, $length)).'</mark>';
            $cursor = $pos + $length;
        }
        if (! $found) {
            return e($text);
        }

        return $html.e(mb_substr($text, $cursor));
    }
}
