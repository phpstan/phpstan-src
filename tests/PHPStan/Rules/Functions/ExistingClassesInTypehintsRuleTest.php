<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassForbiddenNameCheck;
use PHPStan\Rules\ClassNameCheck;
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
		$reflectionProvider = $this->createReflectionProvider();
		return new ExistingClassesInTypehintsRule(
			new FunctionDefinitionCheck(
				$reflectionProvider,
				new ClassNameCheck(
					new ClassCaseSensitivityCheck($reflectionProvider, true),
					new ClassForbiddenNameCheck(),
				),
				new UnresolvableTypeHelper(),
				new PhpVersion($this->phpVersionId),
				true,
				false,
			),
		);
	}

	public function testExistingClassInTypehint(): void
	{
		require_once __DIR__ . '/data/typehints.php';
		$this->analyse([__DIR__ . '/data/typehints.php'], [
			[
				'Function TestFunctionTypehints\foo() has invalid return type TestFunctionTypehints\NonexistentClass.',
				15,
			],
			[
				'Parameter $bar of function TestFunctionTypehints\bar() has invalid type TestFunctionTypehints\BarFunctionTypehints.',
				20,
			],
			[
				'Function TestFunctionTypehints\returnParent() has invalid return type TestFunctionTypehints\parent.',
				33,
			],
			[
				'Class TestFunctionTypehints\FooFunctionTypehints referenced with incorrect case: TestFunctionTypehints\fOOFunctionTypehints.',
				38,
			],
			[
				'Class TestFunctionTypehints\FooFunctionTypehints referenced with incorrect case: TestFunctionTypehints\fOOFunctionTypehintS.',
				38,
			],
			[
				'Class TestFunctionTypehints\FooFunctionTypehints referenced with incorrect case: TestFunctionTypehints\FOOFunctionTypehints.',
				47,
			],
			[
				'Class TestFunctionTypehints\FooFunctionTypehints referenced with incorrect case: TestFunctionTypehints\FOOFunctionTypehints.',
				47,
			],
			[
				'Class TestFunctionTypehints\FooFunctionTypehints referenced with incorrect case: TestFunctionTypehints\FOOFunctionTypehints.',
				56,
			],
			[
				'Class TestFunctionTypehints\FooFunctionTypehints referenced with incorrect case: TestFunctionTypehints\FOOFunctionTypehints.',
				56,
			],
			[
				'Parameter $trait of function TestFunctionTypehints\referencesTraitsInNative() has invalid type TestFunctionTypehints\SomeTrait.',
				61,
			],
			[
				'Function TestFunctionTypehints\referencesTraitsInNative() has invalid return type TestFunctionTypehints\SomeTrait.',
				61,
			],
			[
				'Parameter $trait of function TestFunctionTypehints\referencesTraitsInPhpDoc() has invalid type TestFunctionTypehints\SomeTrait.',
				70,
			],
			[
				'Function TestFunctionTypehints\referencesTraitsInPhpDoc() has invalid return type TestFunctionTypehints\SomeTrait.',
				70,
			],
			[
				'Parameter $string of function TestFunctionTypehints\genericClassString() has invalid type TestFunctionTypehints\SomeNonexistentClass.',
				78,
			],
			[
				'Parameter $string of function TestFunctionTypehints\genericTemplateClassString() has invalid type TestFunctionTypehints\SomeNonexistentClass.',
				87,
			],
			[
				'Template type T of function TestFunctionTypehints\templateTypeMissingInParameter() is not referenced in a parameter.',
				96,
			],
		]);
	}

	public function testWithoutNamespace(): void
	{
		require_once __DIR__ . '/data/typehintsWithoutNamespace.php';
		$this->analyse([__DIR__ . '/data/typehintsWithoutNamespace.php'], [
			[
				'Function fooWithoutNamespace() has invalid return type NonexistentClass.',
				13,
			],
			[
				'Parameter $bar of function barWithoutNamespace() has invalid type BarFunctionTypehints.',
				18,
			],
			[
				'Function returnParentWithoutNamespace() has invalid return type parent.',
				31,
			],
			[
				'Class FooFunctionTypehints referenced with incorrect case: fOOFunctionTypehints.',
				36,
			],
			[
				'Class FooFunctionTypehints referenced with incorrect case: fOOFunctionTypehintS.',
				36,
			],
			[
				'Class FooFunctionTypehints referenced with incorrect case: FOOFunctionTypehints.',
				45,
			],
			[
				'Class FooFunctionTypehints referenced with incorrect case: FOOFunctionTypehints.',
				45,
			],
			[
				'Class FooFunctionTypehints referenced with incorrect case: FOOFunctionTypehints.',
				54,
			],
			[
				'Class FooFunctionTypehints referenced with incorrect case: FOOFunctionTypehints.',
				54,
			],
			[
				'Parameter $trait of function referencesTraitsInNativeWithoutNamespace() has invalid type SomeTraitWithoutNamespace.',
				59,
			],
			[
				'Function referencesTraitsInNativeWithoutNamespace() has invalid return type SomeTraitWithoutNamespace.',
				59,
			],
			[
				'Parameter $trait of function referencesTraitsInPhpDocWithoutNamespace() has invalid type SomeTraitWithoutNamespace.',
				68,
			],
			[
				'Function referencesTraitsInPhpDocWithoutNamespace() has invalid return type SomeTraitWithoutNamespace.',
				68,
			],
		]);
	}

	public function testVoidParameterTypehint(): void
	{
		$this->analyse([__DIR__ . '/data/void-parameter-typehint.php'], [
			[
				'Parameter $param of function VoidParameterTypehint\doFoo() has invalid type void.',
				9,
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
						'Function NativeUnionTypesSupport\foo() uses native union types but they\'re supported only on PHP 8.0 and later.',
						5,
					],
					[
						'Function NativeUnionTypesSupport\bar() uses native union types but they\'re supported only on PHP 8.0 and later.',
						10,
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
	 * @param list<array{0: string, 1: int, 2?: string}> $errors
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
						5,
					],
					[
						'Deprecated in PHP 8.0: Required parameter $bar follows optional parameter $foo.',
						14,
					],
					[
						'Deprecated in PHP 8.0: Required parameter $bar follows optional parameter $foo.',
						18,
					],
				],
			],
		];
	}

	/**
	 * @dataProvider dataRequiredParameterAfterOptional
	 * @param list<array{0: string, 1: int, 2?: string}> $errors
	 */
	public function testRequiredParameterAfterOptional(int $phpVersionId, array $errors): void
	{
		$this->phpVersionId = $phpVersionId;
		$this->analyse([__DIR__ . '/data/required-parameter-after-optional.php'], $errors);
	}

	public function dataIntersectionTypes(): array
	{
		return [
			[80000, []],
			[
				80100,
				[
					[
						'Parameter $a of function FunctionIntersectionTypes\doBar() has unresolvable native type.',
						30,
					],
					[
						'Function FunctionIntersectionTypes\doBar() has unresolvable native return type.',
						30,
					],
					[
						'Parameter $a of function FunctionIntersectionTypes\doBaz() has unresolvable native type.',
						35,
					],
					[
						'Function FunctionIntersectionTypes\doBaz() has unresolvable native return type.',
						35,
					],
				],
			],
		];
	}

	/**
	 * @dataProvider dataIntersectionTypes
	 * @param list<array{0: string, 1: int, 2?: string}> $errors
	 */
	public function testIntersectionTypes(int $phpVersion, array $errors): void
	{
		$this->phpVersionId = $phpVersion;

		$this->analyse([__DIR__ . '/data/intersection-types.php'], $errors);
	}

	public function dataTrueTypes(): array
	{
		return [
			[
				80200,
				[],
			],
		];
	}

	/**
	 * @dataProvider dataTrueTypes
	 * @param list<array{0: string, 1: int, 2?: string}> $errors
	 */
	public function testTrueTypehint(int $phpVersion, array $errors): void
	{
		$this->phpVersionId = $phpVersion;

		$this->analyse([__DIR__ . '/data/true-typehint.php'], $errors);
	}

	public function testConditionalReturnType(): void
	{
		$this->analyse([__DIR__ . '/data/conditional-return-type.php'], [
			[
				'Template type T of function FunctionConditionalReturnType\notGet() is not referenced in a parameter.',
				17,
			],
		]);
	}

	public function testTemplateInParamOut(): void
	{
		$this->analyse([__DIR__ . '/data/param-out.php'], [
			[
				'Template type S of function ParamOutTemplate\uselessGeneric() is not referenced in a parameter.',
				9,
			],
		]);
	}

}
