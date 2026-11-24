<?php declare(strict_types = 1);

namespace PHPStan\Turbo;

use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use function escapeshellarg;
use function exec;
use function implode;
use function sprintf;
use function str_replace;
use function var_export;
use const PHP_BINARY;
use const PHP_DEBUG;
use const PHP_OS_FAMILY;

final class TurboExtensionSelectorTest extends PHPStanTestCase
{

	/**
	 * @return iterable<array{string, string, bool, string|null}>
	 */
	public static function dataResolvePlatformDirectory(): iterable
	{
		yield ['Darwin', 'arm64', false, 'macos-arm64'];
		yield ['Darwin', 'x86_64', false, null];
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
	 * @return iterable<array{string, bool, string}>
	 */
	public static function dataResolveCoreFileName(): iterable
	{
		yield ['Linux', false, 'phpstan_turbo_core.so'];
		yield ['Linux', true, 'phpstan_turbo_core.so'];
		yield ['Darwin', false, 'phpstan_turbo_core.so'];
		yield ['Windows', false, 'phpstan_turbo_core.dll'];
		yield ['Windows', true, 'phpstan_turbo_core-zts.dll'];
	}

	#[DataProvider('dataResolveCoreFileName')]
	public function testResolveCoreFileName(string $osFamily, bool $zts, string $expected): void
	{
		$this->assertSame($expected, TurboExtensionSelector::resolveCoreFileName($osFamily, $zts));
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

	/**
	 * @return iterable<string, array{string|false, string|false, bool}>
	 */
	public static function dataShouldTryLinuxBinaries(): iterable
	{
		// captured with readlink('/proc/self/exe') and /proc/self/maps, one
		// line per pathname and permission set

		// php:8.5-cli, Debian 13, glibc 2.41
		$glibc241 = <<<'MAPS'
		55ce84200000-55ce84381000 r--p 00000000 00:51 451272                     /usr/local/bin/php
		55ce84400000-55ce84c01000 r-xp 00200000 00:51 451272                     /usr/local/bin/php
		55ce85e00000-55ce85ec7000 rw-p 01c00000 00:51 451272                     /usr/local/bin/php
		7f72f7e6d000-7f72f7e95000 r--p 00000000 00:51 135364                     /usr/lib/x86_64-linux-gnu/libc.so.6
		7f72f7e95000-7f72f7ff8000 r-xp 00028000 00:51 135364                     /usr/lib/x86_64-linux-gnu/libc.so.6
		7f72f8052000-7f72f8054000 rw-p 001e4000 00:51 135364                     /usr/lib/x86_64-linux-gnu/libc.so.6
		7f72f8e1d000-7f72f8e1e000 r--p 00000000 00:51 135344                     /usr/lib/x86_64-linux-gnu/ld-linux-x86-64.so.2
		7f72f8e1e000-7f72f8e46000 r-xp 00001000 00:51 135344                     /usr/lib/x86_64-linux-gnu/ld-linux-x86-64.so.2
		7f72f8e53000-7f72f8e54000 rw-p 00036000 00:51 135344                     /usr/lib/x86_64-linux-gnu/ld-linux-x86-64.so.2
		7ffca2df7000-7ffca2e18000 rw-p 00000000 00:00 0                          [stack]
		MAPS;
		yield 'glibc 2.41' => [$glibc241, '/usr/local/bin/php', true];

		// php:8.4-cli-alpine
		$musl = <<<'MAPS'
		56037fc00000-56037fd59000 r--p 00000000 00:51 584944                     /usr/local/bin/php
		56037fe00000-560380334000 r-xp 00200000 00:51 584944                     /usr/local/bin/php
		560381200000-5603812c4000 rw-p 01400000 00:51 584944                     /usr/local/bin/php
		7fcb84616000-7fcb8462a000 r--p 00000000 00:51 585480                     /lib/ld-musl-x86_64.so.1
		7fcb8462a000-7fcb84683000 r-xp 00014000 00:51 585480                     /lib/ld-musl-x86_64.so.1
		7fcb846ba000-7fcb846bb000 rw-p 000a3000 00:51 585480                     /lib/ld-musl-x86_64.so.1
		MAPS;
		yield 'musl' => [$musl, '/usr/local/bin/php', true];

		// php:8.3-cli-bullseye, glibc 2.31
		yield 'glibc 2.31' => [<<<'MAPS'
		55700b000000-55700b121000 r--p 00000000 00:51 515798                     /usr/local/bin/php
		55700b200000-55700b661000 r-xp 00200000 00:51 515798                     /usr/local/bin/php
		55700c600000-55700c607000 rw-p 01400000 00:51 515798                     /usr/local/bin/php
		7fcbc759b000-7fcbc75bd000 r--p 00000000 00:51 519011                     /lib/x86_64-linux-gnu/libc-2.31.so
		7fcbc75bd000-7fcbc7716000 r-xp 00022000 00:51 519011                     /lib/x86_64-linux-gnu/libc-2.31.so
		7fcbc7769000-7fcbc776b000 rw-p 001cd000 00:51 519011                     /lib/x86_64-linux-gnu/libc-2.31.so
		7fcbc810c000-7fcbc810d000 r--p 00000000 00:51 519018                     /lib/x86_64-linux-gnu/ld-2.31.so
		7fcbc810d000-7fcbc812d000 r-xp 00001000 00:51 519018                     /lib/x86_64-linux-gnu/ld-2.31.so
		7fcbc8137000-7fcbc8138000 rw-p 0002a000 00:51 519018                     /lib/x86_64-linux-gnu/ld-2.31.so
		MAPS, '/usr/local/bin/php', false];

		// Laravel Herd Lite PHP 8.5, fully static musl (its path renamed)
		$static = <<<'MAPS'
		00400000-0267b000 r-xp 00000000 fe:10 2678858                            /opt/herd-lite/bin/php
		02800000-05ec0000 r-xp 02400000 fe:10 2678858                            /opt/herd-lite/bin/php
		05ec0000-0621f000 rw-p 05ac0000 fe:10 2678858                            /opt/herd-lite/bin/php
		0621f000-06305000 rw-p 00000000 00:00 0
		43981000-43998000 rw-p 00000000 00:00 0                                  [heap]
		7f4406333000-7f4406334000 r--s 00000000 00:51 512498                     /usr/share/zoneinfo/Etc/UTC
		7f440638d000-7f440638f000 r-xp 00000000 00:00 0                          [vdso]
		7ffc3d860000-7ffc3d881000 rw-p 00000000 00:00 0                          [stack]
		ffffffffff600000-ffffffffff601000 --xp 00000000 00:00 0                  [vsyscall]
		MAPS;
		yield 'static' => [$static, '/opt/herd-lite/bin/php', false];
		yield 'static, executable unknown' => [$static, false, true];
		yield 'static, executable not in the maps' => [$static, '/usr/bin/php', true];

		// static-php-cli PHP 8.2 (its path renamed), fully static musl, UPX-packed
		$staticUpx = <<<'MAPS'
		00400000-00401000 r--p 00000000 00:00 0
		00401000-0164c000 r-xs 00000000 00:01 971628                             /memfd:upx (deleted)
		0164c000-0251f000 r--p 00000000 00:00 0
		0251f000-0274e000 rw-p 00000000 00:00 0
		7f417d3c0000-7f417d3c1000 r--s 00000000 00:32 137257                     /usr/share/zoneinfo/Etc/UTC
		7f417d604000-7f417d605000 r--p 00000000 fe:10 2627760                    /opt/spc/bin/php
		7f417d605000-7f417d606000 rw-p 00000000 00:00 0
		7f417d606000-7f417d60a000 r--p 00000000 00:00 0                          [vvar]
		7f417d60a000-7f417d60c000 r-xp 00000000 00:00 0                          [vdso]
		ffffffffff600000-ffffffffff601000 --xp 00000000 00:00 0                  [vsyscall]
		MAPS;
		yield 'static, UPX-packed' => [$staticUpx, '/opt/spc/bin/php', false];
		yield 'static, UPX-packed, executable unknown' => [$staticUpx, false, true];
		yield 'UPX-packed with a dynamic loader' => [
			$staticUpx . "\n7f417d700000-7f417d728000 r-xp 00001000 00:51 135360                     /usr/lib/x86_64-linux-gnu/ld-linux-x86-64.so.2",
			'/opt/spc/bin/php',
			true,
		];

		// the rest are edits of the captures above
		$withBinaryAt = static fn (string $maps, string $from, string $to): string => str_replace($from, $to, $maps);

		yield 'static under ld-musl- and ld-linux- directories' => [
			$withBinaryAt($static, '/opt/herd-lite/bin/php', '/opt/ld-musl-tools/ld-linux-x86-64/bin/php'),
			'/opt/ld-musl-tools/ld-linux-x86-64/bin/php',
			false,
		];
		yield 'static with another executable file mapped' => [
			$static . "\n7f4406300000-7f4406320000 r-xp 00000000 00:51 512499                     /opt/herd-lite/lib/preload.so",
			'/opt/herd-lite/bin/php',
			true,
		];
		yield 'static with a data file mapped from an x86_64 directory' => [
			$static . "\n7f4406300000-7f4406320000 r--p 00000000 00:51 512499                     /usr/lib/x86_64-linux-gnu/gconv/gconv-modules.cache",
			'/opt/herd-lite/bin/php',
			false,
		];
		yield 'static with the opcache JIT buffer' => [
			$static . "\n0e400000-12400000 r-xs 08000000 00:01 986386                             /dev/zero (deleted)",
			'/opt/herd-lite/bin/php',
			false,
		];
		yield 'static, binary replaced while running' => [
			$withBinaryAt($static, '/opt/herd-lite/bin/php', '/opt/herd-lite/bin/php (deleted)'),
			'/opt/herd-lite/bin/php (deleted)',
			false,
		];

		yield 'glibc 2.41, PHP in a directory named libc-2.x' => [
			$withBinaryAt($glibc241, '/usr/local/bin/php', '/opt/libc-2.project/bin/php'),
			'/opt/libc-2.project/bin/php',
			true,
		];
		yield 'glibc 2.41 with an unfamiliar loader name' => [
			$withBinaryAt($glibc241, '/usr/lib/x86_64-linux-gnu/ld-linux-x86-64.so.2', '/opt/rt/custom-loader.so'),
			'/usr/local/bin/php',
			true,
		];
		yield 'libc 2.31 with a loader that is otherwise accepted' => [
			$withBinaryAt($glibc241, '/usr/lib/x86_64-linux-gnu/libc.so.6', '/usr/lib/x86_64-linux-gnu/libc-2.31.so'),
			'/usr/local/bin/php',
			false,
		];
		yield 'libc 2.33' => [$withBinaryAt($glibc241, '/libc.so.6', '/libc-2.33.so'), '/usr/local/bin/php', false];
		yield 'libc 2.34' => [$withBinaryAt($glibc241, '/libc.so.6', '/libc-2.34.so'), '/usr/local/bin/php', true];
		yield 'libc 2.41' => [$withBinaryAt($glibc241, '/libc.so.6', '/libc-2.41.so'), '/usr/local/bin/php', true];
		yield 'libc 2.31 replaced while running' => [
			$withBinaryAt($glibc241, '/libc.so.6', '/libc-2.31.so (deleted)'),
			'/usr/local/bin/php',
			false,
		];
		yield 'glibc 2.41, libc and loader replaced while running' => [
			$withBinaryAt($withBinaryAt(
				$glibc241,
				'/usr/lib/x86_64-linux-gnu/libc.so.6',
				'/usr/lib/x86_64-linux-gnu/libc.so.6 (deleted)',
			), '/usr/lib/x86_64-linux-gnu/ld-linux-x86-64.so.2', '/usr/lib/x86_64-linux-gnu/ld-linux-x86-64.so.2 (deleted)'),
			'/usr/local/bin/php',
			true,
		];
		yield 'musl, runtime replaced while running' => [
			$withBinaryAt($musl, '/lib/ld-musl-x86_64.so.1', '/lib/ld-musl-x86_64.so.1 (deleted)'),
			'/usr/local/bin/php',
			true,
		];
		yield 'static with a deleted custom loader' => [
			$static . "\n7f4406300000-7f4406320000 r-xp 00000000 00:51 512499                     /opt/rt/custom-loader.so (deleted)",
			'/opt/herd-lite/bin/php',
			true,
		];
		yield 'static with an unfamiliar deleted memfd' => [
			$static . "\n7f4406300000-7f4406320000 r-xp 00000000 00:00 0                          /memfd:unknown-runtime (deleted)",
			'/opt/herd-lite/bin/php',
			true,
		];

		yield 'maps not readable' => [false, '/usr/local/bin/php', true];
		yield 'maps empty' => ['', '/usr/local/bin/php', true];
		yield 'maps not interpretable' => ["not\na maps file", '/usr/local/bin/php', true];
	}

	#[DataProvider('dataShouldTryLinuxBinaries')]
	public function testShouldTryLinuxBinaries(string|false $selfMaps, string|false $executable, bool $expected): void
	{
		$this->assertSame($expected, TurboExtensionSelector::shouldTryLinuxBinaries($selfMaps, $executable));
	}

	public function testFindExtensionDoesNotErrorWhenReadlinkIsDisabled(): void
	{
		if (PHP_OS_FAMILY !== 'Linux' || (bool) PHP_DEBUG) {
			$this->markTestSkipped('the readlink() call only runs on a non-debug Linux PHP');
		}

		$code = sprintf(
			'require %s; echo function_exists("readlink") ? "defined" : json_encode(\\PHPStan\\Turbo\\TurboExtensionSelector::findExtension("/tmp/nonexistent/phpstan.phar"));',
			var_export(__DIR__ . '/../../../src/Turbo/TurboExtensionSelector.php', true),
		);
		exec(sprintf(
			'%s -d disable_functions=readlink -r %s',
			escapeshellarg(PHP_BINARY),
			escapeshellarg($code),
		), $output, $exitCode);

		$this->assertSame(0, $exitCode, implode("\n", $output));
		$this->assertSame('null', implode('', $output));
	}

}
