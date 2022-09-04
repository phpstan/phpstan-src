<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\FunctionDefinitionCheck;
use PHPStan\Rules\PhpDoc\UnresolvableTypeHelper;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<ExistingClassesInTypehintsRule>
 */
class ExistingClassesInTypehintsRuleTest extends RuleTestCase
{

	private int $phpVersionId = PHP_VERSION_ID;

	protected function getRule(): Rule
	{
		$broker = $this->createReflectionProvider();
		return new ExistingClassesInTypehintsRule(new FunctionDefinitionCheck($broker, new ClassCaseSensitivityCheck($broker, true), new UnresolvableTypeHelper(), new PhpVersion($this->phpVersionId), true, false));
	}

	public function testExistingClassInTypehint(): void
	{
		$this->analyse([__DIR__ . '/data/typehints.php'], [
			[
				'Method TestMethodTypehints\FooMethodTypehints::foo() has invalid return type TestMethodTypehints\NonexistentClass.',
				8,
			],
			[
				'Parameter $bar of method TestMethodTypehints\FooMethodTypehints::bar() has invalid type TestMethodTypehints\BarMethodTypehints.',
				13,
			],
			[
				'Parameter $bars of method TestMethodTypehints\FooMethodTypehints::lorem() has invalid type TestMethodTypehints\BarMethodTypehints.',
				28,
			],
			[
				'Method TestMethodTypehints\FooMethodTypehints::lorem() has invalid return type TestMethodTypehints\BazMethodTypehints.',
				28,
			],
			[
				'Parameter $bars of method TestMethodTypehints\FooMethodTypehints::ipsum() has invalid type TestMethodTypehints\BarMethodTypehints.',
				38,
			],
			[
				'Method TestMethodTypehints\FooMethodTypehints::ipsum() has invalid return type TestMethodTypehints\BazMethodTypehints.',
				38,
			],
			[
				'Parameter $bars of method TestMethodTypehints\FooMethodTypehints::dolor() has invalid type TestMethodTypehints\BarMethodTypehints.',
				48,
			],
			[
				'Method TestMethodTypehints\FooMethodTypehints::dolor() has invalid return type TestMethodTypehints\BazMethodTypehints.',
				48,
			],
			[
				'Parameter $parent of method TestMethodTypehints\FooMethodTypehints::parentWithoutParent() has invalid type parent.',
				53,
			],
			[
				'Method TestMethodTypehints\FooMethodTypehints::parentWithoutParent() has invalid return type parent.',
				53,
			],
			[
				'Parameter $parent of method TestMethodTypehints\FooMethodTypehints::phpDocParentWithoutParent() has invalid type parent.',
				62,
			],
			[
				'Method TestMethodTypehints\FooMethodTypehints::phpDocParentWithoutParent() has invalid return type parent.',
				62,
			],
			[
				'Class TestMethodTypehints\FooMethodTypehints referenced with incorrect case: TestMethodTypehints\fOOMethodTypehints.',
				67,
			],
			[
				'Class TestMethodTypehints\FooMethodTypehints referenced with incorrect case: TestMethodTypehints\fOOMethodTypehintS.',
				67,
			],
			[
				'Class TestMethodTypehints\FooMethodTypehints referenced with incorrect case: TestMethodTypehints\fOOMethodTypehints.',
				76,
			],
			[
				'Class stdClass referenced with incorrect case: STDClass.',
				76,
			],
			[
				'Class TestMethodTypehints\FooMethodTypehints referenced with incorrect case: TestMethodTypehints\fOOMethodTypehintS.',
				76,
			],
			[
				'Class stdClass referenced with incorrect case: stdclass.',
				76,
			],
			[
				'Class TestMethodTypehints\FooMethodTypehints referenced with incorrect case: TestMethodTypehints\FOOMethodTypehints.',
				85,
			],
			[
				'Class TestMethodTypehints\FooMethodTypehints referenced with incorrect case: TestMethodTypehints\FOOMethodTypehints.',
				85,
			],
			[
				'Class TestMethodTypehints\FooMethodTypehints referenced with incorrect case: TestMethodTypehints\FOOMethodTypehints.',
				94,
			],
			[
				'Class TestMethodTypehints\FooMethodTypehints referenced with incorrect case: TestMethodTypehints\FOOMethodTypehints.',
				94,
			],
			[
				'Parameter $array of method TestMethodTypehints\FooMethodTypehints::unknownTypesInArrays() has invalid type TestMethodTypehints\AnotherNonexistentClass.',
				102,
			],
			[
				'Parameter $cb of method TestMethodTypehints\CallableTypehints::doFoo() has invalid type TestMethodTypehints\Bla.',
				113,
			],
			[
				'Parameter $cb of method TestMethodTypehints\CallableTypehints::doFoo() has invalid type TestMethodTypehints\Ble.',
				113,
			],
			[
				'Template type U of method TestMethodTypehints\TemplateTypeMissingInParameter::doFoo() is not referenced in a parameter.',
				130,
			],
		]);
	}

