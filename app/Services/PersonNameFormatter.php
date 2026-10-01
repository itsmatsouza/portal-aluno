<?php

declare(strict_types=1);

namespace Leilabrito\PortalAluno\Services;

class PersonNameFormatter
{
    public static function format(string $name): string
    {
        $name = trim(preg_replace('/\s+/u', ' ', $name) ?? $name);
        $name = mb_convert_case($name, MB_CASE_TITLE, 'UTF-8');

        return preg_replace_callback(
            '/(?<= )(Da|Das|De|Do|Dos|E)(?= |$)/u',
            static fn (array $match): string => mb_strtolower($match[0], 'UTF-8'),
            $name
        ) ?? $name;
    }
}
