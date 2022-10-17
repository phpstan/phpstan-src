<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ImpossibleCheckTypeMethodCallRule>
 */
class ImpossibleCheckTypeGenericOverwriteRuleTest extends RuleTestCase
{

	public function getRule(): Rule
	{
		return new ImpossibleCheckTypeMethodCallRule(
			new ImpossibleCheckTypeHelper(
				$this->createReflectionProvider(),
				$this->getTypeSpecifier(),
				[],
				true,
				true,
			),
			true,
			true,
		);
	}

	public function testNoReportedErrorOnOverwrite(): void
	{
		$this->analyse([__DIR__ . '/data/generic-type-override.php'], []);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/impossible-check-type-generic-overwrite.neon',
		];
	}

}
