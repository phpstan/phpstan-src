<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\AccessoryLiteralStringType;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Generic\GenericClassStringType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Generic\TemplateTypeVariance;
use stdClass;
use function sprintf;

class TypeToPhpDocNodeTest extends PHPStanTestCase
{

	public function dataToPhpDocNode(): iterable
	{
		yield [
			new ArrayType(new MixedType(), new MixedType()),
			'array',
		];

		yield [
			new ArrayType(new MixedType(), new IntegerType()),
			'array<int>',
		];

		yield [
			new ArrayType(new IntegerType(), new IntegerType()),
			'array<int, int>',
		];

		yield [
			new MixedType(),
			'mixed',
		];

		yield [
			new ObjectType(stdClass::class),
			'stdClass',
		];

		yield [
			new ConstantArrayType([
				new ConstantStringType('foo'),
				new ConstantStringType('bar'),
				new ConstantStringType('baz'),
				new ConstantStringType('$ref'),
			], [
				new ConstantIntegerType(1),
				new ConstantIntegerType(2),
				new ConstantIntegerType(3),
				new ConstantIntegerType(4),
			], [0], [2]),
			'array{foo: 1, bar: 2, baz?: 3, \'$ref\': 4}',
		];

		yield [
			new ConstantArrayType([
				new ConstantStringType('1100-RB'),
			], [
				new ConstantIntegerType(1),
			], [0]),
			"array{'1100-RB': 1}",
		];

		yield [
			new ConstantArrayType([
				new ConstantStringType('Karlovy Vary'),
			], [
				new ConstantIntegerType(1),
			], [0]),
			"array{'Karlovy Vary': 1}",
		];

		yield [
			new ObjectShapeType([
				'1100-RB' => new ConstantIntegerType(1),
			], []),
			"object{'1100-RB': 1}",
		];

		yield [
			new ConstantArrayType([
				new ConstantIntegerType(1),
				new ConstantIntegerType(2),
				new ConstantIntegerType(3),
			], [
				new ConstantStringType('foo'),
				new ConstantStringType('bar'),
				new ConstantStringType('baz'),
			], [0], [2]),
			'array{1: \'foo\', 2: \'bar\', 3?: \'baz\'}',
		];

		yield [
			new ConstantIntegerType(42),
			'42',
		];

		yield [
			new ConstantFloatType(2.5),
			'2.5',
		];

		yield [
			new ConstantBooleanType(true),
			'true',
		];

		yield [
			new ConstantBooleanType(false),
			'false',
		];

		yield [
			new ConstantStringType('foo'),
			"'foo'",
		];

		yield [
			new GenericClassStringType(new ObjectType('stdClass')),
			'class-string<stdClass>',
		];

		yield [
			new GenericObjectType('stdClass', [
				new ConstantIntegerType(1),
				new ConstantIntegerType(2),
			]),
			'stdClass<1, 2>',
		];

		yield [
			new GenericObjectType('stdClass', [
				new StringType(),
				new IntegerType(),
				new MixedType(),
			], null, null, [
				TemplateTypeVariance::createInvariant(),
				TemplateTypeVariance::createContravariant(),
				TemplateTypeVariance::createBivariant(),
			]),
			'stdClass<string, contravariant int, *>',
		];

		yield [
			new IterableType(new MixedType(), new MixedType()),
			'iterable',
		];

		yield [
			new IterableType(new MixedType(), new IntegerType()),
			'iterable<int>',
		];

		yield [
			new IterableType(new IntegerType(), new IntegerType()),
			'iterable<int, int>',
		];

		yield [
			new UnionType([new StringType(), new IntegerType()]),
			'(int | string)',
		];

		yield [
			new UnionType([new IntegerType(), new StringType()]),
			'(int | string)',
		];

		yield [
			new ObjectShapeType([
				'foo' => new ConstantIntegerType(1),
				'bar' => new StringType(),
				'baz' => new ConstantIntegerType(2),
			], ['baz']),
			'object{foo: 1, bar: string, baz?: 2}',
		];

		yield [
			new ConditionalType(
				new ObjectWithoutClassType(),
				new ObjectType('stdClass'),
				new IntegerType(),
				new StringType(),
				false,
			),
			'(object is stdClass ? int : string)',
		];

		yield [
			new ConditionalType(
				new ObjectWithoutClassType(),
				new ObjectType('stdClass'),
				new IntegerType(),
				new StringType(),
				true,
			),
			'(object is not stdClass ? int : string)',
		];

		yield [
			new IntersectionType([new StringType(), new AccessoryLiteralStringType()]),
			'literal-string',
		];

		yield [
			new IntersectionType([new StringType(), new AccessoryNonEmptyStringType()]),
			'non-empty-string',
		];

		yield [
			new IntersectionType([new StringType(), new AccessoryNumericStringType()]),
			'numeric-string',
		];

		yield [
			new IntersectionType([new StringType(), new AccessoryLiteralStringType(), new AccessoryNonEmptyStringType()]),
			'(literal-string & non-empty-string)',
		];

		yield [
			new IntersectionType([new ArrayType(new IntegerType(), new StringType()), new NonEmptyArrayType()]),
			'non-empty-array<int, string>',
		];

		yield [
			new IntersectionType([new ArrayType(IntegerRangeType::createAllGreaterThanOrEqualTo(0), new StringType()), new AccessoryArrayListType()]),
			'list<string>',
		];

		yield [
			new IntersectionType([new ArrayType(IntegerRangeType::createAllGreaterThanOrEqualTo(0), new StringType()), new NonEmptyArrayType(), new AccessoryArrayListType()]),
			'non-empty-list<string>',
		];

		yield [
			new IntersectionType([new ClassStringType(), new AccessoryLiteralStringType()]),
			'(class-string & literal-string)',
		];

		yield [
			new IntersectionType([new GenericClassStringType(new ObjectType('Foo')), new AccessoryLiteralStringType()]),
			'(class-string<Foo> & literal-string)',
		];

		yield [
			new IntersectionType([new ArrayType(IntegerRangeType::createAllGreaterThanOrEqualTo(0), new MixedType()), new AccessoryArrayListType()]),
			'list<mixed>',
		];

		yield [
			new IntersectionType([
				new ArrayType(IntegerRangeType::createAllGreaterThanOrEqualTo(0), new MixedType()),
				new NonEmptyArrayType(),
				new AccessoryArrayListType(),
			]),
			'non-empty-list<mixed>',
		];

		yield [
			new ConstantArrayType([
				new ConstantIntegerType(0),
				new ConstantIntegerType(1),
			], [
				new ConstantStringType('foo'),
				new ConstantStringType('bar'),
			]),
			"array{'foo', 'bar'}",
		];

		yield [
			new ConstantArrayType([
				new ConstantIntegerType(0),
				new ConstantIntegerType(2),
			], [
				new ConstantStringType('foo'),
				new ConstantStringType('bar'),
			]),
			"array{0: 'foo', 2: 'bar'}",
		];

		yield [
			new ConstantArrayType([
				new ConstantIntegerType(0),
				new ConstantIntegerType(1),
			], [
				new ConstantStringType('foo'),
				new ConstantStringType('bar'),
			], [2], [1]),
			"array{0: 'foo', 1?: 'bar'}",
		];
	}

