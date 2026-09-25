<?php declare(strict_types = 1);

namespace PHPStan\Rules\Pure;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<PureFunctionRule>
 */
class Bug15224Php83PureFunctionRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new PureFunctionRule(new FunctionPurityCheck());
	}

	public function testBug15224(): void
	{
		$this->analyse([__DIR__ . '/data/bug-15224-php83.php'], []);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/data/bug-15224-php83.neon',
		];
	}

}
