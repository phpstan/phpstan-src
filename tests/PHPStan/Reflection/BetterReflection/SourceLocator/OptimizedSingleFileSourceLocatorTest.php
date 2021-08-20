<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PHPStan\BetterReflection\Reflector\ClassReflector;
use PHPStan\BetterReflection\Reflector\ConstantReflector;
use PHPStan\BetterReflection\Reflector\Exception\IdentifierNotFound;
use PHPStan\BetterReflection\Reflector\FunctionReflector;
use PHPStan\Testing\PHPStanTestCase;
use TestSingleFileSourceLocator\AFoo;

class OptimizedSingleFileSourceLocatorTest extends PHPStanTestCase
{

	public function dataClass(): array
	{
		return [
			[
				AFoo::class,
				AFoo::class,
				__DIR__ . '/data/a.php',
			],
			[
				'testSinglefileSourceLocator\\afoo',
				AFoo::class,
				__DIR__ . '/data/a.php',
			],
			[
				\SingleFileSourceLocatorTestClass::class,
				\SingleFileSourceLocatorTestClass::class,
				__DIR__ . '/data/b.php',
			],
			[
				'SinglefilesourceLocatortestClass',
				\SingleFileSourceLocatorTestClass::class,
				__DIR__ . '/data/b.php',
			],
		];
	}

	/**
	 * @dataProvider dataClass
	 * @param string $className
	 * @param string $expectedClassName
	 * @param string $file
	 */
	public function testClass(string $className, string $expectedClassName, string $file): void
	{
		$factory = self::getContainer()->getByType(OptimizedSingleFileSourceLocatorFactory::class);
		$locator = $factory->create($file);
		$classReflector = new ClassReflector($locator);
		$classReflection = $classReflector->reflect($className);
		$this->assertSame($expectedClassName, $classReflection->getName());
	}

	public function dataFunction(): array
	{
		return [
			[
				'TestSingleFileSourceLocator\\doFoo',
				'TestSingleFileSourceLocator\\doFoo',
				__DIR__ . '/data/a.php',
			],
			[
				'testSingleFilesourcelocatOR\\dofoo',
				'TestSingleFileSourceLocator\\doFoo',
				__DIR__ . '/data/a.php',
			],
			[
				'singleFileSourceLocatorTestFunction',
				'singleFileSourceLocatorTestFunction',
				__DIR__ . '/data/b.php',
			],
			[
				'singlefileSourceLocatORTestfunCTion',
				'singleFileSourceLocatorTestFunction',
				__DIR__ . '/data/b.php',
			],
		];
	}

	/**
	 * @dataProvider dataFunction
	 * @param string $functionName
	 * @param string $expectedFunctionName
	 * @param string $file
	 */
	public function testFunction(string $functionName, string $expectedFunctionName, string $file): void
	{
		$factory = self::getContainer()->getByType(OptimizedSingleFileSourceLocatorFactory::class);
		$locator = $factory->create($file);
		$classReflector = new ClassReflector($locator);
		$functionReflector = new FunctionReflector($locator, $classReflector);
		$functionReflection = $functionReflector->reflect($functionName);
		$this->assertSame($expectedFunctionName, $functionReflection->getName());
	}

	public function dataConst(): array
	{
		return [
			[
				'ConstFile\\TABLE_NAME',
				'resized_images',
			],
			[
				'ANOTHER_NAME',
				'foo_images',
			],
			[
				'ConstFile\\ANOTHER_NAME',
				'bar_images',
			],
			[
				'const_with_dir_const',
				str_replace('\\', '/', __DIR__ . '/data'),
			],
		];
	}

	/**
	 * @dataProvider dataConst
	 * @param string $constantName
	 * @param mixed $value
	 */
	public function testConst(string $constantName, $value): void
	{
		$factory = self::getContainer()->getByType(OptimizedSingleFileSourceLocatorFactory::class);
		$locator = $factory->create(__DIR__ . '/data/const.php');
		$classReflector = new ClassReflector($locator);
		$constantReflector = new ConstantReflector($locator, $classReflector);
		$constant = $constantReflector->reflect($constantName);
		$this->assertSame($constantName, $constant->getName());
		$this->assertSame($value, $constant->getValue());
	}

	public function dataConstUnknown(): array
	{
		return [
			['TEST_VARIABLE'],
		];
	}

	/**
	 * @dataProvider dataConstUnknown
	 * @param string $constantName
	 */
	public function testConstUnknown(string $constantName): void
	{
		$factory = self::getContainer()->getByType(OptimizedSingleFileSourceLocatorFactory::class);
		$locator = $factory->create(__DIR__ . '/data/const.php');
		$classReflector = new ClassReflector($locator);
		$constantReflector = new ConstantReflector($locator, $classReflector);
		$this->expectException(IdentifierNotFound::class);
		$constantReflector->reflect($constantName);
	}

}
