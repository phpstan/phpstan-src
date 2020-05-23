<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PHPStan\Testing\TestCase;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\Exception\IdentifierNotFound;
use Roave\BetterReflection\Reflector\FunctionReflector;
use TestDirectorySourceLocator\AFoo;

class OptimizedDirectorySourceLocatorTest extends TestCase
{

	public function dataClass(): array
	{
		return [
			[
				AFoo::class,
				'a.php',
			],
			[
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
	public function testClass(string $className, string $file): void
	{
		$factory = self::getContainer()->getByType(OptimizedDirectorySourceLocatorFactory::class);
		$locator = $factory->create(__DIR__ . '/data/directory');
		$classReflector = new ClassReflector($locator);
		$classReflection = $classReflector->reflect($className);
		$this->assertSame($className, $classReflection->getName());
		$this->assertNotNull($classReflection->getFileName());
		$this->assertSame($file, basename($classReflection->getFileName()));
	}

	public function dataFunctionExists(): array
	{
		return [
			[
				'TestDirectorySourceLocator\\doLorem',
				'a.php',
			],
			[
				'doBar',
				'b.php',
			],
			[
				'doBaz',
				'b.php',
			],
		];
	}

	/**
	 * @dataProvider dataFunctionExists
	 * @param string $functionName
	 * @param string $file
	 */
	public function testFunctionExists(string $functionName, string $file): void
	{
		$factory = self::getContainer()->getByType(OptimizedDirectorySourceLocatorFactory::class);
		$locator = $factory->create(__DIR__ . '/data/directory');
		$classReflector = new ClassReflector($locator);
		$functionReflector = new FunctionReflector($locator, $classReflector);
		$functionReflection = $functionReflector->reflect($functionName);
		$this->assertSame($functionName, $functionReflection->getName());
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
		$locator = $factory->create(__DIR__ . '/data/directory');
		$classReflector = new ClassReflector($locator);
		$functionReflector = new FunctionReflector($locator, $classReflector);

		$this->expectException(IdentifierNotFound::class);
		$functionReflector->reflect($functionName);
	}

}
