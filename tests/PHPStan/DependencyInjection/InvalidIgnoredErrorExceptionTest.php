<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class InvalidIgnoredErrorExceptionTest extends PHPStanTestCase
{

	private static string $configFile;

	public static function dateValidateIgnoreErrors(): iterable
	{
		yield [
			__DIR__ . '/invalidIgnoreErrors/one.neon',
			'An ignoreErrors entry cannot contain both message and messages fields.',
		];
		yield [
			__DIR__ . '/invalidIgnoreErrors/two.neon',
			'An ignoreErrors entry cannot contain both path and paths fields.',
		];
	}

	#[DataProvider('dateValidateIgnoreErrors')]
	public function testValidateIgnoreErrors(string $file, string $expectedMessage): void
	{
		self::$configFile = $file;
		$this->expectExceptionMessage($expectedMessage);
		self::getContainer();
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/../../../conf/bleedingEdge.neon',
			self::$configFile,
		];
	}

}
