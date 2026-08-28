<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ResultCache;

use Override;
use PHPUnit\Framework\TestCase;
use const DIRECTORY_SEPARATOR;

class ResultCachePathTransformerTest extends TestCase
{

	private function createTransformer(): ResultCachePathTransformer
	{
		return new ResultCachePathTransformer('/project/vendor/phpstan/phpstan');
	}

	#[Override]
	protected function setUp(): void
	{
		if (DIRECTORY_SEPARATOR === '/') {
			return;
		}

		self::markTestSkipped('Test runs only on Unix-like systems.');
	}

	public function testRoundTripCanonicalizesDotDotSegments(): void
	{
		$transformer = $this->createTransformer();
		$path = '/project/vendor/composer/../nette/neon/src/Neon.php';

		$this->assertSame(
			'/project/vendor/nette/neon/src/Neon.php',
			$transformer->absolutizePath($transformer->relativizePath($path)),
		);
	}

	/**
	 * Composer records install_path in vendor/composer/installed.php as `vendor/composer/../foo/bar`,
	 * so the meta the result cache stores is not normalized to begin with. Restoring it collapses those
	 * `..` segments, which is why ResultCacheManager::getMeta() puts the freshly computed meta through
	 * the very same transformation - otherwise the restored meta could never equal the computed one and
	 * the cache would be discarded on every run.
	 */
	public function testAbsolutizeMetaIsTheCanonicalFormOfTheRoundTrip(): void
	{
		$transformer = $this->createTransformer();
		$meta = [
			'analysedPaths' => ['/project/src/../src'],
			'scannedFiles' => ['/project/vendor/phpstan/phpstan/../../../vendor/autoload.php' => 'hash'],
			'executedFilesHashes' => ['/project/vendor/../vendor/autoload.php' => 'hash'],
			'composerLocks' => ['/project/./composer.lock' => 'hash'],
			'composerInstalled' => [
				'/project/vendor/composer/installed.php' => [
					'versions' => [
						'nette/neon' => ['install_path' => '/project/vendor/composer/../nette/neon'],
					],
				],
			],
		];

		$canonical = $transformer->absolutizeMeta($meta);

		$this->assertSame([
			'analysedPaths' => ['/project/src'],
			'scannedFiles' => ['/project/vendor/autoload.php' => 'hash'],
			'executedFilesHashes' => ['/project/vendor/autoload.php' => 'hash'],
			'composerLocks' => ['/project/composer.lock' => 'hash'],
			'composerInstalled' => [
				'/project/vendor/composer/installed.php' => [
					'versions' => [
						'nette/neon' => ['install_path' => '/project/vendor/nette/neon'],
					],
				],
			],
		], $canonical);

		$this->assertSame($canonical, $transformer->absolutizeMeta($transformer->relativizeMeta($meta)));
		$this->assertSame($canonical, $transformer->absolutizeMeta($canonical));
	}

}
