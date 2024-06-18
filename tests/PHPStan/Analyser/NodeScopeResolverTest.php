<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use EnumTypeAssertions\Foo;
use PHPStan\Testing\TypeInferenceTestCase;
use stdClass;
use function array_shift;
use function define;
use function sprintf;
use function version_compare;
use const PHP_INT_SIZE;
use const PHP_VERSION;
use const PHP_VERSION_ID;

class NodeScopeResolverTest extends TypeInferenceTestCase
{

	public function dataFileAsserts(): iterable
	{
		yield from $this->gatherAssertFilesFromDirectory(__DIR__, 'nsrt');

		if (PHP_VERSION_ID < 80200 && PHP_VERSION_ID >= 80100) {
			yield [__DIR__ . '/data/enum-reflection-php81.php'];
		}

		if (PHP_VERSION_ID < 80000 && PHP_VERSION_ID >= 70400) {
			yield [__DIR__ . '/data/bug-4902.php'];
		}

		if (PHP_VERSION_ID < 80300) {
			if (PHP_VERSION_ID >= 80200) {
				yield [__DIR__ . '/data/mb-strlen-php82.php'];
			} elseif (PHP_VERSION_ID >= 80000) {
				yield [__DIR__ . '/data/mb-strlen-php8.php'];
			} elseif (PHP_VERSION_ID < 70300) {
				yield [__DIR__ . '/data/mb-strlen-php72.php'];
			} else {
				yield [__DIR__ . '/data/mb-strlen-php73.php'];
			}
		}

		yield [__DIR__ . '/../Rules/Methods/data/bug-6856.php'];

		if (PHP_VERSION_ID >= 80000) {
			yield [__DIR__ . '/../Reflection/data/unionTypes.php'];
			yield [__DIR__ . '/../Reflection/data/mixedType.php'];
			yield [__DIR__ . '/../Reflection/data/staticReturnType.php'];
		}

		if (PHP_INT_SIZE === 8) {
			yield [__DIR__ . '/data/predefined-constants-64bit.php'];
		} else {
			yield [__DIR__ . '/data/predefined-constants-32bit.php'];
		}

		yield [__DIR__ . '/../Rules/Variables/data/bug-10577.php'];
		yield [__DIR__ . '/../Rules/Variables/data/bug-10610.php'];
		yield [__DIR__ . '/../Rules/Comparison/data/bug-2550.php'];
		yield [__DIR__ . '/../Rules/Properties/data/bug-3777.php'];
		yield [__DIR__ . '/../Rules/Methods/data/bug-4552.php'];
		yield [__DIR__ . '/../Rules/Methods/data/infer-array-key.php'];
		yield [__DIR__ . '/../Rules/Generics/data/bug-3769.php'];
		yield [__DIR__ . '/../Rules/Generics/data/bug-6301.php'];
		yield [__DIR__ . '/../Rules/PhpDoc/data/bug-4643.php'];

		if (PHP_VERSION_ID >= 80000) {
			yield [__DIR__ . '/../Rules/Comparison/data/bug-4857.php'];
		}

		yield [__DIR__ . '/../Rules/Methods/data/bug-5089.php'];
		yield [__DIR__ . '/../Rules/Methods/data/unable-to-resolve-callback-parameter-type.php'];

		yield [__DIR__ . '/../Rules/Functions/data/varying-acceptor.php'];
		yield [__DIR__ . '/../Rules/Methods/data/bug-4415.php'];
		if (PHP_VERSION_ID >= 70400) {
			yield [__DIR__ . '/../Rules/Methods/data/bug-5372.php'];
		}
		yield [__DIR__ . '/../Rules/Arrays/data/bug-5372_2.php'];
		yield [__DIR__ . '/../Rules/Methods/data/bug-5562.php'];

		if (PHP_VERSION_ID >= 80100) {
			define('TEST_OBJECT_CONSTANT', new stdClass());
			define('TEST_NULL_CONSTANT', null);
			define('TEST_TRUE_CONSTANT', true);
			define('TEST_FALSE_CONSTANT', false);
			define('TEST_ARRAY_CONSTANT', [true, false, null]);
			define('TEST_ENUM_CONSTANT', Foo::ONE);
			yield [__DIR__ . '/data/new-in-initializers-runtime.php'];
		}

		if (PHP_VERSION_ID >= 70400) {
			yield [__DIR__ . '/../Rules/Comparison/data/bug-6473.php'];
		}

		yield [__DIR__ . '/../Rules/Methods/data/filter-iterator-child-class.php'];

		yield [__DIR__ . '/../Rules/Methods/data/bug-5749.php'];
		yield [__DIR__ . '/../Rules/Methods/data/bug-5757.php'];

		if (PHP_VERSION_ID >= 80000) {
			yield [__DIR__ . '/../Rules/Methods/data/bug-6635.php'];
		}

		if (PHP_VERSION_ID >= 80300) {
			yield [__DIR__ . '/../Rules/Constants/data/bug-10212.php'];
		}

		yield [__DIR__ . '/../Rules/Methods/data/bug-3284.php'];

		if (PHP_VERSION_ID >= 80300) {
			yield [__DIR__ . '/../Rules/Methods/data/return-type-class-constant.php'];
		}

		//define('ALREADY_DEFINED_CONSTANT', true);
		//yield [__DIR__ . '/data/already-defined-constant.php'];

		yield [__DIR__ . '/../Rules/Methods/data/conditional-complex-templates.php'];

		yield [__DIR__ . '/../Rules/Methods/data/bug-7511.php'];
		yield [__DIR__ . '/../Rules/Comparison/data/bug-4708.php'];
		yield [__DIR__ . '/../Rules/Functions/data/bug-7156.php'];
		yield [__DIR__ . '/../Rules/Arrays/data/bug-6364.php'];
		yield [__DIR__ . '/../Rules/Arrays/data/bug-5758.php'];
		yield [__DIR__ . '/../Rules/Functions/data/bug-3931.php'];
		yield [__DIR__ . '/../Rules/Variables/data/bug-7417.php'];
		yield [__DIR__ . '/../Rules/Arrays/data/bug-7469.php'];
		yield [__DIR__ . '/../Rules/Variables/data/bug-3391.php'];

		if (PHP_VERSION_ID >= 70400) {
			yield [__DIR__ . '/../Rules/Functions/data/bug-anonymous-function-method-constant.php'];
		}

		if (PHP_VERSION_ID >= 80200) {
			yield [__DIR__ . '/../Rules/Methods/data/true-typehint.php'];
		}
		yield [__DIR__ . '/../Rules/Arrays/data/bug-6000.php'];

		yield [__DIR__ . '/../Rules/Arrays/data/slevomat-foreach-unset-bug.php'];
		yield [__DIR__ . '/../Rules/Arrays/data/slevomat-foreach-array-key-exists-bug.php'];

		if (PHP_VERSION_ID >= 80000) {
			yield [__DIR__ . '/../Rules/Comparison/data/bug-7898.php'];
		}

		if (PHP_VERSION_ID >= 80000) {
			yield [__DIR__ . '/../Rules/Functions/data/bug-7823.php'];
		}

		yield [__DIR__ . '/../Rules/Arrays/data/bug-7954.php'];
		yield [__DIR__ . '/../Rules/Comparison/data/docblock-assert-equality.php'];
		yield [__DIR__ . '/../Rules/Properties/data/bug-7839.php'];
		yield [__DIR__ . '/../Rules/Classes/data/bug-5333.php'];
		yield [__DIR__ . '/../Rules/Methods/data/bug-8174.php'];
		yield [__DIR__ . '/../Rules/Comparison/data/bug-8169.php'];
		yield [__DIR__ . '/../Rules/Functions/data/bug-8280.php'];
		yield [__DIR__ . '/../Rules/Comparison/data/bug-8277.php'];
		yield [__DIR__ . '/../Rules/Variables/data/bug-8113.php'];
		yield [__DIR__ . '/../Rules/Functions/data/bug-8389.php'];
		yield [__DIR__ . '/../Rules/Arrays/data/bug-8467a.php'];
		if (PHP_VERSION_ID >= 80100) {
			yield [__DIR__ . '/../Rules/Comparison/data/bug-8485.php'];
		}

		if (PHP_VERSION_ID >= 80100) {
			yield [__DIR__ . '/../Rules/Comparison/data/bug-9007.php'];
		}

		yield [__DIR__ . '/../Rules/DeadCode/data/bug-8620.php'];

		if (PHP_VERSION_ID >= 80200) {
			yield [__DIR__ . '/../Rules/Constants/data/bug-8957.php'];
		}

		if (PHP_VERSION_ID >= 80100) {
			yield [__DIR__ . '/../Rules/Comparison/data/bug-9499.php'];
		}

		yield [__DIR__ . '/../Rules/PhpDoc/data/bug-8609-function.php'];
		yield [__DIR__ . '/../Rules/Comparison/data/bug-5365.php'];
		yield [__DIR__ . '/../Rules/Comparison/data/bug-6551.php'];
		yield [__DIR__ . '/../Rules/Variables/data/bug-9403.php'];
		yield [__DIR__ . '/../Rules/Methods/data/bug-9542.php'];
		yield [__DIR__ . '/../Rules/Functions/data/bug-9803.php'];
		yield [__DIR__ . '/../Rules/PhpDoc/data/bug-10594.php'];
	}

	/**
	 * @dataProvider dataFileAsserts
	 * @param ?array{string, string} $requirement
	 */
	public function testFileAsserts(string $file, ?array $requirement = null): void
	{
		if ($requirement && version_compare(PHP_VERSION, $requirement[1], $requirement[0]) === false) {
			$this->markTestSkipped(sprintf('Requires php %s %s', $requirement[0], $requirement[1]));
		}

		foreach (self::gatherAssertTypes($file) as $assert) {
			$assertType = array_shift($assert);
			$file = array_shift($assert);
			$this->assertFileAsserts($assertType, $file, ...$assert);
		}
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/../../../conf/bleedingEdge.neon',
			__DIR__ . '/typeAliases.neon',
		];
	}

}
