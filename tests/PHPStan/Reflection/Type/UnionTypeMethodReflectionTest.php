<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Type;

use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\TrinaryLogic;

class UnionTypeMethodReflectionTest extends PHPStanTestCase
{

	public function testCollectsDeprecatedMessages(): void
	{
		$reflection = new UnionTypeMethodReflection(
			'foo',
			[
				$this->createDeprecatedMethod(TrinaryLogic::createYes(), 'Deprecated'),
				$this->createDeprecatedMethod(TrinaryLogic::createMaybe(), 'Maybe deprecated'),
				$this->createDeprecatedMethod(TrinaryLogic::createNo(), 'Not deprecated'),
			],
		);

		$this->assertSame('Deprecated', $reflection->getDeprecatedDescription());
	}

	public function testMultipleDeprecationsAreJoined(): void
	{
		$reflection = new UnionTypeMethodReflection(
			'foo',
			[
				$this->createDeprecatedMethod(TrinaryLogic::createYes(), 'Deprecated #1'),
				$this->createDeprecatedMethod(TrinaryLogic::createYes(), 'Deprecated #2'),
			],
		);

		$this->assertSame('Deprecated #1 Deprecated #2', $reflection->getDeprecatedDescription());
	}

	private function createDeprecatedMethod(TrinaryLogic $deprecated, ?string $deprecationText): ExtendedMethodReflection
	{
		$method = $this->createStub(ExtendedMethodReflection::class);
		$method->method('isDeprecated')->willReturn($deprecated);
		$method->method('getDeprecatedDescription')->willReturn($deprecationText);
		return $method;
	}

}