	public function testExistingClassInIterableTypehint(): void
	{
		$this->analyse([__DIR__ . '/data/typehints-iterable.php'], [
			[
				'Parameter $iterable of method TestMethodTypehints\IterableTypehints::doFoo() has invalid type TestMethodTypehints\NonexistentClass.',
				11,
			],
			[
				'Parameter $iterable of method TestMethodTypehints\IterableTypehints::doFoo() has invalid type TestMethodTypehints\AnotherNonexistentClass.',
				11,
			],
		]);
	}

	public function testVoidParameterTypehint(): void
	{
		$this->analyse([__DIR__ . '/data/void-parameter-typehint.php'], [
			[
				'Parameter $param of method VoidParameterTypehintMethod\Foo::doFoo() has invalid type void.',
				8,
			],
		]);
	}

	public function dataNativeUnionTypes(): array
	{
		return [
			[
				70400,
				[
					[
						'Method NativeUnionTypesSupport\Foo::doFoo() uses native union types but they\'re supported only on PHP 8.0 and later.',
						8,
					],
					[
						'Method NativeUnionTypesSupport\Foo::doBar() uses native union types but they\'re supported only on PHP 8.0 and later.',
						13,
					],
				],
			],
			[
				80000,
				[],
			],
		];
	}

	/**
	 * @dataProvider dataNativeUnionTypes
	 * @param mixed[] $errors
	 */
	public function testNativeUnionTypes(int $phpVersionId, array $errors): void
	{
		$this->phpVersionId = $phpVersionId;
		$this->analyse([__DIR__ . '/data/native-union-types.php'], $errors);
	}

	public function dataRequiredParameterAfterOptional(): array
	{
		return [
			[
				70400,
				[],
			],
			[
				80000,
				[
					[
						'Deprecated in PHP 8.0: Required parameter $bar follows optional parameter $foo.',
						8,
					],
					[
						'Deprecated in PHP 8.0: Required parameter $bar follows optional parameter $foo.',
						17,
					],
					[
						'Deprecated in PHP 8.0: Required parameter $bar follows optional parameter $foo.',
						21,
					],
				],
			],
		];
	}

	/**
	 * @dataProvider dataRequiredParameterAfterOptional
	 * @param mixed[] $errors
	 */
	public function testRequiredParameterAfterOptional(int $phpVersionId, array $errors): void
	{
		$this->phpVersionId = $phpVersionId;
		$this->analyse([__DIR__ . '/data/required-parameter-after-optional.php'], $errors);
	}

	public function testBug4641(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4641.php'], [
			[
				'Template type U of method Bug4641\I::getRepository() is not referenced in a parameter.',
				26,
			],
		]);
	}

	public function dataIntersectionTypes(): array
	{
		return [
			[80000, []],
			[
				80100,
				[
					[
						'Parameter $a of method MethodIntersectionTypes\Foo::doBar() has unresolvable native type.',
						33,
					],
					[
						'Method MethodIntersectionTypes\Foo::doBar() has unresolvable native return type.',
						33,
					],
					[
						'Parameter $a of method MethodIntersectionTypes\Foo::doBaz() has unresolvable native type.',
						38,
					],
					[
						'Method MethodIntersectionTypes\Foo::doBaz() has unresolvable native return type.',
						38,
					],
				],
			],
		];
	}

	/**
	 * @dataProvider dataIntersectionTypes
	 * @param mixed[] $errors
	 */
	public function testIntersectionTypes(int $phpVersion, array $errors): void
	{
		$this->phpVersionId = $phpVersion;

		$this->analyse([__DIR__ . '/data/intersection-types.php'], $errors);
	}

	public function testEnums(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('This test needs PHP 8.1');
		}

		$this->analyse([__DIR__ . '/data/enums-typehints.php'], [
			[
				'Parameter $int of method EnumsTypehints\Foo::doFoo() has invalid type EnumsTypehints\intt.',
				8,
			],
		]);
	}

	public function dataTrueTypes(): array
	{
		return [
			[80200, []],
			[
				80100,
				[
					[
						'Parameter $v of method NativeTrueType\Truthy::foo() has invalid type NativeTrueType\true.',
						8,
					],
					[
						'Method NativeTrueType\Truthy::foo() has invalid return type NativeTrueType\true.',
						8,
					],
				],
			],
		];
	}

	/**
	 * @dataProvider dataTrueTypes
	 * @param mixed[] $errors
	 */
	public function testTrueTypehint(int $phpVersion, array $errors): void
	{
		$this->phpVersionId = $phpVersion;

		$this->analyse([__DIR__ . '/data/true-typehint.php'], $errors);
	}

}
