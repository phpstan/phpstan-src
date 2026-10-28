<?php declare(strict_types = 1);

namespace PHPStan\Type;

use Closure;
use Countable;
use PhpParser\Node;
use PhpParser\Node\ComplexType;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\NullableType;
use PHPStan\Php\PhpVersion;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\HasMethodType;
use PHPStan\Type\Accessory\HasOffsetType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Generic\TemplateTypeFactory;
use PHPStan\Type\Generic\TemplateTypeScope;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;
use Traversable;
use function array_map;
use function implode;
use function sprintf;

class TypeToNativePhpTypeNodeTest extends PHPStanTestCase
{

	/**
	 * @return iterable<array{Type, NativeTypePosition, string|null, string|null}>
	 */
	public static function dataToNativePhpTypeNode(): iterable
	{
		$php70 = new PhpVersion(70000);
		$php74 = new PhpVersion(70400);
		$php80 = new PhpVersion(80000);
		$php81 = new PhpVersion(80100);
		$php82 = new PhpVersion(80200);
		$php83 = new PhpVersion(80300);

		// scalars are widened, the round trip gives the widened type
		yield [new IntegerType(), NativeTypePosition::parameter($php80), 'int', 'int'];
		yield [IntegerRangeType::fromInterval(1, 5), NativeTypePosition::parameter($php80), 'int', 'int'];
		yield [new ConstantStringType('foo'), NativeTypePosition::returnType($php80), 'string', 'string'];
		yield [new IntersectionType([new StringType(), new AccessoryNonEmptyStringType()]), NativeTypePosition::parameter($php80), 'string', 'string'];
		yield [TypeCombinator::union(new ConstantStringType('a'), new ConstantStringType('b')), NativeTypePosition::parameter($php74), 'string', 'string'];
		yield [TypeCombinator::union(new ConstantIntegerType(1), new ConstantIntegerType(2), new NullType()), NativeTypePosition::parameter($php74), '?int', 'int|null'];

		// arrays and iterables
		yield [new ConstantArrayType([new ConstantStringType('foo')], [new IntegerType()]), NativeTypePosition::parameter($php80), 'array', 'array'];
		yield [new IntersectionType([new ArrayType(new IntegerType(), new StringType()), new AccessoryArrayListType()]), NativeTypePosition::returnType($php80), 'array', 'array'];
		yield [new IterableType(new MixedType(), new MixedType()), NativeTypePosition::parameter($php80), 'iterable', 'iterable'];

		// mixed
		yield [new MixedType(true), NativeTypePosition::parameter($php80), 'mixed', 'mixed'];
		yield [new MixedType(true), NativeTypePosition::parameter($php74), null, null];
		yield [new MixedType(), NativeTypePosition::parameter($php80), null, null];

		// objects
		yield [new ObjectType(stdClass::class), NativeTypePosition::parameter($php70), '\stdClass', 'stdClass'];
		yield [new ObjectWithoutClassType(), NativeTypePosition::parameter($php74), 'object', 'object'];
		yield [new HasMethodType('foo'), NativeTypePosition::parameter($php74), null, null];
		yield [new IntersectionType([new ObjectWithoutClassType(), new HasMethodType('foo')]), NativeTypePosition::parameter($php74), 'object', 'object'];
		yield [new IntersectionType([new ObjectType(stdClass::class), new HasMethodType('foo')]), NativeTypePosition::parameter($php80), '\stdClass', 'stdClass'];
		yield [new ClosureType(), NativeTypePosition::property($php74), '\Closure', Closure::class];

		// nullable is preferred over a union with null
		yield [TypeCombinator::addNull(new ObjectType(stdClass::class)), NativeTypePosition::parameter($php80), '?\stdClass', 'stdClass|null'];
		yield [TypeCombinator::addNull(new ObjectType(stdClass::class)), NativeTypePosition::parameter($php70), null, null];
		yield [TypeCombinator::addNull(new BooleanType()), NativeTypePosition::property($php80), '?bool', 'bool|null'];
		yield [TypeCombinator::union(new IntegerType(), new StringType(), new NullType()), NativeTypePosition::parameter($php80), 'int|string|null', 'int|string|null'];

		// unions
		yield [TypeCombinator::union(new IntegerType(), new StringType()), NativeTypePosition::parameter($php74), null, null];
		yield [TypeCombinator::union(new IntegerType(), new StringType()), NativeTypePosition::parameter($php80), 'int|string', 'int|string'];
		yield [TypeCombinator::union(new StringType(), new ConstantBooleanType(false)), NativeTypePosition::returnType($php80), 'string|false', 'string|false'];

		// true, false and null
		yield [new ConstantBooleanType(false), NativeTypePosition::returnType($php81), 'bool', 'bool'];
		yield [new ConstantBooleanType(false), NativeTypePosition::returnType($php82), 'false', 'false'];
		yield [TypeCombinator::addNull(new ConstantBooleanType(false)), NativeTypePosition::returnType($php81), '?bool', 'bool|null'];
		yield [TypeCombinator::addNull(new ConstantBooleanType(false)), NativeTypePosition::returnType($php82), '?false', 'false|null'];
		yield [new NullType(), NativeTypePosition::returnType($php81), null, null];
		yield [new NullType(), NativeTypePosition::returnType($php82), 'null', 'null'];

		// callable is not allowed in properties
		yield [new CallableType(), NativeTypePosition::parameter($php80), 'callable', 'callable(): mixed'];
		yield [new CallableType(), NativeTypePosition::property($php80), null, null];
		yield [TypeCombinator::addNull(new CallableType()), NativeTypePosition::property($php80), null, null];
		yield [TypeCombinator::addNull(new CallableType()), NativeTypePosition::returnType($php80), '?callable', '(callable(): mixed)|null'];

		// return-only types
		yield [new VoidType(), NativeTypePosition::returnType($php74), 'void', 'void'];
		yield [new VoidType(), NativeTypePosition::parameter($php80), null, null];
		yield [new NeverType(true), NativeTypePosition::returnType($php80), null, null];
		yield [new NonAcceptingNeverType(), NativeTypePosition::returnType($php81), 'never', 'never'];

		// declaration positions
		yield [new IntegerType(), NativeTypePosition::property(new PhpVersion(70300)), null, null];
		yield [new IntegerType(), NativeTypePosition::property($php74), 'int', 'int'];
		yield [new IntegerType(), NativeTypePosition::classConstant($php82), null, null];
		yield [new IntegerType(), NativeTypePosition::classConstant($php83), 'int', 'int'];

		// intersections, and DNF since PHP 8.2
		$countableTraversable = new IntersectionType([new ObjectType(Countable::class), new ObjectType(Traversable::class)]);
		yield [$countableTraversable, NativeTypePosition::parameter($php80), null, null];
		yield [$countableTraversable, NativeTypePosition::parameter($php81), '\Countable&\Traversable', 'Countable&Traversable'];
		yield [TypeCombinator::addNull($countableTraversable), NativeTypePosition::parameter($php81), null, null];
		yield [TypeCombinator::addNull($countableTraversable), NativeTypePosition::parameter($php82), '(\Countable&\Traversable)|null', '(Countable&Traversable)|null'];
		yield [TypeCombinator::union($countableTraversable, new StringType()), NativeTypePosition::returnType($php82), '(\Countable&\Traversable)|string', '(Countable&Traversable)|string'];

		// resources cannot be declared
		yield [new ResourceType(), NativeTypePosition::parameter($php80), null, null];

		// only a certain string, array, object or null widens; maybe-string/array/object accessories alone do not
		yield [new IntersectionType([new HasOffsetType(new ConstantIntegerType(0)), new HasOffsetType(new ConstantIntegerType(1))]), NativeTypePosition::returnType($php82), null, null];
		$templateMixed = TemplateTypeFactory::create(TemplateTypeScope::createWithFunction('foo'), 'T', new MixedType(), TemplateTypeVariance::createInvariant());
		yield [new UnionType([new IntegerType(), $templateMixed]), NativeTypePosition::returnType($php82), null, null];
	}

