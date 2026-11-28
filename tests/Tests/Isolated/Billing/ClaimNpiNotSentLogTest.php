<?php

/**
 * Isolated tests for the Claim NPI logging helpers.
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    Steve-Wiig <322563348+Steve-Wiig@users.noreply.github.com>
 * @copyright Copyright (c) 2026 Steve-Wiig
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Tests\Isolated\Billing;

use OpenEMR\Billing\Claim;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class ClaimNpiNotSentLogTest extends TestCase
{
    #[DataProvider('npiNotSentProvider')]
    public function testNpiNotSentLog(?string $rawNpi, string $expected): void
    {
        $this->assertSame($expected, Claim::npiNotSentLog('Test provider', $rawNpi));
    }

    /**
     * @return array<string, array{0: string|null, 1: string}>
     */
    public static function npiNotSentProvider(): array
    {
        return [
            'null NPI' => [null, "*** Test provider has no NPI.\n"],
            'empty string' => ['', "*** Test provider has no NPI.\n"],
            'whitespace only' => ['   ', "*** Test provider has no NPI.\n"],
            'valid NPI' => ['1234567890', "*** Test provider NPI \"1234567890\" is not valid.\n"],
            'invalid NPI with letters' => ['INVALID123', "*** Test provider NPI \"INVALID123\" is not valid.\n"],
            'too short NPI' => ['12345', "*** Test provider NPI \"12345\" is not valid.\n"],
            'too long NPI' => ['12345678901', "*** Test provider NPI \"12345678901\" is not valid.\n"],
            'NPI with spaces' => ['123 456 7890', "*** Test provider NPI \"123 456 7890\" is not valid.\n"],
        ];
    }

    public function testProviderRawNPIUsesHeaderForNegativeProckey(): void
    {
        $claim = (new ReflectionClass(Claim::class))->newInstanceWithoutConstructor();
        $claim->provider = ['npi' => ' 1111111111 '];
        $claim->procs = [];
        $this->assertSame('1111111111', $claim->providerRawNPI(-1));
    }

    public function testProviderRawNPIUsesServiceLineWhenProviderIdSet(): void
    {
        $claim = (new ReflectionClass(Claim::class))->newInstanceWithoutConstructor();
        $claim->provider = ['npi' => '1111111111'];
        $claim->procs = [
            0 => ['provider_id' => 5, 'provider' => ['npi' => ' 2222222222 ']],
        ];
        $this->assertSame('2222222222', $claim->providerRawNPI(0));
    }

    public function testProviderRawNPIFallsBackToHeaderWhenLineProviderIdEmpty(): void
    {
        $claim = (new ReflectionClass(Claim::class))->newInstanceWithoutConstructor();
        $claim->provider = ['npi' => ' 3333333333 '];
        $claim->procs = [
            0 => ['provider_id' => 0, 'provider' => ['npi' => '9999999999']],
        ];
        $this->assertSame('3333333333', $claim->providerRawNPI(0));
    }

    public function testNpiNotSentLogWithDifferentProviderTypes(): void
    {
        $types = ['Rendering provider', 'Referring provider', 'Operating physician', 'Facility'];
        foreach ($types as $type) {
            $result = Claim::npiNotSentLog($type, '1234567890');
            $this->assertStringContainsString($type, $result);
            $this->assertStringContainsString('1234567890', $result);
        }
    }

    public function testNpiNotSentLogEmptyWithDifferentProviderTypes(): void
    {
        $types = ['Rendering provider', 'Referring provider', 'Operating physician', 'Facility'];
        foreach ($types as $type) {
            $result = Claim::npiNotSentLog($type, null);
            $this->assertStringContainsString($type, $result);
            $this->assertStringContainsString('has no NPI', $result);
        }
    }
}
