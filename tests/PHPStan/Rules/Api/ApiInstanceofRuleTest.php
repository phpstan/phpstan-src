<?php declare(strict_types = 1);

namespace PHPStan\Rules\Api;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use function sprintf;

/**
 * @extends RuleTestCase<ApiInstanceofRule>
 */
class ApiInstanceofRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new ApiInstanceofRule(new ApiRuleHelper(), $this->createReflectionProvider());
	}

	public function testRuleInPhpStan(): void
	{
		$this->analyse([__DIR__ . '/data/instanceof-in-phpstan.php'], []);
	}

	public function testRuleOutOfPhpStan(): void
	{
		$tip = sprintf(
			"If you think it should be covered by backward compatibility promise, open a discussion:\n   %s\n\n   See also:\n   https://phpstan.org/developing-extensions/backward-compatibility-promise",
			'https://github.com/phpstan/phpstan/discussions',
		);

		$this->analyse([__DIR__ . '/data/instanceof-out-of-phpstan.php'], [
			[
				'Asking about instanceof PHPStan\Reflection\BetterReflection\SourceLocator\AutoloadSourceLocator is not covered by backward compatibility promise. The class might change in a minor PHPStan version.',
				17,
				$tip,
			],
		]);
	}

}
