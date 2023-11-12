<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use function sprintf;
use const PHP_VERSION_ID;

/** @extends RuleTestCase<ConsistentConstructorRule> */
class ConsistentConstructorRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new ConsistentConstructorRule(self::getContainer()->getByType(MethodParameterComparisonHelper::class));
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/consistent-constructor.php'], [
			[
				sprintf('Parameter #1 $b (int) of method ConsistentConstructor\Bar2::__construct() is not %s with parameter #1 $b (string) of method ConsistentConstructor\Bar::__construct().', PHP_VERSION_ID >= 70400 ? 'contravariant' : 'compatible'),
				13,
			],
			[
				'Method ConsistentConstructor\Foo2::__construct() overrides method ConsistentConstructor\Foo1::__construct() but misses parameter #1 $a.',
				32,
			],
			[
				'Parameter #1 $i of method ConsistentConstructor\ParentWithoutConstructorChildWithConstructorRequiredParams::__construct() is not optional.',
				58,
			],
			[
				'Method ConsistentConstructor\FakeConnection::__construct() overrides method ConsistentConstructor\Connection::__construct() but misses parameter #1 $i.',
				78,
			],
		]);
	}

	public function testRuleNoErrors(): void
	{
		$this->analyse(
			[__DIR__ . '/data/consistent-constructor-no-errors.php'],
			PHP_VERSION_ID < 70400 ? [['Parameter #1 $b (ConsistentConstructorNoErrors\A) of method ConsistentConstructorNoErrors\Baz::__construct() is not compatible with parameter #1 $b (ConsistentConstructorNoErrors\B) of method ConsistentConstructorNoErrors\Foo::__construct().', 49]] : [],
		);
	}

}
