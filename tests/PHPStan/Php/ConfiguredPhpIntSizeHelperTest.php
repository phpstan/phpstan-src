<?php declare(strict_types = 1);

namespace PHPStan\Php;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ConfiguredPhpIntSizeHelperTest extends TestCase
{

	public static function dataGetIntSize(): iterable
	{
		yield 'nothing configured' => [null, true, [], null];
		yield 'config wins' => [8, true, [], 8];

		yield 'composer requires php-64bit' => [null, true, [__DIR__ . '/data/composer-php-64bit'], 8];
		yield 'composer requires php-64bit only' => [null, true, [__DIR__ . '/data/composer-php-64bit-only'], 8];
		yield 'composer requires php without php-64bit' => [null, true, [__DIR__ . '/data/composer-php-only'], null];
		yield 'composer requires neither' => [null, true, [__DIR__ . '/data/composer-no-php-require'], null];
		yield 'no composer.json' => [null, true, [__DIR__ . '/data/does-not-exist'], null];

		yield 'config wins over composer' => [8, true, [__DIR__ . '/data/composer-php-only'], 8];

		// Reading composer.json is behind a feature toggle, setting phpIntSize is not.
		yield 'toggle off, composer requires php-64bit' => [null, false, [__DIR__ . '/data/composer-php-64bit'], null];
		yield 'toggle off, config set' => [8, false, [__DIR__ . '/data/composer-php-64bit'], 8];
	}

	/**
	 * @param 8|null $configPhpIntSize
	 * @param string[] $composerAutoloaderProjectPaths
	 * @param 8|null $expectedIntSize
	 */
	#[DataProvider('dataGetIntSize')]
	public function testGetIntSize(?int $configPhpIntSize, bool $composerPhp64Bit, array $composerAutoloaderProjectPaths, ?int $expectedIntSize): void
	{
		$helper = new ConfiguredPhpIntSizeHelper($configPhpIntSize, $composerPhp64Bit, $composerAutoloaderProjectPaths);

		$this->assertSame($expectedIntSize, $helper->getIntSize());
	}

}
