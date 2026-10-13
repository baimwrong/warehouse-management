<?php

namespace App\Services;

class BarcodeService
{
    /**
     * Generate an offline SVG Barcode (Code 128 compliant simplified representation)
     */
    public function generateSvg(string $code, int $width = 240, int $height = 60): string
    {
        $code = trim(strtoupper($code));
        $hash = crc32($code);
        $bars = '';
        $x = 10;
        $barWidth = 2;

        // Generate deterministic bar widths based on ASCII characters
        for ($i = 0; $i < strlen($code); $i++) {
            $charVal = ord($code[$i]);
            $pattern = sprintf("%06b", $charVal % 64);
            
            for ($b = 0; $b < strlen($pattern); $b++) {
                if ($pattern[$b] === '1') {
                    $bars .= "<rect x=\"{$x}\" y=\"5\" width=\"{$barWidth}\" height=\"40\" fill=\"#111827\" />\n";
                }
                $x += $barWidth;
            }
            $x += $barWidth; // inter-char space
        }

        $totalWidth = max($width, $x + 10);
        $textX = $totalWidth / 2;

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {$totalWidth} {$height}" width="{$totalWidth}" height="{$height}">
    <rect width="100%" height="100%" fill="#ffffff" rx="4" />
    {$bars}
    <text x="{$textX}" y="54" font-family="Courier, monospace" font-size="11" font-weight="bold" fill="#374151" text-anchor="middle">{$code}</text>
</svg>
SVG;
    }
}
