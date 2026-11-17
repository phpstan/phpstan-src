<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Testing\PHPStanTestCase;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Generic\TemplateTypeFactory;
use PHPStan\Type\Generic\TemplateTypeScope;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPUnit\Framework\Attributes\DataProvider;
use function sprintf;

class ConditionalTypeTest extends PHPStanTestCase
{

	/**
	 * @param non-empty-string $name
	 */
	private static function templateType(string $name): Type
	{
		return TemplateTypeFactory::create(
			TemplateTypeScope::createWithFunction('_'),
			$name,
			new MixedType(),
			TemplateTypeVariance::createInvariant(),
		);
	}

	/**
	 * @return iterable<string, array{ConditionalType, Type, TrinaryLogic}>
	 */
	public static function dataIsSuperTypeOf(): iterable
	{
		$t = self::templateType('T');
		$u = self::templateType('U');
		$tIsIntThenIntElseString = new ConditionalType($t, new IntegerType(), new IntegerType(), new StringType(), false);

		yield 'same condition' => [
			$tIsIntThenIntElseString,
			new ConditionalType($t, new IntegerType(), new IntegerType(), new StringType(), false),
			TrinaryLogic::createYes(),
		];

		yield 'same condition, narrower branches' => [
			$tIsIntThenIntElseString,
			new ConditionalType($t, new IntegerType(), new ConstantIntegerType(1), new StringType(), false),
			TrinaryLogic::createYes(),
		];

		yield 'different negation' => [
			$tIsIntThenIntElseString,
			new ConditionalType($t, new IntegerType(), new IntegerType(), new StringType(), true),
			TrinaryLogic::createMaybe(),
		];

		yield 'different subject' => [
			$tIsIntThenIntElseString,
			new ConditionalType($u, new IntegerType(), new IntegerType(), new StringType(), false),
			TrinaryLogic::createMaybe(),
		];

		yield 'different target' => [
			$tIsIntThenIntElseString,
			new ConditionalType($t, new StringType(), new IntegerType(), new StringType(), false),
			TrinaryLogic::createMaybe(),
		];
	}

	#[DataProvider('dataIsSuperTypeOf')]
	public function testIsSuperTypeOf(ConditionalType $type, Type $otherType, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->isSuperTypeOf($otherType);
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isSuperTypeOf(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise())),
		);
	}

	/**
	 * @return iterable<string, array{ConditionalType, Type, bool}>
	 */
	public static function dataEquals(): iterable
	{
		$t = self::templateType('T');
		$u = self::templateType('U');
		$type = new ConditionalType($t, new IntegerType(), new IntegerType(), new StringType(), false);

		yield 'same' => [
			$type,
			new ConditionalType($t, new IntegerType(), new IntegerType(), new StringType(), false),
			true,
		];

		yield 'different negation' => [
			$type,
			new ConditionalType($t, new IntegerType(), new IntegerType(), new StringType(), true),
			false,
		];

		yield 'different subject' => [
			$type,
			new ConditionalType($u, new IntegerType(), new IntegerType(), new StringType(), false),
			false,
		];

		yield 'different target' => [
			$type,
			new ConditionalType($t, new StringType(), new IntegerType(), new StringType(), false),
			false,
		];

		yield 'different if' => [
			$type,
			new ConditionalType($t, new IntegerType(), new StringType(), new StringType(), false),
			false,
		];

		yield 'different else' => [
			$type,
			new ConditionalType($t, new IntegerType(), new IntegerType(), new IntegerType(), false),
			false,
		];
	}

	#[DataProvider('dataEquals')]
	public function testEquals(ConditionalType $type, Type $otherType, bool $expected): void
	{
		$this->assertSame($expected, $type->equals($otherType));
		$this->assertSame($expected, $otherType->equals($type));
	}

}
