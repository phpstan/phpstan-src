<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPStan\Type\FileTypeMapper;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<WrongVariableNameInVarTagRule>
 */
class WrongVariableNameInVarTagRuleTest extends RuleTestCase
{

	private bool $checkTypeAgainstNativeType = false;

	private bool $checkTypeAgainstPhpDocType = false;

	protected function getRule(): Rule
	{
		return new WrongVariableNameInVarTagRule(
			self::getContainer()->getByType(FileTypeMapper::class),
			new VarTagTypeRuleHelper($this->checkTypeAgainstPhpDocType),
			$this->checkTypeAgainstNativeType,
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/wrong-variable-name-var.php'], [
			[
				'Variable $foo in PHPDoc tag @var does not match assigned variable $test.',
				17,
			],
			[
				'Multiple PHPDoc @var tags above single variable assignment are not supported.',
				23,
			],
			[
				'Variable $foo in PHPDoc tag @var does not match any variable in the foreach loop: $list, $key, $val',
				66,
			],
			[
				'PHPDoc tag @var above foreach loop does not specify variable name.',
				71,
			],
			[
				'PHPDoc tag @var above multiple static variables does not specify variable name.',
				85,
			],
			[
				'PHPDoc tag @var above multiple static variables does not specify variable name.',
				91,
			],
			[
				'PHPDoc tag @var above multiple static variables does not specify variable name.',
				91,
			],
			[
				'Variable $foo in PHPDoc tag @var does not match any static variable: $test',
				94,
			],
			[
				'PHPDoc tag @var does not specify variable name.',
				103,
			],
			[
				'Variable $foo in PHPDoc tag @var does not exist.',
				109,
			],
			[
				'Multiple PHPDoc @var tags above single variable assignment are not supported.',
				125,
			],
			[
				'Variable $b in PHPDoc tag @var does not exist.',
				134,
			],
			[
				'PHPDoc tag @var does not specify variable name.',
				155,
			],
			[
				'PHPDoc tag @var does not specify variable name.',
				176,
			],
			[
				'Variable $foo in PHPDoc tag @var does not exist.',
				210,
			],
			[
				'PHPDoc tag @var above foreach loop does not specify variable name.',
				234,
			],
			[
				'Variable $foo in PHPDoc tag @var does not exist.',
				248,
			],
			[
				'Variable $bar in PHPDoc tag @var does not exist.',
				248,
			],
			[
				'Variable $slots in PHPDoc tag @var does not exist.',
				262,
			],
			[
				'Variable $slots in PHPDoc tag @var does not exist.',
				268,
			],
			[
				'PHPDoc tag @var above assignment does not specify variable name.',
				274,
			],
			[
				'Variable $slots in PHPDoc tag @var does not match assigned variable $itemSlots.',
				280,
			],
			[
				'PHPDoc tag @var above a class has no effect.',
				300,
			],
			[
				'PHPDoc tag @var above a method has no effect.',
				304,
			],
			[
				'PHPDoc tag @var above a function has no effect.',
				312,
			],
		]);
	}

	public function testEmptyFileWithVarThis(): void
	{
		$this->analyse([__DIR__ . '/data/wrong-variable-name-var-empty-this.php'], []);
	}

	public function testAboveUse(): void
	{
		$this->analyse([__DIR__ . '/data/var-above-use.php'], []);
	}

	public function testAboveDeclare(): void
	{
		$this->analyse([__DIR__ . '/data/var-above-declare.php'], []);
	}

	public function testBug3515(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3515.php'], []);
	}

	public function testBug4500(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4500.php'], [
			[
				'PHPDoc tag @var above multiple global variables does not specify variable name.',
				23,
			],
			[
				'Variable $baz in PHPDoc tag @var does not match any global variable: $lorem',
				43,
			],
			[
				'Variable $baz in PHPDoc tag @var does not match any global variable: $lorem',
				49,
			],
		]);
	}

	public function testBug4504(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4504.php'], []);
	}

	public function testBug4505(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4505.php'], []);
	}

	public function testEnums(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('This test needs PHP 8.1');
		}

		$this->analyse([__DIR__ . '/data/wrong-var-enum.php'], [
			[
				'PHPDoc tag @var above an enum has no effect.',
				13,
			],
		]);
	}

	public function dataReportWrongType(): iterable
	{
		yield [false, false, []];
		yield [true, false, [
			[
				'PHPDoc tag @var with type string|null is not subtype of native type string.',
				14,
			],
			[
				'PHPDoc tag @var with type stdClass is not subtype of native type SplObjectStorage<object, mixed>.',
				23,
			],
			[
				'PHPDoc tag @var with type int is not subtype of native type \'foo\'.',
				26,
			],
			[
				'PHPDoc tag @var with type Iterator<mixed, int> is not subtype of native type array.',
				38,
			],
			[
				'PHPDoc tag @var with type string is not subtype of native type 1.',
				99,
			],
			[
				'PHPDoc tag @var with type int is not subtype of native type string.',
				109,
			],
			[
				'PHPDoc tag @var with type int is not subtype of native type \'foo\'.',
				148,
			],
			[
				'PHPDoc tag @var with type stdClass is not subtype of native type PHPStan\Type\Type|null.',
				186,
			],
			[
				'PHPDoc tag @var assumes the expression with type PHPStan\Type\Type|null is always PHPStan\Type\ObjectType|null but it\'s error-prone and dangerous.',
				189,
			],
			[
				'PHPDoc tag @var assumes the expression with type PHPStan\Type\Type|null is always PHPStan\Type\ObjectType but it\'s error-prone and dangerous.',
				192,
			],
			[
				'PHPDoc tag @var assumes the expression with type PHPStan\Type\ObjectType|null is always PHPStan\Type\ObjectType but it\'s error-prone and dangerous.',
				195,
			],
			[
				'PHPDoc tag @var with type PHPStan\Type\Type|null is not subtype of native type PHPStan\Type\ObjectType|null.',
				201,
			],
			[
				'PHPDoc tag @var with type PHPStan\Type\ObjectType|null is not subtype of type PHPStan\Type\Generic\GenericObjectType|null.',
				204,
			],
		]];
		yield [false, true, []];
		yield [true, true, [
			[
				'PHPDoc tag @var with type string|null is not subtype of native type string.',
				14,
			],
			[
				'PHPDoc tag @var with type stdClass is not subtype of native type SplObjectStorage<object, mixed>.',
				23,
			],
			[
				'PHPDoc tag @var with type int is not subtype of native type \'foo\'.',
				26,
			],
			[
				'PHPDoc tag @var with type int is not subtype of type string.',
				29,
			],
			[
				'PHPDoc tag @var with type array<string> is not subtype of type list<int>.',
				35,
			],
			[
				'PHPDoc tag @var with type Iterator<mixed, int> is not subtype of native type array.',
				38,
			],
			[
				'PHPDoc tag @var with type Iterator<mixed, string> is not subtype of type Iterator<int, int>.',
				44,
			],
			/*[
				// reported by VarTagChangedExpressionTypeRule
				'PHPDoc tag @var with type string is not subtype of type int.',
				95,
			],*/
			[
				'PHPDoc tag @var with type string is not subtype of native type 1.',
				99,
			],
			[
				'PHPDoc tag @var with type int is not subtype of native type string.',
				109,
			],
			[
				'PHPDoc tag @var with type array<int> is not subtype of type array<int, string>.',
				137,
			],
			[
				'PHPDoc tag @var with type string is not subtype of type int.',
				137,
			],
			[
				'PHPDoc tag @var with type int is not subtype of type string.',
				137,
			],
			[
				'PHPDoc tag @var with type int is not subtype of native type \'foo\'.',
				148,
			],
			[
				'PHPDoc tag @var with type array<array<int>> is not subtype of type array<list<string|null>>.',
				160,
			],
			[
				'PHPDoc tag @var with type array<Traversable<mixed, string>> is not subtype of type array<list<string|null>>.',
				163,
			],
			[
				'PHPDoc tag @var with type stdClass is not subtype of native type PHPStan\Type\Type|null.',
				186,
			],
			[
				'PHPDoc tag @var assumes the expression with type PHPStan\Type\Type|null is always PHPStan\Type\ObjectType|null but it\'s error-prone and dangerous.',
				189,
			],
			[
				'PHPDoc tag @var assumes the expression with type PHPStan\Type\Type|null is always PHPStan\Type\ObjectType but it\'s error-prone and dangerous.',
				192,
			],
			[
				'PHPDoc tag @var assumes the expression with type PHPStan\Type\ObjectType|null is always PHPStan\Type\ObjectType but it\'s error-prone and dangerous.',
				195,
			],
			[
				'PHPDoc tag @var with type PHPStan\Type\Type|null is not subtype of native type PHPStan\Type\ObjectType|null.',
				201,
			],
			[
				'PHPDoc tag @var with type PHPStan\Type\ObjectType|null is not subtype of type PHPStan\Type\Generic\GenericObjectType|null.',
				204,
			],
		]];
	}

	/**
	 * @dataProvider dataPermutateCheckTypeAgainst
	 */
	public function testEmptyArrayInitWithWiderPhpDoc(bool $checkTypeAgainstNativeType, bool $checkTypeAgainstPhpDocType): void
	{
		$this->checkTypeAgainstNativeType = $checkTypeAgainstNativeType;
		$this->checkTypeAgainstPhpDocType = $checkTypeAgainstPhpDocType;

		$errors = !$checkTypeAgainstNativeType
			? []
			: [
				[
					'PHPDoc tag @var with type int|null is not subtype of native type null.',
					6,
				],
				[
					'PHPDoc tag @var with type int is not subtype of native type array{}.',
					24,
				],
			];

		$this->analyse([__DIR__ . '/data/var-above-empty-array-widening.php'], $errors);
	}

	public function dataPermutateCheckTypeAgainst(): iterable
	{
		yield [true, true];
		yield [false, true];
		yield [true, false];
		yield [false, false];
	}

	/**
	 * @dataProvider dataReportWrongType
	 * @param list<array{0: string, 1: int, 2?: string}> $expectedErrors
	 */
	public function testReportWrongType(bool $checkTypeAgainstNativeType, bool $checkTypeAgainstPhpDocType, array $expectedErrors): void
	{
		$this->checkTypeAgainstNativeType = $checkTypeAgainstNativeType;
		$this->checkTypeAgainstPhpDocType = $checkTypeAgainstPhpDocType;
		$this->analyse([__DIR__ . '/data/wrong-var-native-type.php'], $expectedErrors);
	}

}
