<?php

namespace Tests\Unit;

use App\Services\BarcodeService;
use PHPUnit\Framework\TestCase;

class BarcodeServiceTest extends TestCase
{
    protected BarcodeService $barcodeService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->barcodeService = new BarcodeService();
    }

    public function test_generates_valid_svg_structure(): void
    {
        $code = 'TEST-SKU-101';
        $svg = $this->barcodeService->generateSvg($code);

        $this->assertStringContainsString('<svg xmlns="http://www.w3.org/2000/svg"', $svg);
        $this->assertStringContainsString('</svg>', $svg);
        $this->assertStringContainsString('<rect width="100%" height="100%" fill="#ffffff"', $svg);
        $this->assertStringContainsString($code, $svg);
    }

    public function test_handles_empty_string_gracefully(): void
    {
        $svg = $this->barcodeService->generateSvg('');

        $this->assertStringContainsString('UNKNOWN', $svg);
    }

    public function test_escapes_xml_special_characters_in_text(): void
    {
        $code = '<SKU&1>';
        $svg = $this->barcodeService->generateSvg($code);

        $this->assertStringNotContainsString($code, $svg);
    }
}
