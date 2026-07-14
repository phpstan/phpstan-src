<?php declare(strict_types = 1);

namespace PHPStan\Turbo;

use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class TurboExtensionSelectorTest extends PHPStanTestCase
{

	/**
	 * @return iterable<array{string, string, bool, string|null}>
	 */
	public static function dataResolvePlatformDirectory(): iterable
	{
		yield ['Darwin', 'arm64', false, 'macos'];
		yield ['Darwin', 'x86_64', false, 'macos'];
		yield ['Linux', 'x86_64', false, 'linux-gnu-x86_64'];
		yield ['Linux', 'aarch64', false, 'linux-gnu-arm64'];
		yield ['Linux', 'arm64', false, 'linux-gnu-arm64'];
		yield ['Linux', 'x86_64', true, 'linux-musl-x86_64'];
		yield ['Linux', 'aarch64', true, 'linux-musl-arm64'];
		yield ['Linux', 'riscv64', false, null];
		yield ['Linux', 'i686', true, null];
		yield ['Windows', 'AMD64', false, 'windows-x86_64'];
		yield ['Windows', 'x86', false, null];
		yield ['Windows', 'ARM64', false, null];
		yield ['BSD', 'x86_64', false, null];
		yield ['Unknown', 'x86_64', false, null];
	}

	#[DataProvider('dataResolvePlatformDirectory')]
	public function testResolvePlatformDirectory(string $osFamily, string $machine, bool $isMusl, ?string $expected): void
	{
		$this->assertSame($expected, TurboExtensionSelector::resolvePlatformDirectory($osFamily, $machine, $isMusl));
	}

}
