<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Rules\Classes\ConsistentConstructorHelper;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use function sprintf;

/** @extends RuleTestCase<ConsistentConstructorRule> */
class ConsistentConstructorRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$container = self::getContainer();
		return new ConsistentConstructorRule(
			new ConsistentConstructorHelper(),
			$container->getByType(MethodParameterComparisonHelper::class),
			$container->getByType(MethodVisibilityComparisonHelper::class),
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/consistent-constructor.php'], [
			[
				sprintf('Parameter #1 $b (int) of method ConsistentConstructor\Bar2::__construct() is not %s with parameter #1 $b (string) of method ConsistentConstructor\Bar::__construct().', 'contravariant'),
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
			[
				'Parameter #1 $i of method ConsistentConstructor\ChildTwo::__construct() is not optional.',
				102,
			],
		]);
	}

	public function testRuleNoErrors(): void
	{
		$this->analyse([__DIR__ . '/data/consistent-constructor-no-errors.php'], []);
	}

	public function testBug12137(): void
	{
		$this->analyse([__DIR__ . '/data/bug-12137.php'], [
			[
				'Private method Bug12137\ChildClass::__construct() overriding protected method Bug12137\ParentClass::__construct() should be protected or public.',
				20,
			],
		]);
	}

}
