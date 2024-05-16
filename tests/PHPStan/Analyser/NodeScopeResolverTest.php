<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use EnumTypeAssertions\Foo;
use PHPStan\Testing\TypeInferenceTestCase;
use stdClass;
use function define;
use const PHP_INT_SIZE;
use const PHP_VERSION_ID;

class NodeScopeResolverTest extends TypeInferenceTestCase
{

	public function dataFileAsserts(): iterable
	{
		yield from $this->gatherAssertTypesFromDirectory(__DIR__ . '/nsrt');

		if (PHP_VERSION_ID < 80200 && PHP_VERSION_ID >= 80100) {
			yield from $this->gatherAssertTypes(__DIR__ . '/data/enum-reflection-php81.php');
		}

		if (PHP_VERSION_ID < 80000 && PHP_VERSION_ID >= 70400) {
			yield from $this->gatherAssertTypes(__DIR__ . '/data/bug-4902.php');
		}

		if (PHP_VERSION_ID < 80300) {
			if (PHP_VERSION_ID >= 80200) {
				yield from $this->gatherAssertTypes(__DIR__ . '/data/mb-strlen-php82.php');
			} elseif (PHP_VERSION_ID >= 80000) {
				yield from $this->gatherAssertTypes(__DIR__ . '/data/mb-strlen-php8.php');
			} elseif (PHP_VERSION_ID < 70300) {
				yield from $this->gatherAssertTypes(__DIR__ . '/data/mb-strlen-php72.php');
			} else {
				yield from $this->gatherAssertTypes(__DIR__ . '/data/mb-strlen-php73.php');
			}
		}

		yield from $this->gatherAssertTypes(__DIR__ . '/../Rules/Methods/data/bug-6856.php');

		if (PHP_VERSION_ID >= 80000) {
			yield from $this->gatherAssertTypes(__DIR__ . '/../Reflection/data/unionTypes.php');
			yield from $this->gatherAssertTypes(__DIR__ . '/../Reflection/data/mixedType.php');
			yield from $this->gatherAssertTypes(__DIR__ . '/../Reflection/data/staticReturnType.php');
		}

		if (PHP_INT_SIZE === 8) {
			yield from $this->gatherAssertTypes(__DIR__ . '/data/predefined-constants-64bit.php');
		} else {
			yield from $this->gatherAssertTypes(__DIR__ . '/data/predefined-constants-32bit.php');
		}

		yield from $this->gatherAssertTypes(__DIR__ . '/../Rules/Variables/data/bug-10577.php');
		yield from $this->gatherAssertTypes(__DIR__ . '/../Rules/Variables/data/bug-10610.php');
		yield from $this->gatherAssertTypes(__DIR__ . '/../Rules/Comparison/data/bug-2550.php');
		yield from $this->gatherAssertTypes(__DIR__ . '/../Rules/Properties/data/bug-3777.php');
		yield from $this->gatherAssertTypes(__DIR__ . '/../Rules/Methods/data/bug-4552.php');
		yield from $this->gatherAssertTypes(__DIR__ . '/../Rules/Methods/data/infer-array-key.php');
		yield from $this->gatherAssertTypes(__DIR__ . '/../Rules/Generics/data/bug-3769.php');
		yield from $this->gatherAssertTypes(__DIR__ . '/../Rules/Generics/data/bug-6301.php');
		yield from $this->gatherAssertTypes(__DIR__ . '/../Rules/PhpDoc/data/bug-4643.php');

		if (PHP_VERSION_ID >= 80000) {
			yield from $this->gatherAssertTypes(__DIR__ . '/../Rules/Comparison/data/bug-4857.php');
		}

		yield from $this->gatherAssertTypes(__DIR__ . '/../Rules/Methods/data/bug-5089.php');
		yield from $this->gatherAssertTypes(__DIR__ . '/../Rules/Methods/data/unable-to-resolve-callback-parameter-type.php');

		yield from $this->gatherAssertTypes(__DIR__ . '/../Rules/Functions/data/varying-acceptor.php');
		yield from $this->gatherAssertTypes(__DIR__ . '/../Rules/Methods/data/bug-4415.php');
		if (PHP_VERSION_ID >= 70400) {
			yield from $this->gatherAssertTypes(__DIR__ . '/../Rules/Methods/data/bug-5372.php');
		}
		yield from $this->gatherAssertTypes(__DIR__ . '/../Rules/Arrays/data/bug-5372_2.php');
		yield from $this->gatherAssertTypes(__DIR__ . '/../Rules/Methods/data/bug-5562.php');

		if (PHP_VERSION_ID >= 80100) {
			define('TEST_OBJECT_CONSTANT', new stdClass());
			define('TEST_NULL_CONSTANT', null);
			define('TEST_TRUE_CONSTANT', true);
			define('TEST_FALSE_CONSTANT', false);
			define('TEST_ARRAY_CONSTANT', [true, false, null]);
			define('TEST_ENUM_CONSTANT', Foo::ONE);
			yield from $this->gatherAssertTypes(__DIR__ . '/data/new-in-initializers-runtime.php');
		}

		if (PHP_VERSION_ID >= 70400) {
			yield from $this->gatherAssertTypes(__DIR__ . '/../Rules/Comparison/data/bug-6473.php');
		}

		yield from $this->gatherAssertTypes(__DIR__ . '/../Rules/Methods/data/filter-iterator-child-class.php');

		yield from $this->gatherAssertTypes(__DIR__ . '/../Rules/Methods/data/bug-5749.php');
		yield from $this->gatherAssertTypes(__DIR__ . '/../Rules/Methods/data/bug-5757.php');

		if (PHP_VERSION_ID >= 80000) {
			yield from $this->gatherAssertTypes(__DIR__ . '/../Rules/Methods/data/bug-6635.php');
		}

		if (PHP_VERSION_ID >= 80300) {
			yield from $this->gatherAssertTypes(__DIR__ . '/../Rules/Constants/data/bug-10212.php');
		}

		yield from $this->gatherAssertTypes(__DIR__ . '/../Rules/Methods/data/bug-3284.php');

		if (PHP_VERSION_ID >= 80300) {
			yield from $this->gatherAssertTypes(__DIR__ . '/../Rules/Methods/data/return-type-class-constant.php');
		}

		//define('ALREADY_DEFINED_CONSTANT', true);
		//yield from $this->gatherAssertTypes(__DIR__ . '/data/already-defined-constant.php');

		yield from $this->gatherAssertTypes(__DIR__ . '/../Rules/Methods/data/conditional-complex-templates.php');

		yield from $this->gatherAssertTypes(__DIR__ . '/../Rules/Methods/data/bug-7511.php');
		yield from $this->gatherAssertTypes(__DIR__ . '/../Rules/Comparison/data/bug-4708.php');
		yield from $this->gatherAssertTypes(__DIR__ . '/../Rules/Functions/data/bug-7156.php');
		yield from $this->gatherAssertTypes(__DIR__ . '/../Rules/Arrays/data/bug-6364.php');
		yield from $this->gatherAssertTypes(__DIR__ . '/../Rules/Arrays/data/bug-5758.php');
		yield from $this->gatherAssertTypes(__DIR__ . '/../Rules/Functions/data/bug-3931.php');
		yield from $this->gatherAssertTypes(__DIR__ . '/../Rules/Variables/data/bug-7417.php');
		yield from $this->gatherAssertTypes(__DIR__ . '/../Rules/Arrays/data/bug-7469.php');
		yield from $this->gatherAssertTypes(__DIR__ . '/../Rules/Variables/data/bug-3391.php');

		if (PHP_VERSION_ID >= 70400) {
			yield from $this->gatherAssertTypes(__DIR__ . '/../Rules/Functions/data/bug-anonymous-function-method-constant.php');
		}

		if (PHP_VERSION_ID >= 80200) {
			yield from $this->gatherAssertTypes(__DIR__ . '/../Rules/Methods/data/true-typehint.php');
		}
		yield from $this->gatherAssertTypes(__DIR__ . '/../Rules/Arrays/data/bug-6000.php');

		yield from $this->gatherAssertTypes(__DIR__ . '/../Rules/Arrays/data/slevomat-foreach-unset-bug.php');
		yield from $this->gatherAssertTypes(__DIR__ . '/../Rules/Arrays/data/slevomat-foreach-array-key-exists-bug.php');

		if (PHP_VERSION_ID >= 80000) {
			yield from $this->gatherAssertTypes(__DIR__ . '/../Rules/Comparison/data/bug-7898.php');
		}

		if (PHP_VERSION_ID >= 80000) {
			yield from $this->gatherAssertTypes(__DIR__ . '/../Rules/Functions/data/bug-7823.php');
		}

		yield from $this->gatherAssertTypes(__DIR__ . '/../Rules/Arrays/data/bug-7954.php');
		yield from $this->gatherAssertTypes(__DIR__ . '/../Rules/Comparison/data/docblock-assert-equality.php');
		yield from $this->gatherAssertTypes(__DIR__ . '/../Rules/Properties/data/bug-7839.php');
		yield from $this->gatherAssertTypes(__DIR__ . '/../Rules/Classes/data/bug-5333.php');
		yield from $this->gatherAssertTypes(__DIR__ . '/../Rules/Methods/data/bug-8174.php');
		yield from $this->gatherAssertTypes(__DIR__ . '/../Rules/Comparison/data/bug-8169.php');
		yield from $this->gatherAssertTypes(__DIR__ . '/../Rules/Functions/data/bug-8280.php');
		yield from $this->gatherAssertTypes(__DIR__ . '/../Rules/Comparison/data/bug-8277.php');
		yield from $this->gatherAssertTypes(__DIR__ . '/../Rules/Variables/data/bug-8113.php');
		yield from $this->gatherAssertTypes(__DIR__ . '/../Rules/Functions/data/bug-8389.php');
		yield from $this->gatherAssertTypes(__DIR__ . '/../Rules/Arrays/data/bug-8467a.php');
		if (PHP_VERSION_ID >= 80100) {
			yield from $this->gatherAssertTypes(__DIR__ . '/../Rules/Comparison/data/bug-8485.php');
		}

		if (PHP_VERSION_ID >= 80100) {
			yield from $this->gatherAssertTypes(__DIR__ . '/../Rules/Comparison/data/bug-9007.php');
		}

		yield from $this->gatherAssertTypes(__DIR__ . '/../Rules/DeadCode/data/bug-8620.php');

		if (PHP_VERSION_ID >= 80200) {
			yield from $this->gatherAssertTypes(__DIR__ . '/../Rules/Constants/data/bug-8957.php');
		}

		if (PHP_VERSION_ID >= 80100) {
			yield from $this->gatherAssertTypes(__DIR__ . '/../Rules/Comparison/data/bug-9499.php');
		}

		yield from $this->gatherAssertTypes(__DIR__ . '/../Rules/PhpDoc/data/bug-8609-function.php');
		yield from $this->gatherAssertTypes(__DIR__ . '/../Rules/Comparison/data/bug-5365.php');
		yield from $this->gatherAssertTypes(__DIR__ . '/../Rules/Comparison/data/bug-6551.php');
		yield from $this->gatherAssertTypes(__DIR__ . '/../Rules/Variables/data/bug-9403.php');
		yield from $this->gatherAssertTypes(__DIR__ . '/../Rules/Methods/data/bug-9542.php');
		yield from $this->gatherAssertTypes(__DIR__ . '/../Rules/Functions/data/bug-9803.php');
		yield from $this->gatherAssertTypes(__DIR__ . '/../Rules/PhpDoc/data/bug-10594.php');
		yield from $this->gatherAssertTypes(__DIR__ . '/data/set-type-type-specifying.php');
		yield from $this->gatherAssertTypes(__DIR__ . '/data/mysqli_fetch_object.php');
		yield from $this->gatherAssertTypes(__DIR__ . '/data/bug-10468.php');
		yield from $this->gatherAssertTypes(__DIR__ . '/data/bug-6613.php');
		yield from $this->gatherAssertTypes(__DIR__ . '/data/bug-10187.php');
		yield from $this->gatherAssertTypes(__DIR__ . '/data/bug-10834.php');
		yield from $this->gatherAssertTypes(__DIR__ . '/data/bug-10952.php');
		yield from $this->gatherAssertTypes(__DIR__ . '/data/bug-10952b.php');
		yield from $this->gatherAssertTypes(__DIR__ . '/data/case-insensitive-parent.php');
		
		if (PHP_VERSION_ID < 70400) {
			yield from $this->gatherAssertTypes(__DIR__ . '/data/preg_match_shapes_php73.php');
		} else {
			yield from $this->gatherAssertTypes(__DIR__ . '/data/preg_match_shapes.php');
		}
		if (PHP_VERSION_ID >= 80200) {
			yield from $this->gatherAssertTypes(__DIR__ . '/data/preg_match_shapes_php82.php');
		}

		yield from $this->gatherAssertTypes(__DIR__ . '/data/bug-10893.php');
	}

	/**
	 * @dataProvider dataFileAsserts
	 * @param mixed ...$args
	 */
	public function testFileAsserts(
		string $assertType,
		string $file,
		...$args,
	): void
	{
		$this->assertFileAsserts($assertType, $file, ...$args);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/../../../conf/bleedingEdge.neon',
			__DIR__ . '/typeAliases.neon',
		];
	}

}
