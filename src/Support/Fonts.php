<?php

final class Fonts
{
    // key => [label, Google Fonts family name used by the CI build to fetch the .ttf]
    public const OPTIONS = [
        'default' => ['label' => 'Sistem Varsayılanı (Roboto)', 'family' => ''],
        'opensans' => ['label' => 'Open Sans', 'family' => 'Open Sans'],
        'montserrat' => ['label' => 'Montserrat', 'family' => 'Montserrat'],
        'poppins' => ['label' => 'Poppins', 'family' => 'Poppins'],
        'lobster' => ['label' => 'Lobster', 'family' => 'Lobster'],
        'playfair' => ['label' => 'Playfair Display', 'family' => 'Playfair Display'],
        'nunito' => ['label' => 'Nunito', 'family' => 'Nunito'],
    ];

    public static function isValid(string $key): bool
    {
        return isset(self::OPTIONS[$key]);
    }
}
