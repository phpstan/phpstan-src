<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use PHPStan\Rules\RuleLevelHelper;

/**
 * @extends \PHPStan\Testing\RuleTestCase<VariableCloningRule>
 */
class VariableCloningRuleTest extends \PHPStan\Testing\RuleTestCase
{

	protected function getRule(): \PHPStan\Rules\Rule
	{
		return new VariableCloningRule(new RuleLevelHelper($this->createReflectionProvider(), true, false, true, false));
	}

	public function testClone(): void
	{
		$this->analyse([__DIR__ . '/data/variable-cloning.php'], [
			[
				'Cannot clone int|string.',
				11,
			],
			[
				'Cannot clone non-object variable $stringData of type string.',
				14,
			],
			[
				'Cannot clone string.',
				15,
			],
			[
				'Cannot clone non-object variable $bar of type string|VariableCloning\Foo.',
				19,
			],
			[
				'Cloning object of an unknown class VariableCloning\Bar.',
				23,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
		]);
	}

	public function testRuleWithNullsafeVariant(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$this->analyse([__DIR__ . '/data/variable-cloning-nullsafe.php'], [
			[
				'Cannot clone stdClass|null.',
				11,
			],
		]);
	}

}
