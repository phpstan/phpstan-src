<?php declare(strict_types = 1);

namespace PHPStan\Tests;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\AssertionFailedError;

final class TypeInferenceTestCaseTest extends TypeInferenceTestCase
{

	public function testMissingAssertCertaintyNamespace(): void
	{
		$this->expectException(AssertionFailedError::class);
		$this->expectExceptionMessage('ERROR: Missing import for assertVariableCertainty() on line 8.');

		$this->gatherAssertTypes(__DIR__ . '/data/assert-certainty-missing-namespace.php');
	}

	public function testMissingAssertNativeTypeNamespace(): void
	{
		$this->expectException(AssertionFailedError::class);
		$this->expectExceptionMessage('ERROR: Missing import for assertNativeType() on line 6.');

		$this->gatherAssertTypes(__DIR__ . '/data/assert-native-type-missing-namespace.php');
	}

	public function testMissingAssertTypeNamespace(): void
	{
		$this->expectException(AssertionFailedError::class);
		$this->expectExceptionMessage('ERROR: Missing import for assertType() on line 6.');

		$this->gatherAssertTypes(__DIR__ . '/data/assert-type-missing-namespace.php');
	}

	public function testWrongAssertCertaintyNamespace(): void
	{
		$this->expectException(AssertionFailedError::class);
		$this->expectExceptionMessage('ERROR: Assert-Method SomeWrong\Namespace\assertVariableCertainty imported with wrong namespace called from line 9.');

		$this->gatherAssertTypes(__DIR__ . '/data/assert-certainty-wrong-namespace.php');
	}

	public function testWrongAssertNativeTypeNamespace(): void
	{
		$this->expectException(AssertionFailedError::class);
		$this->expectExceptionMessage('ERROR: Assert-Method SomeWrong\Namespace\assertNativeType imported with wrong namespace called from line 8.');

		$this->gatherAssertTypes(__DIR__ . '/data/assert-native-type-wrong-namespace.php');
	}

	public function testWrongAssertTypeNamespace(): void
	{
		$this->expectException(AssertionFailedError::class);
		$this->expectExceptionMessage('ERROR: Assert-Method SomeWrong\Namespace\assertType imported with wrong namespace called from line 8.');

		$this->gatherAssertTypes(__DIR__ . '/data/assert-type-wrong-namespace.php');
	}

}
