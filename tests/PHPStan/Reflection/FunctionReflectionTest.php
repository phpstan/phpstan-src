<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\TrinaryLogic;
use PHPUnit\Framework\Attributes\DataProvider;
use const PHP_VERSION_ID;

class FunctionReflectionTest extends PHPStanTestCase
{

	public static function dataPhpdocFunctions(): iterable
	{
		yield [
			'FunctionReflectionDocTest\\myFunction',
			'/** some fn phpdoc */',
		];
		yield [
			'FunctionReflectionDocTest\\noDocFunction',
			null,
		];
		yield [
			'FunctionReflectionDocTest\\docViaStub',
			'/** fn doc via stub */',
		];
		yield [
			'FunctionReflectionDocTest\\existingDocButStubOverridden',
			'/** fn stub overridden phpdoc */',
		];
		yield [
			'\\implode',
			'/** php-src native fn stub overridden phpdoc */',
		];
	}

	/**
	 * @param non-empty-string $functionName
	 */
	#[DataProvider('dataPhpdocFunctions')]
	public function testFunctionHasPhpdoc(string $functionName, ?string $expectedDoc): void
	{
		require_once __DIR__ . '/data/function-with-phpdoc.php';

		$reflectionProvider = self::createReflectionProvider();

		$functionReflection = $reflectionProvider->getFunction(new Node\Name($functionName), null);
		$this->assertSame($expectedDoc, $functionReflection->getDocComment());
	}

	public static function dataPhpdocMethods(): iterable
	{
		yield [
			'FunctionReflectionDocTest\\ClassWithPhpdoc',
			'__construct',
			'/** construct doc via stub */',
		];
		yield [
			'FunctionReflectionDocTest\\ClassWithPhpdoc',
			'aMethod',
			'/** some method phpdoc */',
		];
		yield [
			'FunctionReflectionDocTest\\ClassWithPhpdoc',
			'noDocMethod',
			null,
		];
		yield [
			'FunctionReflectionDocTest\\ClassWithPhpdoc',
			'docViaStub',
			'/** method doc via stub */',
		];
		yield [
			'FunctionReflectionDocTest\\ClassWithPhpdoc',
			'existingDocButStubOverridden',
			'/** stub overridden phpdoc */',
		];
		yield [
			'FunctionReflectionDocTest\\ClassWithInheritedPhpdoc',
			'aMethod',
			'/** some method phpdoc */',
		];
		yield [
			'FunctionReflectionDocTest\\ClassWithInheritedPhpdoc',
			'noDocMethod',
			null,
		];
		yield [
			'FunctionReflectionDocTest\\ClassWithInheritedPhpdoc',
			'docViaStub',
			'/** method doc via stub */',
		];
		yield [
			'FunctionReflectionDocTest\\ClassWithInheritedPhpdoc',
			'existingDocButStubOverridden',
			'/** stub overridden phpdoc */',
		];
		yield [
			'FunctionReflectionDocTest\\ClassWithInheritedPhpdoc',
			'aMethodInheritanceOverridden',
			'/** some inheritance overridden method phpdoc */',
		];
		yield [
			'\\DateTime',
			'__construct',
			'/** php-src native construct stub overridden phpdoc */',
		];
		yield [
			'\\DateTime',
			'modify',
			'/** php-src native method stub overridden phpdoc */',
		];
	}

	#[DataProvider('dataPhpdocMethods')]
	public function testMethodHasPhpdoc(string $className, string $methodName, ?string $expectedDocComment): void
	{
		$reflectionProvider = self::createReflectionProvider();
		$class = $reflectionProvider->getClass($className);
		$scope = $this->createStub(Scope::class);
		$scope->method('isInClass')->willReturn(true);
		$scope->method('getClassReflection')->willReturn($class);
		$scope->method('canAccessProperty')->willReturn(true);
		$scope->method('canReadProperty')->willReturn(true);
		$scope->method('canWriteProperty')->willReturn(true);
		$classReflection = $reflectionProvider->getClass($className);

		$methodReflection = $classReflection->getMethod($methodName, $scope);
		$this->assertSame($expectedDocComment, $methodReflection->getDocComment());
	}

	public static function dataFunctionReturnsByReference(): iterable
	{
		yield ['\\implode', TrinaryLogic::createNo()];

		yield ['ReturnsByReference\\foo', TrinaryLogic::createNo()];
		yield ['ReturnsByReference\\refFoo', TrinaryLogic::createYes()];
	}

	/**
	 * @param non-empty-string $functionName
	 */
	#[DataProvider('dataFunctionReturnsByReference')]
	public function testFunctionReturnsByReference(string $functionName, TrinaryLogic $expectedReturnsByRef): void
	{
		require_once __DIR__ . '/data/returns-by-reference.php';

		$reflectionProvider = self::createReflectionProvider();

		$functionReflection = $reflectionProvider->getFunction(new Node\Name($functionName), null);
		$this->assertSame($expectedReturnsByRef, $functionReflection->returnsByReference());
	}

	public static function dataMethodReturnsByReference(): iterable
	{
		yield ['ReturnsByReference\\X', 'foo', TrinaryLogic::createNo()];
		yield ['ReturnsByReference\\X', 'refFoo', TrinaryLogic::createYes()];

		yield ['ReturnsByReference\\SubX', 'foo', TrinaryLogic::createNo()];
		yield ['ReturnsByReference\\SubX', 'refFoo', TrinaryLogic::createYes()];
		yield ['ReturnsByReference\\SubX', 'subRefFoo', TrinaryLogic::createYes()];

		yield ['ReturnsByReference\\TraitX', 'traitFoo', TrinaryLogic::createNo()];
		yield ['ReturnsByReference\\TraitX', 'refTraitFoo', TrinaryLogic::createYes()];

		if (PHP_VERSION_ID < 80100) {
			return;
		}

		yield ['ReturnsByReference\\E', 'enumFoo', TrinaryLogic::createNo()];
		yield ['ReturnsByReference\\E', 'refEnumFoo', TrinaryLogic::createYes()];
		// cases() method cannot be overridden; https://3v4l.org/ebm83
		yield ['ReturnsByReference\\E', 'cases', TrinaryLogic::createNo()];
	}

	#[DataProvider('dataMethodReturnsByReference')]
	public function testMethodReturnsByReference(string $className, string $methodName, TrinaryLogic $expectedReturnsByRef): void
	{
		$reflectionProvider = self::createReflectionProvider();
		$class = $reflectionProvider->getClass($className);
		$scope = $this->createStub(Scope::class);
		$scope->method('isInClass')->willReturn(true);
		$scope->method('getClassReflection')->willReturn($class);
		$scope->method('canAccessProperty')->willReturn(true);
		$scope->method('canReadProperty')->willReturn(true);
		$scope->method('canWriteProperty')->willReturn(true);
		$classReflection = $reflectionProvider->getClass($className);

		$methodReflection = $classReflection->getMethod($methodName, $scope);
		$this->assertSame($expectedReturnsByRef, $methodReflection->returnsByReference());
	}

	/**
	 * @return string[]
	 */
	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/data/function-reflection.neon',
		];
	}

}
