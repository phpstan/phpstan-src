<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PhpParser\Node\Name;
use PHPStan\Testing\TestCase;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use PHPStan\Type\VoidType;

class ReflectionProviderTest extends TestCase
{

	public function dataFunctionThrowType(): iterable
	{
		yield [
			'rand',
			null,
		];

		if (PHP_VERSION_ID >= 70200) {
			yield [
				'sodium_crypto_kx_keypair',
				new ObjectType('SodiumException'),
			];
		}

		yield [
			'bcdiv',
			new ObjectType('DivisionByZeroError'),
		];

		yield [
			'GEOSRelateMatch',
			new ObjectType('Exception'),
		];

		yield [
			'random_int',
			new ObjectType('Exception'),
		];
	}

	/**
	 * @dataProvider dataFunctionThrowType
	 * @param string $functionName
	 * @param ?Type $expectedThrowType
	 */
	public function testFunctionThrowType(string $functionName, ?Type $expectedThrowType): void
	{
		$reflectionProvider = $this->createReflectionProvider();
		$function = $reflectionProvider->getFunction(new Name($functionName), null);
		$throwType = $function->getThrowType();
		if ($expectedThrowType === null) {
			$this->assertNull($throwType);
			return;
		}
		$this->assertNotNull($throwType);
		$this->assertSame(
			$expectedThrowType->describe(VerbosityLevel::precise()),
			$throwType->describe(VerbosityLevel::precise())
		);
	}

	public function dataMethodThrowType(): array
	{
		return [
			[
				\DateTime::class,
				'__construct',
				new ObjectType('Exception'),
			],
			[
				\DateTime::class,
				'format',
				null,
			],
		];
	}

	/**
	 * @dataProvider dataMethodThrowType
	 * @param string $className
	 * @param string $methodName
	 * @param ?Type $expectedThrowType
	 */
	public function testMethodThrowType(string $className, string $methodName, ?Type $expectedThrowType): void
	{
		$reflectionProvider = $this->createBroker();
		$class = $reflectionProvider->getClass($className);
		$method = $class->getNativeMethod($methodName);
		$throwType = $method->getThrowType();
		if ($expectedThrowType === null) {
			$this->assertNull($throwType);
			return;
		}
		$this->assertNotNull($throwType);
		$this->assertSame(
			$expectedThrowType->describe(VerbosityLevel::precise()),
			$throwType->describe(VerbosityLevel::precise())
		);
	}

}
