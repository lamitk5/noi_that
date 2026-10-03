<?php

namespace App\Support;

/**
 * Builds a real-scale glTF binary (1 unit = 1 metre) for a catalog product.
 * Shapes are simple furniture volumes so AR placement stays 1:1 with the size text.
 */
class FurnitureGlb
{
    /** @var array<string, array{positions: array<int, float>, normals: array<int, float>, indices: array<int, int>}> */
    private array $parts = [];

    /**
     * @return array{0: string, 1: float, 2: float, 3: float} shape, width cm, depth cm, height cm
     */
    public static function measure(string $name, ?string $dimensions): array
    {
        $shape = self::shape($name);
        $text = preg_replace('/\d+\s*tầng/u', '', (string) $dimensions) ?? '';

        if (preg_match('/dài\s*(\d+(?:[.,]\d+)?)\s*m\b/ui', $text, $match)) {
            $width = (float) str_replace(',', '.', $match[1]) * 100;

            return [$shape, $width, 60.0, 90.0];
        }

        if (preg_match('/[Øø]\s*(\d+(?:[.,]\d+)?)(?:\s*[x×]\s*(\d+(?:[.,]\d+)?))?/u', $text, $match)) {
            $diameter = (float) str_replace(',', '.', $match[1]);
            $height = isset($match[2]) && $match[2] !== ''
                ? (float) str_replace(',', '.', $match[2])
                : self::fallback($shape)[2];

            return [$shape, $diameter, $diameter, $height];
        }

        if (preg_match('/(\d+(?:[.,]\d+)?)\s*[x×]\s*(\d+(?:[.,]\d+)?)(?:\s*[x×]\s*(\d+(?:[.,]\d+)?))?/u', $text, $match)) {
            $width = (float) str_replace(',', '.', $match[1]);
            $second = (float) str_replace(',', '.', $match[2]);
            if (isset($match[3]) && $match[3] !== '') {
                return [$shape, $width, $second, (float) str_replace(',', '.', $match[3])];
            }

            return [$shape, ...self::expandPair($shape, $width, $second)];
        }

        return [$shape, ...self::fallback($shape)];
    }

    /**
     * Bounding box in centimetres. Width and depth are equal for a round piece.
     *
     * @return array{l: float, w: float, h: float}
     */
    public static function box(string $name, ?string $dimensions): array
    {
        [, $width, $depth, $height] = self::measure($name, $dimensions);

        return ['l' => $width, 'w' => $depth, 'h' => $height];
    }

    public static function boxLabel(string $name, ?string $dimensions): string
    {
        $box = self::box($name, $dimensions);

        return sprintf('%.0f x %.0f x %.0f cm', $box['l'], $box['w'], $box['h']);
    }

    /**
     * Keep a catalog string when it already matches the model. Replace labels
     * such as "Tiêu chuẩn", "Ø80 x 42 cm" or "3 tầng, 80 x 25 x 120 cm".
     */
    public static function displaySize(string $name, ?string $dimensions): string
    {
        $label = self::boxLabel($name, $dimensions);
        $parsed = DimensionFit::parse($dimensions);
        if ($parsed === null) {
            return $label;
        }

        $box = self::box($name, $dimensions);
        $drift = abs($parsed['l'] - $box['l']) + abs($parsed['w'] - $box['w']) + abs($parsed['h'] - $box['h']);

        return $drift > 5 ? $label : (string) $dimensions;
    }

    /**
     * @return array{0: string, 1: string} GLB bytes and USDZ bytes
     */
    public static function filesFor(string $name, ?string $dimensions): array
    {
        [$shape, $width, $depth, $height] = self::measure($name, $dimensions);
        $builder = new self();
        $builder->build($shape, max($width, 8) / 100, max($depth, 4) / 100, max($height, 4) / 100);

        return [$builder->encode(), $builder->encodeUsdz()];
    }

    public static function binaryFor(string $name, ?string $dimensions): string
    {
        return self::filesFor($name, $dimensions)[0];
    }

