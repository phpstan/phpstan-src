<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PHPStan\Testing\TestCase;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\FunctionReflector;
use TestSingleFileSourceLocator\AFoo;

function testFunctionForLocator()
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
		$aFoo = $classReflector->reflect(AFoo::class);
		$this->assertSame('a.php', basename($aFoo->getFileName()));

		$testFunctionReflection = $functionReflector->reflect('PHPStan\\Reflection\\BetterReflection\\SourceLocator\testFunctionForLocator');
		$this->assertSame(__FILE__, $testFunctionReflection->getFileName());
	}

}
