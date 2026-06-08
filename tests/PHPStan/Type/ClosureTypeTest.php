<?php declare(strict_types = 1);

namespace PHPStan\Type;

use Closure;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\TrinaryLogic;
use PHPUnit\Framework\Attributes\DataProvider;
use function sprintf;

class ClosureTypeTest extends PHPStanTestCase
{

	public static function dataIsSuperTypeOf(): array
	{
		return [
			[
				new ClosureType([], new MixedType(), false),
				new ObjectType(Closure::class),
				TrinaryLogic::createMaybe(),
			],
			[
				new ClosureType([], new MixedType(), false),
				new ClosureType([], new MixedType(), false),
				TrinaryLogic::createYes(),
			],
			[
				new ClosureType([], new MixedType(), false, impurePoints: []),
				new ClosureType([], new MixedType(), false),
				TrinaryLogic::createMaybe(),
			],
			[
				new ClosureType([], new UnionType([new IntegerType(), new StringType()]), false),
				new ClosureType([], new IntegerType(), false),
				TrinaryLogic::createYes(),
			],
			[
				new ClosureType([], new MixedType(), false),
				new CallableType(),
				TrinaryLogic::createMaybe(),
			],
			[
				new ObjectType(Closure::class),
				new ClosureType([], new MixedType(), false),
				TrinaryLogic::createYes(),
			],
			[
				new ClosureType([], new MixedType(), false),
				new ClosureType([], new MixedType(), false),
				TrinaryLogic::createYes(),
			],
			[
				new ClosureType([], new IntegerType(), false),
				new ClosureType([], new UnionType([new IntegerType(), new StringType()]), false),
				TrinaryLogic::createMaybe(),
			],
			[
				new ClosureType([], new UnionType([new IntegerType(), new StringType()]), false),
				new ClosureType([], new IntegerType(), false),
				TrinaryLogic::createYes(),
			],
			[
				new CallableType(),
				new ClosureType([], new MixedType(), false),
				TrinaryLogic::createYes(),
			],
			[
				new ObjectWithoutClassType(),
				new ClosureType([], new MixedType(), false),
				TrinaryLogic::createYes(),
			],
			[
				new ClosureType([], new MixedType(), false),
				new ObjectWithoutClassType(),
				TrinaryLogic::createMaybe(),
			],
			[
				new ObjectWithoutClassType(new ClosureType([], new MixedType(), false)),
				new ClosureType([], new MixedType(), false),
				TrinaryLogic::createNo(),
			],
			[
				new ObjectWithoutClassType(new ObjectType(Closure::class)),
				new ClosureType([], new MixedType(), false),
				TrinaryLogic::createNo(),
			],
			[
				new ClosureType([], new MixedType(), false),
				new ObjectWithoutClassType(new ObjectType(Closure::class)),
				TrinaryLogic::createNo(),
			],
			'static closure is supertype of static closure' => [
				new ClosureType([], new MixedType(), false, isStatic: TrinaryLogic::createYes()),
				new ClosureType([], new MixedType(), false, isStatic: TrinaryLogic::createYes()),
				TrinaryLogic::createYes(),
			],
			'static closure is not supertype of non-static closure' => [
				new ClosureType([], new MixedType(), false, isStatic: TrinaryLogic::createYes()),
				new ClosureType([], new MixedType(), false, isStatic: TrinaryLogic::createNo()),
				TrinaryLogic::createNo(),
			],
			'non-static closure is not supertype of static closure' => [
				new ClosureType([], new MixedType(), false, isStatic: TrinaryLogic::createNo()),
				new ClosureType([], new MixedType(), false, isStatic: TrinaryLogic::createYes()),
				TrinaryLogic::createNo(),
			],
			'non-static closure is supertype of non-static closure' => [
				new ClosureType([], new MixedType(), false, isStatic: TrinaryLogic::createNo()),
				new ClosureType([], new MixedType(), false, isStatic: TrinaryLogic::createNo()),
				TrinaryLogic::createYes(),
			],
			'maybe-static closure is supertype of static closure' => [
				new ClosureType([], new MixedType(), false, isStatic: TrinaryLogic::createMaybe()),
				new ClosureType([], new MixedType(), false, isStatic: TrinaryLogic::createYes()),
				TrinaryLogic::createYes(),
			],
			'maybe-static closure is supertype of non-static closure' => [
				new ClosureType([], new MixedType(), false, isStatic: TrinaryLogic::createMaybe()),
				new ClosureType([], new MixedType(), false, isStatic: TrinaryLogic::createNo()),
				TrinaryLogic::createYes(),
			],
			'static closure is maybe supertype of maybe-static closure' => [
				new ClosureType([], new MixedType(), false, isStatic: TrinaryLogic::createYes()),
				new ClosureType([], new MixedType(), false, isStatic: TrinaryLogic::createMaybe()),
				TrinaryLogic::createMaybe(),
			],
		];
	}

