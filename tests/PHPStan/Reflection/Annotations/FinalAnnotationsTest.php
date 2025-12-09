<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Annotations;

use FinalAnnotations\FinalFoo;
use FinalAnnotations\Foo;
use PHPStan\Analyser\Scope;
use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class FinalAnnotationsTest extends PHPStanTestCase
{

	public static function dataFinalAnnotations(): array
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
	 * @param array<string, mixed> $finalAnnotations
	 */
	#[DataProvider('dataFinalAnnotations')]
	public function testFinalAnnotations(bool $final, string $className, array $finalAnnotations): void
	{
		$reflectionProvider = self::createReflectionProvider();
		$class = $reflectionProvider->getClass($className);
		$scope = $this->createStub(Scope::class);
		$scope->method('isInClass')->willReturn(true);
		$scope->method('getClassReflection')->willReturn($class);
		$scope->method('canAccessProperty')->willReturn(true);
		$scope->method('canReadProperty')->willReturn(true);
		$scope->method('canWriteProperty')->willReturn(true);

		$this->assertSame($final, $class->isFinal());

		foreach ($finalAnnotations['method'] ?? [] as $methodName) {
			$methodAnnotation = $class->getMethod($methodName, $scope);
			$this->assertSame($final, $methodAnnotation->isFinal()->yes());
		}
	}

}
