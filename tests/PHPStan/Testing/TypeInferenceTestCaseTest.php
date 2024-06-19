<?php declare(strict_types = 1);

namespace PHPStan\Testing;

use PHPUnit\Framework\AssertionFailedError;

final class TypeInferenceTestCaseTest extends TypeInferenceTestCase
{

	public static function dataFileAssertionFailedErrors(): iterable
	{
		yield [
			__DIR__ . '/data/assert-certainty-missing-namespace.php',
			'Missing use statement for assertVariableCertainty() in tests/PHPStan/Testing/data/assert-certainty-missing-namespace.php on line 8.',
		];
		yield [
			__DIR__ . '/data/assert-native-type-missing-namespace.php',
			'Missing use statement for assertNativeType() in tests/PHPStan/Testing/data/assert-native-type-missing-namespace.php on line 6.',
		];
		yield [
			__DIR__ . '/data/assert-type-missing-namespace.php',
			'Missing use statement for assertType() in tests/PHPStan/Testing/data/assert-type-missing-namespace.php on line 6.',
		];
		yield [
			__DIR__ . '/data/assert-certainty-wrong-namespace.php',
			'Function PHPStan\Testing\assertVariableCertainty imported with wrong namespace SomeWrong\Namespace\assertVariableCertainty called in tests/PHPStan/Testing/data/assert-certainty-wrong-namespace.php on line 9.',
		];
		yield [
			__DIR__ . '/data/assert-native-type-wrong-namespace.php',
			'Function PHPStan\Testing\assertNativeType imported with wrong namespace SomeWrong\Namespace\assertNativeType called in tests/PHPStan/Testing/data/assert-native-type-wrong-namespace.php on line 8.',
		];
		yield [
			__DIR__ . '/data/assert-type-wrong-namespace.php',
			'Function PHPStan\Testing\assertType imported with wrong namespace SomeWrong\Namespace\assertType called in tests/PHPStan/Testing/data/assert-type-wrong-namespace.php on line 8.',
		];
		yield [
			__DIR__ . '/data/assert-certainty-case-insensitive.php',
			'Missing use statement for assertvariablecertainty() in tests/PHPStan/Testing/data/assert-certainty-case-insensitive.php on line 8.',
		];
		yield [
			__DIR__ . '/data/assert-native-type-case-insensitive.php',
			'Missing use statement for assertNATIVEType() in tests/PHPStan/Testing/data/assert-native-type-case-insensitive.php on line 6.',
		];
		yield [
			__DIR__ . '/data/assert-type-case-insensitive.php',
			'Missing use statement for assertTYPe() in tests/PHPStan/Testing/data/assert-type-case-insensitive.php on line 6.',
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