	#[DataProvider('dataIsSuperTypeOf')]
	public function testIsSuperTypeOf(
		Type $type,
		Type $otherType,
		TrinaryLogic $expectedResult,
	): void
	{
		$actualResult = $type->isSuperTypeOf($otherType);
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isSuperTypeOf(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise())),
		);
	}

	public static function dataEquals(): array
	{
		return [
			'static equals static' => [
				new ClosureType([], new MixedType(), false, isStatic: TrinaryLogic::createYes()),
				new ClosureType([], new MixedType(), false, isStatic: TrinaryLogic::createYes()),
				true,
			],
			'static does not equal non-static' => [
				new ClosureType([], new MixedType(), false, isStatic: TrinaryLogic::createYes()),
				new ClosureType([], new MixedType(), false, isStatic: TrinaryLogic::createNo()),
				false,
			],
			'static does not equal maybe-static' => [
				new ClosureType([], new MixedType(), false, isStatic: TrinaryLogic::createYes()),
				new ClosureType([], new MixedType(), false, isStatic: TrinaryLogic::createMaybe()),
				false,
			],
			'maybe-static equals maybe-static' => [
				new ClosureType([], new MixedType(), false, isStatic: TrinaryLogic::createMaybe()),
				new ClosureType([], new MixedType(), false, isStatic: TrinaryLogic::createMaybe()),
				true,
			],
		];
	}

	#[DataProvider('dataEquals')]
	public function testEquals(
		ClosureType $type,
		ClosureType $otherType,
		bool $expectedResult,
	): void
	{
		$this->assertSame($expectedResult, $type->equals($otherType));
	}

	public static function dataDescribe(): array
	{
		return [
			'static closure at typeOnly' => [
				new ClosureType([], new MixedType(), false, isStatic: TrinaryLogic::createYes()),
				VerbosityLevel::typeOnly(),
				'Closure',
			],
			'static closure at value' => [
				new ClosureType([], new MixedType(), false, isStatic: TrinaryLogic::createYes()),
				VerbosityLevel::value(),
				'Closure(): mixed',
			],
			'static closure at precise' => [
				new ClosureType([], new MixedType(), false, isStatic: TrinaryLogic::createYes()),
				VerbosityLevel::precise(),
				'static-Closure(): mixed',
			],
			'static closure at cache' => [
				new ClosureType([], new MixedType(), false, isStatic: TrinaryLogic::createYes()),
				VerbosityLevel::cache(),
				'static-Closure(): mixed',
			],
			'non-static closure at precise' => [
				new ClosureType([], new MixedType(), false, isStatic: TrinaryLogic::createNo()),
				VerbosityLevel::precise(),
				'Closure(): mixed',
			],
			'non-static closure at cache' => [
				new ClosureType([], new MixedType(), false, isStatic: TrinaryLogic::createNo()),
				VerbosityLevel::cache(),
				'Closure(): mixed',
			],
			'maybe-static closure at precise' => [
				new ClosureType([], new MixedType(), false, isStatic: TrinaryLogic::createMaybe()),
				VerbosityLevel::precise(),
				'Closure(): mixed',
			],
			'maybe-static closure at cache' => [
				new ClosureType([], new MixedType(), false, isStatic: TrinaryLogic::createMaybe()),
				VerbosityLevel::cache(),
				'Closure(): mixed',
			],
			'static common closure at precise' => [
				new ClosureType(isStatic: TrinaryLogic::createYes()),
				VerbosityLevel::precise(),
				'static-Closure',
			],
			'static common closure at cache' => [
				new ClosureType(isStatic: TrinaryLogic::createYes()),
				VerbosityLevel::cache(),
				'static-Closure',
			],
			'static pure common closure at precise' => [
				new ClosureType(impurePoints: [], isStatic: TrinaryLogic::createYes()),
				VerbosityLevel::precise(),
				'static-pure-Closure',
			],
			'static pure common closure at cache' => [
				new ClosureType(impurePoints: [], isStatic: TrinaryLogic::createYes()),
				VerbosityLevel::cache(),
				'static-pure-Closure',
			],
			'static pure closure at precise' => [
				new ClosureType([], new MixedType(), false, impurePoints: [], isStatic: TrinaryLogic::createYes()),
				VerbosityLevel::precise(),
				'static-Closure(): mixed',
			],
			'static pure closure at cache' => [
				new ClosureType([], new MixedType(), false, impurePoints: [], isStatic: TrinaryLogic::createYes()),
				VerbosityLevel::cache(),
				'static-Closure(): mixed',
			],
			'static pure common closure at value' => [
				new ClosureType(impurePoints: [], isStatic: TrinaryLogic::createYes()),
				VerbosityLevel::value(),
				'static-pure-Closure',
			],
		];
	}

	#[DataProvider('dataDescribe')]
	public function testDescribe(
		ClosureType $type,
		VerbosityLevel $level,
		string $expectedDescription,
	): void
	{
		$this->assertSame($expectedDescription, $type->describe($level));
	}

	public static function dataAccepts(): array
	{
		return [
			'static closure accepts static closure' => [
				new ClosureType([], new MixedType(), false, isStatic: TrinaryLogic::createYes()),
				new ClosureType([], new MixedType(), false, isStatic: TrinaryLogic::createYes()),
				TrinaryLogic::createYes(),
			],
			'static closure does not accept non-static closure' => [
				new ClosureType([], new MixedType(), false, isStatic: TrinaryLogic::createYes()),
				new ClosureType([], new MixedType(), false, isStatic: TrinaryLogic::createNo()),
				TrinaryLogic::createNo(),
			],
			'non-static closure does not accept static closure' => [
				new ClosureType([], new MixedType(), false, isStatic: TrinaryLogic::createNo()),
				new ClosureType([], new MixedType(), false, isStatic: TrinaryLogic::createYes()),
				TrinaryLogic::createNo(),
			],
			'non-static closure accepts non-static closure' => [
				new ClosureType([], new MixedType(), false, isStatic: TrinaryLogic::createNo()),
				new ClosureType([], new MixedType(), false, isStatic: TrinaryLogic::createNo()),
				TrinaryLogic::createYes(),
			],
			'maybe-static closure accepts static closure' => [
				new ClosureType([], new MixedType(), false, isStatic: TrinaryLogic::createMaybe()),
				new ClosureType([], new MixedType(), false, isStatic: TrinaryLogic::createYes()),
				TrinaryLogic::createYes(),
			],
			'maybe-static closure accepts non-static closure' => [
				new ClosureType([], new MixedType(), false, isStatic: TrinaryLogic::createMaybe()),
				new ClosureType([], new MixedType(), false, isStatic: TrinaryLogic::createNo()),
				TrinaryLogic::createYes(),
			],
			'static closure maybe accepts maybe-static closure' => [
				new ClosureType([], new MixedType(), false, isStatic: TrinaryLogic::createYes()),
				new ClosureType([], new MixedType(), false, isStatic: TrinaryLogic::createMaybe()),
				TrinaryLogic::createMaybe(),
			],
			'non-static closure maybe accepts maybe-static closure' => [
				new ClosureType([], new MixedType(), false, isStatic: TrinaryLogic::createNo()),
				new ClosureType([], new MixedType(), false, isStatic: TrinaryLogic::createMaybe()),
				TrinaryLogic::createMaybe(),
			],
		];
	}

	#[DataProvider('dataAccepts')]
	public function testAccepts(
		Type $type,
		Type $otherType,
		TrinaryLogic $expectedResult,
	): void
	{
		$actualResult = $type->accepts($otherType, true);
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->result->describe(),
			sprintf('%s -> accepts(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise())),
		);
	}

	public function testIsStaticClosure(): void
	{
		$staticClosure = new ClosureType([], new MixedType(), false, isStatic: TrinaryLogic::createYes());
		$this->assertTrue($staticClosure->isStaticClosure()->yes());

		$nonStaticClosure = new ClosureType([], new MixedType(), false, isStatic: TrinaryLogic::createNo());
		$this->assertTrue($nonStaticClosure->isStaticClosure()->no());

		$maybeClosure = new ClosureType([], new MixedType(), false);
		$this->assertTrue($maybeClosure->isStaticClosure()->maybe());

		$defaultClosure = new ClosureType();
		$this->assertTrue($defaultClosure->isStaticClosure()->maybe());
	}

}
