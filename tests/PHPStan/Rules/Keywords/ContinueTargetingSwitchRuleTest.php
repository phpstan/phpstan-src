<?php declare(strict_types = 1);

namespace PHPStan\Rules\Keywords;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ContinueTargetingSwitchRule>
 */
class ContinueTargetingSwitchRuleTest extends RuleTestCase
{

	private int $phpVersionId = 70300;

	protected function getRule(): Rule
	{
		return new ContinueTargetingSwitchRule(new PhpVersion($this->phpVersionId));
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/continue-targeting-switch.php'], [
			[
				'Keyword continue targeting switch is equivalent to break.',
				10,
			],
			[
				'Keyword continue 2 targeting switch is equivalent to break 2.',
				14,
			],
			[
				'Keyword continue targeting switch is equivalent to break.',
				27,
				'Did you mean to use continue 2 to continue the enclosing loop? Use break to keep the current behaviour.',
			],
			[
				'Keyword continue 2 targeting switch is equivalent to break 2.',
				32,
				'Did you mean to use continue 3 to continue the enclosing loop? Use break 2 to keep the current behaviour.',
			],
			[
				'Keyword continue targeting switch is equivalent to break.',
				46,
				'Did you mean to use continue 3 to continue the enclosing loop? Use break to keep the current behaviour.',
			],
			[
				'Keyword continue targeting switch is equivalent to break.',
				64,
			],
		]);
	}

	public function testOlderPhp(): void
	{
		$this->phpVersionId = 70200;
		$this->analyse([__DIR__ . '/data/continue-targeting-switch.php'], []);
	}

	public function testFix(): void
	{
		$this->fix(__DIR__ . '/data/continue-targeting-switch.php', __DIR__ . '/data/continue-targeting-switch.php.fixed');
	}

}
