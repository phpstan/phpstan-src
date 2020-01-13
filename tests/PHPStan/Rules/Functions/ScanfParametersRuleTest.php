<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

/**
 * @extends \PHPStan\Testing\RuleTestCase<ScanfParametersRule>
 */
class ScanfParametersRuleTest extends \PHPStan\Testing\RuleTestCase
{

	protected function getRule(): \PHPStan\Rules\Rule
	{
		return new ScanfParametersRule();
	}

	public function testFile(): void
	{
		$this->analyse([__DIR__ . '/data/scanf.php'], [
			[
				'Call to sscanf contains 2 placeholders, 1 value given.',
				5,
			],
			[
				'Call to fscanf contains 2 placeholders, 1 value given.',
				9,
			],
		]);
	}

}
