<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PHPStan\BetterReflection\Reflector\ClassReflector;
use PHPStan\BetterReflection\Reflector\Exception\IdentifierNotFound;
use PHPStan\BetterReflection\Reflector\FunctionReflector;
use PHPStan\Testing\TestCase;
use TestDirectorySourceLocator\AFoo;

class OptimizedDirectorySourceLocatorTest extends TestCase
{

	public function dataClass(): array
	{
		return [
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
				\BFoo::class,
				\BFoo::class,
				'b.php',
			],
			[
				'bfOO',
				\BFoo::class,
				'b.php',
			],
		];
	}

	/**
	 * @dataProvider dataClass
	 * @param string $className
	 * @param string $file
	 */
	public function testClass(string $className, string $expectedClassName, string $file): void
	{
		$factory = self::getContainer()->getByType(OptimizedDirectorySourceLocatorFactory::class);
		$locator = $factory->createByDirectory(__DIR__ . '/data/directory');
		$classReflector = new ClassReflector($locator);
		$classReflection = $classReflector->reflect($className);
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
				'doBar',
				'doBar',
				'b.php',
			],
			[
				'doBaz',
				'doBaz',
				'b.php',
			],
			[
				'dobaz',
				'doBaz',
				'b.php',
			],
			[
				'get_smarty',
				'get_smarty',
				'b.php',
			],
			[
				'get_smarty2',
				'get_smarty2',
				'b.php',
			],
		];
	}

	/**
	 * @dataProvider dataFunctionExists
	 * @param string $functionName
	 * @param string $expectedFunctionName
	 * @param string $file
	 */
	public function testFunctionExists(string $functionName, string $expectedFunctionName, string $file): void
	{
		$factory = self::getContainer()->getByType(OptimizedDirectorySourceLocatorFactory::class);
		$locator = $factory->createByDirectory(__DIR__ . '/data/directory');
		$classReflector = new ClassReflector($locator);
		$functionReflector = new FunctionReflector($locator, $classReflector);
		$functionReflection = $functionReflector->reflect($functionName);
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
	 * @param string $functionName
	 */
	public function testFunctionDoesNotExist(string $functionName): void
	{
		$factory = self::getContainer()->getByType(OptimizedDirectorySourceLocatorFactory::class);
		$locator = $factory->createByDirectory(__DIR__ . '/data/directory');
		$classReflector = new ClassReflector($locator);
		$functionReflector = new FunctionReflector($locator, $classReflector);

		$this->expectException(IdentifierNotFound::class);
		$functionReflector->reflect($functionName);
	}

}
