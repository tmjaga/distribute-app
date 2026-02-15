<?php

namespace App\Helpers;

use Faker\Factory as Faker;

class ColorHelper
{
    public static function getIconColor(string $bgColor): string
    {
        $faker = Faker::create();

        $bgHex = self::colorToHex($bgColor);

        do {
            $color = $faker->hexColor();
        } while (! self::isContrastEnough($bgHex, $color));

        return $color;
    }

    private static function isContrastEnough(string $bgHex, string $iconHex): bool
    {
        $bgLum = self::luminance($bgHex);
        $iconLum = self::luminance($iconHex);

        return abs($bgLum - $iconLum) >= 125;
    }

    private static function luminance(string $hex): float
    {
        $hex = ltrim($hex, '#');

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        return 0.299 * $r + 0.587 * $g + 0.114 * $b;
    }

    private static function colorToHex(string $color): string
    {
        $colors = [
            'red' => '#FF0000',
            'green' => '#008000',
            'blue' => '#0000FF',
            'orange' => '#FFA500',
            'yellow' => '#FFFF00',
            'white' => '#FFFFFF',
            'black' => '#000000',
        ];

        return $colors[strtolower($color)] ?? $color;
    }
}
