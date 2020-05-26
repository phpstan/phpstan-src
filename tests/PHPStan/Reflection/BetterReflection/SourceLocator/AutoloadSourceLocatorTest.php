<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PHPStan\Testing\TestCase;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\ConstantReflector;
use Roave\BetterReflection\Reflector\FunctionReflector;
use TestSingleFileSourceLocator\AFoo;

function testFunctionForLocator(): void // phpcs:disable
{

}

class AutoloadSourceLocatorTest extends TestCase
{

	public function testAutoloadEverythingInFile(): void
	{
		/** @var FunctionReflector $functionReflector */
		$functionReflector = null;
		$locator = new AutoloadSourceLocator(self::getContainer()->getByType(FileNodesFetcher::class));
		$classReflector = new ClassReflector($locator);
		$functionReflector = new FunctionReflector($locator, $classReflector);
		$constantReflector = new ConstantReflector($locator, $classReflector);
		$aFoo = $classReflector->reflect(AFoo::class);
		$this->assertNotNull($aFoo->getFileName());
		$this->assertSame('a.php', basename($aFoo->getFileName()));

		$testFunctionReflection = $functionReflector->reflect('PHPStan\\Reflection\\BetterReflection\\SourceLocator\testFunctionForLocator');
		$this->assertSame(__FILE__, $testFunctionReflection->getFileName());

		$someConstant = $constantReflector->reflect('TestSingleFileSourceLocator\\SOME_CONSTANT');
		$this->assertNotNull($someConstant->getFileName());
		$this->assertSame('a.php', basename($someConstant->getFileName()));
		$this->assertSame(1, $someConstant->getValue());

		$anotherConstant = $constantReflector->reflect('TestSingleFileSourceLocator\\ANOTHER_CONSTANT');
		$this->assertNotNull($anotherConstant->getFileName());
		$this->assertSame('a.php', basename($anotherConstant->getFileName()));
		$this->assertSame(2, $anotherConstant->getValue());

		$doFooFunctionReflection = $functionReflector->reflect('TestSingleFileSourceLocator\\doFoo');
		$this->assertSame('TestSingleFileSourceLocator\\doFoo', $doFooFunctionReflection->getName());
		$this->assertNotNull($doFooFunctionReflection->getFileName());
		$this->assertSame('a.php', basename($doFooFunctionReflection->getFileName()));
	}

}
