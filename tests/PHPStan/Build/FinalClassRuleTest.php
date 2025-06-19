<?php declare(strict_types = 1);

namespace PHPStan\Build;

use PHPStan\File\FileHelper;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<FinalClassRule>
 */
class FinalClassRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new FinalClassRule(self::getContainer()->getByType(FileHelper::class), false);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/final-class-rule.php'], [
			[
				'Class FinalClassRule\Baz must be abstract or final.',
				29,
			],
		]);
	}

	public function testFix(): void
	{
		$this->fix(__DIR__ . '/data/final-class-rule.php', __DIR__ . '/data/final-class-rule.php.fixed');
	}

}
