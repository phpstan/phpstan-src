<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PHPStan\Testing\TestCase;
use Roave\BetterReflection\Reflector\ClassReflector;
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
		$this->assertSame($file, basename($classReflection->getFileName()));
	}

}
