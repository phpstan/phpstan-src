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
 * @extends RuleTestCase<ExistingClassesInArrowFunctionTypehintsRule>
 */
class ExistingClassesInArrowFunctionTypehintsRuleTest extends RuleTestCase
{

	private int $phpVersionId = PHP_VERSION_ID;

	protected function getRule(): Rule
	{
		$reflectionProvider = self::createReflectionProvider();
		$container = self::getContainer();
		return new ExistingClassesInArrowFunctionTypehintsRule(
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
			new PhpVersion(PHP_VERSION_ID),
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/arrow-function-typehints.php'], [
			[
				'Parameter $bar of anonymous function has invalid type ArrowFunctionExistingClassesInTypehints\Bar.',
				10,
			],
			[
				'Anonymous function has invalid return type ArrowFunctionExistingClassesInTypehints\Baz.',
				10,
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
						23,
					],
					[
						'Anonymous function uses native union types but they\'re supported only on PHP 8.0 and later.',
						24,
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
						17,
					],
					[
						"Anonymous function uses native union types but they're supported only on PHP 8.0 and later.",
						19,
					],
					[
						"Anonymous function uses native union types but they're supported only on PHP 8.0 and later.",
						25,
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
						9,
					],
					[
						'Deprecated in PHP 8.0: Required parameter $bar follows optional parameter $foo.',
						11,
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
						'Deprecated in PHP 8.0: Required parameter $quuz follows optional parameter $quux.',
						25,
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
						9,
					],
					[
						'Deprecated in PHP 8.0: Required parameter $bar follows optional parameter $foo.',
						11,
					],
					[
						'Deprecated in PHP 8.0: Required parameter $bar follows optional parameter $foo.',
						13,
					],
					[
						'Deprecated in PHP 8.1: Required parameter $bar follows optional parameter $foo.',
						15,
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
						'Deprecated in PHP 8.1: Required parameter $qux follows optional parameter $baz.',
						25,
					],
					[
						'Deprecated in PHP 8.0: Required parameter $quuz follows optional parameter $quux.',
						25,
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
						9,
					],
					[
						'Deprecated in PHP 8.0: Required parameter $bar follows optional parameter $foo.',
						11,
					],
					[
						'Deprecated in PHP 8.0: Required parameter $bar follows optional parameter $foo.',
						13,
					],
					[
						'Deprecated in PHP 8.1: Required parameter $bar follows optional parameter $foo.',
						15,
					],
					[
						'Deprecated in PHP 8.0: Required parameter $bar follows optional parameter $foo.',
						17,
					],
					[
						'Deprecated in PHP 8.3: Required parameter $bar follows optional parameter $foo.',
						19,
					],
					[
						'Deprecated in PHP 8.0: Required parameter $bar follows optional parameter $foo.',
						21,
					],
					[
						'Deprecated in PHP 8.3: Required parameter $bar follows optional parameter $foo.',
						23,
					],
					[
						'Deprecated in PHP 8.3: Required parameter $bar follows optional parameter $foo.',
						25,
					],
					[
						'Deprecated in PHP 8.1: Required parameter $qux follows optional parameter $baz.',
						25,
					],
					[
						'Deprecated in PHP 8.0: Required parameter $quuz follows optional parameter $quux.',
						25,
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
		$this->analyse([__DIR__ . '/data/required-parameter-after-optional-arrow.php'], $errors);
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
						27,
					],
					[
						'Anonymous function has unresolvable native return type.',
						27,
					],
					[
						'Parameter $a of anonymous function has unresolvable native type.',
						29,
					],
					[
						'Anonymous function has unresolvable native return type.',
						29,
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

		$this->analyse([__DIR__ . '/data/arrow-function-intersection-types.php'], $errors);
	}

	public function testNever(): void
	{
		$errors = [];
		if (PHP_VERSION_ID < 80100) {
			$errors = [
				[
					'Anonymous function has invalid return type ArrowFunctionNever\never.',
					6,
				],
			];
		} elseif (PHP_VERSION_ID < 80200) {
			$errors = [
				[
					'Never return type in arrow function is supported only on PHP 8.2 and later.',
					6,
				],
			];
		}
		$this->analyse([__DIR__ . '/data/arrow-function-never.php'], $errors);
	}

	public function testBug5206(): void
	{
		$errors = [];
		if (PHP_VERSION_ID < 80000) {
			$errors[] = [
				'Parameter $mixed of anonymous function has invalid type Bug5206\mixed.',
				9,
			];
		}

		$this->analyse([__DIR__ . '/data/bug-5206.php'], $errors);
	}

	#[RequiresPhp('>= 8.2')]
	public function testNoDiscardVoid(): void
	{
		$this->analyse([__DIR__ . '/data/arrow-function-typehints-nodiscard.php'], [
			[
				'Attribute NoDiscard cannot be used on void anonymous function.',
				10,
			],
			[
				'Attribute NoDiscard cannot be used on never anonymous function.',
				15,
			],
		]);
	}

}
