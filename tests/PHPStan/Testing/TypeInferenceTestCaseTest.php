<?php declare(strict_types = 1);

namespace PHPStan\Testing;

use PHPStan\File\FileHelper;
use PHPUnit\Framework\AssertionFailedError;
use function sprintf;

final class TypeInferenceTestCaseTest extends TypeInferenceTestCase
{

	public static function dataFileAssertionFailedErrors(): iterable
	{
		/** @var FileHelper $fileHelper */
		$fileHelper = self::getContainer()->getByType(FileHelper::class);

		yield [
			__DIR__ . '/data/assert-certainty-missing-namespace.php',
			sprintf(
				'Missing use statement for assertVariableCertainty() in %s on line 8.',
				$fileHelper->normalizePath('tests/PHPStan/Testing/data/assert-certainty-missing-namespace.php'),
			),
		];
		yield [
			__DIR__ . '/data/assert-native-type-missing-namespace.php',
			sprintf(
				'Missing use statement for assertNativeType() in %s on line 6.',
				$fileHelper->normalizePath('tests/PHPStan/Testing/data/assert-native-type-missing-namespace.php'),
			),
		];
		yield [
			__DIR__ . '/data/assert-type-missing-namespace.php',
			sprintf(
				'Missing use statement for assertType() in %s on line 6.',
				$fileHelper->normalizePath('tests/PHPStan/Testing/data/assert-type-missing-namespace.php'),
			),
		];
		yield [
			__DIR__ . '/data/assert-certainty-wrong-namespace.php',
			sprintf(
				'Function PHPStan\Testing\assertVariableCertainty imported with wrong namespace SomeWrong\Namespace\assertVariableCertainty called in %s on line 9.',
				$fileHelper->normalizePath('tests/PHPStan/Testing/data/assert-certainty-wrong-namespace.php'),
			),
		];
		yield [
			__DIR__ . '/data/assert-native-type-wrong-namespace.php',
			sprintf(
				'Function PHPStan\Testing\assertNativeType imported with wrong namespace SomeWrong\Namespace\assertNativeType called in %s on line 8.',
				$fileHelper->normalizePath('tests/PHPStan/Testing/data/assert-native-type-wrong-namespace.php'),
			),
		];
		yield [
			__DIR__ . '/data/assert-type-wrong-namespace.php',
			sprintf(
				'Function PHPStan\Testing\assertType imported with wrong namespace SomeWrong\Namespace\assertType called in %s on line 8.',
				$fileHelper->normalizePath('tests/PHPStan/Testing/data/assert-type-wrong-namespace.php'),
			),
		];
		yield [
			__DIR__ . '/data/assert-certainty-case-insensitive.php',
			sprintf(
				'Missing use statement for assertvariablecertainty() in %s on line 8.',
				$fileHelper->normalizePath('tests/PHPStan/Testing/data/assert-certainty-case-insensitive.php'),
			),
		];
		yield [
			__DIR__ . '/data/assert-native-type-case-insensitive.php',
			sprintf(
				'Missing use statement for assertNATIVEType() in %s on line 6.',
				$fileHelper->normalizePath('tests/PHPStan/Testing/data/assert-native-type-case-insensitive.php'),
			),
		];
		yield [
			__DIR__ . '/data/assert-type-case-insensitive.php',
			sprintf(
				'Missing use statement for assertTYPe() in %s on line 6.',
				$fileHelper->normalizePath('tests/PHPStan/Testing/data/assert-type-case-insensitive.php'),
			),
		];
	}

	/**
	 * @dataProvider dataFileAssertionFailedErrors
	 */
	public function testFileAssertionFailedErrors(string $filePath, string $errorMessage): void
	{
		$this->expectException(AssertionFailedError::class);
		$this->expectExceptionMessage($errorMessage);

		$this->gatherAssertTypes($filePath);
	}

}
