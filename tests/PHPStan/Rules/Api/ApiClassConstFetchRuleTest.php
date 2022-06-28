<?php declare(strict_types = 1);

namespace PHPStan\Rules\Api;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use function sprintf;

/**
 * @extends RuleTestCase<ApiClassConstFetchRule>
 */
class ApiClassConstFetchRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new ApiClassConstFetchRule(new ApiRuleHelper(), $this->createReflectionProvider());
	}

	public function testRuleInPhpStan(): void
	{
		$this->analyse([__DIR__ . '/data/class-const-fetch-in-phpstan.php'], []);
	}

	public function testRuleOutOfPhpStan(): void
	{
		$tip = sprintf(
			"If you think it should be covered by backward compatibility promise, open a discussion:\n   %s\n\n   See also:\n   https://phpstan.org/developing-extensions/backward-compatibility-promise",
			'https://github.com/phpstan/phpstan/discussions',
		);

		$this->analyse([__DIR__ . '/data/class-const-fetch-out-of-phpstan.php'], [
			[
				'Accessing PHPStan\Command\AnalyseCommand::class is not covered by backward compatibility promise. The class might change in a minor PHPStan version.',
				16,
				$tip,
			],
			[
				'Accessing PHPStan\Analyser\NodeScopeResolver::FOO is not covered by backward compatibility promise. The class might change in a minor PHPStan version.',
				20,
				$tip,
			],
		]);
	}

}
