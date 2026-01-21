<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PHPStan\PhpDoc\TypeNodeResolver;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPStan\Type\FileTypeMapper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<WrongVariableNameInVarTagRule>
 */
class WrongVariableNameInVarTagRuleTest extends RuleTestCase
{

	private bool $checkTypeAgainstPhpDocType = false;

	private bool $strictWideningCheck = false;

	protected function getRule(): Rule
	{
		$container = self::getContainer();
		return new WrongVariableNameInVarTagRule(
			$container->getByType(FileTypeMapper::class),
			new VarTagTypeRuleHelper(
				$container->getByType(TypeNodeResolver::class),
				$container->getByType(FileTypeMapper::class),
				self::createReflectionProvider(),
				$this->checkTypeAgainstPhpDocType,
				$this->strictWideningCheck,
			),
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/wrong-variable-name-var.php'], [
			[
				'PHPDoc tag @var with type int is not subtype of native type void.',
				11,
			],
			[
				'PHPDoc tag @var with type int is not subtype of native type void.',
				14,
			],
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
				'PHPDoc tag @var with type int is not subtype of native type void.',
				120,
			],
			[
				'Multiple PHPDoc @var tags above single variable assignment are not supported.',
				126,
			],
			[
				'Variable $b in PHPDoc tag @var does not exist.',
				135,
			],
			[
				'PHPDoc tag @var does not specify variable name.',
				156,
			],
			[
				'PHPDoc tag @var does not specify variable name.',
				177,
			],
			[
				'Variable $foo in PHPDoc tag @var does not exist.',
				211,
			],
			[
				'PHPDoc tag @var above foreach loop does not specify variable name.',
				235,
			],
			[
				'Variable $foo in PHPDoc tag @var does not exist.',
				249,
			],
			[
				'Variable $bar in PHPDoc tag @var does not exist.',
				249,
			],
			[
				'Variable $slots in PHPDoc tag @var does not exist.',
				263,
			],
			[
				'Variable $slots in PHPDoc tag @var does not exist.',
				269,
			],
			[
				'PHPDoc tag @var above assignment does not specify variable name.',
				275,
			],
			[
				'Variable $slots in PHPDoc tag @var does not match assigned variable $itemSlots.',
				281,
			],
			[
				'PHPDoc tag @var above a class has no effect.',
				301,
			],
			[
				'PHPDoc tag @var above a method has no effect.',
				305,
			],
			[
				'PHPDoc tag @var above a function has no effect.',
				313,
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

	public function testBug12458(): void
	{
		$this->checkTypeAgainstPhpDocType = true;
		$this->strictWideningCheck = true;

		$this->analyse([__DIR__ . '/data/bug-12458.php'], []);
	}

	public function testBug11015(): void
	{
		$this->checkTypeAgainstPhpDocType = true;
		$this->strictWideningCheck = true;

		$this->analyse([__DIR__ . '/data/bug-11015.php'], []);
	}

	public function testBug10861(): void
	{
		$this->checkTypeAgainstPhpDocType = true;
		$this->strictWideningCheck = true;

		$this->analyse([__DIR__ . '/data/bug-10861.php'], []);
	}

	public function testBug11535(): void
	{
		$this->checkTypeAgainstPhpDocType = true;
		$this->strictWideningCheck = true;

		$this->analyse([__DIR__ . '/data/bug-11535.php'], [
			[
				'PHPDoc tag @var with type Closure(string): array<int> is not subtype of native type Closure(string): array{1, 2, 3}.',
				6,
			],
		]);
	}

	#[RequiresPhp('>= 8.1')]
	public function testEnums(): void
	{
		$this->analyse([__DIR__ . '/data/wrong-var-enum.php'], [
			[
				'PHPDoc tag @var above an enum has no effect.',
				13,
			],
		]);
	}

	public static function dataReportWrongType(): iterable
	{
		$nativeCheckOnly = [
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
		];

		yield [false, false, $nativeCheckOnly];
		yield [false, true, $nativeCheckOnly];
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
				'PHPDoc tag @var with type array<int> is not subtype of type list<int>.',
				32,
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
			[
				'PHPDoc tag @var with type array<int> is not subtype of type array<int, int>.',
				47,
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
				'PHPDoc tag @var with type array<string> is not subtype of type array<int, string>.',
				122,
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
				'PHPDoc tag @var with type array<array<string>> is not subtype of type array<list<string|null>>.',
				154,
			],
			[
				'PHPDoc tag @var with type array<array<string>> is not subtype of type array<list<string|null>>.',
				157,
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
			[
				'PHPDoc tag @var with type array<mixed>|null is not subtype of type array{id: int}|null.',
				235,
			],
		]];
	}

	#[DataProvider('dataPermutateCheckTypeAgainst')]
	public function testEmptyArrayInitWithWiderPhpDoc(bool $checkTypeAgainstPhpDocType): void
	{
		$this->checkTypeAgainstPhpDocType = $checkTypeAgainstPhpDocType;
		$this->analyse([__DIR__ . '/data/var-above-empty-array-widening.php'], [
			[
				'PHPDoc tag @var with type int is not subtype of native type array{}.',
				24,
			],
		]);
	}

	public static function dataPermutateCheckTypeAgainst(): iterable
	{
		yield [true];
		yield [false];
	}

	/**
	 * @param list<array{0: string, 1: int, 2?: string}> $expectedErrors
	 */
	#[DataProvider('dataReportWrongType')]
	public function testReportWrongType(
		bool $checkTypeAgainstPhpDocType,
		bool $strictWideningCheck,
		array $expectedErrors,
	): void
	{
		$this->checkTypeAgainstPhpDocType = $checkTypeAgainstPhpDocType;
		$this->strictWideningCheck = $strictWideningCheck;
		$this->analyse([__DIR__ . '/data/wrong-var-native-type.php'], $expectedErrors);
	}

	public function testBug12457(): void
	{
		$this->checkTypeAgainstPhpDocType = true;
		$this->strictWideningCheck = true;
		$this->analyse([__DIR__ . '/data/bug-12457.php'], [
			[
				'PHPDoc tag @var with type array{numeric-string} is not subtype of type array{lowercase-string&numeric-string&uppercase-string}.',
				13,
			],
			[
				'PHPDoc tag @var with type callable(): string is not subtype of type callable(): numeric-string&lowercase-string&uppercase-string.',
				22,
			],
		]);
	}

	public function testNewIsAlwaysFinalClass(): void
	{
		$this->checkTypeAgainstPhpDocType = true;
		$this->strictWideningCheck = true;
		$this->analyse([__DIR__ . '/data/new-is-always-final-var-tag-type.php'], []);
	}

}
