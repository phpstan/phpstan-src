<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\ShouldNotHappenException;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\IntegerRangeType;
use PHPUnit\Framework\Attributes\DataProvider;

class TypeSpecifierContextTest extends PHPStanTestCase
{

	public static function dataContext(): array
	{
		return [
			[
				TypeSpecifierContext::createTrue(),
				[true, true, false, false, false],
			],
			[
				TypeSpecifierContext::createTruthy(),
				[true, true, false, false, false],
			],
			[
				TypeSpecifierContext::createFalse(),
				[false, false, true, true, false],
			],
			[
				TypeSpecifierContext::createFalsey(),
				[false, false, true, true, false],
			],
			[
				TypeSpecifierContext::createNull(),
				[false, false, false, false, true],
			],
		];
	}

	/**
	 * @param bool[] $results
	 */
	#[DataProvider('dataContext')]
	public function testContext(TypeSpecifierContext $context, array $results): void
	{
		$this->assertSame($results[0], $context->true());
		$this->assertSame($results[1], $context->truthy());
		$this->assertSame($results[2], $context->false());
		$this->assertSame($results[3], $context->falsey());
		$this->assertSame($results[4], $context->null());
	}

	public static function dataNegate(): array
	{
		return [
			[
				TypeSpecifierContext::createTrue()->negate(),
				[false, true, true, true, false],
			],
			[
				TypeSpecifierContext::createTruthy()->negate(),
				[false, false, true, true, false],
			],
			[
				TypeSpecifierContext::createFalse()->negate(),
				[true, true, false, true, false],
			],
			[
				TypeSpecifierContext::createFalsey()->negate(),
				[true, true, false, false, false],
			],
		];
	}

	/**
	 * @param bool[] $results
	 */
	#[DataProvider('dataNegate')]
	public function testNegate(TypeSpecifierContext $context, array $results): void
	{
		$this->assertSame($results[0], $context->true());
		$this->assertSame($results[1], $context->truthy());
		$this->assertSame($results[2], $context->false());
		$this->assertSame($results[3], $context->falsey());
		$this->assertSame($results[4], $context->null());
	}

	public function testNegateNull(): void
	{
		$this->expectException(ShouldNotHappenException::class);
		TypeSpecifierContext::createNull()->negate();
	}

	public function testConditionTypeNullByDefault(): void
	{
		$this->assertNull(TypeSpecifierContext::createTrue()->getNarrowedReturnType());
		$this->assertNull(TypeSpecifierContext::createTruthy()->getNarrowedReturnType());
		$this->assertNull(TypeSpecifierContext::createFalsey()->getNarrowedReturnType());
		$this->assertNull(TypeSpecifierContext::createNull()->getNarrowedReturnType());
	}

	public function testWithConditionType(): void
	{
		$narrowedReturnType = IntegerRangeType::createAllGreaterThanOrEqualTo(2);
		$context = TypeSpecifierContext::createTruthy()->withNarrowedReturnType($narrowedReturnType);

		$this->assertSame($narrowedReturnType, $context->getNarrowedReturnType());

		// the bitmask-derived bool accessors are unaffected by the narrowed return type
		$this->assertTrue($context->true());
		$this->assertTrue($context->truthy());
		$this->assertFalse($context->false());
		$this->assertFalse($context->falsey());
		$this->assertFalse($context->null());
	}

	public function testNegateDropsConditionType(): void
	{
		$context = TypeSpecifierContext::createTruthy()->withNarrowedReturnType(IntegerRangeType::createAllGreaterThanOrEqualTo(2));

		$this->assertNull($context->negate()->getNarrowedReturnType());
	}

}
