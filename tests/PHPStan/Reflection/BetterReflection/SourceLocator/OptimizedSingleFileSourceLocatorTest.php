<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PHPStan\Testing\TestCase;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\ConstantReflector;
use Roave\BetterReflection\Reflector\Exception\IdentifierNotFound;

class OptimizedSingleFileSourceLocatorTest extends TestCase
{

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
