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
use PHPStan\Broker\Broker;
use PHPStan\Php\PhpVersion;
use PHPStan\PhpDoc\PhpDocInheritanceResolver;
use PHPStan\PhpDoc\StubPhpDocProvider;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\FileTypeMapper;
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
		$broker = $this->createMock(Broker::class);
		$fileTypeMapper = $this->createMock(FileTypeMapper::class);
		$stubPhpDocProvider = $this->createMock(StubPhpDocProvider::class);
		$phpDocInheritanceResolver = $this->createMock(PhpDocInheritanceResolver::class);
		$classReflection = new ClassReflection($broker, $fileTypeMapper, $stubPhpDocProvider, $phpDocInheritanceResolver, new PhpVersion(PHP_VERSION_ID), [], [], $className, new ReflectionClass($className), null, null, null);
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
				PHP_VERSION_ID < 70400 ?
				[
					Ipsum::class => 0,
					TraitOne::class => 1,
					Lorem::class => 2,
					TraitTwo::class => 3,
					TraitThree::class => 4,
					SecondLoremInterface::class => 5,
					FirstLoremInterface::class => 6,
					FirstIpsumInterface::class => 7,
					ExtendedIpsumInterface::class => 8,
					SecondIpsumInterface::class => 9,
					ThirdIpsumInterface::class => 10,
				]
				:
				[
					Ipsum::class => 0,
					TraitOne::class => 1,
					Lorem::class => 2,
					TraitTwo::class => 3,
					TraitThree::class => 4,
					FirstLoremInterface::class => 5,
					SecondLoremInterface::class => 6,
					FirstIpsumInterface::class => 7,
					SecondIpsumInterface::class => 8,
					ThirdIpsumInterface::class => 9,
					ExtendedIpsumInterface::class => 10,
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
		array $expectedDistances
	): void
	{
		$broker = $this->createReflectionProvider();
		$fileTypeMapper = $this->createMock(FileTypeMapper::class);
		$stubPhpDocProvider = $this->createMock(StubPhpDocProvider::class);
		$phpDocInheritanceResolver = $this->createMock(PhpDocInheritanceResolver::class);

		$classReflection = new ClassReflection(
			$broker,
			$fileTypeMapper,
			$stubPhpDocProvider,
			$phpDocInheritanceResolver,
			new PhpVersion(PHP_VERSION_ID),
			[],
			[],
			$class,
			new ReflectionClass($class),
			null,
			null,
			null,
		);
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
		$methodVariant = ParametersAcceptorSelector::selectSingle($variadicMethod->getVariants());
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
		], array_map(static function (ClassReflection $r): string {
			return $r->getDisplayName();
		}, array_values($reflection->getInterfaces())));
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
		if (!self::$useStaticReflectionProvider && PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}
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
				static function (ClassReflection $classReflection): string {
					return $classReflection->getNativeReflection()->getName();
				},
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

}
