<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\FunctionDefinitionCheck;
use const PHP_VERSION_ID;

/**
 * @extends \PHPStan\Testing\RuleTestCase<ExistingClassesInTypehintsRule>
 */
class ExistingClassesInTypehintsRuleTest extends \PHPStan\Testing\RuleTestCase
{

	/** @var int */
	private $phpVersionId = PHP_VERSION_ID;

	protected function getRule(): \PHPStan\Rules\Rule
	{
		$broker = $this->createReflectionProvider();
		return new ExistingClassesInTypehintsRule(new FunctionDefinitionCheck($broker, new ClassCaseSensitivityCheck($broker), new PhpVersion($this->phpVersionId), true, false, true));
	}

	public function testExistingClassInTypehint(): void
	{
		require_once __DIR__ . '/data/typehints.php';
		$this->analyse([__DIR__ . '/data/typehints.php'], [
			[
				'Return typehint of function TestFunctionTypehints\foo() has invalid type TestFunctionTypehints\NonexistentClass.',
				15,
			],
			[
				'Parameter $bar of function TestFunctionTypehints\bar() has invalid typehint type TestFunctionTypehints\BarFunctionTypehints.',
				20,
			],
			[
				'Return typehint of function TestFunctionTypehints\returnParent() has invalid type TestFunctionTypehints\parent.',
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
				'Parameter $trait of function TestFunctionTypehints\referencesTraitsInNative() has invalid typehint type TestFunctionTypehints\SomeTrait.',
				61,
			],
			[
				'Return typehint of function TestFunctionTypehints\referencesTraitsInNative() has invalid type TestFunctionTypehints\SomeTrait.',
				61,
			],
			[
				'Parameter $trait of function TestFunctionTypehints\referencesTraitsInPhpDoc() has invalid typehint type TestFunctionTypehints\SomeTrait.',
				70,
			],
			[
				'Return typehint of function TestFunctionTypehints\referencesTraitsInPhpDoc() has invalid type TestFunctionTypehints\SomeTrait.',
				70,
			],
			[
				'Parameter $string of function TestFunctionTypehints\genericClassString() has invalid typehint type TestFunctionTypehints\SomeNonexistentClass.',
				78,
			],
			[
				'Parameter $string of function TestFunctionTypehints\genericTemplateClassString() has invalid typehint type TestFunctionTypehints\SomeNonexistentClass.',
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
				'Return typehint of function fooWithoutNamespace() has invalid type NonexistentClass.',
				13,
			],
			[
				'Parameter $bar of function barWithoutNamespace() has invalid typehint type BarFunctionTypehints.',
				18,
			],
			[
				'Return typehint of function returnParentWithoutNamespace() has invalid type parent.',
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
				'Parameter $trait of function referencesTraitsInNativeWithoutNamespace() has invalid typehint type SomeTraitWithoutNamespace.',
				59,
			],
			[
				'Return typehint of function referencesTraitsInNativeWithoutNamespace() has invalid type SomeTraitWithoutNamespace.',
				59,
			],
			[
				'Parameter $trait of function referencesTraitsInPhpDocWithoutNamespace() has invalid typehint type SomeTraitWithoutNamespace.',
				68,
			],
			[
				'Return typehint of function referencesTraitsInPhpDocWithoutNamespace() has invalid type SomeTraitWithoutNamespace.',
				68,
			],
		]);
	}

	public function testVoidParameterTypehint(): void
	{
		if (!self::$useStaticReflectionProvider) {
			$this->markTestSkipped('Test requires static reflection');
		}
		$this->analyse([__DIR__ . '/data/void-parameter-typehint.php'], [
			[
				'Parameter $param of function VoidParameterTypehint\doFoo() has invalid typehint type void.',
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
	 * @param int $phpVersionId
	 * @param mixed[] $errors
	 */
	public function testNativeUnionTypes(int $phpVersionId, array $errors): void
	{
		if (!self::$useStaticReflectionProvider && PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

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
	 * @param int $phpVersionId
	 * @param mixed[] $errors
	 */
	public function testRequiredParameterAfterOptional(int $phpVersionId, array $errors): void
	{
		$this->phpVersionId = $phpVersionId;
		$this->analyse([__DIR__ . '/data/required-parameter-after-optional.php'], $errors);
	}

}
