<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ResultCache;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use function array_key_first;

class ResultCachePathTransformerTest extends TestCase
{

	/**
	 * @return iterable<string, array{string, string, string}>
	 */
	public static function dataRoundTripOnUnix(): iterable
	{
		$anchor = '/project/vendor/phpstan/phpstan-src';

		yield 'analysed path' => [$anchor, '/project/src', '../../../src'];
		yield 'composer.lock' => [$anchor, '/project/composer.lock', '../../../composer.lock'];
		// no shared prefix with the anchor, so it stays absolute
		yield 'path outside the anchor' => [$anchor, '/elsewhere/lib', '/elsewhere/lib'];
		yield 'phar bootstrap file' => [
			$anchor,
			'phar:///usr/local/bin/phpstan.phar/stubs/runtime/Attribute85.php',
			'phar:///usr/local/bin/phpstan.phar/stubs/runtime/Attribute85.php',
		];
		yield 'phar under the anchor' => [
			'/usr/local/bin',
			'phar:///usr/local/bin/phpstan.phar/stubs/runtime/Attribute85.php',
			'phar://phpstan.phar/stubs/runtime/Attribute85.php',
		];
	}

	#[DataProvider('dataRoundTripOnUnix')]
	public function testRoundTripOnUnix(string $anchorDirectory, string $path, string $expectedRelativePath): void
	{
		$transformer = new ResultCachePathTransformer($anchorDirectory, '/');
		$relativePath = $transformer->relativizePath($path);

		$this->assertSame($expectedRelativePath, $relativePath);
		$this->assertSame($path, $transformer->absolutizePath($relativePath));
	}

	/**
	 * The Windows behaviour is exercised with an injected directory separator so it is covered on
	 * every platform.
	 *
	 * @return iterable<string, array{string, string, string}>
	 */
	public static function dataRoundTripOnWindows(): iterable
	{
		// a phar install: rootDir - the anchor - is the directory the phar lives in
		$anchor = 'C:\var\php';

		yield 'analysed path' => [$anchor, 'C:\project\src', '../../project/src'];
		yield 'the phar itself' => [$anchor, 'C:\var\php\phpstan.phar', 'phpstan.phar'];
		yield 'path on another drive' => [$anchor, 'D:\other\lib', 'D:/other/lib'];
		yield 'phar bootstrap file' => [
			$anchor,
			'phar://C:/var/php/phpstan.phar/stubs/runtime/Attribute85.php',
			'phar://phpstan.phar/stubs/runtime/Attribute85.php',
		];
		yield 'phar bootstrap file outside the anchor' => [
			'C:\project\vendor\phpstan\phpstan-src',
			'phar://C:/var/php/phpstan.phar/stubs/runtime/Attribute85.php',
			'phar://../../../../var/php/phpstan.phar/stubs/runtime/Attribute85.php',
		];
	}

	#[DataProvider('dataRoundTripOnWindows')]
	public function testRoundTripOnWindows(string $anchorDirectory, string $path, string $expectedRelativePath): void
	{
		$transformer = new ResultCachePathTransformer($anchorDirectory, '\\');
		$relativePath = $transformer->relativizePath($path);

		$this->assertSame($expectedRelativePath, $relativePath);
		$this->assertSame($path, $transformer->absolutizePath($relativePath));
	}

	/**
	 * An anchor inside a phar never produces a relative entry (nothing shares a prefix with a
	 * `phar://` anchor), but a stored path still resolves against it without doubling the scheme.
	 */
	public function testAbsolutizePathAgainstAnchorWithScheme(): void
	{
		$transformer = new ResultCachePathTransformer('phar:///usr/local/bin/app.phar', '/');

		$this->assertSame(
			'phar:///usr/local/bin/app.phar/stubs/Foo.php',
			$transformer->absolutizePath('phar://stubs/Foo.php'),
		);
	}

	/**
	 * @return iterable<string, array{non-empty-string, string, string, string}>
	 */
	public static function dataNormalizeMeta(): iterable
	{
		yield 'composer.lock joined with a forward slash' => [
			'\\',
			'C:\var\php',
			'C:\project/composer.lock',
			'C:\project\composer.lock',
		];
		yield 'install_path with a dot segment' => [
			'/',
			'/project/vendor/phpstan/phpstan-src',
			'/project/vendor/composer/./pcre',
			'/project/vendor/composer/pcre',
		];
		yield 'install_path with a parent segment' => [
			'/',
			'/project/vendor/phpstan/phpstan-src',
			'/project/vendor/composer/../clue/ndjson-react',
			'/project/vendor/clue/ndjson-react',
		];
		yield 'phar path with the platform separator' => [
			'\\',
			'C:\var\php',
			'phar://C:\var\php\phpstan.phar\stubs\runtime\Attribute85.php',
			'phar://C:/var/php/phpstan.phar/stubs/runtime/Attribute85.php',
		];
	}

	/** @param non-empty-string $directorySeparator */
	#[DataProvider('dataNormalizeMeta')]
	public function testNormalizeMeta(string $directorySeparator, string $anchorDirectory, string $path, string $expectedNormalizedPath): void
	{
		$transformer = new ResultCachePathTransformer($anchorDirectory, $directorySeparator);

		$meta = $transformer->normalizeMeta([
			'analysedPaths' => [$path],
			'composerLocks' => [$path => 'hash'],
			'executedFilesHashes' => [$path => 'hash'],
			'composerInstalled' => [
				$path => [
					'versions' => [
						'foo/bar' => ['install_path' => $path, 'reference' => 'abcdef'],
					],
				],
			],
		]);

		$this->assertSame([$expectedNormalizedPath], $meta['analysedPaths']);
		$this->assertSame([$expectedNormalizedPath => 'hash'], $meta['composerLocks']);
		$this->assertSame([$expectedNormalizedPath => 'hash'], $meta['executedFilesHashes']);
		$this->assertSame($expectedNormalizedPath, array_key_first($meta['composerInstalled']));
		$this->assertSame($expectedNormalizedPath, $meta['composerInstalled'][$expectedNormalizedPath]['versions']['foo/bar']['install_path']);

		// what ResultCacheManager compares on the next run: the restored meta against the current one
		$this->assertSame($meta, $transformer->absolutizeMeta($transformer->relativizeMeta($meta)));
	}

}
