<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class InvalidIgnoredErrorExceptionTest extends PHPStanTestCase
{

	private static ?string $configFile = null;

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
			__DIR__ . '/invalidIgnoreErrors/rawMessage-and-message.neon',
			'An ignoreErrors entry cannot contain both rawMessage and message fields.',
		];
		yield [
			__DIR__ . '/invalidIgnoreErrors/rawMessage-and-messages.neon',
			'An ignoreErrors entry cannot contain both rawMessage and messages fields.',
		];
		yield [
			__DIR__ . '/invalidIgnoreErrors/rawMessage-and-rawMessages.neon',
			'An ignoreErrors entry cannot contain both rawMessage and rawMessages fields.',
		];
		yield [
			__DIR__ . '/invalidIgnoreErrors/rawMessages-and-message.neon',
			'An ignoreErrors entry cannot contain both rawMessages and message fields.',
		];
		yield [
			__DIR__ . '/invalidIgnoreErrors/rawMessages-and-messages.neon',
			'An ignoreErrors entry cannot contain both rawMessages and messages fields.',
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
			'An ignoreErrors entry must contain at least one of the following fields: message, messages, rawMessage, rawMessages, identifier, identifiers, path, paths.',
		];
		yield [
			__DIR__ . '/invalidIgnoreErrors/count-without-path.neon',
			'An ignoreErrors entry with count field must also contain path field.',
		];
		yield [
			__DIR__ . '/invalidIgnoreErrors/path-with-array-value.neon',
			"Key 'path' of ignoreErrors expects a string, array given. Did you mean 'paths'?",
		];
		yield [
			__DIR__ . '/invalidIgnoreErrors/paths-with-string-value.neon',
			"Key 'paths' of ignoreErrors expects an array, string given. Did you mean 'path'?",
		];
	}

	#[DataProvider('dataValidateIgnoreErrors')]
	public function testValidateIgnoreErrors(string $file, string $expectedMessage): void
	{
		self::$configFile = $file;
		$this->expectExceptionMessage($expectedMessage);
		self::getContainer();
	}

	public function testBug12430(): void
	{
		self::$configFile = __DIR__ . '/invalidIgnoreErrors/baseline-nonexistent-path.neon';
		$this->expectNotToPerformAssertions();
		self::getContainer();
	}

	public static function getAdditionalConfigFiles(): array
	{
		$files = [
			__DIR__ . '/../../../conf/bleedingEdge.neon',
		];
		if (self::$configFile !== null) {
			$files[] = self::$configFile;
		}

		return $files;
	}

}
