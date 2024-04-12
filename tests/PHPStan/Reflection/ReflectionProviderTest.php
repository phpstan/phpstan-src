<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use DateTime;
use PhpParser\Node\Name;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\TrinaryLogic;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use const PHP_VERSION_ID;

class ReflectionProviderTest extends PHPStanTestCase
{

	public function dataFunctionThrowType(): iterable
	{
		yield [
			'rand',
			null,
		];

		yield [
			'sodium_crypto_kx_keypair',
			new ObjectType('SodiumException'),
		];

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
			new ObjectType('Random\RandomException'),
		];
	}

	/**
	 * @dataProvider dataFunctionThrowType
	 *
	 * @param non-empty-string $functionName
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
			$throwType->describe(VerbosityLevel::precise()),
		);
	}

	public function dataFunctionDeprecated(): iterable
	{
		if (PHP_VERSION_ID < 80000) {
			yield 'create_function' => [
				'create_function',
				true,
			];
			yield 'each' => [
				'each',
				true,
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
	 *
	 * @param non-empty-string $functionName
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
				DateTime::class,
				'__construct',
				new ObjectType('Exception'),
			],
			[
				DateTime::class,
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
			$throwType->describe(VerbosityLevel::precise()),
		);
	}

	public function testNativeClassConstantTypeInEvaledClass(): void
	{
		if (PHP_VERSION_ID < 80300) {
			$this->markTestSkipped('Test requires PHP 8.3.');
		}

		eval('namespace NativeClassConstantInEvaledClass; class Foo { public const int FOO = 1; }');

		$reflectionProvider = $this->createReflectionProvider();
		$class = $reflectionProvider->getClass('NativeClassConstantInEvaledClass\\Foo');
		$constant = $class->getConstant('FOO');
		$this->assertSame('int', $constant->getValueType()->describe(VerbosityLevel::precise()));
	}

}
