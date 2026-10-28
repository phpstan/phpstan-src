<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<ReturnedExprGetTypeRule>
 */
class ReturnedExprGetTypeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new ReturnedExprGetTypeRule();
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/returned-expr-get-type.php'], []);
	}

}
