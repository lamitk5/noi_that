<?php

namespace App\Support;

class DimensionFit
{
    /**
     * Read the first three centimetre numbers from a size string such as "160 x 80 x 75 cm".
     *
     * @return array{l: float, w: float, h: float}|null
     */
    public static function parse(?string $text): ?array
    {
        if ($text === null || trim($text) === '') {
            return null;
        }

        if (! preg_match_all('/(\d+(?:[.,]\d+)?)/', $text, $matches) || count($matches[1]) < 3) {
            return null;
        }

        $numbers = array_map(
            fn (string $value) => (float) str_replace(',', '.', $value),
            array_slice($matches[1], 0, 3)
        );

        if (min($numbers) <= 0) {
            return null;
        }

        return ['l' => $numbers[0], 'w' => $numbers[1], 'h' => $numbers[2]];
    }

    /**
     * A piece fits an opening when one axis can point through the doorway
     * and the other two are no larger than the opening width and height.
     *
     * @param  array{l: float, w: float, h: float}|null  $item
     */
    public static function fits(?array $item, ?float $openingWidth, ?float $openingHeight): ?bool
    {
        if ($item === null || $openingWidth === null || $openingHeight === null || $openingWidth <= 0 || $openingHeight <= 0) {
            return null;
        }

        $dims = [$item['l'], $item['w'], $item['h']];

        foreach ([0, 1, 2] as $depthAxis) {
            $face = [];
            foreach ([0, 1, 2] as $axis) {
                if ($axis !== $depthAxis) {
                    $face[] = $dims[$axis];
                }
            }

            $fitsUpright = $face[0] <= $openingWidth && $face[1] <= $openingHeight;
            $fitsTurned = $face[1] <= $openingWidth && $face[0] <= $openingHeight;
            if ($fitsUpright || $fitsTurned) {
                return true;
            }
        }

        return false;
    }
}
