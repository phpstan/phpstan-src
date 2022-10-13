<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use Iterator;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Testing\PHPStanTestCase;

class FunctionReflectionTest extends PHPStanTestCase
{

	/**
	 * @dataProvider providePhpdocFunctions
	 */
	public function testFunctionHasPhpdoc(string $functionName, ?string $expectedDoc): void
	{
		require_once __DIR__ . '/data/function-with-phpdoc.php';

		$reflectionProvider = $this->createReflectionProvider();

		$functionReflection = $reflectionProvider->getFunction(new Node\Name($functionName), null);
		$this->assertSame($expectedDoc, $functionReflection->getDocComment());
	}

	public function providePhpdocFunctions(): Iterator
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
	}

	/**
	 * @dataProvider providePhpdocMethods
	 */
	public function testMethodHasPhpdoc(string $methodName, ?string $expectedDocComment): void
	{
		$className = 'FunctionReflectionDocTest\\ClassWithPhpdoc';

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

	public function providePhpdocMethods(): Iterator
	{
		yield [
			'aMethod',
			'/** some method phpdoc */',
		];
		yield [
			'noDocMethod',
			null,
		];
		yield [
			'docViaStub',
			'/** method doc via stub */',
		];
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
