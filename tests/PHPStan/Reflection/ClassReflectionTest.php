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
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use WrongClassConstantFile\SecuredRouter;
use function array_map;
use function array_values;
use const PHP_VERSION_ID;

class ClassReflectionTest extends PHPStanTestCase
{

	public function dataHasTraitUse(): array
	{
		return [
			[Foo::class, true],
			[Bar::class, true],
			[Baz::class, false],
		];
	}

	/**
	 * @dataProvider dataHasTraitUse
	 * @param class-string $className
	 */
	public function testHasTraitUse(string $className, bool $has): void
	{
		$reflectionProvider = $this->createReflectionProvider();
		$classReflection = $reflectionProvider->getClass($className);
		$this->assertSame($has, $classReflection->hasTraitUse(FooTrait::class));
	}

	public function dataClassHierarchyDistances(): array
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
	 * @dataProvider dataClassHierarchyDistances
	 * @param class-string $class
	 * @param int[] $expectedDistances
	 */
	public function testClassHierarchyDistances(
		string $class,
		array $expectedDistances,
	): void
	{
		$reflectionProvider = $this->createReflectionProvider();
		$classReflection = $reflectionProvider->getClass($class);
		$this->assertSame(
			$expectedDistances,
			$classReflection->getClassHierarchyDistances(),
		);
	}

	public function testVariadicTraitMethod(): void
	{
		$reflectionProvider = $this->createReflectionProvider();
		$fooReflection = $reflectionProvider->getClass(Foo::class);
		$variadicMethod = $fooReflection->getNativeMethod('variadicMethod');
		$methodVariant = $variadicMethod->getOnlyVariant();
		$this->assertTrue($methodVariant->isVariadic());
	}

	public function testGenericInheritance(): void
	{
		$reflectionProvider = $this->createReflectionProvider();
		$reflection = $reflectionProvider->getClass(C::class);

		$this->assertSame('GenericInheritance\\C', $reflection->getDisplayName());

		$parent = $reflection->getParentClass();
		$this->assertNotNull($parent);

		$this->assertSame('GenericInheritance\\C0<DateTime>', $parent->getDisplayName());

		$this->assertSame([
			'GenericInheritance\\I<DateTime>',
			'GenericInheritance\\I0<DateTime>',
			'GenericInheritance\\I1<int>',
		], array_map(static fn (ClassReflection $r): string => $r->getDisplayName(), array_values($reflection->getInterfaces())));
	}

	public function testIsGenericWithStubPhpDoc(): void
	{
		$reflectionProvider = $this->createReflectionProvider();
		$reflection = $reflectionProvider->getClass(ReflectionClass::class);
		$this->assertTrue($reflection->isGeneric());
	}

	public function dataIsAttributeClass(): array
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

	/**
	 * @dataProvider dataIsAttributeClass
	 */
	public function testIsAttributeClass(string $className, bool $expected, int $expectedFlags = Attribute::TARGET_ALL): void
	{
		$reflectionProvider = $this->createReflectionProvider();
		$reflection = $reflectionProvider->getClass($className);
		$this->assertSame($expected, $reflection->isAttributeClass());
		if (!$expected) {
			return;
		}
		$this->assertSame($expectedFlags, $reflection->getAttributeClassFlags());
	}

	public function testDeprecatedConstantFromAnotherFile(): void
	{
		$reflectionProvider = $this->createReflectionProvider();
		$reflection = $reflectionProvider->getClass(SecuredRouter::class);
		$constant = $reflection->getConstant('SECURED');
		$this->assertTrue($constant->isDeprecated()->yes());
	}

	/**
	 * @dataProvider dataNestedRecursiveTraits
	 * @param class-string $className
	 * @param array<class-string, class-string> $expected
	 */
	public function testGetTraits(string $className, array $expected, bool $recursive): void
	{
		$reflectionProvider = $this->createReflectionProvider();

		$this->assertSame(
			array_map(
				static fn (ClassReflection $classReflection): string => $classReflection->getNativeReflection()->getName(),
				$reflectionProvider->getClass($className)->getTraits($recursive),
			),
			$expected,
		);
	}

	public function dataNestedRecursiveTraits(): array
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

	public function testEnumIsFinal(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$reflectionProvider = $this->createReflectionProvider();
		$enum = $reflectionProvider->getClass('PHPStan\Fixture\TestEnum');
		$this->assertTrue($enum->isEnum());
		$this->assertInstanceOf('ReflectionEnum', $enum->getNativeReflection());
		$this->assertTrue($enum->isFinal());
		$this->assertTrue($enum->isFinalByKeyword());
	}

	public function testBackedEnumType(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$reflectionProvider = $this->createReflectionProvider();
		$enum = $reflectionProvider->getClass('PHPStan\Fixture\TestEnum');
		$this->assertInstanceOf(IntegerType::class, $enum->getBackedEnumType());
	}

	public function testIs(): void
	{
		$className = static::class;

		$reflectionProvider = $this->createReflectionProvider();
		$classReflection = $reflectionProvider->getClass($className);

		$this->assertTrue($classReflection->is($className));
		$this->assertTrue($classReflection->is(PHPStanTestCase::class));
		$this->assertTrue($classReflection->is(TestCase::class));
		$this->assertFalse($classReflection->is(RuleTestCase::class));
	}

}
