<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\Accessory\AccessoryLiteralStringType;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\ClassStringType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Generic\GenericClassStringType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

class TypeDescriptionTest extends PHPStanTestCase
{

	public static function dataTest(): iterable
	{
		yield ['string', new StringType()];
		yield ['array', new ArrayType(new MixedType(), new MixedType())];
		yield ['literal-string', new IntersectionType([new StringType(), new AccessoryLiteralStringType()])];
		yield ['non-empty-string', new IntersectionType([new StringType(), new AccessoryNonEmptyStringType()])];
		yield ['numeric-string', new IntersectionType([new StringType(), new AccessoryNumericStringType()])];
		yield ['literal-string&non-empty-string', new IntersectionType([new StringType(), new AccessoryLiteralStringType(), new AccessoryNonEmptyStringType()])];
		yield ['non-empty-array', new IntersectionType([new ArrayType(new MixedType(), new MixedType()), new NonEmptyArrayType()])];
		yield ['non-empty-array<int, string>', new IntersectionType([new ArrayType(new IntegerType(), new StringType()), new NonEmptyArrayType()])];
		yield ['class-string&literal-string', new IntersectionType([new ClassStringType(), new AccessoryLiteralStringType()])];
		yield ['class-string<Foo>&literal-string', new IntersectionType([new GenericClassStringType(new ObjectType('Foo')), new AccessoryLiteralStringType()])];

		$builder = ConstantArrayTypeBuilder::createEmpty();
		$builder->setOffsetValueType(new ConstantStringType('foo'), new IntegerType());
		yield ['array{foo: int}', $builder->getArray()];

		$builder = ConstantArrayTypeBuilder::createEmpty();
		$builder->setOffsetValueType(new ConstantStringType('foo'), new IntegerType(), true);
		yield ['array{foo?: int}', $builder->getArray()];

		$builder = ConstantArrayTypeBuilder::createEmpty();
		$builder->setOffsetValueType(new ConstantStringType('foo'), new IntegerType(), true);
		$builder->setOffsetValueType(new ConstantStringType('bar'), new StringType());
		yield ['array{foo?: int, bar: string}', $builder->getArray()];

		$builder = ConstantArrayTypeBuilder::createEmpty();
		$builder->setOffsetValueType(null, new IntegerType());
		$builder->setOffsetValueType(null, new StringType());
		yield ['array{int, string}', $builder->getArray()];

		$builder = ConstantArrayTypeBuilder::createEmpty();
		$builder->setOffsetValueType(null, new IntegerType());
		$builder->setOffsetValueType(null, new StringType(), true);
		yield ['array{0: int, 1?: string}', $builder->getArray()];

		$builder = ConstantArrayTypeBuilder::createEmpty();
		$builder->setOffsetValueType(new ConstantStringType('\'foo\''), new IntegerType());
		yield ['array{"\'foo\'": int}', $builder->getArray()];

		$builder = ConstantArrayTypeBuilder::createEmpty();
		$builder->setOffsetValueType(new ConstantStringType('"foo"'), new IntegerType());
		yield ['array{\'"foo"\': int}', $builder->getArray()];
	}

	/**
	 * @dataProvider dataTest
	 */
	public function testParsingDesiredTypeDescription(string $description, Type $expectedType): void
	{
		$typeStringResolver = self::getContainer()->getByType(TypeStringResolver::class);
		$type = $typeStringResolver->resolve($description);
		$this->assertTrue($expectedType->equals($type), sprintf('Parsing %s did not result in %s, but in %s', $description, $expectedType->describe(VerbosityLevel::value()), $type->describe(VerbosityLevel::value())));

		$newDescription = $type->describe(VerbosityLevel::value());
		$newType = $typeStringResolver->resolve($newDescription);
		$this->assertTrue($type->equals($newType), sprintf('Parsing %s again did not result in %s, but in %s', $newDescription, $type->describe(VerbosityLevel::value()), $newType->describe(VerbosityLevel::value())));
	}

	/**
	 * @dataProvider dataTest
	 */
	public function testDesiredTypeDescription(string $description, Type $expectedType): void
	{
		$this->assertSame($description, $expectedType->describe(VerbosityLevel::value()));

		$typeStringResolver = self::getContainer()->getByType(TypeStringResolver::class);
		$type = $typeStringResolver->resolve($description);
		$this->assertSame($description, $type->describe(VerbosityLevel::value()));
	}

}
