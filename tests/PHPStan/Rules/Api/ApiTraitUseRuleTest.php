<?php declare(strict_types = 1);

namespace PHPStan\Rules\Api;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use function sprintf;

/**
 * @extends RuleTestCase<ApiTraitUseRule>
 */
class ApiTraitUseRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new ApiTraitUseRule(new ApiRuleHelper(), self::createReflectionProvider());
	}

	public function testRuleInPhpStan(): void
	{
		$this->analyse([__DIR__ . '/data/trait-use-in-phpstan.php'], []);
	}

	public function testRuleOutOfPhpStan(): void
	{
		$tip = sprintf(
			"If you think it should be covered by backward compatibility promise, open a discussion:\n   %s\n\n   See also:\n   https://phpstan.org/developing-extensions/backward-compatibility-promise",
			'https://github.com/phpstan/phpstan/discussions',
		);

		$this->analyse([__DIR__ . '/data/trait-use-out-of-phpstan.php'], [
			[
				'Using PHPStan\Type\JustNullableTypeTrait is not covered by backward compatibility promise. The trait might change in a minor PHPStan version.',
				15,
				$tip,
			],
		]);
	}

}
