<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class InvalidSuperglobalTypesTest extends PHPStanTestCase
{

	private static ?string $configFile = null;

	/**
	 * @return iterable<array{string, string}>
	 */
	public static function dataValidateSuperglobalTypes(): iterable
	{
		$expected = static fn (string $key, string $given): string => "The key of item 'parameters\u{a0}›\u{a0}superglobalTypes\u{a0}›\u{a0}" . $key . "' expects to be 'GLOBALS'|'_SERVER'|'_GET'|'_POST'|'_FILES'|'_COOKIE'|'_SESSION'|'_REQUEST'|'_ENV', " . $given . ' given.';
		yield [
			__DIR__ . '/invalidSuperglobalTypes/typo.neon',
			$expected('_GTE', "'_GTE'"),
		];
		yield [
			__DIR__ . '/invalidSuperglobalTypes/dollar-sign.neon',
			$expected('$_GET', '\'$_GET\''),
		];
		yield [
			__DIR__ . '/invalidSuperglobalTypes/list.neon',
			$expected('0', '0'),
		];
	}

	#[DataProvider('dataValidateSuperglobalTypes')]
	public function testValidateSuperglobalTypes(string $file, string $expectedMessage): void
	{
		self::$configFile = $file;
		$this->expectExceptionMessage($expectedMessage);
		self::getContainer();
	}

	public static function getAdditionalConfigFiles(): array
	{
		$files = parent::getAdditionalConfigFiles();
		if (self::$configFile !== null) {
			$files[] = self::$configFile;
		}

		return $files;
	}

}
