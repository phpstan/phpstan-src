<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use Attribute;
use Attributes\IsAttribute;
use Attributes\IsAttribute2;
use Attributes\IsAttribute3;
use Attributes\IsNotAttribute;
use GenericInheritance\C;
use HasTraitUse\Bar;
use HasTraitUse\Baz;
use HasTraitUse\Foo;
use HasTraitUse\FooTrait;
use HierarchyDistances\ExtendedIpsumInterface;
use HierarchyDistances\FirstIpsumInterface;
use HierarchyDistances\FirstLoremInterface;
use HierarchyDistances\Ipsum;
use HierarchyDistances\Lorem;
use HierarchyDistances\SecondIpsumInterface;
use HierarchyDistances\SecondLoremInterface;
use HierarchyDistances\ThirdIpsumInterface;
use HierarchyDistances\TraitOne;
use HierarchyDistances\TraitThree;
use HierarchyDistances\TraitTwo;
use NestedTraits\BarTrait;
use NestedTraits\BazChild;
use NestedTraits\BazTrait;
use NestedTraits\NoTrait;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Testing\RuleTestCase;
use PHPStan\Type\IntegerType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use WrongClassConstantFile\SecuredRouter;
use function array_map;
use function array_values;

class ClassReflectionTest extends PHPStanTestCase
{

	public static function dataHasTraitUse(): array
	{
		return [
			[Foo::class, true],
			[Bar::class, true],
			[Baz::class, false],
		];
	}

	/**
	 * @param class-string $className
	 */
	#[DataProvider('dataHasTraitUse')]
	public function testHasTraitUse(string $className, bool $has): void
	{
		$reflectionProvider = self::createReflectionProvider();
		$classReflection = $reflectionProvider->getClass($className);
		self::assertSame($has, $classReflection->hasTraitUse(FooTrait::class));
	}

	public static function dataClassHierarchyDistances(): array
	{
		return [
			[
				Lorem::class,
				[
					Lorem::class => 0,
					TraitTwo::class => 1,
					TraitThree::class => 2,
					FirstLoremInterface::class => 3,
					SecondLoremInterface::class => 4,
				],
			],
			[
				Ipsum::class,
				[
					Ipsum::class => 0,
					TraitOne::class => 1,
					Lorem::class => 2,
					TraitTwo::class => 3,
					TraitThree::class => 4,
					FirstLoremInterface::class => 5,
					SecondLoremInterface::class => 6,
					FirstIpsumInterface::class => 7,
					ExtendedIpsumInterface::class => 8,
					SecondIpsumInterface::class => 9,
					ThirdIpsumInterface::class => 10,
				],
			],
		];
	}

	/**
	 * @param class-string $class
	 * @param int[] $expectedDistances
	 */
	#[DataProvider('dataClassHierarchyDistances')]
	public function testClassHierarchyDistances(
		string $class,
		array $expectedDistances,
	): void
	{
		$reflectionProvider = self::createReflectionProvider();
		$classReflection = $reflectionProvider->getClass($class);
		self::assertSame(
			$expectedDistances,
			$classReflection->getClassHierarchyDistances(),
		);
	}

	public function testVariadicTraitMethod(): void
	{
		$reflectionProvider = self::createReflectionProvider();
		$fooReflection = $reflectionProvider->getClass(Foo::class);
		$variadicMethod = $fooReflection->getNativeMethod('variadicMethod');
		$methodVariant = $variadicMethod->getOnlyVariant();
		self::assertTrue($methodVariant->isVariadic());
	}

	public function testGenericInheritance(): void
	{
		$reflectionProvider = self::createReflectionProvider();
		$reflection = $reflectionProvider->getClass(C::class);

		self::assertSame('GenericInheritance\\C', $reflection->getDisplayName());

		$parent = $reflection->getParentClass();
		self::assertNotNull($parent);

		self::assertSame('GenericInheritance\\C0<DateTime>', $parent->getDisplayName());

		self::assertSame([
			'GenericInheritance\\I<DateTime>',
			'GenericInheritance\\I0<DateTime>',
			'GenericInheritance\\I1<int>',
		], array_map(static fn (ClassReflection $r): string => $r->getDisplayName(), array_values($reflection->getInterfaces())));
	}

	public function testIsGenericWithStubPhpDoc(): void
	{
		$reflectionProvider = self::createReflectionProvider();
		$reflection = $reflectionProvider->getClass(ReflectionClass::class);
		self::assertTrue($reflection->isGeneric());
	}

	public static function dataIsAttributeClass(): array
	{
		return [
			[
				IsNotAttribute::class,
				false,
			],
			[
				IsAttribute::class,
				true,
			],
			[
				IsAttribute2::class,
				true,
				Attribute::IS_REPEATABLE,
			],
			[
				IsAttribute3::class,
				true,
				Attribute::IS_REPEATABLE | Attribute::TARGET_PROPERTY,
			],
		];
	}

