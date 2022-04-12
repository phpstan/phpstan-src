<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PHPStan\BetterReflection\Reflection\ReflectionClass;
use PHPStan\BetterReflection\Reflector\DefaultReflector;
use PHPStan\BetterReflection\Reflector\Exception\IdentifierNotFound;
use PHPStan\File\FileExcluder;
use PHPStan\File\FileHelper;
use PHPStan\Testing\PHPStanTestCase;
use TestSingleFileSourceLocator\AFoo;
use TestSingleFileSourceLocator\Ceee;
use TestSingleFileSourceLocator\InCondition;
use function class_alias;

function testFunctionForLocator(): void // phpcs:disable
{

}

class AutoloadSourceLocatorTest extends PHPStanTestCase
{

	public function testAutoloadEverythingInFile(): void
	{
		$fileHelper = new FileHelper(__DIR__);
		$locator = new AutoloadSourceLocator(self::getContainer()->getByType(FileNodesFetcher::class), $fileHelper, new FileExcluder($fileHelper, [], []), false);
		$reflector = new DefaultReflector($locator);
		$aFoo = $reflector->reflectClass(AFoo::class);
		$this->assertNotNull($aFoo->getFileName());
		$this->assertSame('a.php', basename($aFoo->getFileName()));

		$testFunctionReflection = $reflector->reflectFunction('PHPStan\\Reflection\\BetterReflection\\SourceLocator\testFunctionForLocator');
		$this->assertSame(str_replace('\\', '/', __FILE__), $testFunctionReflection->getFileName());

		$someConstant = $reflector->reflectConstant('TestSingleFileSourceLocator\\SOME_CONSTANT');
		$this->assertNotNull($someConstant->getFileName());
		$this->assertSame('a.php', basename($someConstant->getFileName()));
		$this->assertSame(1, $someConstant->getValue());

		$anotherConstant = $reflector->reflectConstant('TestSingleFileSourceLocator\\ANOTHER_CONSTANT');
		$this->assertNotNull($anotherConstant->getFileName());
		$this->assertSame('a.php', basename($anotherConstant->getFileName()));
		$this->assertSame(2, $anotherConstant->getValue());

		$doFooFunctionReflection = $reflector->reflectFunction('TestSingleFileSourceLocator\\doFoo');
		$this->assertSame('TestSingleFileSourceLocator\\doFoo', $doFooFunctionReflection->getName());
		$this->assertNotNull($doFooFunctionReflection->getFileName());
		$this->assertSame('a.php', basename($doFooFunctionReflection->getFileName()));

		class_exists(InCondition::class);
		$classInCondition = $reflector->reflectClass(InCondition::class);
		$classInConditionFilename = $classInCondition->getFileName();
		$this->assertNotNull($classInConditionFilename);
		$this->assertSame('a.php', basename($classInConditionFilename));
		$this->assertSame(InCondition::class, $classInCondition->getName());
		$this->assertSame(25, $classInCondition->getStartLine());
		$this->assertInstanceOf(ReflectionClass::class, $classInCondition->getParentClass());
		$this->assertSame(AFoo::class, $classInCondition->getParentClass()->getName());
	}

	public function testClassAlias(): void
	{
		class_alias(AFoo::class, 'A_Foo');
		$fileHelper = new FileHelper(__DIR__);
		$locator = new AutoloadSourceLocator(self::getContainer()->getByType(FileNodesFetcher::class), $fileHelper, new FileExcluder($fileHelper, [], []), true);
		$reflector = new DefaultReflector($locator);
		$class = $reflector->reflectClass('A_Foo');
		$this->assertSame(AFoo::class, $class->getName());
	}

	public function dataSkipExcludedFiles(): array
	{
		return [
			[true, false, []],
			[false, false, []],
			[true, true, [__DIR__ . '/data/c.php']],
			[false, false, [__DIR__ . '/data/c.php']],
		];
	}

	/**
	 * @dataProvider dataSkipExcludedFiles
	 * @runInSeparateProcess
	 * @param string[] $excludedFiles
	 * @return void
	 */
	public function testSkipExcludedFiles(bool $disableRuntimeReflectionProvider, bool $expectException, array $excludedFiles): void
	{
		$fileHelper = new FileHelper(__DIR__);
		$locator = new AutoloadSourceLocator(
			self::getContainer()->getByType(FileNodesFetcher::class),
			$fileHelper,
			new FileExcluder($fileHelper, $excludedFiles, []),
			$disableRuntimeReflectionProvider,
		);
		$reflector = new DefaultReflector($locator);

		if ($expectException) {
			$this->expectException(IdentifierNotFound::class);
		}

		$class = $reflector->reflectClass(Ceee::class);
		$this->assertSame(Ceee::class, $class->getName());
	}

}
