<?php declare(strict_types = 1);

namespace PHPStan\Fixable;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<FixIssetRule>
 */
class FixIssetRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new FixIssetRule();
	}

	public function testRule(): void
	{
		$this->fix(__DIR__ . '/data/isset.php', __DIR__ . '/data/isset.php.fixed');
	}

}