	#[DataProvider('dataToNativePhpTypeNode')]
	public function testToNativePhpTypeNode(Type $type, NativeTypePosition $position, ?string $expected, ?string $expectedRoundTrip): void
	{
		$node = $type->toNativePhpTypeNode($position);
		$this->assertSame($expected, $node !== null ? self::print($node) : null);
		if ($node === null) {
			return;
		}

		$roundTripType = ParserNodeTypeToPHPStanType::resolve($node, null);
		$this->assertSame($expectedRoundTrip, $roundTripType->describe(VerbosityLevel::precise()));
		$this->assertTrue(
			$roundTripType->isSuperTypeOf($type)->yes(),
			sprintf('%s is not a supertype of %s', $roundTripType->describe(VerbosityLevel::precise()), $type->describe(VerbosityLevel::precise())),
		);
	}

	public function testStatic(): void
	{
		$classReflection = self::createReflectionProvider()->getClass(stdClass::class);
		$staticType = new StaticType($classReflection);

		$this->assertSame('static', self::print($staticType->toNativePhpTypeNode(NativeTypePosition::returnType(new PhpVersion(80000)))));
		$this->assertSame('self', self::print($staticType->toNativePhpTypeNode(NativeTypePosition::returnType(new PhpVersion(70400)))));
		$this->assertSame('self', self::print($staticType->toNativePhpTypeNode(NativeTypePosition::parameter(new PhpVersion(80000)))));

		$roundTripType = ParserNodeTypeToPHPStanType::resolve($staticType->toNativePhpTypeNode(NativeTypePosition::returnType(new PhpVersion(80000))), $classReflection);
		$this->assertTrue($roundTripType->equals($staticType));
	}

	public function testTrue(): void
	{
		$this->assertSame('bool', self::print((new ConstantBooleanType(true))->toNativePhpTypeNode(NativeTypePosition::parameter(new PhpVersion(80100)))));
		$this->assertSame('true', self::print((new ConstantBooleanType(true))->toNativePhpTypeNode(NativeTypePosition::parameter(new PhpVersion(80200)))));
	}

	private static function print(Identifier|Name|ComplexType|null $node): string
	{
		if ($node instanceof FullyQualified) {
			return $node->toCodeString();
		}

		if ($node instanceof Identifier || $node instanceof Name) {
			return $node->toString();
		}

		if ($node instanceof NullableType) {
			return '?' . self::print($node->type);
		}

		if ($node instanceof Node\UnionType) {
			return implode('|', array_map(static fn (Node $member): string => $member instanceof Node\IntersectionType ? '(' . self::print($member) . ')' : self::print($member), $node->types));
		}

		if ($node instanceof Node\IntersectionType) {
			return implode('&', array_map(static fn (Node $member): string => self::print($member), $node->types));
		}

		return 'null';
	}

}
