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

	public function dataFunctionThrowType(): array
	{
		return [
			[
				'rand',
				new VoidType(),
			],
			[
				'sodium_crypto_kx_keypair',
				new ObjectType('SodiumException'),
			],
			[
				'bcdiv',
				new ObjectType('DivisionByZeroError'),
			],
			[
				'GEOSRelateMatch',
				new ObjectType('Exception'),
			],
			[
				'random_int',
				new ObjectType('Exception'),
			],
		];
	}

	/**
	 * @dataProvider dataFunctionThrowType
	 * @param string $functionName
	 * @param Type $expectedThrowType
	 */
	public function testFunctionThrowType(string $functionName, Type $expectedThrowType): void
	{
		$reflectionProvider = $this->createReflectionProvider();
		$function = $reflectionProvider->getFunction(new Name($functionName), null);
		$throwType = $function->getThrowType();
		$this->assertNotNull($throwType);
		$this->assertSame(
			$expectedThrowType->describe(VerbosityLevel::precise()),
			$throwType->describe(VerbosityLevel::precise())
		);
	}

}
