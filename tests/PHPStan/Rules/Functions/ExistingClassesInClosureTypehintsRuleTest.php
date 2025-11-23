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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhp;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<ExistingClassesInClosureTypehintsRule>
 */
class ExistingClassesInClosureTypehintsRuleTest extends RuleTestCase
{

	private int $phpVersionId = PHP_VERSION_ID;

	protected function getRule(): Rule
	{
		$reflectionProvider = self::createReflectionProvider();
		$container = self::getContainer();
		return new ExistingClassesInClosureTypehintsRule(
			new FunctionDefinitionCheck(
				$reflectionProvider,
				new ClassNameCheck(
					new ClassCaseSensitivityCheck($reflectionProvider, true),
					new ClassForbiddenNameCheck($container),
					$reflectionProvider,
					$container,
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
		$this->analyse([__DIR__ . '/data/closure-typehints.php'], [
			[
				'Anonymous function has invalid return type TestClosureFunctionTypehints\NonexistentClass.',
				10,
			],
			[
				'Parameter $bar of anonymous function has invalid type TestClosureFunctionTypehints\BarFunctionTypehints.',
				15,
			],
			[
				'Class TestClosureFunctionTypehints\FooFunctionTypehints referenced with incorrect case: TestClosureFunctionTypehints\fOOfUnctionTypehints.',
				30,
			],
			[
				'Class TestClosureFunctionTypehints\FooFunctionTypehints referenced with incorrect case: TestClosureFunctionTypehints\FOOfUnctionTypehintS.',
				30,
			],
			[
				'Parameter $trait of anonymous function has invalid type TestClosureFunctionTypehints\SomeTrait.',
				45,
			],
			[
				'Anonymous function has invalid return type TestClosureFunctionTypehints\SomeTrait.',
				50,
			],
		]);
	}

	public function testValidTypehintPhp71(): void
	{
		$this->analyse([__DIR__ . '/data/closure-7.1-typehints.php'], [
			[
				'Parameter $bar of anonymous function has invalid type TestClosureFunctionTypehintsPhp71\NonexistentClass.',
				35,
			],
			[
				'Anonymous function has invalid return type TestClosureFunctionTypehintsPhp71\NonexistentClass.',
				35,
			],
		]);
	}

	public function testValidTypehintPhp72(): void
	{
		$this->analyse([__DIR__ . '/data/closure-7.2-typehints.php'], []);
	}

	public function testVoidParameterTypehint(): void
	{
		$this->analyse([__DIR__ . '/data/void-parameter-typehint.php'], [
			[
				'Parameter $param of anonymous function has invalid type void.',
				5,
			],
		]);
	}

	public static function dataNativeUnionTypes(): array
	{
		return [
			[
				70400,
				[
					[
						'Anonymous function uses native union types but they\'re supported only on PHP 8.0 and later.',
						15,
					],
					[
						'Anonymous function uses native union types but they\'re supported only on PHP 8.0 and later.',
						19,
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
	 * @param list<array{0: string, 1: int, 2?: string}> $errors
	 */
	#[DataProvider('dataNativeUnionTypes')]
	public function testNativeUnionTypes(int $phpVersionId, array $errors): void
	{
		$this->phpVersionId = $phpVersionId;
		$this->analyse([__DIR__ . '/data/native-union-types.php'], $errors);
	}

	public static function dataRequiredParameterAfterOptional(): array
	{
		return [
			[
				70400,
				[
					[
						"Anonymous function uses native union types but they're supported only on PHP 8.0 and later.",
						29,
					],
					[
						"Anonymous function uses native union types but they're supported only on PHP 8.0 and later.",
						33,
					],
					[
						"Anonymous function uses native union types but they're supported only on PHP 8.0 and later.",
						45,
					],
				],
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
						13,
					],
					[
						'Deprecated in PHP 8.0: Required parameter $bar follows optional parameter $foo.',
						17,
					],
					[
						'Deprecated in PHP 8.0: Required parameter $bar follows optional parameter $foo.',
						21,
					],
					[
						'Deprecated in PHP 8.0: Required parameter $bar follows optional parameter $foo.',
						29,
					],
					[
						'Deprecated in PHP 8.0: Required parameter $bar follows optional parameter $foo.',
						37,
					],
					[
						'Deprecated in PHP 8.0: Required parameter $quuz follows optional parameter $quux.',
						45,
					],
				],
			],
			[
				80100,
				[
					[
						'Deprecated in PHP 8.0: Required parameter $bar follows optional parameter $foo.',
						5,
					],
					[
						'Deprecated in PHP 8.0: Required parameter $bar follows optional parameter $foo.',
						13,
					],
					[
						'Deprecated in PHP 8.0: Required parameter $bar follows optional parameter $foo.',
						17,
					],
					[
						'Deprecated in PHP 8.0: Required parameter $bar follows optional parameter $foo.',
						21,
					],
					[
						'Deprecated in PHP 8.1: Required parameter $bar follows optional parameter $foo.',
						25,
					],
					[
						'Deprecated in PHP 8.0: Required parameter $bar follows optional parameter $foo.',
						29,
					],
					[
						'Deprecated in PHP 8.0: Required parameter $bar follows optional parameter $foo.',
						37,
					],
					[
						'Deprecated in PHP 8.1: Required parameter $qux follows optional parameter $baz.',
						45,
					],
					[
						'Deprecated in PHP 8.0: Required parameter $quuz follows optional parameter $quux.',
						45,
					],
				],
			],
			[
				80300,
				[
					[
						'Deprecated in PHP 8.0: Required parameter $bar follows optional parameter $foo.',
						5,
					],
					[
						'Deprecated in PHP 8.0: Required parameter $bar follows optional parameter $foo.',
						13,
					],
					[
						'Deprecated in PHP 8.0: Required parameter $bar follows optional parameter $foo.',
						17,
					],
					[
						'Deprecated in PHP 8.0: Required parameter $bar follows optional parameter $foo.',
						21,
					],
					[
						'Deprecated in PHP 8.1: Required parameter $bar follows optional parameter $foo.',
						25,
					],
					[
						'Deprecated in PHP 8.0: Required parameter $bar follows optional parameter $foo.',
						29,
					],
					[
						'Deprecated in PHP 8.3: Required parameter $bar follows optional parameter $foo.',
						33,
					],
					[
						'Deprecated in PHP 8.0: Required parameter $bar follows optional parameter $foo.',
						37,
					],
					[
						'Deprecated in PHP 8.3: Required parameter $bar follows optional parameter $foo.',
						41,
					],
					[
						'Deprecated in PHP 8.3: Required parameter $bar follows optional parameter $foo.',
						45,
					],
					[
						'Deprecated in PHP 8.1: Required parameter $qux follows optional parameter $baz.',
						45,
					],
					[
						'Deprecated in PHP 8.0: Required parameter $quuz follows optional parameter $quux.',
						45,
					],
				],
			],
		];
	}

	/**
	 * @param list<array{0: string, 1: int, 2?: string}> $errors
	 */
	#[RequiresPhp('>= 8.0')]
	#[DataProvider('dataRequiredParameterAfterOptional')]
	public function testRequiredParameterAfterOptional(int $phpVersionId, array $errors): void
	{
		$this->phpVersionId = $phpVersionId;
		$this->analyse([__DIR__ . '/data/required-parameter-after-optional-closures.php'], $errors);
	}

	public static function dataIntersectionTypes(): array
	{
		return [
			[80000, []],
			[
				80100,
				[
					[
						'Parameter $a of anonymous function has unresolvable native type.',
						30,
					],
					[
						'Anonymous function has unresolvable native return type.',
						30,
					],
					[
						'Parameter $a of anonymous function has unresolvable native type.',
						35,
					],
					[
						'Anonymous function has unresolvable native return type.',
						35,
					],
				],
			],
		];
	}

	/**
	 * @param list<array{0: string, 1: int, 2?: string}> $errors
	 */
	#[DataProvider('dataIntersectionTypes')]
	public function testIntersectionTypes(int $phpVersion, array $errors): void
	{
		$this->phpVersionId = $phpVersion;

		$this->analyse([__DIR__ . '/data/closure-intersection-types.php'], $errors);
	}

	#[RequiresPhp('>= 8.4')]
	public function testDeprecatedImplicitlyNullableParameterType(): void
	{
		$this->analyse([__DIR__ . '/data/closure-implicitly-nullable.php'], [
			[
				'Deprecated in PHP 8.4: Parameter #3 $c (int) is implicitly nullable via default value null.',
				13,
			],
			[
				'Deprecated in PHP 8.4: Parameter #5 $e (int|string) is implicitly nullable via default value null.',
				15,
			],
			[
				'Deprecated in PHP 8.4: Parameter #7 $g (stdClass) is implicitly nullable via default value null.',
				17,
			],
		]);
	}

	#[RequiresPhp('>= 8.2')]
	public function testNoDiscardVoid(): void
	{
		$this->analyse([__DIR__ . '/data/closure-typehints-nodiscard.php'], [
			[
				'Attribute NoDiscard cannot be used on void anonymous function.',
				5,
			],
			[
				'Attribute NoDiscard cannot be used on never anonymous function.',
				10,
			],
		]);
	}

}
