<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Fixture\TestDecimal;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

class TestDecimalOperatorTypeSpecifyingExtensionTest extends TestCase
{

	#[DataProvider('dataSigilAndSidesProvider')]
	public function testSupportsMatchingSigilsAndSides(string $sigil, Type $leftType, Type $rightType): void
	{
		$extension = new TestDecimalOperatorTypeSpecifyingExtension();

		$result = $extension->isOperatorSupported($sigil, $leftType, $rightType);

		self::assertTrue($result);
	}

	public static function dataSigilAndSidesProvider(): iterable
	{
		yield '+' => [
			'+',
			new ObjectType(TestDecimal::class),
			new ObjectType(TestDecimal::class),
		];

		yield '-' => [
			'-',
			new ObjectType(TestDecimal::class),
			new ObjectType(TestDecimal::class),
		];

		yield '*' => [
			'*',
			new ObjectType(TestDecimal::class),
			new ObjectType(TestDecimal::class),
		];

		yield '/' => [
			'/',
			new ObjectType(TestDecimal::class),
			new ObjectType(TestDecimal::class),
		];

		yield '^' => [
			'^',
			new ObjectType(TestDecimal::class),
			new ObjectType(TestDecimal::class),
		];

		yield '**' => [
			'**',
			new ObjectType(TestDecimal::class),
			new ObjectType(TestDecimal::class),
		];
	}

	#[DataProvider('dataNotMatchingSidesProvider')]
	public function testNotSupportsNotMatchingSides(string $sigil, Type $leftType, Type $rightType): void
	{
		$extension = new TestDecimalOperatorTypeSpecifyingExtension();

		$result = $extension->isOperatorSupported($sigil, $leftType, $rightType);

		self::assertFalse($result);
	}

	public static function dataNotMatchingSidesProvider(): iterable
	{
		yield 'left' => [
			'+',
			new ObjectType(stdClass::class),
			new ObjectType(TestDecimal::class),
		];

		yield 'right' => [
			'+',
			new ObjectType(TestDecimal::class),
			new ObjectType(stdClass::class),
		];
	}

}
