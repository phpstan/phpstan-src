<?php declare(strict_types = 1);

namespace PHPStan\Php;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ComposerPhpVersionFactoryTest extends TestCase
{

	public static function dataMinVersion(): iterable
	{
		yield 'no composer.json' => [[], true, null];
		yield 'require php' => [[__DIR__ . '/data/composer-php-only'], true, 80100];

		// php-64bit is versioned with the PHP version, so it constrains it the same way php does
		yield 'require php-64bit only' => [[__DIR__ . '/data/composer-php-64bit-only'], true, 80100];

		// "php-64bit": "*" carries no constraint, the one on php is the one that counts
		yield 'require both' => [[__DIR__ . '/data/composer-php-64bit'], true, 80100];

		// both requirements have to hold at once, so the narrower one wins
		yield 'require both, php-64bit narrower' => [[__DIR__ . '/data/composer-php-64bit-narrower'], true, 80300];

		yield 'require neither' => [[__DIR__ . '/data/composer-no-php-require'], true, null];

		// Without the feature toggle, php-64bit is not read at all.
		yield 'toggle off, require php-64bit only' => [[__DIR__ . '/data/composer-php-64bit-only'], false, null];
		yield 'toggle off, php-64bit narrower' => [[__DIR__ . '/data/composer-php-64bit-narrower'], false, 80100];
	}

	/**
	 * @param string[] $composerAutoloaderProjectPaths
	 */
	#[DataProvider('dataMinVersion')]
	public function testMinVersion(array $composerAutoloaderProjectPaths, bool $composerPhp64Bit, ?int $expectedMinVersionId): void
	{
		$factory = new ComposerPhpVersionFactory($composerAutoloaderProjectPaths, $composerPhp64Bit);
		$minVersion = $factory->getMinVersion();

		$this->assertSame($expectedMinVersionId, $minVersion !== null ? $minVersion->getVersionId() : null);
	}

}