	/**
	 * @dataProvider dataToPhpDocNode
	 */
	public function testToPhpDocNode(Type $type, string $expected): void
	{
		$phpDocNode = $type->toPhpDocNode();

		$typeString = (string) $phpDocNode;
		$this->assertSame($expected, $typeString);

		$typeStringResolver = self::getContainer()->getByType(TypeStringResolver::class);
		$parsedType = $typeStringResolver->resolve($typeString);
		$this->assertTrue($type->equals($parsedType), sprintf('%s->equals(%s)', $type->describe(VerbosityLevel::precise()), $parsedType->describe(VerbosityLevel::precise())));
	}

	public function dataToPhpDocNodeWithoutCheckingEquals(): iterable
	{
		yield [
			new ConstantStringType("foo\nbar\nbaz"),
			'(literal-string & non-falsy-string)',
		];

		yield [
			new ConstantFloatType(9223372036854775807),
			'9223372036854775808',
		];

		yield [
			new ConstantFloatType(-9223372036854775808),
			'-9223372036854775808',
		];

		yield [
			new ConstantFloatType(2.35),
			'2.35000000000000008882',
		];

		yield [
			new ConstantFloatType(100),
			'100',
		];
	}

	/**
	 * @dataProvider dataToPhpDocNodeWithoutCheckingEquals
	 */
	public function testToPhpDocNodeWithoutCheckingEquals(Type $type, string $expected): void
	{
		$phpDocNode = $type->toPhpDocNode();

		$typeString = (string) $phpDocNode;
		$this->assertSame($expected, $typeString);

		$typeStringResolver = self::getContainer()->getByType(TypeStringResolver::class);
		$typeStringResolver->resolve($typeString);
	}

	public function dataFromTypeStringToPhpDocNode(): iterable
	{
		foreach ($this->dataToPhpDocNode() as [, $typeString]) {
			yield [$typeString];
		}

		yield ['callable'];
		yield ['callable(Foo): Bar'];
		yield ['callable(Foo=, Bar=): Bar'];
		yield ['Closure(Foo=, Bar=): Bar'];

		yield ['callable(Foo $foo): Bar'];
		yield ['callable(Foo $foo=, Bar $bar=): Bar'];
		yield ['Closure(Foo $foo=, Bar $bar=): Bar'];
		yield ['Closure(Foo $foo=, Bar $bar=): (Closure(Foo): Bar)'];
	}

	/**
	 * @dataProvider dataFromTypeStringToPhpDocNode
	 */
	public function testFromTypeStringToPhpDocNode(string $typeString): void
	{
		$typeStringResolver = self::getContainer()->getByType(TypeStringResolver::class);
		$type = $typeStringResolver->resolve($typeString);
		$this->assertSame($typeString, (string) $type->toPhpDocNode());

		$typeAgain = $typeStringResolver->resolve((string) $type->toPhpDocNode());
		$this->assertTrue($type->equals($typeAgain));
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/../../../conf/bleedingEdge.neon',
		];
	}

}
