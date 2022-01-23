<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use OptimizedDirectory\BFoo;
use PHPStan\BetterReflection\Reflector\DefaultReflector;
use PHPStan\BetterReflection\Reflector\Exception\IdentifierNotFound;
use PHPStan\Testing\PHPStanTestCase;
use TestDirectorySourceLocator\AFoo;
use function basename;
use const PHP_VERSION_ID;

class OptimizedDirectorySourceLocatorTest extends PHPStanTestCase
{

	public function dataClass(): iterable
	{
		yield from [
			[
				AFoo::class,
				AFoo::class,
				'a.php',
			],
			[
				'testdirectorySourceLocator\aFoo',
				AFoo::class,
				'a.php',
			],
			[
				BFoo::class,
				BFoo::class,
				'b.php',
			],
			[
				'OptimizedDirectory\\bfOO',
				BFoo::class,
				'b.php',
			],
		];

		if (PHP_VERSION_ID < 80100) {
			return;
		}

		yield [
			'OptimizedDirectory\\TestEnum',
			'OptimizedDirectory\\TestEnum',
			'enum.php',
		];
	}

	/**
	 * @dataProvider dataClass
	 */
	public function testClass(string $className, string $expectedClassName, string $file): void
	{
		$factory = self::getContainer()->getByType(OptimizedDirectorySourceLocatorFactory::class);
		$locator = $factory->createByDirectory(__DIR__ . '/data/directory');
		$reflector = new DefaultReflector($locator);
		$classReflection = $reflector->reflectClass($className);
		$this->assertSame($expectedClassName, $classReflection->getName());
		$this->assertNotNull($classReflection->getFileName());
		$this->assertSame($file, basename($classReflection->getFileName()));
	}

	public function dataFunctionExists(): array
	{
		return [
			[
				'TestDirectorySourceLocator\\doLorem',
				'TestDirectorySourceLocator\\doLorem',
				'a.php',
			],
			[
				'testdirectorysourcelocator\\doLorem',
				'TestDirectorySourceLocator\\doLorem',
				'a.php',
			],
			[
				'OptimizedDirectory\\doBar',
				'OptimizedDirectory\\doBar',
				'b.php',
			],
			[
				'OptimizedDirectory\\doBaz',
				'OptimizedDirectory\\doBaz',
				'b.php',
			],
			[
				'OptimizedDirectory\\dobaz',
				'OptimizedDirectory\\doBaz',
				'b.php',
			],
			[
				'OptimizedDirectory\\get_smarty',
				'OptimizedDirectory\\get_smarty',
				'b.php',
			],
			[
				'OptimizedDirectory\\get_smarty2',
				'OptimizedDirectory\\get_smarty2',
				'b.php',
			],
		];
	}

	/**
	 * @dataProvider dataFunctionExists
	 */
	public function testFunctionExists(string $functionName, string $expectedFunctionName, string $file): void
	{
		$factory = self::getContainer()->getByType(OptimizedDirectorySourceLocatorFactory::class);
		$locator = $factory->createByDirectory(__DIR__ . '/data/directory');
		$reflector = new DefaultReflector($locator);
		$functionReflection = $reflector->reflectFunction($functionName);
		$this->assertSame($expectedFunctionName, $functionReflection->getName());
		$this->assertNotNull($functionReflection->getFileName());
		$this->assertSame($file, basename($functionReflection->getFileName()));
	}

	public function dataFunctionDoesNotExist(): array
	{
		return [
			['doFoo'],
			['TestDirectorySourceLocator\\doFoo'],
		];
	}

	/**
	 * @dataProvider dataFunctionDoesNotExist
	 */
	public function testFunctionDoesNotExist(string $functionName): void
	{
		$factory = self::getContainer()->getByType(OptimizedDirectorySourceLocatorFactory::class);
		$locator = $factory->createByDirectory(__DIR__ . '/data/directory');
		$reflector = new DefaultReflector($locator);

		$this->expectException(IdentifierNotFound::class);
		$reflector->reflectFunction($functionName);
	}

	public function testBug5525(): void
	{
		if (PHP_VERSION_ID < 70300) {
			self::markTestSkipped('This test needs at least PHP 7.3 because of different PCRE engine');
		}

		$factory = self::getContainer()->getByType(OptimizedDirectorySourceLocatorFactory::class);
		$locator = $factory->createByFiles([__DIR__ . '/data/bug-5525.php']);
		$reflector = new DefaultReflector($locator);

		$class = $reflector->reflectClass('Faker\\Provider\\nl_BE\\Text');

		/** @var string $className */
		$className = $class->getName();
		$this->assertSame('Faker\\Provider\\nl_BE\\Text', $className);
	}

}
