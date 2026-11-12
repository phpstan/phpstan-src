<?php declare(strict_types = 1);

namespace PHPStan\Build;

use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Node\Printer\Printer;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<NullsafeOperatorRule>
 */
final class NullsafeOperatorRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new NullsafeOperatorRule(new ExprPrinter(new Printer()));
	}

	public function testRule(): void
	{
		$message = 'This null check can be replaced with the nullsafe operator (?->).';
		$this->analyse([__DIR__ . '/data/nullsafe-operator.php'], [
			[$message, 22],
			[$message, 23],
			[$message, 24],
			[$message, 25],
			[$message, 26],
			[$message, 27],
			[$message, 29],
			[$message, 34],
		]);
	}

	public function testFix(): void
	{
		$this->fix(__DIR__ . '/data/nullsafe-operator.php', __DIR__ . '/data/nullsafe-operator.php.fixed');
	}

}
