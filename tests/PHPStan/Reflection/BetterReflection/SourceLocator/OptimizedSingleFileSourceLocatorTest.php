<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PHPStan\BetterReflection\Reflector\DefaultReflector;
use PHPStan\BetterReflection\Reflector\Exception\IdentifierNotFound;
use PHPStan\Reflection\InitializerExprContext;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\VerbosityLevel;
use SingleFileSourceLocatorTestClass;
use TestSingleFileSourceLocator\AFoo;
use function str_replace;
use const PHP_VERSION_ID;

class OptimizedSingleFileSourceLocatorTest extends PHPStanTestCase
{

	public function dataClass(): iterable
	{
		yield from [
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
				SingleFileSourceLocatorTestClass::class,
				SingleFileSourceLocatorTestClass::class,
				__DIR__ . '/data/b.php',
			],
			[
				'SinglefilesourceLocatortestClass',
				SingleFileSourceLocatorTestClass::class,
				__DIR__ . '/data/b.php',
			],
		];

		if (PHP_VERSION_ID < 80100) {
			return;
		}

		yield [
			'OptimizedDirectory\\TestEnum',
			'OptimizedDirectory\\TestEnum',
			__DIR__ . '/data/directory/enum.php',
		];
	}

	/**
	 * @dataProvider dataClass
	 */
	public function testClass(string $className, string $expectedClassName, string $file): void
	{
		$factory = self::getContainer()->getByType(OptimizedSingleFileSourceLocatorFactory::class);
		$locator = $factory->create($file);
		$reflector = new DefaultReflector($locator);
		$classReflection = $reflector->reflectClass($className);
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
	 */
	public function testFunction(string $functionName, string $expectedFunctionName, string $file): void
	{
		$factory = self::getContainer()->getByType(OptimizedSingleFileSourceLocatorFactory::class);
		$locator = $factory->create($file);
		$reflector = new DefaultReflector($locator);
		$functionReflection = $reflector->reflectFunction($functionName);
		$this->assertSame($expectedFunctionName, $functionReflection->getName());
	}

	public function dataConst(): array
	{
		return [
			[
				'ConstFile\\TABLE_NAME',
				"'resized_images'",
			],
			[
				'ANOTHER_NAME',
				"'foo_images'",
			],
			[
				'ConstFile\\ANOTHER_NAME',
				"'bar_images'",
			],
			[
				'const_with_dir_const',
				"'" . str_replace('\\', '/', __DIR__ . '/data') . "'",
			],
			[
				'OPTIMIZED_SFSL_OBJECT_CONSTANT',
				'stdClass',
			],
		];
	}

	/**
	 * @dataProvider dataConst
	 */
	public function testConst(string $constantName, string $valueTypeDescription): void
	{
		$factory = self::getContainer()->getByType(OptimizedSingleFileSourceLocatorFactory::class);
		$locator = $factory->create(__DIR__ . '/data/const.php');
		$reflector = new DefaultReflector($locator);
		$constant = $reflector->reflectConstant($constantName);
		$this->assertSame($constantName, $constant->getName());

		$initializerExprTypeResolver = self::getContainer()->getByType(InitializerExprTypeResolver::class);
		$valueType = $initializerExprTypeResolver->getType(
			$constant->getValueExpr(),
			new InitializerExprContext($constant->getFileName()),
		);
		$this->assertSame($valueTypeDescription, $valueType->describe(VerbosityLevel::precise()));
	}

	public function dataConstUnknown(): array
	{
		return [
			['TEST_VARIABLE'],
		];
	}

	/**
	 * @dataProvider dataConstUnknown
	 */
	public function testConstUnknown(string $constantName): void
	{
		$factory = self::getContainer()->getByType(OptimizedSingleFileSourceLocatorFactory::class);
		$locator = $factory->create(__DIR__ . '/data/const.php');
		$reflector = new DefaultReflector($locator);
		$this->expectException(IdentifierNotFound::class);
		$reflector->reflectConstant($constantName);
	}

}
