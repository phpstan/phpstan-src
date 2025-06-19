<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;
use function array_merge;

/**
 * @extends RuleTestCase<MatchExpressionRule>
 */
class MatchExpressionDoNotRememberPossiblyImpureValuesRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return self::getContainer()->getByType(MatchExpressionRule::class);
	}

	#[RequiresPhp('>= 8.1')]
	public function testBug9357(): void
	{
		$this->analyse([__DIR__ . '/data/bug-9357.php'], []);
	}

	#[RequiresPhp('>= 8.1')]
	public function testBug9007(): void
	{
		$this->analyse([__DIR__ . '/data/bug-9007.php'], []);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return array_merge(
			parent::getAdditionalConfigFiles(),
			[
				__DIR__ . '/doNotRememberPossiblyImpureValues.neon',
			],
		);
	}

}
