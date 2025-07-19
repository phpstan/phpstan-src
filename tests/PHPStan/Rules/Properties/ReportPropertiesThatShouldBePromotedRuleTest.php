<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;
use function sprintf;

/**
 * @extends RuleTestCase<ReportPropertiesThatShouldBePromoted>
 */
class ReportPropertiesThatShouldBePromotedRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new ReportPropertiesThatShouldBePromoted(true);
	}

	#[RequiresPhp('>= 8.0')]
	public function testRule(): void
	{
		$error = static fn (string $property) => sprintf('Property [%s] should be promoted.', $property);

		$this->analyse([__DIR__ . '/data/properties-that-should-be-promoted.php'], [
			[$error('name'), 20],
		]);
	}

}
