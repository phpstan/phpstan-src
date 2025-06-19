<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PHPStan\BetterReflection\Identifier\IdentifierType;
use PHPStan\BetterReflection\Reflection\Reflection;
use PHPStan\BetterReflection\Reflector\DefaultReflector;
use PHPStan\BetterReflection\Reflector\Exception\IdentifierNotFound;
use PHPStan\Reflection\InitializerExprContext;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\VerbosityLevel;
use PHPUnit\Framework\Attributes\DataProvider;
use SingleFileSourceLocatorTestClass;
use TestSingleFileSourceLocator\AFoo;
use function array_map;
use const PHP_VERSION_ID;

class OptimizedSingleFileSourceLocatorTest extends PHPStanTestCase
{

	public static function dataClass(): iterable
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

	public static function dataForIdenifiersByType(): iterable
	{
		yield from [
			'classes wrapped in conditions' => [
				new IdentifierType(IdentifierType::IDENTIFIER_CLASS),
				[
					'TestSingleFileSourceLocator\AFoo',
					'TestSingleFileSourceLocator\InCondition',
					'TestSingleFileSourceLocator\InCondition',
					'TestSingleFileSourceLocator\InCondition',
				],
				__DIR__ . '/data/a.php',
			],
			'class with function in same file' => [
				new IdentifierType(IdentifierType::IDENTIFIER_CLASS),
				['SingleFileSourceLocatorTestClass'],
				__DIR__ . '/data/b.php',
			],
			'class bug-5525' => [
				new IdentifierType(IdentifierType::IDENTIFIER_CLASS),
				['Faker\Provider\nl_BE\Text'],
				__DIR__ . '/data/bug-5525.php',
			],
			'file without classes' => [
				new IdentifierType(IdentifierType::IDENTIFIER_CLASS),
				[],
				__DIR__ . '/data/const.php',
			],
			'plain function in complex file' => [
				new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION),
				[
					'TestSingleFileSourceLocator\doFoo',
				],
				__DIR__ . '/data/a.php',
			],
			'function with class in same file' => [
				new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION),
				['singleFileSourceLocatorTestFunction'],
				__DIR__ . '/data/b.php',
			],
			'file without functions' => [
				new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION),
				[],
				__DIR__ . '/data/only-class.php',
			],
			'constants' => [
				new IdentifierType(IdentifierType::IDENTIFIER_CONSTANT),
				[
					'ANOTHER_NAME',
					'ConstFile\ANOTHER_NAME',
					'ConstFile\TABLE_NAME',
					'OPTIMIZED_SFSL_OBJECT_CONSTANT',
					'const_with_dir_const',
				],
				__DIR__ . '/data/const.php',
			],
		];

		if (PHP_VERSION_ID < 80100) {
			return;
		}

		yield 'enums as classes' => [
			new IdentifierType(IdentifierType::IDENTIFIER_CLASS),
			[
				'OptimizedDirectory\BackedByStringWithoutSpace',
				'OptimizedDirectory\TestEnum',
				'OptimizedDirectory\UppercaseEnum',
			],
			__DIR__ . '/data/directory/enum.php',
		];
	}

	#[DataProvider('dataClass')]
	public function testClass(string $className, string $expectedClassName, string $file): void
	{
		$factory = self::getContainer()->getByType(OptimizedSingleFileSourceLocatorFactory::class);
		$locator = $factory->create($file);
		$reflector = new DefaultReflector($locator);
		$classReflection = $reflector->reflectClass($className);
		$this->assertSame($expectedClassName, $classReflection->getName());
	}

	public static function dataFunction(): array
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

	#[DataProvider('dataFunction')]
	public function testFunction(string $functionName, string $expectedFunctionName, string $file): void
	{
		$factory = self::getContainer()->getByType(OptimizedSingleFileSourceLocatorFactory::class);
		$locator = $factory->create($file);
		$reflector = new DefaultReflector($locator);
		$functionReflection = $reflector->reflectFunction($functionName);
		$this->assertSame($expectedFunctionName, $functionReflection->getName());
	}

	public static function dataConst(): array
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
				'literal-string&non-falsy-string',
			],
			[
				'OPTIMIZED_SFSL_OBJECT_CONSTANT',
				'stdClass',
			],
		];
	}

	#[DataProvider('dataConst')]
	public function testConst(string $constantName, string $valueTypeDescription): void
	{
		$factory = self::getContainer()->getByType(OptimizedSingleFileSourceLocatorFactory::class);
		$locator = $factory->create(__DIR__ . '/data/const.php');
		$reflector = new DefaultReflector($locator);
		$constant = $reflector->reflectConstant($constantName);
		$this->assertSame($constantName, $constant->getName());

		$initializerExprTypeResolver = self::getContainer()->getByType(InitializerExprTypeResolver::class);
		$valueType = $initializerExprTypeResolver->getType(
			$constant->getValueExpression(),
			InitializerExprContext::fromGlobalConstant($constant),
		);
		$this->assertSame($valueTypeDescription, $valueType->describe(VerbosityLevel::precise()));
	}

	public static function dataConstUnknown(): array
	{
		return [
			['TEST_VARIABLE'],
		];
	}

	#[DataProvider('dataConstUnknown')]
	public function testConstUnknown(string $constantName): void
	{
		$factory = self::getContainer()->getByType(OptimizedSingleFileSourceLocatorFactory::class);
		$locator = $factory->create(__DIR__ . '/data/const.php');
		$reflector = new DefaultReflector($locator);
		$this->expectException(IdentifierNotFound::class);
		$reflector->reflectConstant($constantName);
	}

	/**
	 * @param class-string[] $expectedIdentifiers
	 */
	#[DataProvider('dataForIdenifiersByType')]
	public function testLocateIdentifiersByType(
		IdentifierType $identifierType,
		array $expectedIdentifiers,
		string $file,
	): void
	{
		/** @var OptimizedSingleFileSourceLocatorFactory $factory */
		$factory = self::getContainer()->getByType(OptimizedSingleFileSourceLocatorFactory::class);
		$locator = $factory->create($file);
		$reflector = new DefaultReflector($locator);

		$reflections = $locator->locateIdentifiersByType(
			$reflector,
			$identifierType,
		);

		$actualIdentifiers = array_map(static fn (Reflection $reflection) => $reflection->getName(), $reflections);
		$this->assertEqualsCanonicalizing($expectedIdentifiers, $actualIdentifiers);
	}

}
