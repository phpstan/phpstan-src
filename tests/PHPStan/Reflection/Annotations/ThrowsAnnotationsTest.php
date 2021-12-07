<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Annotations;

use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\VerbosityLevel;
use RuntimeException;
use ThrowsAnnotations\BarTrait;
use ThrowsAnnotations\Foo;
use ThrowsAnnotations\FooInterface;
use ThrowsAnnotations\FooTrait;
use ThrowsAnnotations\PhpstanFoo;

class ThrowsAnnotationsTest extends PHPStanTestCase
{

	public function dataThrowsAnnotations(): array
	{
		return [
			[
				Foo::class,
				[
					'withoutThrows' => null,
					'throwsRuntime' => RuntimeException::class,
					'staticThrowsRuntime' => RuntimeException::class,

				],
			],
			[
				PhpstanFoo::class,
				[
					'withoutThrows' => 'void',
					'throwsRuntime' => RuntimeException::class,
					'staticThrowsRuntime' => RuntimeException::class,

				],
			],
			[
				FooInterface::class,
				[
					'withoutThrows' => null,
					'throwsRuntime' => RuntimeException::class,
					'staticThrowsRuntime' => RuntimeException::class,

				],
			],
			[
				FooTrait::class,
				[
					'withoutThrows' => null,
					'throwsRuntime' => RuntimeException::class,
					'staticThrowsRuntime' => RuntimeException::class,

				],
			],
			[
				BarTrait::class,
				[
					'withoutThrows' => null,
					'throwsRuntime' => RuntimeException::class,
					'staticThrowsRuntime' => RuntimeException::class,

				],
			],
		];
	}

	/**
	 * @dataProvider dataThrowsAnnotations
	 * @param array<string, mixed> $throwsAnnotations
	 */
	public function testThrowsAnnotations(string $className, array $throwsAnnotations): void
	{
		$reflectionProvider = $this->createReflectionProvider();
		$class = $reflectionProvider->getClass($className);
		$scope = $this->createMock(Scope::class);

		foreach ($throwsAnnotations as $methodName => $type) {
			$methodAnnotation = $class->getMethod($methodName, $scope);
			$throwType = $methodAnnotation->getThrowType();
			$this->assertSame($type, $throwType !== null ? $throwType->describe(VerbosityLevel::typeOnly()) : null);
		}
	}

	public function testThrowsOnUserFunctions(): void
	{
		require_once __DIR__ . '/data/annotations-throws.php';

		$reflectionProvider = $this->createReflectionProvider();

		$this->assertNull($reflectionProvider->getFunction(new Name\FullyQualified('ThrowsAnnotations\withoutThrows'), null)->getThrowType());

		$throwType = $reflectionProvider->getFunction(new Name\FullyQualified('ThrowsAnnotations\throwsRuntime'), null)->getThrowType();
		$this->assertNotNull($throwType);
		$this->assertSame(RuntimeException::class, $throwType->describe(VerbosityLevel::typeOnly()));
	}

}
