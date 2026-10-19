<?php

namespace App\Services;

class BarcodeService
{
    /**
     * Standard Code 128 (Table B) bar-and-space module patterns (0 to 106).
     * Each pattern represents the widths of consecutive [bar, space, bar, space, bar, space].
     */
    protected const PATTERNS = [
        '212222', '222122', '222221', '121223', '121322', '131222', '122213', '122312', '132212', '221213', // 0-9
        '221312', '231212', '112232', '122132', '122231', '113222', '123122', '123221', '223211', '221132', // 10-19
        '221231', '213212', '223112', '312131', '311222', '321122', '321221', '312212', '322112', '322211', // 20-29
        '212123', '212321', '232121', '111323', '131123', '131321', '112313', '132113', '132311', '211313', // 30-39
        '231113', '231311', '112133', '112331', '132131', '113123', '113321', '133121', '313121', '211331', // 40-49
        '231131', '213113', '213311', '213131', '311123', '311321', '331121', '312113', '312311', '332111', // 50-59
        '314111', '221411', '431111', '111224', '111422', '121124', '121421', '141122', '141221', '112214', // 60-69
        '112412', '122114', '122411', '142112', '142211', '241211', '221114', '413111', '241112', '134111', // 70-79
        '111242', '121142', '121241', '114212', '124112', '124211', '411212', '421112', '421211', '212141', // 80-89
        '214121', '412121', '111143', '111341', '131141', '114113', '114311', '411113', '411311', '113141', // 90-99
        '114131', '311141', '411131', '211412', '211214', '211232', '2331112'                               // 100-106 (104=Start B, 106=Stop)
    ];

    /**
     * Generate standard scannable Code 128 (Subset B) SVG barcode
     */
    public function generateSvg(string $code, int $width = 240, int $height = 65): string
    {
        $code = trim($code);
        if ($code === '') {
            $code = 'UNKNOWN';
        }

        // Limit code length to reasonable barcode size
        if (strlen($code) > 40) {
            $code = substr($code, 0, 40);
        }

        $startCode = 104; // Start B
        $values = [$startCode];
        $checksum = $startCode;

        for ($i = 0; $i < strlen($code); $i++) {
            $ascii = ord($code[$i]);
            // Table B supports ASCII 32 to 126
            $val = ($ascii >= 32 && $ascii <= 126) ? ($ascii - 32) : 0;
            $values[] = $val;
            $checksum += ($val * ($i + 1));
        }

        $checkDigit = $checksum % 103;
        $values[] = $checkDigit;
        $values[] = 106; // Stop code

        // Render bars
        $moduleWidth = 1.6;
        $barHeight = max(30, $height - 25);
        $x = 14.0; // Left quiet zone
        $svgBars = '';

        foreach ($values as $patternIndex) {
            $pattern = self::PATTERNS[$patternIndex] ?? self::PATTERNS[0];
            $isBar = true;

            for ($p = 0; $p < strlen($pattern); $p++) {
                $modules = (int) $pattern[$p];
                $elemWidth = $modules * $moduleWidth;

                if ($isBar) {
                    $svgBars .= sprintf(
                        '<rect x="%.2f" y="8" width="%.2f" height="%d" fill="#111827" />%s',
                        $x,
                        $elemWidth,
                        $barHeight,
                        "\n"
                    );
                }
                $x += $elemWidth;
                $isBar = !$isBar;
            }
        }

        $x += 14.0; // Right quiet zone
        $totalWidth = (int) ceil(max($width, $x));
        $textX = (int) ($totalWidth / 2);
        $textY = $barHeight + 20;
        $escapedCode = htmlspecialchars($code, ENT_QUOTES | ENT_XML1, 'UTF-8');

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {$totalWidth} {$height}" width="{$totalWidth}" height="{$height}">
    <rect width="100%" height="100%" fill="#ffffff" rx="4" />
    {$svgBars}
    <text x="{$textX}" y="{$textY}" font-family="Courier, monospace" font-size="11" font-weight="bold" fill="#374151" text-anchor="middle">{$escapedCode}</text>
</svg>
SVG;
    }
}
