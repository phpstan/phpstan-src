<?php declare(strict_types = 1);

namespace PHPStan\Testing;

use LogicException;
use PHPUnit\Framework\AssertionFailedError;
use Throwable;
use function is_string;
use const PHP_VERSION_ID;

final class TypeInferenceTestCaseTest extends TypeInferenceTestCase
{

	public static function dataCheckOnlyIfAnnotation(): iterable
	{
		yield 'data-annotation-1' => [
			__DIR__ . '/data/data-annotation-1.php',
			(PHP_VERSION_ID >= 80100 && PHP_VERSION_ID < 80300),
		];
		yield 'data-annotation-2' => [
			__DIR__ . '/data/data-annotation-2.php',
			(PHP_VERSION_ID >= 80100 && PHP_VERSION_ID < 80300),
		];
		yield 'data-annotation-3' => [
			__DIR__ . '/data/data-annotation-3.php',
			LogicException::class,
		];
		yield 'data-annotation-4' => [
			__DIR__ . '/data/data-annotation-4.php',
			LogicException::class,
		];
	}

	/**
	 * @dataProvider dataCheckOnlyIfAnnotation
	 * @param bool|class-string<Throwable> $result
	 */
	public function testCheckOnlyIfAnnotation(string $file, $result): void
	{
		if (is_string($result)) {
			$this->expectException($result);
			$this->checkOnlyIfAnnotation($file);
		} else {
			$this->assertSame(parent::checkOnlyIfAnnotation($file), $result);
		}
	}

	public static function dataFileAssertionFailedErrors(): iterable
	{
		yield [
			__DIR__ . '/data/assert-certainty-missing-namespace.php',
			'Missing use statement for assertVariableCertainty() on line 8.',
		];
		yield [
			__DIR__ . '/data/assert-native-type-missing-namespace.php',
			'Missing use statement for assertNativeType() on line 6.',
		];
		yield [
			__DIR__ . '/data/assert-type-missing-namespace.php',
			'Missing use statement for assertType() on line 6.',
		];
		yield [
			__DIR__ . '/data/assert-certainty-wrong-namespace.php',
			'Function PHPStan\Testing\assertVariableCertainty imported with wrong namespace SomeWrong\Namespace\assertVariableCertainty called on line 9.',
		];
		yield [
			__DIR__ . '/data/assert-native-type-wrong-namespace.php',
			'Function PHPStan\Testing\assertNativeType imported with wrong namespace SomeWrong\Namespace\assertNativeType called on line 8.',
		];
		yield [
			__DIR__ . '/data/assert-type-wrong-namespace.php',
			'Function PHPStan\Testing\assertType imported with wrong namespace SomeWrong\Namespace\assertType called on line 8.',
		];
		yield [
			__DIR__ . '/data/assert-certainty-case-insensitive.php',
			'Missing use statement for assertvariablecertainty() on line 8.',
		];
		yield [
			__DIR__ . '/data/assert-native-type-case-insensitive.php',
			'Missing use statement for assertNATIVEType() on line 6.',
		];
		yield [
			__DIR__ . '/data/assert-type-case-insensitive.php',
			'Missing use statement for assertTYPe() on line 6.',
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
