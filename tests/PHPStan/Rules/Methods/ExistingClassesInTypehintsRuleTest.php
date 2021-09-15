<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

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
		if (!self::$useStaticReflectionProvider && PHP_VERSION_ID >= 70400) {
			$this->markTestSkipped('Test does not run on PHP 7.4 because of referencing parent:: without parent class.');
		}
		$this->analyse([__DIR__ . '/data/typehints.php'], [
			[
				'Return typehint of method TestMethodTypehints\FooMethodTypehints::foo() has invalid type TestMethodTypehints\NonexistentClass.',
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
				'Return typehint of method TestMethodTypehints\FooMethodTypehints::lorem() has invalid type TestMethodTypehints\BazMethodTypehints.',
				28,
			],
			[
				'Parameter $bars of method TestMethodTypehints\FooMethodTypehints::ipsum() has invalid type TestMethodTypehints\BarMethodTypehints.',
				38,
			],
			[
				'Return typehint of method TestMethodTypehints\FooMethodTypehints::ipsum() has invalid type TestMethodTypehints\BazMethodTypehints.',
				38,
			],
			[
				'Parameter $bars of method TestMethodTypehints\FooMethodTypehints::dolor() has invalid type TestMethodTypehints\BarMethodTypehints.',
				48,
			],
			[
				'Return typehint of method TestMethodTypehints\FooMethodTypehints::dolor() has invalid type TestMethodTypehints\BazMethodTypehints.',
				48,
			],
			[
				'Parameter $parent of method TestMethodTypehints\FooMethodTypehints::parentWithoutParent() has invalid type parent.',
				53,
			],
			[
				'Return type of method TestMethodTypehints\FooMethodTypehints::parentWithoutParent() has invalid type parent.',
				53,
			],
			[
				'Parameter $parent of method TestMethodTypehints\FooMethodTypehints::phpDocParentWithoutParent() has invalid type parent.',
				62,
			],
			[
				'Return type of method TestMethodTypehints\FooMethodTypehints::phpDocParentWithoutParent() has invalid type parent.',
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
				'Class TestMethodTypehints\FooMethodTypehints referenced with incorrect case: TestMethodTypehints\fOOMethodTypehintS.',
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
		if (!self::$useStaticReflectionProvider) {
			$this->markTestSkipped('Test requires static reflection');
		}
		$this->analyse([__DIR__ . '/data/void-parameter-typehint.php'], [
			[
				'Parameter $param of method VoidParameterTypehintMethod\Foo::doFoo() has invalid type void.',
				8,
			],
		]);
	}

	public function dataNativeUnionTypes(): array
	{
		if (PHP_VERSION_ID < 80000 && !self::$useStaticReflectionProvider) {
			return [];
		}

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
	 * @param int $phpVersionId
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
				PHP_VERSION_ID < 80000 || self::$useStaticReflectionProvider ? [] : [
					[
						'Required parameter $bar follows optional parameter $foo',
						8,
					],
					[
						'Required parameter $bar follows optional parameter $foo',
						17,
					],
					[
						'Required parameter $bar follows optional parameter $foo',
						21,
					],
				],
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
	 * @param int $phpVersionId
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

}
