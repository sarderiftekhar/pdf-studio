<?php

namespace PdfStudio\Laravel\Builder\Schema;

class SchemaVersion
{
    public const CURRENT = '1.0';

    /** @var array<int, string> */
    public const SUPPORTED = ['1.0'];

    public static function current(): string
    {
        return self::CURRENT;
    }

    public static function isSupported(string $version): bool
    {
        return in_array($version, self::SUPPORTED, true);
    }
}
