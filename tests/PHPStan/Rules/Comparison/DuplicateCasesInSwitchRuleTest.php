<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Node\Printer\Printer;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use function define;
use function defined;

/**
 * @extends RuleTestCase<DuplicateCasesInSwitchRule>
 */
class DuplicateCasesInSwitchRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new DuplicateCasesInSwitchRule(
			new ExprPrinter(new Printer()),
		);
	}

	public function testDuplicateCases(): void
	{
		if (!defined('DUPLICATE_SWITCH_CASE_CONST')) {
			define('DUPLICATE_SWITCH_CASE_CONST', 'unknown');
		}

		$this->analyse([__DIR__ . '/data/duplicate-cases-in-switch.php'], [
			[
				'Case \'lb\' in switch is a duplicate of case \'lb\' on line 24.',
				30,
			],
			[
				'Case \'oz\' in switch is a duplicate of case \'oz\' on line 27.',
				33,
			],
			[
				'Case 1 in switch is a duplicate of case 1 on line 42.',
				46,
			],
			[
				'Case \'x\' in switch is a duplicate of case \'x\' on line 54.',
				58,
			],
			[
				'Case \'x\' in switch is a duplicate of case \'x\' on line 54.',
				60,
			],
			[
				'Case self::EQ in switch is a duplicate of case \'=\' on line 68.',
				72,
			],
			[
				'Case DUPLICATE_SWITCH_CASE_CONST in switch is a duplicate of case \'unknown\' on line 80.',
				82,
			],
			[
				'Case \'a\' in switch is a duplicate of case \'a\' on line 90.',
				94,
			],
			[
				'Case true in switch is a duplicate of case true on line 103.',
				107,
			],
			[
				'Case null in switch is a duplicate of case null on line 105.',
				109,
			],
		]);
	}

	public function testDuplicateCasesEnum(): void
	{
		$this->analyse([__DIR__ . '/data/duplicate-cases-in-switch-enum.php'], [
			[
				'Case \DuplicateCasesInSwitchEnum\Status::Active in switch is a duplicate of case \DuplicateCasesInSwitchEnum\Status::Active on line 20.',
				24,
			],
		]);
	}

}
