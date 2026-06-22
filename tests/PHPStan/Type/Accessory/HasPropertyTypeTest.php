<?php declare(strict_types = 1);

namespace PHPStan\Type\Accessory;

use Closure;
use DateInterval;
use Override;
use PHPStan\DependencyInjection\ContainerFactory;
use PHPStan\File\FileHelper;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\TrinaryLogic;
use PHPStan\Type\CallableType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\IterableType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use PHPUnit\Framework\Attributes\DataProvider;
use function file_put_contents;
use function sprintf;
use function sys_get_temp_dir;
use const PHP_VERSION_ID;

class HasPropertyTypeTest extends PHPStanTestCase
{

	#[Override]
	protected function setUp(): void
	{
		// Re-register the default (runtime) container as the global static reflection
		// provider before every test. Without this, a container configured with a
		// different PhpVersion - registered by another test (e.g. through its data
		// provider) - can leak in and make the version-dependent Closure data set
		// below flaky. See https://github.com/phpstan/phpstan/issues/14860
		self::getContainer();
	}

	public static function dataIsSuperTypeOf(): array
	{
		return [
			[
				new HasPropertyType('format'),
				new HasPropertyType('format'),
				TrinaryLogic::createYes(),
			],
			[
				new HasPropertyType('format'),
				new HasPropertyType('FORMAT'),
				TrinaryLogic::createMaybe(),
			],
			[
				new HasPropertyType('d'),
				new ObjectType(DateInterval::class),
				TrinaryLogic::createYes(),
			],
			[
				new HasPropertyType('foo'),
				new ObjectType('UnknownClass'),
				TrinaryLogic::createMaybe(),
			],
			[
				new HasPropertyType('foo'),
				new ObjectType(Closure::class),
				PHP_VERSION_ID < 80200 ? TrinaryLogic::createMaybe() : TrinaryLogic::createNo(),
			],
			[
				new HasPropertyType('foo'),
				new ObjectWithoutClassType(),
				TrinaryLogic::createMaybe(),
			],
			[
				new HasPropertyType('foo'),
				new HasPropertyType('bar'),
				TrinaryLogic::createMaybe(),
			],
			[
				new HasPropertyType('foo'),
				new IterableType(new MixedType(), new MixedType()),
				TrinaryLogic::createMaybe(),
			],
			[
				new HasPropertyType('foo'),
				new CallableType(),
				TrinaryLogic::createMaybe(),
			],
			[
				new HasPropertyType('d'),
				new ObjectWithoutClassType(),
				TrinaryLogic::createMaybe(), // an intentional imprecision
			],
			[
				new HasPropertyType('d'),
				new UnionType([
					new ObjectType(DateInterval::class),
					new ObjectType('UnknownClass'),
				]),
				TrinaryLogic::createMaybe(),
			],
			[
				new HasPropertyType('d'),
				new IntersectionType([
					new ObjectType('UnknownClass'),
					new HasPropertyType('d'),
				]),
				TrinaryLogic::createYes(),
			],
			[
				new HasPropertyType('d'),
				new IntersectionType([
					new ObjectWithoutClassType(),
					new HasPropertyType('d'),
				]),
				TrinaryLogic::createYes(),
			],
			[
				new HasPropertyType('foo'),
				new MixedType(),
				TrinaryLogic::createMaybe(),
			],
		];
	}

	public function testIsSuperTypeOfClosureRespectsActivePhpVersion(): void
	{
		$type = new HasPropertyType('foo');

		// Whether a final class without the property is a possible subtype of
		// hasProperty() depends on whether the PHP version still allows dynamic
		// properties, so the result must follow the active container's PhpVersion
		// rather than the global PHP_VERSION_ID constant. This coupling is what made
		// the Closure data set above flaky when another test left a container
		// configured with a different PhpVersion registered as the global static
		// reflection provider. See https://github.com/phpstan/phpstan/issues/14860
		// A fresh ObjectType is used each time because it caches its ClassReflection.
		try {
			self::registerContainerWithPhpVersion(80100);
			$this->assertSame('Maybe', $type->isSuperTypeOf(new ObjectType(Closure::class))->describe());

			self::registerContainerWithPhpVersion(80200);
			$this->assertSame('No', $type->isSuperTypeOf(new ObjectType(Closure::class))->describe());
		} finally {
			// Restore the default (runtime) container so this test does not leak its
			// foreign container into other tests.
			self::getContainer();
		}
	}

	#[DataProvider('dataIsSuperTypeOf')]
	public function testIsSuperTypeOf(HasPropertyType $type, Type $otherType, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->isSuperTypeOf($otherType);
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isSuperTypeOf(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise())),
		);
	}

	public static function dataIsSubTypeOf(): array
	{
		return [
			[
				new HasPropertyType('foo'),
				new HasPropertyType('foo'),
				TrinaryLogic::createYes(),
			],
			[
				new HasPropertyType('foo'),
				new UnionType([
					new HasPropertyType('foo'),
					new NullType(),
				]),
				TrinaryLogic::createYes(),
			],
			[
				new HasPropertyType('foo'),
				new IntersectionType([
					new ObjectWithoutClassType(),
					new HasPropertyType('foo'),
					new HasPropertyType('bar'),
				]),
				TrinaryLogic::createMaybe(),
			],
			[
				new HasPropertyType('d'),
				new ObjectType(DateInterval::class),
				TrinaryLogic::createMaybe(),
			],
		];
	}

	#[DataProvider('dataIsSubTypeOf')]
	public function testIsSubTypeOf(HasPropertyType $type, Type $otherType, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->isSubTypeOf($otherType);
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isSubTypeOf(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise())),
		);
	}

	#[DataProvider('dataIsSubTypeOf')]
	public function testIsSubTypeOfInversed(HasPropertyType $type, Type $otherType, TrinaryLogic $expectedResult): void
	{
		$actualResult = $otherType->isSuperTypeOf($type);
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isSuperTypeOf(%s)', $otherType->describe(VerbosityLevel::precise()), $type->describe(VerbosityLevel::precise())),
		);
	}

	private static function registerContainerWithPhpVersion(int $versionId): void
	{
		$fileHelper = new FileHelper(__DIR__ . '/../../../..');
		$rootDir = $fileHelper->normalizePath(__DIR__ . '/../../../..', '/');

		// The directory is already created by self::getContainer() (called in setUp()).
		$tmpDir = sys_get_temp_dir() . '/phpstan-tests';

		$configFile = $tmpDir . '/has-property-php-version-' . $versionId . '.neon';
		file_put_contents($configFile, sprintf("parameters:\n\tphpVersion: %d\n", $versionId));

		$containerFactory = new ContainerFactory($rootDir);
		$container = $containerFactory->create($tmpDir, [
			$containerFactory->getConfigDirectory() . '/config.level8.neon',
			__DIR__ . '/../../../../src/Testing/TestCase.neon',
			$configFile,
		], []);

		ContainerFactory::postInitializeContainer($container);
	}

}
