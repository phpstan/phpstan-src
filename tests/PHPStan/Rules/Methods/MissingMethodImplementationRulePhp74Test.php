<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<MissingMethodImplementationRule>
 */
class MissingMethodImplementationRulePhp74Test extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new MissingMethodImplementationRule();
	}

	public function testBug14964(): void
	{
		$this->analyse([__DIR__ . '/data/bug-14964.php'], []);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/data/missing-method-impl-php74.neon',
		];
	}

}
