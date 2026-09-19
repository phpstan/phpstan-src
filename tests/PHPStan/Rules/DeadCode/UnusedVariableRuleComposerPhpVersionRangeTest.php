<?php declare(strict_types = 1);

namespace PHPStan\Rules\DeadCode;

use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<UnusedVariableRule>
 */
class UnusedVariableRuleComposerPhpVersionRangeTest extends RuleTestCase
{

	public static function getComposerAutoloaderProjectPaths(): array
	{
		return [__DIR__ . '/../../Analyser/data/composer-require-php-7-and-8'];
	}

	protected function getRule(): Rule
	{
		return new UnusedVariableRule(self::getContainer()->getByType(ExprPrinter::class));
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testCatchVariableNotReportedWhenComposerAllowsPhp7(): void
	{
		$this->analyse([__DIR__ . '/data/unused-variable-catch.php'], []);
	}

}
