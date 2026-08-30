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

	/**
	 * @return iterable<array{string|false, bool, bool}>
	 */
	public static function dataResolveIsMusl(): iterable
	{
		// captured from `php -r 'echo file_get_contents("/proc/self/maps");'`
		// running inside the official php:8.4-cli (glibc/Debian) image
		$glibcMaps = <<<'MAPS'
		58d76a000000-58d76a159000 r--p 00000000 00:67 14567852                   /usr/local/bin/php
		58d76a200000-58d76a714000 r-xp 00200000 00:67 14567852                   /usr/local/bin/php
		7da75ca7a000-7da75ca85000 r--p 00029000 00:67 14556924                   /usr/lib/x86_64-linux-gnu/ld-linux-x86-64.so.2
		7da75ca85000-7da75ca87000 r--p 00034000 00:67 14556924                   /usr/lib/x86_64-linux-gnu/ld-linux-x86-64.so.2
		7ffe37e20000-7ffe37e41000 rw-p 00000000 00:00 0                          [stack]
		MAPS;

		// same command, inside php:8.4-cli-alpine3.23 — /lib/ld-musl-x86_64.so.1
		// is the musl dynamic loader mapped into the process
		$muslMaps = <<<'MAPS'
		58adcee00000-58adcef58000 r--p 00000000 00:67 14555462                   /usr/local/bin/php
		58adcf000000-58adcf525000 r-xp 00200000 00:67 14555462                   /usr/local/bin/php
		7934c7f6a000-7934c7f7e000 r--p 00000000 00:67 14554411                   /lib/ld-musl-x86_64.so.1
		7934c7f7e000-7934c7fd6000 r-xp 00014000 00:67 14554411                   /lib/ld-musl-x86_64.so.1
		7934c800c000-7934c8010000 rw-p 00000000 00:00 0
		MAPS;

		// glibc host with a musl loader present on disk (e.g. musl-tools
		// installed) but not mapped into this process — the false positive
		// the old glob-based heuristic produced
		yield [$glibcMaps, false, false];
		yield [$muslMaps, false, true];
		yield [false, false, false];
		yield [false, true, true];
	}

	#[DataProvider('dataResolveIsMusl')]
	public function testResolveIsMusl(string|false $selfMaps, bool $hasAlpineRelease, bool $expected): void
	{
		$this->assertSame($expected, TurboExtensionSelector::resolveIsMusl($selfMaps, $hasAlpineRelease));
	}

}
