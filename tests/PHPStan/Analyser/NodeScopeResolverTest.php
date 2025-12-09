<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use EnumTypeAssertions\Foo;
use PHPStan\File\FileHelper;
use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;
use function array_merge;
use function define;
use function dirname;
use function implode;
use function sprintf;
use function str_starts_with;
use function strlen;
use function substr;
use const DIRECTORY_SEPARATOR;
use const PHP_INT_SIZE;
use const PHP_VERSION_ID;

class NodeScopeResolverTest extends TypeInferenceTestCase
{

	/**
	 * @return iterable<string>
	 */
	private static function findTestFiles(): iterable
	{
		foreach (self::findTestDataFilesFromDirectory(__DIR__ . '/nsrt') as $testFile) {
			yield $testFile;
		}

		if (PHP_VERSION_ID < 80200 && PHP_VERSION_ID >= 80100) {
			yield __DIR__ . '/data/enum-reflection-php81.php';
		}

		if (PHP_VERSION_ID >= 80100 && PHP_VERSION_ID < 80400) {
			yield __DIR__ . '/data/enum-reflection-backed.php';
		}

		if (PHP_VERSION_ID < 80000) {
			yield __DIR__ . '/data/bug-4902.php';
		}

		if (PHP_VERSION_ID < 80300) {
			if (PHP_VERSION_ID >= 80200) {
				yield __DIR__ . '/data/mb-strlen-php82.php';
			} elseif (PHP_VERSION_ID >= 80000) {
				yield __DIR__ . '/data/mb-strlen-php8.php';
			} else {
				yield __DIR__ . '/data/mb-strlen-php73.php';
			}
		}

		if (PHP_VERSION_ID >= 80200) {
			yield __DIR__ . '/data/str-split-php82.php';
		} elseif (PHP_VERSION_ID >= 80000) {
			yield __DIR__ . '/data/str-split-php80.php';
		} else {
			yield __DIR__ . '/data/str-split-php74.php';
		}
		if (PHP_VERSION_ID >= 80200) {
			yield __DIR__ . '/data/mb-str-split-php82.php';
		} elseif (PHP_VERSION_ID >= 80000) {
			yield __DIR__ . '/data/mb-str-split-php80.php';
		} elseif (PHP_VERSION_ID >= 74000) {
			yield __DIR__ . '/data/mb-str-split-php74.php';
		}

		yield __DIR__ . '/../Rules/Methods/data/bug-6856.php';
		yield __DIR__ . '/../Rules/Arrays/data/bug-12981.php';

		if (PHP_VERSION_ID < 80000) {
			yield __DIR__ . '/data/explode-php74.php';
		} else {
			yield __DIR__ . '/data/explode-php80.php';
		}

		if (PHP_VERSION_ID >= 80000) {
			yield __DIR__ . '/../Reflection/data/unionTypes.php';
			yield __DIR__ . '/../Reflection/data/mixedType.php';
			yield __DIR__ . '/../Reflection/data/staticReturnType.php';
		}

		if (PHP_INT_SIZE === 8) {
			yield __DIR__ . '/data/predefined-constants-64bit.php';
		} else {
			yield __DIR__ . '/data/predefined-constants-32bit.php';
		}

		yield __DIR__ . '/../Rules/Variables/data/bug-10577.php';
		yield __DIR__ . '/../Rules/Variables/data/bug-10610.php';
		yield __DIR__ . '/../Rules/Comparison/data/bug-2550.php';
		yield __DIR__ . '/../Rules/Comparison/data/bug-12412.php';
		yield __DIR__ . '/../Rules/Properties/data/bug-3777.php';
		yield __DIR__ . '/../Rules/Methods/data/bug-4552.php';
		yield __DIR__ . '/../Rules/Methods/data/infer-array-key.php';
		yield __DIR__ . '/../Rules/Generics/data/bug-3769.php';
		yield __DIR__ . '/../Rules/Generics/data/bug-6301.php';
		yield __DIR__ . '/../Rules/PhpDoc/data/bug-4643.php';
		yield __DIR__ . '/../Rules/Arrays/data/bug-13538.php';

		if (PHP_VERSION_ID >= 80000) {
			yield __DIR__ . '/../Rules/Comparison/data/bug-4857.php';
			yield __DIR__ . '/../Rules/PhpDoc/data/bug-13652.php';
		}

		yield __DIR__ . '/../Rules/Methods/data/bug-5089.php';
		yield __DIR__ . '/../Rules/Methods/data/unable-to-resolve-callback-parameter-type.php';

		yield __DIR__ . '/../Rules/Functions/data/varying-acceptor.php';
		yield __DIR__ . '/../Rules/Methods/data/bug-4415.php';
		yield __DIR__ . '/../Rules/Methods/data/bug-5372.php';
		yield __DIR__ . '/../Rules/Arrays/data/bug-5372_2.php';
		yield __DIR__ . '/../Rules/Methods/data/bug-5562.php';

		if (PHP_VERSION_ID >= 80100) {
			define('TEST_OBJECT_CONSTANT', new stdClass());
			define('TEST_NULL_CONSTANT', null);
			define('TEST_TRUE_CONSTANT', true);
			define('TEST_FALSE_CONSTANT', false);
			define('TEST_ARRAY_CONSTANT', [true, false, null]);
			define('TEST_ENUM_CONSTANT', Foo::ONE);
			yield __DIR__ . '/data/new-in-initializers-runtime.php';
			yield __DIR__ . '/data/scope-in-enum-match-arm-body.php';
		}

		yield __DIR__ . '/../Rules/Comparison/data/bug-6473.php';

		yield __DIR__ . '/../Rules/Methods/data/filter-iterator-child-class.php';

		yield __DIR__ . '/../Rules/Methods/data/bug-5749.php';
		yield __DIR__ . '/../Rules/Methods/data/bug-5757.php';

		if (PHP_VERSION_ID >= 80000) {
			yield __DIR__ . '/../Rules/Methods/data/bug-6635.php';
		}

		if (PHP_VERSION_ID >= 80300) {
			yield __DIR__ . '/../Rules/Constants/data/bug-10212.php';
		}

		yield __DIR__ . '/../Rules/Methods/data/bug-3284.php';

		if (PHP_VERSION_ID >= 80300) {
			yield __DIR__ . '/../Rules/Methods/data/return-type-class-constant.php';
		}

		//define('ALREADY_DEFINED_CONSTANT', true);
		//yield from self::gatherAssertTypes(__DIR__ . '/data/already-defined-constant.php');

		yield __DIR__ . '/../Rules/Methods/data/conditional-complex-templates.php';

		yield __DIR__ . '/../Rules/Methods/data/bug-7511.php';
		yield __DIR__ . '/../Rules/Properties/data/trait-mixin.php';
		yield __DIR__ . '/../Rules/Methods/data/trait-mixin.php';
		yield __DIR__ . '/../Rules/Comparison/data/bug-4708.php';
		yield __DIR__ . '/../Rules/Functions/data/bug-7156.php';
		yield __DIR__ . '/../Rules/Arrays/data/bug-6364.php';
		yield __DIR__ . '/../Rules/Arrays/data/bug-5758.php';
		yield __DIR__ . '/../Rules/Functions/data/bug-3931.php';
		yield __DIR__ . '/../Rules/Variables/data/bug-7417.php';
		yield __DIR__ . '/../Rules/Arrays/data/bug-7469.php';
		yield __DIR__ . '/../Rules/Variables/data/bug-3391.php';

		yield __DIR__ . '/../Rules/Functions/data/bug-anonymous-function-method-constant.php';

		if (PHP_VERSION_ID >= 80200) {
			yield __DIR__ . '/../Rules/Methods/data/true-typehint.php';
		}
		yield __DIR__ . '/../Rules/Arrays/data/bug-6000.php';

		yield __DIR__ . '/../Rules/Arrays/data/slevomat-foreach-unset-bug.php';
		yield __DIR__ . '/../Rules/Arrays/data/slevomat-foreach-array-key-exists-bug.php';

		yield __DIR__ . '/../Rules/Methods/data/inherit-phpdoc-return-type-with-narrower-native-return-type.php';

		if (PHP_VERSION_ID >= 80000) {
			yield __DIR__ . '/../Rules/Comparison/data/bug-7898.php';
		}

		if (PHP_VERSION_ID >= 80000) {
			yield __DIR__ . '/../Rules/Functions/data/bug-7823.php';
		}

		yield __DIR__ . '/../Analyser/data/is-resource-specified.php';

		yield __DIR__ . '/../Rules/Arrays/data/bug-7954.php';
		yield __DIR__ . '/../Rules/Comparison/data/docblock-assert-equality.php';
		yield __DIR__ . '/../Rules/Properties/data/bug-7839.php';
		yield __DIR__ . '/../Rules/Classes/data/bug-5333.php';
		yield __DIR__ . '/../Rules/Methods/data/bug-8174.php';

		yield __DIR__ . '/../Rules/Properties/data/bug-13537.php';

		if (PHP_VERSION_ID >= 80000) {
			yield __DIR__ . '/../Rules/Comparison/data/bug-8169.php';
		}
		yield __DIR__ . '/../Rules/Functions/data/bug-8280.php';
		yield __DIR__ . '/../Rules/Comparison/data/bug-8277.php';
		yield __DIR__ . '/../Rules/Variables/data/bug-8113.php';
		yield __DIR__ . '/../Rules/Functions/data/bug-8389.php';
		yield __DIR__ . '/../Rules/Arrays/data/bug-8467a.php';

		if (PHP_VERSION_ID >= 80100) {
			yield __DIR__ . '/../Rules/Comparison/data/bug-8485.php';
			yield __DIR__ . '/../Rules/PhpDoc/data/bug-11033.php';
		}

		if (PHP_VERSION_ID >= 80100) {
			yield __DIR__ . '/../Rules/Comparison/data/bug-9007.php';
		}

		yield __DIR__ . '/../Rules/DeadCode/data/bug-8620.php';

		if (PHP_VERSION_ID >= 80200) {
			yield __DIR__ . '/../Rules/Constants/data/bug-8957.php';
		}

		if (PHP_VERSION_ID >= 80100) {
			yield __DIR__ . '/../Rules/Comparison/data/bug-9499.php';
		}

		yield __DIR__ . '/../Rules/PhpDoc/data/bug-8609-function.php';
		yield __DIR__ . '/../Rules/Comparison/data/bug-5365.php';
		yield __DIR__ . '/../Rules/Comparison/data/bug-6551.php';
		yield __DIR__ . '/../Rules/Variables/data/bug-9403.php';
		yield __DIR__ . '/../Rules/Methods/data/bug-9542.php';
		yield __DIR__ . '/../Rules/Functions/data/bug-9803.php';
		yield __DIR__ . '/../Rules/PhpDoc/data/bug-10594.php';
		yield __DIR__ . '/../Rules/Classes/data/bug-11591.php';
		yield __DIR__ . '/../Rules/Classes/data/bug-11591-method-tag.php';
		yield __DIR__ . '/../Rules/Classes/data/bug-11591-property-tag.php';
		yield __DIR__ . '/../Rules/Classes/data/mixin-trait-use.php';

		yield __DIR__ . '/../Rules/Arrays/data/bug-11679.php';
		yield __DIR__ . '/../Rules/Methods/data/bug-4801.php';
		yield __DIR__ . '/../Rules/Arrays/data/narrow-superglobal.php';
		yield __DIR__ . '/../Rules/Methods/data/bug-12927.php';
	}

