<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use ArrayIterator;
use Countable;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\IterableType;
use PHPStan\Type\KeyOfType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectShapeType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;
use Traversable;
use function get_class;
use function sprintf;

class TemplateTypeEqualsTest extends PHPStanTestCase
{

	/**
	 * @return iterable<string, array{Type}>
	 */
	public static function dataBounds(): iterable
	{
		yield 'object' => [new ObjectWithoutClassType()];
		yield 'class' => [new ObjectType(stdClass::class)];
		yield 'generic object' => [new GenericObjectType(ArrayIterator::class, [new IntegerType(), new StringType()])];
		yield 'string' => [new StringType()];
		yield 'int' => [new IntegerType()];
		yield 'float' => [new FloatType()];
		yield 'bool' => [new BooleanType()];
		yield 'array' => [new ArrayType(new MixedType(), new MixedType())];
		yield 'iterable' => [new IterableType(new MixedType(), new MixedType())];
		yield 'mixed' => [new MixedType(true)];
		yield 'null' => [new NullType()];
		yield 'constant int' => [new ConstantIntegerType(1)];
		yield 'constant string' => [new ConstantStringType('a')];
		yield 'constant array' => [new ConstantArrayType([new ConstantIntegerType(0)], [new IntegerType()])];
		yield 'union' => [new UnionType([new StringType(), new IntegerType()])];
		yield 'benevolent union' => [new BenevolentUnionType([new StringType(), new IntegerType()])];
		yield 'intersection' => [new IntersectionType([new ObjectType(Countable::class), new ObjectType(Traversable::class)])];
		yield 'object shape' => [new ObjectShapeType(['a' => new IntegerType()], [])];
		yield 'key-of' => [new KeyOfType(new ConstantArrayType([new ConstantIntegerType(0)], [new IntegerType()]))];
	}

	#[DataProvider('dataBounds')]
	public function testBoundDoesNotEqualTemplateType(Type $bound): void
	{
		$templateType = TemplateTypeFactory::create(
			TemplateTypeScope::createWithFunction('foo'),
			'T',
			$bound,
			TemplateTypeVariance::createInvariant(),
		);
		$this->assertNotSame(get_class($bound), get_class($templateType));

		$this->assertFalse($bound->equals($templateType), sprintf('%s->equals(%s)', get_class($bound), get_class($templateType)));
		$this->assertFalse($templateType->equals($bound), sprintf('%s->equals(%s)', get_class($templateType), get_class($bound)));
		$this->assertTrue($bound->equals($bound));
		$this->assertTrue($templateType->equals($templateType));
	}

}
