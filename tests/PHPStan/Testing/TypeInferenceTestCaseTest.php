<?php declare(strict_types = 1);

namespace PHPStan\Testing;

use PHPStan\File\FileHelper;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Attributes\DataProvider;
use function array_values;
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
			__DIR__ . '/data/assert-super-type-missing-namespace.php',
			sprintf(
				'Missing use statement for assertSuperType() in %s on line 6.',
				$fileHelper->normalizePath('tests/PHPStan/Testing/data/assert-super-type-missing-namespace.php'),
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
			__DIR__ . '/data/assert-super-type-wrong-namespace.php',
			sprintf(
				'Function PHPStan\Testing\assertSuperType imported with wrong namespace SomeWrong\Namespace\assertSuperType called in %s on line 8.',
				$fileHelper->normalizePath('tests/PHPStan/Testing/data/assert-super-type-wrong-namespace.php'),
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
		yield [
			__DIR__ . '/data/assert-super-type-case-insensitive.php',
			sprintf(
				'Missing use statement for assertSuperTYPe() in %s on line 6.',
				$fileHelper->normalizePath('tests/PHPStan/Testing/data/assert-super-type-case-insensitive.php'),
			),
		];
	}

	#[DataProvider('dataFileAssertionFailedErrors')]
	public function testFileAssertionFailedErrors(string $filePath, string $errorMessage): void
	{
		$this->expectException(AssertionFailedError::class);
		$this->expectExceptionMessage($errorMessage);

		self::gatherAssertTypes($filePath);
	}

	public function testVariableOrOffsetDescription(): void
	{
		$filePath = __DIR__ . '/data/assert-certainty-variable-or-offset.php';

		[$variableAssert, $offsetAssert] = array_values(self::gatherAssertTypes($filePath));

		$this->assertSame('variable $context', $variableAssert[4]);
		$this->assertSame("offset 'email'", $offsetAssert[4]);
	}

	public function testSuperType(): void
	{
		foreach (self::gatherAssertTypes(__DIR__ . '/data/assert-super-type.php') as $data) {
			$this->assertFileAsserts(...$data);
		}
	}

	public static function dataSuperTypeFailed(): array
	{
		return self::gatherAssertTypes(__DIR__ . '/data/assert-super-type-failed.php');
	}

	/**
	 * @param mixed ...$args
	 */
	#[DataProvider('dataSuperTypeFailed')]
	public function testSuperTypeFailed(...$args): void
	{
		$this->expectException(AssertionFailedError::class);
		$this->assertFileAsserts(...$args);
	}

	public function testNonexistentClassInAnalysedFile(): void
	{
		foreach (self::gatherAssertTypes(__DIR__ . '/../../notAutoloaded/nonexistentClasses.php') as $data) {
			$this->assertFileAsserts(...$data);
		}
	}

	public function testNonexistentClassInAnalysedFileWithError(): void
	{
		try {
			foreach (self::gatherAssertTypes(__DIR__ . '/../../notAutoloaded/nonexistentClasses-error.php') as $data) {
				$this->assertFileAsserts(...$data);
			}

			$this->fail('Should have failed');
		} catch (AssertionFailedError $e) {
			$this->assertStringContainsString('not found in ReflectionProvider', $e->getMessage());
		}
	}

}
