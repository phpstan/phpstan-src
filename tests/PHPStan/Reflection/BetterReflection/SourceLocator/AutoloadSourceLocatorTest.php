<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PHPStan\Testing\TestCase;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\ConstantReflector;
use Roave\BetterReflection\Reflector\FunctionReflector;
use TestSingleFileSourceLocator\AFoo;
use TestSingleFileSourceLocator\InCondition;

function testFunctionForLocator(): void // phpcs:disable
{

}

class AutoloadSourceLocatorTest extends TestCase
{

	public function testAutoloadEverythingInFile(): void
	{
		/** @var FunctionReflector $functionReflector */
		$functionReflector = null;
		$locator = new AutoloadSourceLocator(self::getContainer()->getByType(FileNodesFetcher::class), []);
		$classReflector = new ClassReflector($locator);
		$functionReflector = new FunctionReflector($locator, $classReflector);
		$constantReflector = new ConstantReflector($locator, $classReflector);
		$aFoo = $classReflector->reflect(AFoo::class);
		$this->assertNotNull($aFoo->getFileName());
		$this->assertSame('a.php', basename($aFoo->getFileName()));

		$testFunctionReflection = $functionReflector->reflect('PHPStan\\Reflection\\BetterReflection\\SourceLocator\testFunctionForLocator');
		$this->assertSame(str_replace('\\', '/', __FILE__), $testFunctionReflection->getFileName());

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

		class_exists(InCondition::class);
		$classInCondition = $classReflector->reflect(InCondition::class);
		$classInConditionFilename = $classInCondition->getFileName();
		$this->assertNotNull($classInConditionFilename);
		$this->assertSame('a.php', basename($classInConditionFilename));
		$this->assertSame(InCondition::class, $classInCondition->getName());
		$this->assertSame(25, $classInCondition->getStartLine());
		$this->assertInstanceOf(ReflectionClass::class, $classInCondition->getParentClass());
		$this->assertSame(AFoo::class, $classInCondition->getParentClass()->getName());
	}

}
