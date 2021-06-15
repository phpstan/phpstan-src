<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use Attributes\IsAttribute;
use Attributes\IsAttribute2;
use Attributes\IsAttribute3;
use Attributes\IsNotAttribute;
use PHPStan\Broker\Broker;
use PHPStan\Php\PhpVersion;
use PHPStan\Type\FileTypeMapper;
use WrongClassConstantFile\SecuredRouter;

class ClassReflectionTest extends \PHPStan\Testing\TestCase
{

	public function dataHasTraitUse(): array
	{
		return [
			[\HasTraitUse\Foo::class, true],
			[\HasTraitUse\Bar::class, true],
			[\HasTraitUse\Baz::class, false],
		];
	}

	/**
	 * @dataProvider dataHasTraitUse
	 * @param class-string $className
	 * @param bool $has
	 */
	public function testHasTraitUse(string $className, bool $has): void
	{
		$broker = $this->createMock(Broker::class);
		$fileTypeMapper = $this->createMock(FileTypeMapper::class);
		$classReflection = new ClassReflection($broker, $fileTypeMapper, new PhpVersion(PHP_VERSION_ID), [], [], $className, new \ReflectionClass($className), null, null, null);
		$this->assertSame($has, $classReflection->hasTraitUse(\HasTraitUse\FooTrait::class));
	}

