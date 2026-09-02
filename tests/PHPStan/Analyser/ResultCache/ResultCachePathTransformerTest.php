<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ResultCache;

use PHPUnit\Framework\TestCase;
use function str_replace;
use const DIRECTORY_SEPARATOR;

class ResultCachePathTransformerTest extends TestCase
{

	/**
	 * ResultCacheManager compares the meta restored from the cache (absolutizeMeta() of what was stored
	 * with relativizeMeta()) against the freshly computed one with !==. The round trip normalizes, so
	 * the freshly computed side has to be normalized as well: normalizeMeta() must be the fixed point of
	 * the round trip, and it must map the shapes the meta sources hand over onto that fixed point.
	 */
	public function testNormalizeMetaIsTheRoundTripFixedPoint(): void
	{
		$transformer = new ResultCachePathTransformer(self::raw('/opt/project/vendor/phpstan/phpstan'));

		$raw = [
			'cacheVersion' => 'v14-relativePaths',
			// config paths through a %placeholder% keep the '.' and '..' segments they were written with
			'analysedPaths' => [
				self::raw('/opt/project/./src'),
				self::raw('/opt/project/app/../lib'),
			],
			'scannedFiles' => [
				self::raw('/opt/project/../project/vendor/autoload.php') => 'hash-autoload',
			],
			// composerAutoloaderProjectPaths . '/composer.lock': mixed separators on Windows
			'composerLocks' => [
				self::canonical('/opt/project') . '/composer.lock' => 'hash-lock',
			],
			'composerInstalled' => [
				self::canonical('/opt/project') . '/vendor/composer/installed.php' => [
					'versions' => [
						// Composer records install_path as __DIR__ . '/../acme/dep'
						'acme/dep' => [
							'version' => '1.0.0.0',
							'install_path' => self::canonical('/opt/project/vendor/composer') . '/../acme/dep',
						],
					],
				],
			],
			'executedFilesHashes' => [
				// --autoload-file ./vendor/autoload.php
				self::raw('/opt/project/./vendor/autoload.php') => 'hash-cli-autoload',
				// a bootstrapFile inside a phar, written through a placeholder
				'phar://' . self::raw('/opt/project/boot/../boot.phar/boot.php') => 'hash-boot',
			],
			'configStubFiles' => [
				self::raw('/opt/project/stubs/../stubs/Foo.stub'),
			],
			'stubFiles' => [
				self::raw('/opt/project/stubs/../stubs/Foo.stub') => 'hash-stub',
			],
			'level' => '8',
		];
		$canonical = [
			'cacheVersion' => 'v14-relativePaths',
			'analysedPaths' => [
				self::canonical('/opt/project/src'),
				self::canonical('/opt/project/lib'),
			],
			'scannedFiles' => [
				self::canonical('/opt/project/vendor/autoload.php') => 'hash-autoload',
			],
			'composerLocks' => [
				self::canonical('/opt/project/composer.lock') => 'hash-lock',
			],
			'composerInstalled' => [
				self::canonical('/opt/project/vendor/composer/installed.php') => [
					'versions' => [
						'acme/dep' => [
							'version' => '1.0.0.0',
							'install_path' => self::canonical('/opt/project/vendor/acme/dep'),
						],
					],
				],
			],
			'executedFilesHashes' => [
				self::canonical('/opt/project/vendor/autoload.php') => 'hash-cli-autoload',
				'phar://' . self::canonical('/opt/project/boot.phar/boot.php') => 'hash-boot',
			],
			'configStubFiles' => [
				self::canonical('/opt/project/stubs/Foo.stub'),
			],
			'stubFiles' => [
				self::canonical('/opt/project/stubs/Foo.stub') => 'hash-stub',
			],
			'level' => '8',
		];

		$this->assertSame($canonical, $transformer->normalizeMeta($raw));

		// the restored meta equals the normalized current one
		$this->assertSame($canonical, $transformer->absolutizeMeta($transformer->relativizeMeta($canonical)));

		// and the round trip is what canonicalizes: a cache written from the raw meta restores to the
		// same canonical form, so a cache from before the normalization is still reused
		$this->assertSame($canonical, $transformer->absolutizeMeta($transformer->relativizeMeta($raw)));
	}

	/**
	 * The path as its source spells it: '/'-separated, with a drive letter on Windows.
	 */
	private static function raw(string $unixPath): string
	{
		return DIRECTORY_SEPARATOR === '\\' ? 'C:' . $unixPath : $unixPath;
	}

	/**
	 * The path as FileHelper::normalizePath() spells it on the running platform.
	 */
	private static function canonical(string $unixPath): string
	{
		return DIRECTORY_SEPARATOR === '\\' ? 'C:' . str_replace('/', '\\', $unixPath) : $unixPath;
	}

}