    private static function shape(string $name): string
    {
        $name = mb_strtolower($name);

        return match (true) {
            str_contains($name, 'đèn chùm') => 'pendant',
            str_contains($name, 'đèn cây') => 'floor-lamp',
            str_contains($name, 'đèn bàn') => 'table-lamp',
            str_contains($name, 'đèn') => 'wall-lamp',
            str_contains($name, 'ghế bành') => 'armchair',
            str_contains($name, 'ghế đôn'), str_contains($name, 'ghế lười') => 'pouf',
            str_contains($name, 'ghế quầy') => 'stool',
            str_contains($name, 'ghế xoay') => 'office',
            str_contains($name, 'ghế băng'), str_contains($name, 'bục ngồi'), str_contains($name, 'ghế thay') => 'bench',
            str_contains($name, 'ghế sofa') || (str_contains($name, 'sofa') && ! str_contains($name, 'bàn')) => 'sofa',
            str_contains($name, 'ghế') => 'chair',
            str_contains($name, 'bàn trà') => 'round-table',
            str_contains($name, 'bàn') => 'table',
            str_contains($name, 'kệ tivi') => 'tv',
            str_contains($name, 'kệ sách'), str_contains($name, 'kệ góc') => 'shelf',
            str_contains($name, 'kệ') => 'cabinet',
            str_contains($name, 'giường') => 'bed',
            str_contains($name, 'gương') => 'mirror',
            str_contains($name, 'rèm') => 'curtain',
            str_contains($name, 'thảm') => 'rug',
            str_contains($name, 'bình phong') => 'screen',
            str_contains($name, 'vách') => 'glass',
            str_contains($name, 'bồn') => 'tub',
            str_contains($name, 'đảo') => 'island',
            str_contains($name, 'tủ') => 'cabinet',
            default => 'box',
        };
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    private static function expandPair(string $shape, float $a, float $b): array
    {
        return match ($shape) {
            'bed' => [$a, $b, 50.0],
            'mirror', 'curtain', 'screen', 'glass' => [$a, $shape === 'curtain' ? 8.0 : 4.0, $b],
            'rug' => [$a, $b, 2.0],
            default => [$a, $b, self::fallback($shape)[2]],
        };
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    private static function fallback(string $shape): array
    {
        return match ($shape) {
            'bench' => [120.0, 40.0, 45.0],
            'chair', 'stool' => [45.0, 45.0, 90.0],
            'bed' => [180.0, 200.0, 50.0],
            'rug' => [160.0, 230.0, 2.0],
            'curtain' => [140.0, 8.0, 250.0],
            default => [80.0, 40.0, 75.0],
        };
    }

    private function build(string $shape, float $w, float $d, float $h): void
    {
        match ($shape) {
            'sofa', 'armchair' => $this->sofa($w, $d, $h, $shape === 'armchair'),
            'pouf' => $this->addCylinder('fabric', 0, $h / 2, 0, min($w, $d) / 2, $h),
            'stool' => $this->stool($w, $d, $h),
            'office' => $this->office($w, $d, $h),
            'bench' => $this->bench($w, $d, $h),
            'chair' => $this->chair($w, $d, $h),
            'bed' => $this->bed($w, $d, $h),
            'round-table' => $this->roundTable($w, $h),
            'table' => $this->table($w, $d, $h),
            'pendant' => $this->lamp($w, $h, 0.15),
            'floor-lamp' => $this->lamp($w, $h, 0.04),
            'table-lamp' => $this->lamp($w, $h, 0.08),
            'wall-lamp' => $this->addBox('shade', 0, $h / 2, 0, $w, $h, max($d, 0.08)),
            'mirror' => $this->mirror($w, $d, $h),
            'curtain' => $this->curtain($w, $h),
            'rug' => $this->addBox('rug', 0, 0.008, 0, $w, 0.015, $d),
            'screen' => $this->screen($w, $h),
            'glass' => $this->addBox('glass', 0, $h / 2, 0, $w, $h, 0.012),
            'tub' => $this->tub($w, $d, $h),
            'shelf' => $this->shelf($w, $d, $h),
            'tv' => $this->cabinet($w, $d, $h, true),
            'island' => $this->island($w, $d, $h),
            'cabinet' => $this->cabinet($w, $d, $h, false),
            default => $this->addBox('wood', 0, $h / 2, 0, $w, $h, $d),
        };
    }

    private function table(float $w, float $d, float $h): void
    {
        $top = min(0.045, $h * 0.12);
        $this->addBox('wood', 0, $h - $top / 2, 0, $w, $top, $d);
        $leg = 0.05;
        $insetX = min(0.08, $w * 0.15);
        $insetZ = min(0.08, $d * 0.15);
        $legH = max($h - $top, 0.05);
        foreach ([-1, 1] as $sx) {
            foreach ([-1, 1] as $sz) {
                $this->addBox('wood', $sx * ($w / 2 - $insetX), $legH / 2, $sz * ($d / 2 - $insetZ), $leg, $legH, $leg);
            }
        }
    }

    private function roundTable(float $diameter, float $h): void
    {
        $radius = $diameter / 2;
        $top = 0.04;
        $this->addCylinder('wood', 0, $h - $top / 2, 0, $radius, $top);
        $this->addCylinder('wood', 0, ($h - $top) / 2, 0, max(0.04, $radius * 0.12), $h - $top);
        $this->addCylinder('wood', 0, 0.015, 0, $radius * 0.45, 0.03);
    }

    private function sofa(float $w, float $d, float $h, bool $single): void
    {
        $leg = 0.08;
        $arm = min(0.14, $w * 0.18);
        $back = min(0.16, $d * 0.28);
        $seatTop = min($h * 0.48, $leg + 0.32);
        $this->addBox('wood', -$w / 2 + 0.06, $leg / 2, -$d / 2 + 0.06, 0.05, $leg, 0.05);
        $this->addBox('wood', $w / 2 - 0.06, $leg / 2, -$d / 2 + 0.06, 0.05, $leg, 0.05);
        $this->addBox('wood', -$w / 2 + 0.06, $leg / 2, $d / 2 - 0.06, 0.05, $leg, 0.05);
        $this->addBox('wood', $w / 2 - 0.06, $leg / 2, $d / 2 - 0.06, 0.05, $leg, 0.05);
        $this->addBox('fabric', 0, ($seatTop + $leg) / 2, $back / 2, $w, $seatTop - $leg, $d - $back);
        $this->addBox('fabric', 0, ($h + $seatTop) / 2, -$d / 2 + $back / 2, $w, $h - $seatTop, $back);
        $this->addBox('fabric', -$w / 2 + $arm / 2, ($h * 0.72 + $seatTop) / 2, $back / 2, $arm, $h * 0.72 - $seatTop, $d - $back);
        $this->addBox('fabric', $w / 2 - $arm / 2, ($h * 0.72 + $seatTop) / 2, $back / 2, $arm, $h * 0.72 - $seatTop, $d - $back);
        $cushions = $single ? 1 : max(1, (int) round($w / 0.7));
        $inner = $w - $arm * 2 - 0.04;
        $cw = $inner / $cushions;
        for ($i = 0; $i < $cushions; $i++) {
            $x = -$inner / 2 + $cw * ($i + 0.5);
            $this->addBox('cushion', $x, $seatTop + 0.04, 0.02, $cw - 0.03, 0.1, $d - $back - 0.1);
        }
    }

    private function chair(float $w, float $d, float $h): void
    {
        $seat = min(0.46, $h * 0.5);
        $this->addBox('wood', 0, $seat - 0.025, 0, $w * 0.92, 0.05, $d * 0.9);
        $this->addBox('cushion', 0, $seat + 0.02, 0, $w * 0.84, 0.05, $d * 0.78);
        $this->addBox('wood', 0, ($h + $seat) / 2, -$d / 2 + 0.03, $w * 0.86, $h - $seat, 0.04);
        foreach ([-1, 1] as $sx) {
            foreach ([-1, 1] as $sz) {
                $this->addBox('wood', $sx * ($w * 0.38), $seat / 2, $sz * ($d * 0.36), 0.04, $seat, 0.04);
            }
        }
    }

    private function bench(float $w, float $d, float $h): void
    {
        $top = min(0.08, $h * 0.25);
        $this->addBox('wood', 0, $h - $top / 2, 0, $w, $top, $d);
        $this->addBox('cushion', 0, $h + 0.02, 0, $w * 0.92, 0.04, $d * 0.8);
        foreach ([-1, 1] as $sx) {
            foreach ([-1, 1] as $sz) {
                $this->addBox('wood', $sx * ($w / 2 - 0.06), ($h - $top) / 2, $sz * ($d / 2 - 0.05), 0.045, $h - $top, 0.045);
            }
        }
    }

    private function stool(float $w, float $d, float $h): void
    {
        $radius = min($w, $d) / 2;
        $this->addCylinder('wood', 0, $h - 0.03, 0, $radius, 0.06);
        $this->addCylinder('metal', 0, ($h - 0.06) / 2, 0, 0.03, $h - 0.06);
        $this->addCylinder('metal', 0, $h * 0.38, 0, $radius * 0.72, 0.02);
        $this->addCylinder('metal', 0, 0.015, 0, $radius * 0.7, 0.03);
    }

    private function office(float $w, float $d, float $h): void
    {
        $seat = min(0.5, $h * 0.48);
        $this->addBox('fabric', 0, $seat, 0, $w * 0.85, 0.08, $d * 0.8);
        $this->addBox('fabric', 0, ($h + $seat) / 2, -$d * 0.32, $w * 0.8, $h - $seat, 0.08);
        $this->addCylinder('metal', 0, $seat * 0.45, 0, 0.03, $seat * 0.7);
        $this->addCylinder('metal', 0, 0.025, 0, $w * 0.42, 0.04);
    }

    private function bed(float $w, float $d, float $h): void
    {
        $mattress = $h > 0.8 ? $h * 0.45 : 0.5;
        $head = $h > 0.8 ? $h : 1.15;
        $this->addBox('wood', 0, 0.08, 0, $w, 0.16, $d);
        $this->addBox('cushion', 0, 0.16 + ($mattress - 0.16) / 2, 0.03, $w - 0.08, $mattress - 0.16, $d - 0.12);
        $this->addBox('wood', 0, $head / 2, -$d / 2 + 0.04, $w, $head, 0.08);
        $this->addBox('wood', 0, 0.2, $d / 2 - 0.03, $w, 0.28, 0.05);
    }

    private function cabinet(float $w, float $d, float $h, bool $low): void
    {
        $plinth = 0.05;
        $this->addBox('wood', 0, $plinth / 2, 0, $w - 0.04, $plinth, $d - 0.04);
        $this->addBox('wood', 0, $plinth + ($h - $plinth) / 2, 0, $w, $h - $plinth, $d);
        $doors = $low ? 2 : ($w > 1.2 ? 3 : 2);
        $gap = 0.012;
        $doorW = ($w - 0.06 - $gap * ($doors - 1)) / $doors;
        $doorH = ($h - $plinth) * 0.86;
        for ($i = 0; $i < $doors; $i++) {
            $x = -$w / 2 + 0.03 + $doorW / 2 + $i * ($doorW + $gap);
            $this->addBox('cushion', $x, $plinth + 0.04 + $doorH / 2, $d / 2 + 0.008, $doorW, $doorH, 0.016);
        }
    }

    private function island(float $w, float $d, float $h): void
    {
        $this->cabinet($w * 0.92, $d * 0.9, $h - 0.05, false);
        $this->addBox('white', 0, $h - 0.02, 0, $w, 0.04, $d);
    }

    private function shelf(float $w, float $d, float $h): void
    {
        $this->addBox('wood', -$w / 2 + 0.015, $h / 2, 0, 0.03, $h, $d);
        $this->addBox('wood', $w / 2 - 0.015, $h / 2, 0, 0.03, $h, $d);
        $this->addBox('wood', 0, $h / 2, -$d / 2 + 0.01, $w, $h, 0.02);
        $levels = max(2, (int) floor($h / 0.38));
        for ($i = 0; $i <= $levels; $i++) {
            $y = $h * ($i / $levels);
            $this->addBox('wood', 0, max($y, 0.015), 0, $w - 0.04, 0.025, $d);
        }
    }

    private function lamp(float $diameter, float $h, float $baseRadius): void
    {
        $shadeH = min(0.28, $h * 0.28);
        $this->addCylinder('metal', 0, 0.015, 0, max($baseRadius, $diameter * 0.22), 0.03);
        $this->addCylinder('metal', 0, ($h - $shadeH) / 2, 0, 0.015, $h - $shadeH);
        $this->addCylinder('shade', 0, $h - $shadeH / 2, 0, max($diameter / 2, 0.08), $shadeH);
    }

    private function mirror(float $w, float $d, float $h): void
    {
        $depth = max($d, 0.04);
        $this->addBox('wood', 0, $h / 2, 0, $w, $h, $depth);
        $this->addBox('glass', 0, $h / 2, $depth / 2 + 0.004, $w * 0.86, $h * 0.86, 0.008);
    }

    private function curtain(float $w, float $h): void
    {
        $this->addCylinder('metal', 0, $h - 0.015, 0, 0.015, 0.03);
        $this->addBox('metal', 0, $h - 0.02, 0, $w, 0.025, 0.025);
        $this->addBox('fabric', -$w * 0.22, $h * 0.46, 0, $w * 0.48, $h * 0.9, 0.04);
        $this->addBox('fabric', $w * 0.22, $h * 0.46, 0.02, $w * 0.48, $h * 0.9, 0.04);
    }

    private function screen(float $w, float $h): void
    {
        $panel = $w / 3;
        foreach ([-1, 0, 1] as $i) {
            $this->addBox('wood', $i * $panel, $h / 2, $i === 0 ? -0.06 : 0.04, $panel - 0.02, $h, 0.03);
        }
    }

    private function tub(float $w, float $d, float $h): void
    {
        $this->addBox('white', 0, $h / 2, 0, $w, $h, $d);
        $this->addBox('glass', 0, $h - 0.04, 0, $w * 0.78, 0.06, $d * 0.62);
    }

    private function addBox(string $material, float $cx, float $cy, float $cz, float $sx, float $sy, float $sz): void
    {
        if ($sx < 0.004 || $sy < 0.004 || $sz < 0.004) {
            return;
        }

        $hx = $sx / 2;
        $hy = $sy / 2;
        $hz = $sz / 2;
        $faces = [
            [[1, 0, 0], [[1, -1, -1], [1, -1, 1], [1, 1, 1], [1, 1, -1]]],
            [[-1, 0, 0], [[-1, -1, 1], [-1, -1, -1], [-1, 1, -1], [-1, 1, 1]]],
            [[0, 1, 0], [[-1, 1, 1], [1, 1, 1], [1, 1, -1], [-1, 1, -1]]],
            [[0, -1, 0], [[-1, -1, -1], [1, -1, -1], [1, -1, 1], [-1, -1, 1]]],
            [[0, 0, 1], [[-1, -1, 1], [1, -1, 1], [1, 1, 1], [-1, 1, 1]]],
            [[0, 0, -1], [[1, -1, -1], [-1, -1, -1], [-1, 1, -1], [1, 1, -1]]],
        ];

        foreach ($faces as [$normal, $corners]) {
            $verts = array_map(
                fn (array $sign) => [
                    $cx + $sign[0] * $hx,
                    $cy + $sign[1] * $hy,
                    $cz + $sign[2] * $hz,
                ],
                $corners
            );
            $this->addQuad($material, $normal, $verts);
        }
    }

    private function addCylinder(string $material, float $cx, float $cy, float $cz, float $radius, float $height, int $segments = 18): void
    {
        if ($radius < 0.004 || $height < 0.004) {
            return;
        }

        $y0 = $cy - $height / 2;
        $y1 = $cy + $height / 2;
        for ($i = 0; $i < $segments; $i++) {
            $a0 = 2 * M_PI * $i / $segments;
            $a1 = 2 * M_PI * ($i + 1) / $segments;
            $p0 = [cos($a0) * $radius, sin($a0) * $radius];
            $p1 = [cos($a1) * $radius, sin($a1) * $radius];
            $mid = ($a0 + $a1) / 2;
            $this->addQuad($material, [cos($mid), 0, sin($mid)], [
                [$cx + $p0[0], $y0, $cz + $p0[1]],
                [$cx + $p1[0], $y0, $cz + $p1[1]],
                [$cx + $p1[0], $y1, $cz + $p1[1]],
                [$cx + $p0[0], $y1, $cz + $p0[1]],
            ]);
            $this->addTri($material, [0, 1, 0], [
                [$cx, $y1, $cz],
                [$cx + $p0[0], $y1, $cz + $p0[1]],
                [$cx + $p1[0], $y1, $cz + $p1[1]],
            ]);
            $this->addTri($material, [0, -1, 0], [
                [$cx, $y0, $cz],
                [$cx + $p1[0], $y0, $cz + $p1[1]],
                [$cx + $p0[0], $y0, $cz + $p0[1]],
            ]);
        }
    }

    /**
     * @param  array{0: float, 1: float, 2: float}  $normal
     * @param  array<int, array{0: float, 1: float, 2: float}>  $verts
     */
    private function addQuad(string $material, array $normal, array $verts): void
    {
        if ($this->facesIn($normal, $verts[0], $verts[1], $verts[2]) < 0) {
            $verts = [$verts[0], $verts[3], $verts[2], $verts[1]];
        }

        $base = $this->pushVerts($material, $verts, $normal);
        $part = &$this->parts[$material];
        array_push($part['indices'], $base, $base + 1, $base + 2, $base, $base + 2, $base + 3);
    }

    /**
     * @param  array{0: float, 1: float, 2: float}  $normal
     * @param  array<int, array{0: float, 1: float, 2: float}>  $verts
     */
    private function addTri(string $material, array $normal, array $verts): void
    {
        if ($this->facesIn($normal, $verts[0], $verts[1], $verts[2]) < 0) {
            $verts = [$verts[0], $verts[2], $verts[1]];
        }

        $base = $this->pushVerts($material, $verts, $normal);
        $part = &$this->parts[$material];
        array_push($part['indices'], $base, $base + 1, $base + 2);
    }

    /**
     * @param  array<int, array{0: float, 1: float, 2: float}>  $verts
     * @param  array{0: float, 1: float, 2: float}  $normal
     */
    private function pushVerts(string $material, array $verts, array $normal): int
    {
        if (! isset($this->parts[$material])) {
            $this->parts[$material] = ['positions' => [], 'normals' => [], 'indices' => []];
        }

        $base = intdiv(count($this->parts[$material]['positions']), 3);
        foreach ($verts as $vert) {
            array_push($this->parts[$material]['positions'], $vert[0], $vert[1], $vert[2]);
            array_push($this->parts[$material]['normals'], $normal[0], $normal[1], $normal[2]);
        }

        return $base;
    }

    /**
     * @param  array{0: float, 1: float, 2: float}  $normal
     * @param  array{0: float, 1: float, 2: float}  $a
     * @param  array{0: float, 1: float, 2: float}  $b
     * @param  array{0: float, 1: float, 2: float}  $c
     */
    private function facesIn(array $normal, array $a, array $b, array $c): float
    {
        $e1 = [$b[0] - $a[0], $b[1] - $a[1], $b[2] - $a[2]];
        $e2 = [$c[0] - $a[0], $c[1] - $a[1], $c[2] - $a[2]];
        $cross = [
            $e1[1] * $e2[2] - $e1[2] * $e2[1],
            $e1[2] * $e2[0] - $e1[0] * $e2[2],
            $e1[0] * $e2[1] - $e1[1] * $e2[0],
        ];

        return $cross[0] * $normal[0] + $cross[1] * $normal[1] + $cross[2] * $normal[2];
    }

    private function encode(): string
    {
        if ($this->parts === []) {
            $this->addBox('wood', 0, 0.1, 0, 0.2, 0.2, 0.2);
        }

        $colors = [
            'wood' => [0.62, 0.42, 0.24, 1],
            'fabric' => [0.48, 0.40, 0.34, 1],
            'cushion' => [0.86, 0.78, 0.66, 1],
            'metal' => [0.25, 0.25, 0.27, 1],
            'glass' => [0.72, 0.86, 0.92, 0.55],
            'white' => [0.94, 0.94, 0.92, 1],
            'rug' => [0.55, 0.28, 0.20, 1],
            'shade' => [0.95, 0.90, 0.78, 1],
        ];

        $bin = '';
        $bufferViews = [];
        $accessors = [];
        $primitives = [];
        $materials = [];
        $materialIndex = [];

        foreach ($this->parts as $name => $part) {
            if (! isset($materialIndex[$name])) {
                $materialIndex[$name] = count($materials);
                $color = $colors[$name] ?? $colors['wood'];
                $material = [
                    'name' => $name,
                    'pbrMetallicRoughness' => [
                        'baseColorFactor' => $color,
                        'metallicFactor' => $name === 'metal' ? 0.7 : 0.0,
                        'roughnessFactor' => $name === 'metal' ? 0.35 : 0.72,
                    ],
                ];
                if ($color[3] < 1) {
                    $material['alphaMode'] = 'BLEND';
                }
                if (in_array($name, ['glass', 'rug', 'fabric'], true)) {
                    $material['doubleSided'] = true;
                }
                $materials[] = $material;
            }

            $pos = pack('f*', ...$part['positions']);
            $norm = pack('f*', ...$part['normals']);
            $idx = pack('v*', ...$part['indices']);
            $vertexCount = intdiv(count($part['positions']), 3);

            $min = [INF, INF, INF];
            $max = [-INF, -INF, -INF];
            for ($i = 0; $i < $vertexCount; $i++) {
                foreach ([0, 1, 2] as $axis) {
                    $value = $part['positions'][$i * 3 + $axis];
                    $min[$axis] = min($min[$axis], $value);
                    $max[$axis] = max($max[$axis], $value);
                }
            }

            $views = [
                [$pos, 34962],
                [$norm, 34962],
                [$idx, 34963],
            ];
            $accessorIds = [];
            foreach ($views as [$bytes, $target]) {
                $pad = (4 - (strlen($bin) % 4)) % 4;
                $bin .= str_repeat("\0", $pad);
                $offset = strlen($bin);
                $bin .= $bytes;
                $bufferViews[] = [
                    'buffer' => 0,
                    'byteOffset' => $offset,
                    'byteLength' => strlen($bytes),
                    'target' => $target,
                ];
                $accessorIds[] = count($accessors);
                $isIndex = $target === 34963;
                $accessor = [
                    'bufferView' => count($bufferViews) - 1,
                    'componentType' => $isIndex ? 5123 : 5126,
                    'count' => $isIndex ? count($part['indices']) : $vertexCount,
                    'type' => $isIndex ? 'SCALAR' : 'VEC3',
                ];
                if (! $isIndex && $bytes === $pos) {
                    $accessor['min'] = $min;
                    $accessor['max'] = $max;
                }
                $accessors[] = $accessor;
            }

            $primitives[] = [
                'attributes' => ['POSITION' => $accessorIds[0], 'NORMAL' => $accessorIds[1]],
                'indices' => $accessorIds[2],
                'material' => $materialIndex[$name],
            ];
        }

        $json = json_encode([
            'asset' => ['version' => '2.0', 'generator' => 'MocAn'],
            'scene' => 0,
            'scenes' => [['nodes' => [0]]],
            'nodes' => [['mesh' => 0, 'name' => 'furniture']],
            'meshes' => [['primitives' => $primitives]],
            'materials' => $materials,
            'buffers' => [['byteLength' => strlen($bin)]],
            'bufferViews' => $bufferViews,
            'accessors' => $accessors,
        ], JSON_UNESCAPED_SLASHES);

        $jsonChunk = $json.str_repeat(' ', (4 - (strlen($json) % 4)) % 4);
        $bin .= str_repeat("\0", (4 - (strlen($bin) % 4)) % 4);
        $total = 12 + 8 + strlen($jsonChunk) + 8 + strlen($bin);

        return pack('V', 0x46546C67)
            .pack('V', 2)
            .pack('V', $total)
            .pack('V', strlen($jsonChunk))
            .pack('V', 0x4E4F534A)
            .$jsonChunk
            .pack('V', strlen($bin))
            .pack('V', 0x004E4942)
            .$bin;
    }

    private function encodeUsdz(): string
    {
        $colors = [
            'wood' => [0.62, 0.42, 0.24],
            'fabric' => [0.48, 0.40, 0.34],
            'cushion' => [0.86, 0.78, 0.66],
            'metal' => [0.25, 0.25, 0.27],
            'glass' => [0.72, 0.86, 0.92],
            'white' => [0.94, 0.94, 0.92],
            'rug' => [0.55, 0.28, 0.20],
            'shade' => [0.95, 0.90, 0.78],
        ];

        $meshes = '';
        $index = 0;
        foreach ($this->parts as $name => $part) {
            $points = $this->usdVec3($part['positions']);
            $normals = $this->usdVec3($part['normals']);
            $counts = implode(', ', array_fill(0, intdiv(count($part['indices']), 3), '3'));
            $indices = implode(', ', $part['indices']);
            $color = $colors[$name] ?? $colors['wood'];
            $colorText = sprintf('(%.3f, %.3f, %.3f)', $color[0], $color[1], $color[2]);
            $meshes .= <<<USD
    def Mesh "Part{$index}"
    {
        uniform token subdivisionScheme = "none"
        uniform bool doubleSided = 1
        int[] faceVertexCounts = [{$counts}]
        int[] faceVertexIndices = [{$indices}]
        point3f[] points = [{$points}]
        normal3f[] normals = [{$normals}] (
            interpolation = "vertex"
        )
        color3f[] primvars:displayColor = [{$colorText}]
    }

USD;
            $index++;
        }

        $usda = <<<USD
#usda 1.0
(
    defaultPrim = "Root"
    metersPerUnit = 1
    upAxis = "Y"
)

def Xform "Root"
{
{$meshes}}

USD;

        return $this->storeZip('model.usda', $usda);
    }

    /**
     * @param  array<int, float>  $values
     */
    private function usdVec3(array $values): string
    {
        $points = [];
        for ($i = 0; $i < count($values); $i += 3) {
            $points[] = sprintf('(%.5f, %.5f, %.5f)', $values[$i], $values[$i + 1], $values[$i + 2]);
        }

        return implode(', ', $points);
    }

    /**
     * Uncompressed zip with the file payload aligned to 64 bytes, which Quick Look requires.
     */
    private function storeZip(string $name, string $data): string
    {
        $crc = crc32($data) & 0xffffffff;
        $size = strlen($data);
        $extraLength = (64 - ((30 + strlen($name)) % 64)) % 64;
        $extra = str_repeat("\0", $extraLength);

        $local = pack('VvvvvvVVVvv', 0x04034b50, 20, 0, 0, 0, 0, $crc, $size, $size, strlen($name), $extraLength)
            .$name.$extra.$data;
        $central = pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, 0, 0, 0, 0, $crc, $size, $size, strlen($name), $extraLength, 0, 0, 0, 0, 0)
            .$name.$extra;
        $end = pack('VvvvvVVv', 0x06054b50, 0, 0, 1, 1, strlen($central), strlen($local), 0);

        return $local.$central.$end;
    }
}
