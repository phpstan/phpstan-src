<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\PhpDocParser\Printer\Printer;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeFactory;
use PHPStan\Type\Generic\TemplateTypeScope;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPUnit\Framework\Attributes\DataProvider;

class LateResolvableObjectShapeTypeTest extends PHPStanTestCase
{

	private static function templateType(?Type $bound = null): Type
	{
		return TemplateTypeFactory::create(
			TemplateTypeScope::createWithFunction('doFoo'),
			'TKey',
			$bound ?? new StringType(),
			TemplateTypeVariance::createInvariant(),
		);
	}

	public function testConstantKeysResolveImmediately(): void
	{
		$type = LateResolvableObjectShapeType::create([
			[new ConstantStringType('a'), new IntegerType(), false],
		]);

		$this->assertInstanceOf(ObjectShapeType::class, $type);
		$this->assertSame('object{a: int}', $type->describe(VerbosityLevel::precise()));
	}

	public function testNumericStringKeysStayOptional(): void
	{
		$type = LateResolvableObjectShapeType::create([
			[new ConstantStringType('123'), new IntegerType(), true],
		]);

		$this->assertInstanceOf(ObjectShapeType::class, $type);
		$this->assertSame('object{123?: int}', $type->describe(VerbosityLevel::precise()));
	}

	/**
	 * @return iterable<string, array{Type, string}>
	 */
	public static function dataDescribe(): iterable
	{
		$templateType = self::templateType();

		yield 'required key' => [
			LateResolvableObjectShapeType::create([
				[$templateType, new IntegerType(), false],
			]),
			'object{TKey: int}',
		];

		yield 'optional key' => [
			LateResolvableObjectShapeType::create([
				[$templateType, new IntegerType(), true],
			]),
			'object{TKey?: int}',
		];

		yield 'next to constant keys' => [
			LateResolvableObjectShapeType::create([
				[new ConstantStringType('a'), new IntegerType(), false],
				[$templateType, new BooleanType(), false],
				[new ConstantStringType('foo bar'), new StringType(), false],
			]),
			'object{a: int, TKey: bool, \'foo bar\': string}',
		];
	}

	#[DataProvider('dataDescribe')]
	public function testDescribe(Type $type, string $expectedDescription): void
	{
		$this->assertInstanceOf(LateResolvableObjectShapeType::class, $type);
		$this->assertFalse($type->isResolvable());
		$this->assertSame($expectedDescription, $type->describe(VerbosityLevel::precise()));
		$this->assertSame($expectedDescription, (new Printer())->print($type->toPhpDocNode()));
	}

	/**
	 * @return iterable<string, array{Type, Type, string}>
	 */
	public static function dataResolve(): iterable
	{
		$templateType = self::templateType();

		yield 'constant key' => [
			LateResolvableObjectShapeType::create([
				[$templateType, new IntegerType(), false],
			]),
			new ConstantStringType('a'),
			'object{a: int}',
		];

		yield 'optional constant key' => [
			LateResolvableObjectShapeType::create([
				[$templateType, new IntegerType(), true],
			]),
			new ConstantStringType('a'),
			'object{a?: int}',
		];

		yield 'union of constant keys makes each of them optional' => [
			LateResolvableObjectShapeType::create([
				[$templateType, new IntegerType(), false],
			]),
			new UnionType([new ConstantStringType('a'), new ConstantStringType('b')]),
			'object{a?: int, b?: int}',
		];

		yield 'key resolved to an existing property wins like a later written key' => [
			LateResolvableObjectShapeType::create([
				[new ConstantStringType('a'), new IntegerType(), true],
				[$templateType, new BooleanType(), false],
			]),
			new ConstantStringType('a'),
			'object{a?: bool}',
		];

		yield 'written key after a key resolved to the same property wins' => [
			LateResolvableObjectShapeType::create([
				[$templateType, new BooleanType(), false],
				[new ConstantStringType('a'), new IntegerType(), false],
			]),
			new ConstantStringType('a'),
			'object{a: int}',
		];

		yield 'union of keys over an existing property might be either value' => [
			LateResolvableObjectShapeType::create([
				[new ConstantStringType('a'), new BooleanType(), false],
				[$templateType, new IntegerType(), false],
			]),
			new UnionType([new ConstantStringType('a'), new ConstantStringType('b')]),
			'object{a: bool|int, b?: int}',
		];

		yield 'non-constant key leaves the other properties alone' => [
			LateResolvableObjectShapeType::create([
				[new ConstantStringType('a'), new IntegerType(), false],
				[$templateType, new BooleanType(), false],
			]),
			new StringType(),
			'object{a: int}',
		];

		yield 'only the string part of a key names a property' => [
			LateResolvableObjectShapeType::create([
				[$templateType, new IntegerType(), false],
			]),
			new UnionType([new ConstantStringType('a'), new ConstantIntegerType(1)]),
			'object{a?: int}',
		];

		yield 'non-constant key alone degrades to object' => [
			LateResolvableObjectShapeType::create([
				[$templateType, new IntegerType(), false],
			]),
			new StringType(),
			'object',
		];

		yield 'integer key cannot be a property name' => [
			LateResolvableObjectShapeType::create([
				[$templateType, new IntegerType(), false],
			]),
			new ConstantIntegerType(1),
			'*ERROR*',
		];

		yield 'object key cannot be a property name' => [
			LateResolvableObjectShapeType::create([
				[$templateType, new IntegerType(), false],
			]),
			new ObjectType('stdClass'),
			'*ERROR*',
		];
	}

