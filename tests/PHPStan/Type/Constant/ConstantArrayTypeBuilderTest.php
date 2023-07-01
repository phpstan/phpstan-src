<?php declare(strict_types = 1);

namespace PHPStan\Type\Constant;

use PHPStan\Type\BooleanType;
use PHPStan\Type\NullType;
use PHPStan\Type\StringType;
use PHPStan\Type\VerbosityLevel;
use PHPUnit\Framework\TestCase;

class ConstantArrayTypeBuilderTest extends TestCase
{

	public function testOptionalKeysNextAutoIndex(): void
	{
		$builder = ConstantArrayTypeBuilder::createEmpty();
		$builder->setOffsetValueType(null, new ConstantIntegerType(1));

		$array1 = $builder->getArray();
		$this->assertInstanceOf(ConstantArrayType::class, $array1);
		$this->assertSame('array{1}', $array1->describe(VerbosityLevel::precise()));
		$this->assertSame([1], $array1->getNextAutoIndexes());

		$builder->setOffsetValueType(null, new ConstantIntegerType(2), true);
		$array2 = $builder->getArray();
		$this->assertInstanceOf(ConstantArrayType::class, $array2);
		$this->assertSame('array{0: 1, 1?: 2}', $array2->describe(VerbosityLevel::precise()));
		$this->assertSame([1, 2], $array2->getNextAutoIndexes());

		$builder->setOffsetValueType(null, new ConstantIntegerType(3));
		$array3 = $builder->getArray();
		$this->assertInstanceOf(ConstantArrayType::class, $array3);
		$this->assertSame('array{0: 1, 1: 2|3, 2?: 3}', $array3->describe(VerbosityLevel::precise()));
		$this->assertSame([2, 3], $array3->getNextAutoIndexes());

		$this->assertTrue($array3->isKeysSupersetOf($array2));
		$array2MergedWith3 = $array3->mergeWith($array2);
		$this->assertSame('array{0: 1, 1?: 2|3, 2?: 3}', $array2MergedWith3->describe(VerbosityLevel::precise()));
		$this->assertSame([1, 2, 3], $array2MergedWith3->getNextAutoIndexes());

		$builder->setOffsetValueType(null, new ConstantIntegerType(4));
		$array4 = $builder->getArray();
		$this->assertInstanceOf(ConstantArrayType::class, $array4);
		$this->assertSame('array{0: 1, 1: 2|3, 2: 3|4, 3?: 4}', $array4->describe(VerbosityLevel::precise()));
		$this->assertSame([3, 4], $array4->getNextAutoIndexes());

		$builder->setOffsetValueType(new ConstantIntegerType(3), new ConstantIntegerType(5), true);
		$array5 = $builder->getArray();
		$this->assertInstanceOf(ConstantArrayType::class, $array5);
		$this->assertSame('array{0: 1, 1: 2|3, 2: 3|4, 3?: 4|5}', $array5->describe(VerbosityLevel::precise()));
		$this->assertSame([3, 4], $array5->getNextAutoIndexes());

		$builder->setOffsetValueType(new ConstantIntegerType(3), new ConstantIntegerType(6));
		$array6 = $builder->getArray();
		$this->assertInstanceOf(ConstantArrayType::class, $array6);
		$this->assertSame('array{1, 2|3, 3|4, 6}', $array6->describe(VerbosityLevel::precise()));
		$this->assertSame([4], $array6->getNextAutoIndexes());
	}

	public function testNextAutoIndex(): void
	{
		$builder = ConstantArrayTypeBuilder::createFromConstantArray(new ConstantArrayType(
			[new ConstantIntegerType(0)],
			[new ConstantStringType('foo')],
			[1],
		));
		$builder->setOffsetValueType(new ConstantIntegerType(0), new ConstantStringType('bar'));
		$array = $builder->getArray();
		$this->assertInstanceOf(ConstantArrayType::class, $array);
		$this->assertSame('array{\'bar\'}', $array->describe(VerbosityLevel::precise()));
		$this->assertSame([1], $array->getNextAutoIndexes());
	}

	public function testNextAutoIndexAnother(): void
	{
		$builder = ConstantArrayTypeBuilder::createFromConstantArray(new ConstantArrayType(
			[new ConstantIntegerType(0)],
			[new ConstantStringType('foo')],
			[1],
		));
		$builder->setOffsetValueType(new ConstantIntegerType(1), new ConstantStringType('bar'));
		$array = $builder->getArray();
		$this->assertInstanceOf(ConstantArrayType::class, $array);
		$this->assertSame('array{\'foo\', \'bar\'}', $array->describe(VerbosityLevel::precise()));
		$this->assertSame([2], $array->getNextAutoIndexes());
	}

	public function testAppendingOptionalKeys(): void
	{
		$builder = ConstantArrayTypeBuilder::createEmpty();

		$builder->setOffsetValueType(null, new BooleanType(), true);
		$this->assertSame('array{0?: bool}', $builder->getArray()->describe(VerbosityLevel::precise()));

		$builder->setOffsetValueType(null, new NullType(), true);
		$this->assertSame('array{0?: bool|null, 1?: null}', $builder->getArray()->describe(VerbosityLevel::precise()));

		$builder->setOffsetValueType(null, new ConstantIntegerType(17));
		$this->assertSame('array{0: 17|bool|null, 1?: 17|null, 2?: 17}', $builder->getArray()->describe(VerbosityLevel::precise()));
	}

	public function testDegradedArrayIsNotAlwaysOversized(): void
	{
		$builder = ConstantArrayTypeBuilder::createEmpty();
		$builder->degradeToGeneralArray();
		for ($i = 0; $i < 300; $i++) {
			$builder->setOffsetValueType(new StringType(), new StringType());
		}

		$array = $builder->getArray();
		$this->assertSame('non-empty-array<string, string>', $array->describe(VerbosityLevel::precise()));
	}

	public function testIsList(): void
	{
		$builder = ConstantArrayTypeBuilder::createEmpty();

		$builder->setOffsetValueType(null, new ConstantIntegerType(0));
		$this->assertTrue($builder->isList());

		$builder->setOffsetValueType(new ConstantIntegerType(0), new NullType());
		$this->assertTrue($builder->isList());

		$builder->setOffsetValueType(new ConstantIntegerType(1), new NullType(), true);
		$this->assertTrue($builder->isList());

		$builder->setOffsetValueType(new ConstantIntegerType(2), new NullType(), true);
		$this->assertFalse($builder->isList());
	}

}
