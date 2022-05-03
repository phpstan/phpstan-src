<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use OptimizedDirectory\BFoo;
use PHPStan\BetterReflection\Identifier\IdentifierType;
use PHPStan\BetterReflection\Reflection\Reflection;
use PHPStan\BetterReflection\Reflector\DefaultReflector;
use PHPStan\BetterReflection\Reflector\Exception\IdentifierNotFound;
use PHPStan\Testing\PHPStanTestCase;
use TestDirectorySourceLocator\AFoo;
use function array_map;
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

		yield [
			'OptimizedDirectory\\BackedByStringWithoutSpace',
			'OptimizedDirectory\\BackedByStringWithoutSpace',
			'enum.php',
		];

		yield [
			'OptimizedDirectory\\UppercaseEnum',
			'OptimizedDirectory\\UppercaseEnum',
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
			[
				'OptimizedDirectory\\upperCaseFunction',
				'OptimizedDirectory\\upperCaseFunction',
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

	public function dataConstant(): iterable
	{
		yield from [
			[
				'OptimizedDirectory\\SOMETHING',
				'b.php',
			],
			[
				'OptimizedDirectory\\CLASS_CONST',
				null,
			],
			[
				'OptimizedDirectory2\\ANYTHING',
				'd.php',
			],
			[
				'NOTHING',
				'd.php',
			],
		];
	}

	/**
	 * @dataProvider dataConstant
	 */
	public function testConstant(string $constantName, ?string $expectedFile): void
	{
		$factory = self::getContainer()->getByType(OptimizedDirectorySourceLocatorFactory::class);
		$locator = $factory->createByDirectory(__DIR__ . '/data/directory');
		$reflector = new DefaultReflector($locator);

		if ($expectedFile === null) {
			$this->expectException(IdentifierNotFound::class);
			$reflector->reflectConstant($constantName);
		} else {
			$constantReflection = $reflector->reflectConstant($constantName);

			$this->assertNotNull($constantReflection->getFileName());
			$this->assertSame($expectedFile, basename($constantReflection->getFileName()));
		}
	}

	public function testLocateIdentifiersByType(): void
	{
		/** @var OptimizedDirectorySourceLocatorFactory $factory */
		$factory = self::getContainer()->getByType(OptimizedDirectorySourceLocatorFactory::class);
		$locator = $factory->createByDirectory(__DIR__ . '/data/directory');
		$reflector = new DefaultReflector($locator);

		$classIdentifiers = $locator->locateIdentifiersByType(
			$reflector,
			new IdentifierType(IdentifierType::IDENTIFIER_CLASS),
		);

		$expectedClasses = [
			'TestDirectorySourceLocator\AFoo',
			'OptimizedDirectory\BFoo',
			'CFoo',
		];
		if (PHP_VERSION_ID >= 80100) {
			$expectedClasses[] = 'OptimizedDirectory\TestEnum';
			$expectedClasses[] = 'OptimizedDirectory\BackedByStringWithoutSpace';
			$expectedClasses[] = 'OptimizedDirectory\UppercaseEnum';
		}

		$actualClasses = array_map(static fn (Reflection $reflection) => $reflection->getName(), $classIdentifiers);
		$this->assertEqualsCanonicalizing($expectedClasses, $actualClasses);

		$functionIdentifiers = $locator->locateIdentifiersByType(
			$reflector,
			new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION),
		);

		$actualFunctions = array_map(static fn (Reflection $reflection) => $reflection->getName(), $functionIdentifiers);

		$this->assertEqualsCanonicalizing([
			'TestDirectorySourceLocator\doLorem',
			'OptimizedDirectory\doBar',
			'OptimizedDirectory\doBaz',
			'OptimizedDirectory\get_smarty',
			'OptimizedDirectory\get_smarty2',
			'OptimizedDirectory\upperCaseFunction',
		], $actualFunctions);
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
