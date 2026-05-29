<?php

namespace Tests\Unit;

use App\Services\Shop\WhatsAppService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class WhatsAppServiceTest extends TestCase
{
    #[DataProvider('phoneProvider')]
    public function test_normalize_phone_for_wa_me(string $input, string $expected): void
    {
        $this->assertSame($expected, WhatsAppService::normalizePhone($input));
    }

    public static function phoneProvider(): array
    {
        return [
            'international spaced' => ['+20 12 22878031', '201222878031'],
            'local egyptian' => ['01222878031', '201222878031'],
            'digits only' => ['201222878031', '201222878031'],
            'empty' => ['', ''],
        ];
    }
}