	#[DataProvider('dataResolve')]
	public function testResolve(Type $type, Type $resolvedTemplateType, string $expectedDescription): void
	{
		$resolved = TypeTraverser::map($type, static function (Type $type, callable $traverse) use ($resolvedTemplateType): Type {
			if ($type instanceof TemplateType) {
				return $resolvedTemplateType;
			}

			return $traverse($type);
		});

		$this->assertNotInstanceOf(LateResolvableObjectShapeType::class, $resolved);
		$this->assertSame($expectedDescription, $resolved->describe(VerbosityLevel::precise()));
	}

	public function testResolvesToTemplateTypeBoundsWhenTheTemplateTypeStays(): void
	{
		$type = LateResolvableObjectShapeType::create([
			[self::templateType(new UnionType([new ConstantStringType('a'), new ConstantStringType('b')])), new IntegerType(), false],
		]);
		$this->assertInstanceOf(LateResolvableObjectShapeType::class, $type);
		$this->assertFalse($type->isResolvable());

		// the shape is asked about itself before the template types get
		// substituted - it degrades to the bounds instead of leaking template
		// types into the resulting object shape
		$this->assertSame('object{a?: int, b?: int}', $type->resolve()->describe(VerbosityLevel::precise()));
	}

	public function testTraverseSimultaneouslyVisitsKeysAndValues(): void
	{
		$left = LateResolvableObjectShapeType::create([
			[self::templateType(), new IntegerType(), false],
		]);
		$right = LateResolvableObjectShapeType::create([
			[self::templateType(new IntegerType()), new StringType(), false],
		]);

		$visited = [];
		$left->traverseSimultaneously($right, static function (Type $left, Type $right) use (&$visited): Type {
			$visited[] = [
				$left->describe(VerbosityLevel::typeOnly()),
				$right->describe(VerbosityLevel::typeOnly()),
			];

			return $left;
		});

		$this->assertSame(
			[
				['TKey of string', 'TKey of int'],
				['int', 'string'],
			],
			$visited,
		);
	}

	public function testEquals(): void
	{
		$shape = LateResolvableObjectShapeType::create([[self::templateType(), new IntegerType(), false]]);

		$this->assertTrue($shape->equals(LateResolvableObjectShapeType::create([[self::templateType(), new IntegerType(), false]])));
		$this->assertFalse($shape->equals(LateResolvableObjectShapeType::create([[self::templateType(), new IntegerType(), true]])));
		$this->assertFalse($shape->equals(LateResolvableObjectShapeType::create([[self::templateType(), new BooleanType(), false]])));
		$this->assertFalse($shape->equals(LateResolvableObjectShapeType::create([[self::templateType(new ConstantStringType('a')), new IntegerType(), false]])));
		$this->assertFalse($shape->equals(LateResolvableObjectShapeType::create([
			[self::templateType(), new IntegerType(), false],
			[new ConstantStringType('a'), new IntegerType(), false],
		])));
	}

}
