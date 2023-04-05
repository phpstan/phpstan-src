<?php declare(strict_types = 1);

namespace PHPStan\Testing;

use PHPUnit\Framework\AssertionFailedError;

final class TypeInferenceTestCaseTest extends TypeInferenceTestCase
{

	public function testMissingAssertCertaintyNamespace(): void
	{
		$this->expectException(AssertionFailedError::class);
		$this->expectExceptionMessage('Missing use statement for assertVariableCertainty() on line 8.');

		$this->gatherAssertTypes(__DIR__ . '/data/assert-certainty-missing-namespace.php');
	}

	public function testMissingAssertNativeTypeNamespace(): void
	{
		$this->expectException(AssertionFailedError::class);
		$this->expectExceptionMessage('Missing use statement for assertNativeType() on line 6.');

		$this->gatherAssertTypes(__DIR__ . '/data/assert-native-type-missing-namespace.php');
	}

	public function testMissingAssertTypeNamespace(): void
	{
		$this->expectException(AssertionFailedError::class);
		$this->expectExceptionMessage('Missing use statement for assertType() on line 6.');

		$this->gatherAssertTypes(__DIR__ . '/data/assert-type-missing-namespace.php');
	}

	public function testWrongAssertCertaintyNamespace(): void
	{
		$this->expectException(AssertionFailedError::class);
		$this->expectExceptionMessage('Function PHPStan\Testing\assertVariableCertainty imported with wrong namespace SomeWrong\Namespace\assertVariableCertainty called on line 9.');

		$this->gatherAssertTypes(__DIR__ . '/data/assert-certainty-wrong-namespace.php');
	}

	public function testWrongAssertNativeTypeNamespace(): void
	{
		$this->expectException(AssertionFailedError::class);
		$this->expectExceptionMessage('Function PHPStan\Testing\assertNativeType imported with wrong namespace SomeWrong\Namespace\assertNativeType called on line 8.');

		$this->gatherAssertTypes(__DIR__ . '/data/assert-native-type-wrong-namespace.php');
	}

	public function testWrongAssertTypeNamespace(): void
	{
		$this->expectException(AssertionFailedError::class);
		$this->expectExceptionMessage('Function PHPStan\Testing\assertType imported with wrong namespace SomeWrong\Namespace\assertType called on line 8.');

		$this->gatherAssertTypes(__DIR__ . '/data/assert-type-wrong-namespace.php');
	}

	public function testMissingAssertCertaintyCaseSensitivity(): void
	{
		$this->expectException(AssertionFailedError::class);
		$this->expectExceptionMessage('Missing use statement for assertvariablecertainty() on line 8.');

		$this->gatherAssertTypes(__DIR__ . '/data/assert-certainty-case-insensitive.php');
	}

	public function testMissingAssertNativeTypeCaseSensitivity(): void
	{
		$this->expectException(AssertionFailedError::class);
		$this->expectExceptionMessage('Missing use statement for assertNATIVEType() on line 6.');

		$this->gatherAssertTypes(__DIR__ . '/data/assert-native-type-case-insensitive.php');
	}

	public function testMissingAssertTypeCaseSensitivity(): void
	{
		$this->expectException(AssertionFailedError::class);
		$this->expectExceptionMessage('Missing use statement for assertTYPe() on line 6.');

		$this->gatherAssertTypes(__DIR__ . '/data/assert-type-case-insensitive.php');
	}

}
