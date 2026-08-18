<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PHPStan\BetterReflection\Reflector\DefaultReflector;
use PHPStan\BetterReflection\Reflector\Exception\IdentifierNotFound;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\ConditionallyDeclaredSymbolDetector;
use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class SkipPolyfillSourceLocatorTest extends PHPStanTestCase
{

	public static function dataSkippedClasses(): iterable
	{
		yield ['ValueError'];
		yield ['Stringable'];
	}

	#[DataProvider('dataSkippedClasses')]
	public function testPolyfilledNativeClassIsSkipped(string $className): void
	{
		$this->expectException(IdentifierNotFound::class);
		$this->createReflector()->reflectClass($className);
	}

	public static function dataKeptClasses(): iterable
	{
		yield ['SkipPolyfillNotNativeClass'];
		yield ['SkipPolyfillUnconditionalClass'];
	}

	#[DataProvider('dataKeptClasses')]
	public function testOtherClassesAreKept(string $className): void
	{
		$this->assertSame($className, $this->createReflector()->reflectClass($className)->getName());
	}

	public function testPolyfilledNativeConstantIsSkipped(): void
	{
		$this->expectException(IdentifierNotFound::class);
		$this->createReflector()->reflectConstant('JSON_THROW_ON_ERROR');
	}

	public function testOtherConstantsAreKept(): void
	{
		$this->assertSame(
			'SKIP_POLYFILL_NOT_NATIVE_CONSTANT',
			$this->createReflector()->reflectConstant('SKIP_POLYFILL_NOT_NATIVE_CONSTANT')->getName(),
		);
	}

	private function createReflector(): Reflector
	{
		$container = self::getContainer();
		$locator = $container->getByType(OptimizedSingleFileSourceLocatorFactory::class)
			->create(__DIR__ . '/../../../../notAutoloaded/shadowed-native-symbols.php');

		return new DefaultReflector(new SkipPolyfillSourceLocator(
			$locator,
			$container->getByType(PhpVersion::class),
			$container->getByType(ConditionallyDeclaredSymbolDetector::class),
			$container->getByType(PhpStormStubsSourceStubber::class),
		));
	}

}
