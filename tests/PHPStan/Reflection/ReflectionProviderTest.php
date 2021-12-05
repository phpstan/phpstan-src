<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PhpParser\Node\Name;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\TrinaryLogic;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;

class ReflectionProviderTest extends PHPStanTestCase
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

		if (PHP_VERSION_ID >= 80000) {
			yield [
				'bcdiv',
				new ObjectType('DivisionByZeroError'),
			];
		} else {
			yield [
				'bcdiv',
				null,
			];
		}

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

	public function dataFunctionDeprecated(): iterable
	{
		if (PHP_VERSION_ID < 80000) {
			yield 'create_function' => [
				'create_function',
				PHP_VERSION_ID >= 70200,
			];
			yield 'each' => [
				'each',
				PHP_VERSION_ID >= 70200,
			];
		}

		if (PHP_VERSION_ID < 90000) {
			yield 'date_sunrise' => [
				'date_sunrise',
				PHP_VERSION_ID >= 80100,
			];
		}

		yield 'strtolower' => [
			'strtolower',
			false,
		];
	}

	/**
	 * @dataProvider dataFunctionDeprecated
	 */
	public function testFunctionDeprecated(string $functionName, bool $isDeprecated): void
	{
		$reflectionProvider = $this->createReflectionProvider();
		$function = $reflectionProvider->getFunction(new Name($functionName), null);
		$this->assertEquals(TrinaryLogic::createFromBoolean($isDeprecated), $function->isDeprecated());
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
	 */
	public function testMethodThrowType(string $className, string $methodName, ?Type $expectedThrowType): void
	{
		$reflectionProvider = $this->createReflectionProvider();
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