	public function dataClassHierarchyDistances(): array
	{
		return [
			[
				\HierarchyDistances\Lorem::class,
				[
					\HierarchyDistances\Lorem::class => 0,
					\HierarchyDistances\TraitTwo::class => 1,
					\HierarchyDistances\TraitThree::class => 2,
					\HierarchyDistances\FirstLoremInterface::class => 3,
					\HierarchyDistances\SecondLoremInterface::class => 4,
				],
			],
			[
				\HierarchyDistances\Ipsum::class,
				PHP_VERSION_ID < 70400 ?
				[
					\HierarchyDistances\Ipsum::class => 0,
					\HierarchyDistances\TraitOne::class => 1,
					\HierarchyDistances\Lorem::class => 2,
					\HierarchyDistances\TraitTwo::class => 3,
					\HierarchyDistances\TraitThree::class => 4,
					\HierarchyDistances\SecondLoremInterface::class => 5,
					\HierarchyDistances\FirstLoremInterface::class => 6,
					\HierarchyDistances\FirstIpsumInterface::class => 7,
					\HierarchyDistances\ExtendedIpsumInterface::class => 8,
					\HierarchyDistances\SecondIpsumInterface::class => 9,
					\HierarchyDistances\ThirdIpsumInterface::class => 10,
				]
				:
				[
					\HierarchyDistances\Ipsum::class => 0,
					\HierarchyDistances\TraitOne::class => 1,
					\HierarchyDistances\Lorem::class => 2,
					\HierarchyDistances\TraitTwo::class => 3,
					\HierarchyDistances\TraitThree::class => 4,
					\HierarchyDistances\FirstLoremInterface::class => 5,
					\HierarchyDistances\SecondLoremInterface::class => 6,
					\HierarchyDistances\FirstIpsumInterface::class => 7,
					\HierarchyDistances\SecondIpsumInterface::class => 8,
					\HierarchyDistances\ThirdIpsumInterface::class => 9,
					\HierarchyDistances\ExtendedIpsumInterface::class => 10,
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

		$classReflection = new ClassReflection(
			$broker,
			$fileTypeMapper,
			new PhpVersion(PHP_VERSION_ID),
			[],
			[],
			$class,
			new \ReflectionClass($class),
			null,
			null,
			null
		);
		$this->assertSame(
			$expectedDistances,
			$classReflection->getClassHierarchyDistances()
		);
	}

	public function testVariadicTraitMethod(): void
	{
		/** @var Broker $broker */
		$broker = self::getContainer()->getService('broker');
		$fooReflection = $broker->getClass(\HasTraitUse\Foo::class);
		$variadicMethod = $fooReflection->getNativeMethod('variadicMethod');
		$methodVariant = ParametersAcceptorSelector::selectSingle($variadicMethod->getVariants());
		$this->assertTrue($methodVariant->isVariadic());
	}

	public function testGenericInheritance(): void
	{
		/** @var Broker $broker */
		$broker = self::getContainer()->getService('broker');
		$reflection = $broker->getClass(\GenericInheritance\C::class);

		$this->assertSame('GenericInheritance\\C', $reflection->getDisplayName());

		$parent = $reflection->getParentClass();
		$this->assertNotFalse($parent);

		$this->assertSame('GenericInheritance\\C0<DateTime>', $parent->getDisplayName());

		$this->assertSame([
			'GenericInheritance\\I0<DateTime>',
			'GenericInheritance\\I1<int>',
			'GenericInheritance\\I<DateTime>',
		], array_map(static function (ClassReflection $r): string {
			return $r->getDisplayName();
		}, array_values($reflection->getInterfaces())));
	}

	public function testGenericInheritanceOverride(): void
	{
		/** @var Broker $broker */
		$broker = self::getContainer()->getService('broker');
		$reflection = $broker->getClass(\GenericInheritance\Override::class);

		$this->assertSame([
			'GenericInheritance\\I0<DateTimeInterface>',
			'GenericInheritance\\I1<int>',
			'GenericInheritance\\I<DateTimeInterface>',
		], array_map(static function (ClassReflection $r): string {
			return $r->getDisplayName();
		}, array_values($reflection->getInterfaces())));
	}

	public function testIsGenericWithStubPhpDoc(): void
	{
		/** @var Broker $broker */
		$broker = self::getContainer()->getService('broker');
		$reflection = $broker->getClass(\ReflectionClass::class);
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
				\Attribute::IS_REPEATABLE,
			],
			[
				IsAttribute3::class,
				true,
				\Attribute::IS_REPEATABLE | \Attribute::TARGET_PROPERTY,
			],
		];
	}

	/**
	 * @dataProvider dataIsAttributeClass
	 * @param string $className
	 * @param bool $expected
	 */
	public function testIsAttributeClass(string $className, bool $expected, int $expectedFlags = \Attribute::TARGET_ALL): void
	{
		if (!self::$useStaticReflectionProvider && PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}
		$reflectionProvider = $this->createBroker();
		$reflection = $reflectionProvider->getClass($className);
		$this->assertSame($expected, $reflection->isAttributeClass());
		if (!$expected) {
			return;
		}
		$this->assertSame($expectedFlags, $reflection->getAttributeClassFlags());
	}

	public function testDeprecatedConstantFromAnotherFile(): void
	{
		$reflectionProvider = $this->createBroker();
		$reflection = $reflectionProvider->getClass(SecuredRouter::class);
		$constant = $reflection->getConstant('SECURED');
		$this->assertTrue($constant->isDeprecated()->yes());
	}

	public function testGetTraits(): void
	{
		$reflectionProvider = $this->createBroker();

		$classes = [
			\NestedTraits\NoTrait::class => [],
			\NestedTraits\Foo::class => [
				\NestedTraits\FooTrait::class,
			],
			\NestedTraits\Bar::class => [
				\NestedTraits\BarTrait::class,
				\NestedTraits\FooTrait::class,
			],
			\NestedTraits\Baz::class => [
				\NestedTraits\BazTrait::class,
				\NestedTraits\BarTrait::class,
				\NestedTraits\FooTrait::class,
			],
			\NestedTraits\BazChild::class => [
				// TOOD is this expected?
				// \NestedTraits\BazTrait::class,
				// \NestedTraits\BarTrait::class,
				// \NestedTraits\FooTrait::class,
			],
		];

		foreach ($classes as $class => $expectedTraits) {
			$this->assertSame(
				array_map(
					static fn(ClassReflection $classReflection) => $classReflection->getNativeReflection()->getName(),
					$reflectionProvider->getClass($class)->getTraits()
				),
				$expectedTraits
			);
		}
	}

}
