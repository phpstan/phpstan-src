<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class InvalidIgnoredErrorExceptionTest extends PHPStanTestCase
{

	private static string $configFile;

	/**
	 * @return iterable<array{string, string}>
	 */
	public static function dataValidateIgnoreErrors(): iterable
	{
		yield [
			__DIR__ . '/invalidIgnoreErrors/message-and-messages.neon',
			'An ignoreErrors entry cannot contain both message and messages fields.',
		];
		yield [
			__DIR__ . '/invalidIgnoreErrors/identifier-and-identifiers.neon',
			'An ignoreErrors entry cannot contain both identifier and identifiers fields.',
		];
		yield [
			__DIR__ . '/invalidIgnoreErrors/path-and-paths.neon',
			'An ignoreErrors entry cannot contain both path and paths fields.',
		];
		yield [
			__DIR__ . '/invalidIgnoreErrors/missing-main-key.neon',
			'An ignoreErrors entry must contain at least one of the following fields: message, messages, identifier, identifiers, path, paths.',
		];
		yield [
			__DIR__ . '/invalidIgnoreErrors/count-without-path.neon',
			'An ignoreErrors entry with count field must also contain path field.',
		];
	}

	#[DataProvider('dataValidateIgnoreErrors')]
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
