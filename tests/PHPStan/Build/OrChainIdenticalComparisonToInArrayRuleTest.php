<?php declare(strict_types = 1);

namespace PHPStan\Build;

use PHPStan\File\FileHelper;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Node\Printer\Printer;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<OrChainIdenticalComparisonToInArrayRule>
 */
final class OrChainIdenticalComparisonToInArrayRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new OrChainIdenticalComparisonToInArrayRule(new ExprPrinter(new Printer()), self::getContainer()->getByType(FileHelper::class), false);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/or-chain-identical-comparison.php'], [
			[
				'This chain of identical comparisons can be simplified using in_array().',
				7,
			],
			[
				'This chain of identical comparisons can be simplified using in_array().',
				11,
			],
			[
				'This chain of identical comparisons can be simplified using in_array().',
				15,
			],
			[
				'This chain of identical comparisons can be simplified using in_array().',
				17,
			],
		]);
	}

	public function testFix(): void
	{
		$this->fix(__DIR__ . '/data/or-chain-identical-comparison.php', __DIR__ . '/data/or-chain-identical-comparison.php.fixed');
	}

}
