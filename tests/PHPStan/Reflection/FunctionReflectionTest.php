<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Testing\PHPStanTestCase;
use const PHP_VERSION_ID;

class FunctionReflectionTest extends PHPStanTestCase
{

	public function dataPhpdocFunctions(): iterable
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
	 * @dataProvider dataPhpdocFunctions
	 */
	public function testFunctionHasPhpdoc(string $functionName, ?string $expectedDoc): void
	{
		require_once __DIR__ . '/data/function-with-phpdoc.php';

		$reflectionProvider = $this->createReflectionProvider();

		$functionReflection = $reflectionProvider->getFunction(new Node\Name($functionName), null);
		$this->assertSame($expectedDoc, $functionReflection->getDocComment());
	}

	public function dataPhpdocMethods(): iterable
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

	/**
	 * @dataProvider dataPhpdocMethods
	 */
	public function testMethodHasPhpdoc(string $className, string $methodName, ?string $expectedDocComment): void
	{
		$reflectionProvider = $this->createReflectionProvider();
		$class = $reflectionProvider->getClass($className);
		$scope = $this->createMock(Scope::class);
		$scope->method('isInClass')->willReturn(true);
		$scope->method('getClassReflection')->willReturn($class);
		$scope->method('canAccessProperty')->willReturn(true);
		$classReflection = $reflectionProvider->getClass($className);

		$methodReflection = $classReflection->getMethod($methodName, $scope);
		$this->assertSame($expectedDocComment, $methodReflection->getDocComment());
	}

	public function dataFunctionReturnsByReference(): iterable
	{
		yield ['\\implode', false];

		yield ['ReturnsByReference\\foo', false];
		yield ['ReturnsByReference\\refFoo', true];
	}

	/**
	 * @dataProvider dataFunctionReturnsByReference
	 */
	public function testFunctionReturnsByReference(string $functionName, bool $expectedReturnsByRef): void
	{
		require_once __DIR__ . '/data/returns-by-reference.php';

		$reflectionProvider = $this->createReflectionProvider();

		$functionReflection = $reflectionProvider->getFunction(new Node\Name($functionName), null);
		$this->assertSame($expectedReturnsByRef, $functionReflection->returnsByReference());
	}

	public function dataMethodReturnsByReference(): iterable
	{
		yield ['ReturnsByReference\\X', 'foo', false];
		yield ['ReturnsByReference\\X', 'refFoo', true];

		yield ['ReturnsByReference\\SubX', 'foo', false];
		yield ['ReturnsByReference\\SubX', 'refFoo', true];
		yield ['ReturnsByReference\\SubX', 'subRefFoo', true];

		yield ['ReturnsByReference\\TraitX', 'traitFoo', false];
		yield ['ReturnsByReference\\TraitX', 'refTraitFoo', true];

		if (PHP_VERSION_ID < 80100) {
			return;
		}

		yield ['ReturnsByReference\\E', 'enumFoo', false];
		yield ['ReturnsByReference\\E', 'refEnumFoo', true];
	}

	/**
	 * @dataProvider dataMethodReturnsByReference
	 */
	public function testMethodReturnsByReference(string $className, string $methodName, bool $expectedReturnsByRef): void
	{
		$reflectionProvider = $this->createReflectionProvider();
		$class = $reflectionProvider->getClass($className);
		$scope = $this->createMock(Scope::class);
		$scope->method('isInClass')->willReturn(true);
		$scope->method('getClassReflection')->willReturn($class);
		$scope->method('canAccessProperty')->willReturn(true);
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
