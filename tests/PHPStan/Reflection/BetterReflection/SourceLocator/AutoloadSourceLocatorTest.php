<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PHPStan\BetterReflection\Reflection\ReflectionClass;
use PHPStan\BetterReflection\Reflector\DefaultReflector;
use PHPStan\Reflection\InitializerExprContext;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\Constant\ConstantIntegerType;
use TestSingleFileSourceLocator\AFoo;
use TestSingleFileSourceLocator\InCondition;
use function class_alias;

function testFunctionForLocator(): void // phpcs:disable
{
	echo 'test';
}

class AutoloadSourceLocatorTest extends PHPStanTestCase
{

	public function testAutoloadEverythingInFile(): void
	{
		$locator = new AutoloadSourceLocator(self::getContainer()->getByType(FileNodesFetcher::class), true);
		$reflector = new DefaultReflector($locator);
		$aFoo = $reflector->reflectClass(AFoo::class);
		self::assertNotNull($aFoo->getFileName());
		self::assertSame('a.php', basename($aFoo->getFileName()));

		$testFunctionReflection = $reflector->reflectFunction('PHPStan\\Reflection\\BetterReflection\\SourceLocator\testFunctionForLocator');
		self::assertSame(str_replace('\\', '/', __FILE__), $testFunctionReflection->getFileName());

		$someConstant = $reflector->reflectConstant('TestSingleFileSourceLocator\\SOME_CONSTANT');
		self::assertNotNull($someConstant->getFileName());
		self::assertSame('a.php', basename($someConstant->getFileName()));

		$initializerExprTypeResolver = self::getContainer()->getByType(InitializerExprTypeResolver::class);
		$someConstantValue = $initializerExprTypeResolver->getType(
			$someConstant->getValueExpression(),
			InitializerExprContext::fromGlobalConstant($someConstant),
		);
		self::assertInstanceOf(ConstantIntegerType::class, $someConstantValue);
		self::assertSame(1, $someConstantValue->getValue());

		$anotherConstant = $reflector->reflectConstant('TestSingleFileSourceLocator\\ANOTHER_CONSTANT');
		self::assertNotNull($anotherConstant->getFileName());
		self::assertSame('a.php', basename($anotherConstant->getFileName()));
		$anotherConstantValue = $initializerExprTypeResolver->getType(
			$anotherConstant->getValueExpression(),
			InitializerExprContext::fromGlobalConstant($anotherConstant),
		);
		self::assertInstanceOf(ConstantIntegerType::class, $anotherConstantValue);
		self::assertSame(2, $anotherConstantValue->getValue());

		$doFooFunctionReflection = $reflector->reflectFunction('TestSingleFileSourceLocator\\doFoo');
		self::assertSame('TestSingleFileSourceLocator\\doFoo', $doFooFunctionReflection->getName());
		self::assertNotNull($doFooFunctionReflection->getFileName());
		self::assertSame('a.php', basename($doFooFunctionReflection->getFileName()));

		class_exists(InCondition::class);
		$classInCondition = $reflector->reflectClass(InCondition::class);
		$classInConditionFilename = $classInCondition->getFileName();
		self::assertNotNull($classInConditionFilename);
		self::assertSame('a.php', basename($classInConditionFilename));
		self::assertSame(InCondition::class, $classInCondition->getName());
		self::assertSame(25, $classInCondition->getStartLine());
		self::assertInstanceOf(ReflectionClass::class, $classInCondition->getParentClass());
		self::assertSame(AFoo::class, $classInCondition->getParentClass()->getName());
	}

	public function testClassAlias(): void
	{
		class_alias(AFoo::class, 'A_Foo');
		$locator = new AutoloadSourceLocator(self::getContainer()->getByType(FileNodesFetcher::class), true);
		$reflector = new DefaultReflector($locator);
		$class = $reflector->reflectClass('A_Foo');
		self::assertSame(AFoo::class, $class->getName());
	}

}
