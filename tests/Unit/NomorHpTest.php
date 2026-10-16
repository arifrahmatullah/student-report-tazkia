<?php

namespace Tests\Unit;

use App\Support\NomorHp;
use PHPUnit\Framework\TestCase;

class NomorHpTest extends TestCase
{
    public function test_berbagai_format_menjadi_628(): void
    {
        foreach (['081234567890', '+62 812-3456-7890', '6281234567890', '81234567890', '(0812) 3456 7890'] as $in) {
            $this->assertSame('6281234567890', NomorHp::normalisasi($in), $in);
        }
    }

    public function test_nomor_tidak_valid_menghasilkan_null(): void
    {
        foreach ([null, '', '   ', 'abc', '12345', '0211234567', '0812345', '08123456789012345', '+1 202 555 0100'] as $in) {
            $this->assertNull(NomorHp::normalisasi($in), var_export($in, true));
        }
    }
}
