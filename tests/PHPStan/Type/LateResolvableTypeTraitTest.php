<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\TrinaryLogic;
use PHPStan\Type\IntegerType;
use PHPStan\Type\NeverType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\ConditionalTypeForParameter;
use PHPStan\Type\UnionType;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class LateResolvableTypeTraitTest extends TestCase
{
	public static function dataIsSuperTypeOf(): array
	{
		return self::provideCases();
	}

	public static function dataIsSubTypeOf(): array
	{
		return self::provideCases();
	}

	private static function createConditional(
		string $parameterName = '$operator',
		string $targetLiteral = 'in',
		?Type $ifType = null,
		?Type $elseType = null,
		bool $negated = false,
	): ConditionalTypeForParameter
	{
		return new ConditionalTypeForParameter(
			$parameterName,
			new ConstantStringType($targetLiteral),
			$ifType ?? new IntegerType(),
			$elseType ?? new NeverType(),
			$negated,
		);
	}

	/**
	 * @return list<array{Type, Type, TrinaryLogic}>
	 */
	private static function provideCases(): array
	{
		return [
			'conditional vs same conditional' => [
				self::createConditional(),
				self::createConditional(),
				TrinaryLogic::createYes(),
			],
			'conditional vs union containing it' => [
				self::createConditional(),
				new UnionType([new StringType(), self::createConditional()]),
				TrinaryLogic::createYes(),
			],
		];
	}

	#[DataProvider('dataIsSuperTypeOf')]
	public function testIsSuperTypeOf(Type $left, Type $right, TrinaryLogic $expected): void
	{
		$actual = $left->isSuperTypeOf($right);
		$this->assertSame($expected->describe(), $actual->describe());
	}

	#[DataProvider('dataIsSubTypeOf')]
	public function testIsSubTypeOf(Type $left, Type $right, TrinaryLogic $expected): void
	{
		$actual = $left->isSubTypeOf($right);
		$this->assertSame($expected->describe(), $actual->describe());
	}

}
