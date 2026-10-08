<?php

namespace App\Support;

class OfficeName
{
    public static function matches(string $left, string $right): bool
    {
        $leftCore = self::core($left);
        $rightCore = self::core($right);

        if ($leftCore === '' || $rightCore === '') {
            return DirectoryText::fold($left) !== '' && DirectoryText::fold($left) === DirectoryText::fold($right);
        }

        return $leftCore === $rightCore;
    }

    public static function core(string $name): string
    {
        $tokens = array_values(array_filter(
            explode(' ', DirectoryText::fold($name)),
            fn (string $token) => strlen($token) > 1 && ! in_array($token, self::dropped(), true),
        ));

        return implode(' ', $tokens);
    }

    /**
     * @return list<string>
     */
    private static function dropped(): array
    {
        return [
            'odvjetnicko',
            'odvjetnicka',
            'odvjetnicki',
            'odvjetnik',
            'odvjetnica',
            'drustvo',
            'doo',
            'jtd',
            'javno',
            'trgovacko',
            'ogranicenom',
            'odgovornoscu',
            'ogranicena',
            'odgovornost',
            'partneri',
            'suradnici',
            'and',
            'te',
        ];
    }
}