	/**
	 * @return iterable<string, array{string}>
	 */
	public static function dataFile(): iterable
	{
		$base = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR;
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

	#[DataProvider('dataFile')]
	public function testFile(string $file): void
	{
		$asserts = self::gatherAssertTypes($file);
		$this->assertNotCount(0, $asserts, sprintf('File %s has no asserts.', $file));
		$failures = [];

		foreach ($asserts as $args) {
			if ($args[0] === 'type') {
				$file = $args[1];

				$expected = $args[2];
				$actual = $args[3];

				if ($expected !== $actual) {
					$failures[] = sprintf("Line %d:\nExpected: %s\nActual:   %s\n", $args[4], (string) $expected, $actual);
				}
			} elseif ($args[0] === 'variableCertainty') {
				$file = $args[1];

				$expectedCertainty = $args[2];
				$actualCertainty = $args[3];
				$variableName = $args[4];

				if ($expectedCertainty->equals($actualCertainty) !== true) {
					$failures[] = sprintf("Certainty of %s on line %d:\nExpected: %s\nActual:   %s\n", $variableName, $args[5], $expectedCertainty->describe(), $actualCertainty->describe());
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
		return array_merge(
			parent::getAdditionalConfigFiles(),
			[
				__DIR__ . '/../../../conf/bleedingEdge.neon',
				__DIR__ . '/typeAliases.neon',
				__DIR__ . '/gnsr-extensions.neon',
			],
		);
	}

}
