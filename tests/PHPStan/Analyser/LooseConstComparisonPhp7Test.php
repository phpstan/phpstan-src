<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class LooseConstComparisonPhp7Test extends TypeInferenceTestCase
{

	public static function dataFileAsserts(): iterable
	{
		// compares constants according to the php-version phpstan configuration,
		// _NOT_ the current php runtime version
		yield from self::gatherAssertTypes(__DIR__ . '/data/loose-const-comparison-php7.php');
		yield from self::gatherAssertTypes(__DIR__ . '/data/bug-11732-php7.php');
		yield from self::gatherAssertTypes(__DIR__ . '/data/loose-equality-narrowing-php7.php');
		yield from self::gatherAssertTypes(__DIR__ . '/data/bug-15667-php7.php');
	}

	#[DataProvider('dataFileAsserts')]
	public function testFileAsserts(
		string $assertType,
		string $file,
		mixed ...$args,
	): void
	{
		$this->assertFileAsserts($assertType, $file, ...$args);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/nodeScopeResolverPhp7.neon',
		];
	}

}
