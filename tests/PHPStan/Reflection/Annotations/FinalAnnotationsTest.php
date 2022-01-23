<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Annotations;

use FinalAnnotations\FinalFoo;
use FinalAnnotations\Foo;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Testing\PHPStanTestCase;

class FinalAnnotationsTest extends PHPStanTestCase
{

	public function dataFinalAnnotations(): array
	{
		return [
			[
				false,
				Foo::class,
				[
					'method' => [
						'foo',
						'staticFoo',
					],
				],
			],
			[
				true,
				FinalFoo::class,
				[
					'method' => [
						'finalFoo',
						'finalStaticFoo',
					],
				],
			],
		];
	}

	/**
	 * @dataProvider dataFinalAnnotations
	 * @param array<string, mixed> $finalAnnotations
	 */
	public function testFinalAnnotations(bool $final, string $className, array $finalAnnotations): void
	{
		$reflectionProvider = $this->createReflectionProvider();
		$class = $reflectionProvider->getClass($className);
		$scope = $this->createMock(Scope::class);
		$scope->method('isInClass')->willReturn(true);
		$scope->method('getClassReflection')->willReturn($class);
		$scope->method('canAccessProperty')->willReturn(true);

		$this->assertSame($final, $class->isFinal());

		foreach ($finalAnnotations['method'] ?? [] as $methodName) {
			$methodAnnotation = $class->getMethod($methodName, $scope);
			$this->assertSame($final, $methodAnnotation->isFinal()->yes());
		}
	}

	public function testFinalUserFunctions(): void
	{
		require_once __DIR__ . '/data/annotations-final.php';

		$reflectionProvider = $this->createReflectionProvider();

		$this->assertFalse($reflectionProvider->getFunction(new Name\FullyQualified('FinalAnnotations\foo'), null)->isFinal()->yes());
		$this->assertTrue($reflectionProvider->getFunction(new Name\FullyQualified('FinalAnnotations\finalFoo'), null)->isFinal()->yes());
	}

}