	#[RequiresPhp('>= 8.0')]
	#[DataProvider('dataIsAttributeClass')]
	public function testIsAttributeClass(string $className, bool $expected, int $expectedFlags = Attribute::TARGET_ALL): void
	{
		$reflectionProvider = self::createReflectionProvider();
		$reflection = $reflectionProvider->getClass($className);
		self::assertSame($expected, $reflection->isAttributeClass());
		if (!$expected) {
			return;
		}
		self::assertSame($expectedFlags, $reflection->getAttributeClassFlags());
	}

	public function testDeprecatedConstantFromAnotherFile(): void
	{
		$reflectionProvider = self::createReflectionProvider();
		$reflection = $reflectionProvider->getClass(SecuredRouter::class);
		$constant = $reflection->getConstant('SECURED');
		self::assertTrue($constant->isDeprecated()->yes());
	}

	/**
	 * @param class-string $className
	 * @param array<class-string, class-string> $expected
	 */
	#[DataProvider('dataNestedRecursiveTraits')]
	public function testGetTraits(string $className, array $expected, bool $recursive): void
	{
		$reflectionProvider = self::createReflectionProvider();

		self::assertSame(
			array_map(
				static fn (ClassReflection $classReflection): string => $classReflection->getNativeReflection()->getName(),
				$reflectionProvider->getClass($className)->getTraits($recursive),
			),
			$expected,
		);
	}

	public static function dataNestedRecursiveTraits(): array
	{
		return [
			[
				NoTrait::class,
				[],
				false,
			],
			[
				NoTrait::class,
				[],
				true,
			],
			[
				\NestedTraits\Foo::class,
				[
					\NestedTraits\FooTrait::class => \NestedTraits\FooTrait::class,
				],
				false,
			],
			[
				\NestedTraits\Foo::class,
				[
					\NestedTraits\FooTrait::class => \NestedTraits\FooTrait::class,
				],
				true,
			],
			[
				\NestedTraits\Bar::class,
				[
					BarTrait::class => BarTrait::class,
				],
				false,
			],
			[
				\NestedTraits\Bar::class,
				[
					BarTrait::class => BarTrait::class,
					\NestedTraits\FooTrait::class => \NestedTraits\FooTrait::class,
				],
				true,
			],
			[
				\NestedTraits\Baz::class,
				[
					BazTrait::class => BazTrait::class,
				],
				false,
			],
			[
				\NestedTraits\Baz::class,
				[
					BazTrait::class => BazTrait::class,
					BarTrait::class => BarTrait::class,
					\NestedTraits\FooTrait::class => \NestedTraits\FooTrait::class,
				],
				true,
			],
			[
				BazChild::class,
				[],
				false,
			],
			[
				BazChild::class,
				[
					BazTrait::class => BazTrait::class,
					BarTrait::class => BarTrait::class,
					\NestedTraits\FooTrait::class => \NestedTraits\FooTrait::class,
				],
				true,
			],
		];
	}

	#[RequiresPhp('>= 8.1')]
	public function testEnumIsFinal(): void
	{
		$reflectionProvider = self::createReflectionProvider();
		$enum = $reflectionProvider->getClass('PHPStan\Fixture\TestEnum');
		self::assertTrue($enum->isEnum());

		// @phpstan-ignore-next-line Exact error differs on PHP 7.4 and others
		self::assertInstanceOf('ReflectionEnum', $enum->getNativeReflection());
		self::assertTrue($enum->isFinal());
		self::assertTrue($enum->isFinalByKeyword());
	}

	#[RequiresPhp('>= 8.1')]
	public function testBackedEnumType(): void
	{
		$reflectionProvider = self::createReflectionProvider();
		$enum = $reflectionProvider->getClass('PHPStan\Fixture\TestEnum');
		self::assertInstanceOf(IntegerType::class, $enum->getBackedEnumType());
	}

	public function testIs(): void
	{
		$className = static::class;

		$reflectionProvider = self::createReflectionProvider();
		$classReflection = $reflectionProvider->getClass($className);

		self::assertTrue($classReflection->is($className));
		self::assertTrue($classReflection->is(PHPStanTestCase::class));
		self::assertTrue($classReflection->is(TestCase::class));
		self::assertFalse($classReflection->is(RuleTestCase::class));
	}

	public static function dataDeprecatedAttribute(): iterable
	{
		yield ['DeprecatedAttrOnClass\Foo', false];
		yield ['DeprecatedAttributeOnTrait\DeprTrait', true];
	}

	#[DataProvider('dataDeprecatedAttribute')]
	public function testDeprecatedAttribute(string $className, bool $expected): void
	{
		$reflectionProvider = self::createReflectionProvider();
		$classReflection = $reflectionProvider->getClass($className);
		self::assertSame($expected, $classReflection->isDeprecated());
	}

}
