<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

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
				'Parameter #1 $b (int) of method ConsistentConstructor\Bar2::__construct() is not contravariant with parameter #1 $b (string) of method ConsistentConstructor\Bar::__construct().',
				13,
			],
			[
				'Method ConsistentConstructor\Foo2::__construct() overrides method ConsistentConstructor\Foo1::__construct() but misses parameter #1 $a.',
				32,
			],
		]);
	}

	public function testRuleNoErrors(): void
	{
		$this->analyse([__DIR__ . '/data/consistent-constructor-no-errors.php'], []);
	}

}
