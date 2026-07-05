<?php

namespace Tests\Unit;

use App\Support\Branding;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BrandingTest extends TestCase
{
    // ── hexToHsl ──────────────────────────────────────────────────────────────

    #[DataProvider('hexCases')]
    public function test_hex_to_hsl(string $hex, string $expected): void
    {
        $this->assertSame($expected, Branding::hexToHsl($hex));
    }

    /** @return array<string, array{string, string}> */
    public static function hexCases(): array
    {
        return [
            'amber primary'    => ['#F4A81C', '39 91% 53%'],
            'green success'    => ['#2FA84F', '136 56% 42%'],
            'red error'        => ['#E5484D', '358 75% 59%'],
            'white'            => ['#FFFFFF', '0 0% 100%'],
            'black'            => ['#000000', '0 0% 0%'],
            'without hash'     => ['F4A81C', '39 91% 53%'],
            'shorthand'        => ['#FFF', '0 0% 100%'],
            'lowercase'        => ['#f4a81c', '39 91% 53%'],
        ];
    }

    // ── cssTokens ─────────────────────────────────────────────────────────────

    public function test_css_tokens_map_primary_to_ring_and_sidebar(): void
    {
        config(['company.theme' => [
            'primary'    => '#F4A81C',
            'bg'         => '#F6F2EA',
            'text'       => '#211E1A',
            'text_muted' => '#6B655E',
            'success'    => '#2FA84F',
            'warning'    => '#F4A81C',
            'error'      => '#E5484D',
        ]]);

        $tokens = Branding::cssTokens();

        $this->assertSame('39 91% 53%', $tokens['--primary']);
        $this->assertSame($tokens['--primary'], $tokens['--ring']);
        $this->assertSame($tokens['--primary'], $tokens['--sidebar-primary']);
        $this->assertSame('136 56% 42%', $tokens['--success']);
    }
}
