<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use EnumTypeAssertions\Foo;
use PHPStan\File\FileHelper;
use PHPStan\Testing\TypeInferenceTestCase;
use stdClass;
use function array_shift;
use function define;
use function dirname;
use function implode;
use function sprintf;
use function str_starts_with;
use function strlen;
use function substr;
use const PHP_INT_SIZE;
use const PHP_VERSION_ID;

class NodeScopeResolverTest extends TypeInferenceTestCase
{

	/**
	 * @return iterable<string>
	 */
	private static function findTestFiles(): iterable
	{
        yield __DIR__ . '/nsrt/static-with-this-chained.php';

		if (PHP_VERSION_ID >= 80000) {
			yield __DIR__ . '/../Reflection/data/unionTypes.php';
			yield __DIR__ . '/../Reflection/data/mixedType.php';
			yield __DIR__ . '/../Reflection/data/staticReturnType.php';
		}
	}

	/**
	 * @return iterable<string, array{string}>
	 */
	public static function dataFile(): iterable
	{
		$base = dirname(__DIR__, 3) . '/';
		$baseLength = strlen($base);

		$fileHelper = new FileHelper($base);
		foreach (self::findTestFiles() as $file) {
			$file = $fileHelper->normalizePath($file);

			$testName = $file;
			if (str_starts_with($file, $base)) {
				$testName = substr($file, $baseLength);
			}

			yield $testName => [$file];
		}
	}

	/**
	 * @dataProvider dataFile
	 */
	public function testFile(string $file): void
	{
		$asserts = $this->gatherAssertTypes($file);
		$this->assertNotCount(0, $asserts, sprintf('File %s has no asserts.', $file));
		$failures = [];

		foreach ($asserts as $args) {
			$assertType = array_shift($args);
			$file = array_shift($args);

			if ($assertType === 'type') {
				$expected = $args[0];
				$actual = $args[1];

				if ($expected !== $actual) {
					$failures[] = sprintf("Line %d:\nExpected: %s\nActual:   %s\n", $args[2], $expected, $actual);
				}
			} elseif ($assertType === 'variableCertainty') {
				$expectedCertainty = $args[0];
				$actualCertainty = $args[1];
				$variableName = $args[2];

				if ($expectedCertainty->equals($actualCertainty) !== true) {
					$failures[] = sprintf("Certainty of %s on line %d:\nExpected: %s\nActual:   %s\n", $variableName, $args[3], $expectedCertainty->describe(), $actualCertainty->describe());
				}
			}
		}

		if ($failures === []) {
			return;
		}

		self::fail(sprintf("Failed assertions in %s:\n\n%s", $file, implode("\n", $failures)));
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/../../../conf/bleedingEdge.neon',
			__DIR__ . '/typeAliases.neon',
		];
	}

}
