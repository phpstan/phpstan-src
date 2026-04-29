<?php declare(strict_types = 1);

namespace PHPStan\Type\Constant;

use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\BooleanType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\NullType;
use PHPStan\Type\StringType;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\VerbosityLevel;
use function sprintf;
use const PHP_INT_MAX;

class ConstantArrayTypeBuilderTest extends PHPStanTestCase
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
		$this->assertSame('list{0: 1, 1?: 2|3, 2?: 3}', $array2MergedWith3->describe(VerbosityLevel::precise()));
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
		$this->assertSame('list{0?: bool|null, 1?: null}', $builder->getArray()->describe(VerbosityLevel::precise()));

		$builder->setOffsetValueType(null, new ConstantIntegerType(17));
		$this->assertSame('list{0: 17|bool|null, 1?: 17|null, 2?: 17}', $builder->getArray()->describe(VerbosityLevel::precise()));
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

	public function testDegradesWhileDisableArrayDegradation(): void
	{
		$builder = ConstantArrayTypeBuilder::createEmpty();
		$builder->disableArrayDegradation();
		for ($i = 0; $i < 30; $i++) {
			$builder->setOffsetValueType(new StringType(), new ConstantIntegerType($i));
		}
		$builder->setOffsetValueType(new StringType(), new IntegerType());

		$array = $builder->getArray();
		$this->assertSame('non-empty-array<string, int>', $array->describe(VerbosityLevel::precise()));
	}

	public function testDisableArrayDegradation(): void
	{
		$builder = ConstantArrayTypeBuilder::createEmpty();
		$builder->disableArrayDegradation();
		for ($i = 0; $i < 300; $i++) {
			$builder->setOffsetValueType(new ConstantIntegerType($i), new ConstantIntegerType($i));
		}

		$array = $builder->getArray();
		$this->assertSame('array{0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47, 48, 49, 50, 51, 52, 53, 54, 55, 56, 57, 58, 59, 60, 61, 62, 63, 64, 65, 66, 67, 68, 69, 70, 71, 72, 73, 74, 75, 76, 77, 78, 79, 80, 81, 82, 83, 84, 85, 86, 87, 88, 89, 90, 91, 92, 93, 94, 95, 96, 97, 98, 99, 100, 101, 102, 103, 104, 105, 106, 107, 108, 109, 110, 111, 112, 113, 114, 115, 116, 117, 118, 119, 120, 121, 122, 123, 124, 125, 126, 127, 128, 129, 130, 131, 132, 133, 134, 135, 136, 137, 138, 139, 140, 141, 142, 143, 144, 145, 146, 147, 148, 149, 150, 151, 152, 153, 154, 155, 156, 157, 158, 159, 160, 161, 162, 163, 164, 165, 166, 167, 168, 169, 170, 171, 172, 173, 174, 175, 176, 177, 178, 179, 180, 181, 182, 183, 184, 185, 186, 187, 188, 189, 190, 191, 192, 193, 194, 195, 196, 197, 198, 199, 200, 201, 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215, 216, 217, 218, 219, 220, 221, 222, 223, 224, 225, 226, 227, 228, 229, 230, 231, 232, 233, 234, 235, 236, 237, 238, 239, 240, 241, 242, 243, 244, 245, 246, 247, 248, 249, 250, 251, 252, 253, 254, 255, 256, 257, 258, 259, 260, 261, 262, 263, 264, 265, 266, 267, 268, 269, 270, 271, 272, 273, 274, 275, 276, 277, 278, 279, 280, 281, 282, 283, 284, 285, 286, 287, 288, 289, 290, 291, 292, 293, 294, 295, 296, 297, 298, 299}', $array->describe(VerbosityLevel::precise()));
	}

	public function testArrayDegradation(): void
	{
		$builder = ConstantArrayTypeBuilder::createEmpty();
		for ($i = 0; $i < 300; $i++) {
			$builder->setOffsetValueType(new ConstantIntegerType($i), new ConstantIntegerType($i));
		}

		$array = $builder->getArray();
		$this->assertSame('non-empty-array<0|1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17|18|19|20|21|22|23|24|25|26|27|28|29|30|31|32|33|34|35|36|37|38|39|40|41|42|43|44|45|46|47|48|49|50|51|52|53|54|55|56|57|58|59|60|61|62|63|64|65|66|67|68|69|70|71|72|73|74|75|76|77|78|79|80|81|82|83|84|85|86|87|88|89|90|91|92|93|94|95|96|97|98|99|100|101|102|103|104|105|106|107|108|109|110|111|112|113|114|115|116|117|118|119|120|121|122|123|124|125|126|127|128|129|130|131|132|133|134|135|136|137|138|139|140|141|142|143|144|145|146|147|148|149|150|151|152|153|154|155|156|157|158|159|160|161|162|163|164|165|166|167|168|169|170|171|172|173|174|175|176|177|178|179|180|181|182|183|184|185|186|187|188|189|190|191|192|193|194|195|196|197|198|199|200|201|202|203|204|205|206|207|208|209|210|211|212|213|214|215|216|217|218|219|220|221|222|223|224|225|226|227|228|229|230|231|232|233|234|235|236|237|238|239|240|241|242|243|244|245|246|247|248|249|250|251|252|253|254|255|256|257|258|259|260|261|262|263|264|265|266|267|268|269|270|271|272|273|274|275|276|277|278|279|280|281|282|283|284|285|286|287|288|289|290|291|292|293|294|295|296|297|298|299, 0|1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17|18|19|20|21|22|23|24|25|26|27|28|29|30|31|32|33|34|35|36|37|38|39|40|41|42|43|44|45|46|47|48|49|50|51|52|53|54|55|56|57|58|59|60|61|62|63|64|65|66|67|68|69|70|71|72|73|74|75|76|77|78|79|80|81|82|83|84|85|86|87|88|89|90|91|92|93|94|95|96|97|98|99|100|101|102|103|104|105|106|107|108|109|110|111|112|113|114|115|116|117|118|119|120|121|122|123|124|125|126|127|128|129|130|131|132|133|134|135|136|137|138|139|140|141|142|143|144|145|146|147|148|149|150|151|152|153|154|155|156|157|158|159|160|161|162|163|164|165|166|167|168|169|170|171|172|173|174|175|176|177|178|179|180|181|182|183|184|185|186|187|188|189|190|191|192|193|194|195|196|197|198|199|200|201|202|203|204|205|206|207|208|209|210|211|212|213|214|215|216|217|218|219|220|221|222|223|224|225|226|227|228|229|230|231|232|233|234|235|236|237|238|239|240|241|242|243|244|245|246|247|248|249|250|251|252|253|254|255|256|257|258|259|260|261|262|263|264|265|266|267|268|269|270|271|272|273|274|275|276|277|278|279|280|281|282|283|284|285|286|287|288|289|290|291|292|293|294|295|296|297|298|299>&oversized-array', $array->describe(VerbosityLevel::precise()));
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

	public function testIsListWithUnion(): void
	{
		$builder = ConstantArrayTypeBuilder::createEmpty();

		$builder->setOffsetValueType(null, new ConstantIntegerType(0));
		$this->assertTrue($builder->isList());

		$builder->setOffsetValueType(new ConstantIntegerType(0), new NullType());
		$this->assertTrue($builder->isList());

		$builder->setOffsetValueType(new ConstantIntegerType(1), new NullType());
		$this->assertTrue($builder->isList());

		$builder->setOffsetValueType(new ConstantIntegerType(2), new NullType());
		$this->assertTrue($builder->isList());

		$oneOrZero = TypeCombinator::union(
			new ConstantIntegerType(0),
			new ConstantIntegerType(1),
		);

		$builder->setOffsetValueType($oneOrZero, new NullType());
		$this->assertTrue($builder->isList());

		$oneOrFour = TypeCombinator::union(
			new ConstantIntegerType(1),
			new ConstantIntegerType(4),
		);

		$builder->setOffsetValueType($oneOrFour, new NullType());
		$this->assertFalse($builder->isList());
	}

	public function testAppendToBuilderWithEmptyNextAutoIndexes(): void
	{
		$builder = ConstantArrayTypeBuilder::createFromConstantArray(new ConstantArrayType(
			[new ConstantIntegerType(PHP_INT_MAX)],
			[new ConstantIntegerType(4)],
			[],
		));

		$builder->setOffsetValueType(null, new ConstantIntegerType(5));

		$array = $builder->getArray();
		$this->assertInstanceOf(ConstantArrayType::class, $array);
		$this->assertSame(sprintf('array{%d: 4}', PHP_INT_MAX), $array->describe(VerbosityLevel::precise()));
		$this->assertSame([], $array->getNextAutoIndexes());
	}

	public function testAddIntegerOffsetToBuilderWithEmptyNextAutoIndexes(): void
	{
		$builder = ConstantArrayTypeBuilder::createFromConstantArray(new ConstantArrayType(
			[new ConstantIntegerType(PHP_INT_MAX)],
			[new ConstantIntegerType(4)],
			[],
		));

		$builder->setOffsetValueType(new ConstantIntegerType(5), new ConstantStringType('x'));

		$array = $builder->getArray();
		$this->assertInstanceOf(ConstantArrayType::class, $array);
		$this->assertSame(sprintf("array{%d: 4, 5: 'x'}", PHP_INT_MAX), $array->describe(VerbosityLevel::precise()));
		$this->assertSame([], $array->getNextAutoIndexes());
		$this->assertFalse($builder->isList());
	}

	public function testAddIntegerUnionOffsetToBuilderWithEmptyNextAutoIndexes(): void
	{
		$builder = ConstantArrayTypeBuilder::createFromConstantArray(new ConstantArrayType(
			[new ConstantIntegerType(PHP_INT_MAX)],
			[new ConstantIntegerType(4)],
			[],
		));

		$twoOrThree = TypeCombinator::union(
			new ConstantIntegerType(2),
			new ConstantIntegerType(3),
		);
		$builder->setOffsetValueType($twoOrThree, new ConstantStringType('x'));

		$array = $builder->getArray();
		$this->assertInstanceOf(ConstantArrayType::class, $array);
		$this->assertSame(sprintf("array{%d: 4, 2?: 'x', 3?: 'x'}", PHP_INT_MAX), $array->describe(VerbosityLevel::precise()));
		$this->assertSame([], $array->getNextAutoIndexes());
	}

	public function testSetOffsetValueTypeOnConstantArrayWithEmptyNextAutoIndexesReturnsErrorType(): void
	{
		$arrayType = new ConstantArrayType(
			[new ConstantIntegerType(PHP_INT_MAX)],
			[new ConstantIntegerType(4)],
			[],
		);

		$result = $arrayType->setOffsetValueType(null, new ConstantIntegerType(5));
		$this->assertInstanceOf(ErrorType::class, $result);
	}

	public function testNonOptionalUnionOffsetOnEmptyArrayIsNonEmpty(): void
	{
		$builder = ConstantArrayTypeBuilder::createEmpty();

		$aOrB = TypeCombinator::union(
			new ConstantStringType('a'),
			new ConstantStringType('b'),
		);
		$builder->setOffsetValueType($aOrB, new ConstantIntegerType(1));

		$array = $builder->getArray();
		$this->assertSame('non-empty-array{a?: 1, b?: 1}', $array->describe(VerbosityLevel::precise()));
	}

	public function testOptionalSingleOffsetOnEmptyArrayIsPossiblyEmpty(): void
	{
		$builder = ConstantArrayTypeBuilder::createEmpty();
		$builder->setOffsetValueType(new ConstantStringType('a'), new ConstantIntegerType(1), true);

		$array = $builder->getArray();
		$this->assertSame('array{a?: 1}', $array->describe(VerbosityLevel::precise()));
	}

	public function testOptionalUnionOffsetOnEmptyArrayIsPossiblyEmpty(): void
	{
		$builder = ConstantArrayTypeBuilder::createEmpty();

		$aOrB = TypeCombinator::union(
			new ConstantStringType('a'),
			new ConstantStringType('b'),
		);
		$builder->setOffsetValueType($aOrB, new ConstantIntegerType(1), true);

		$array = $builder->getArray();
		$this->assertSame('array{a?: 1, b?: 1}', $array->describe(VerbosityLevel::precise()));
	}

	public function testOptionalNullOffsetOnEmptyArrayIsPossiblyEmpty(): void
	{
		$builder = ConstantArrayTypeBuilder::createEmpty();
		$builder->setOffsetValueType(null, new ConstantIntegerType(1), true);

		$array = $builder->getArray();
		$this->assertSame('array{0?: 1}', $array->describe(VerbosityLevel::precise()));
	}

}
