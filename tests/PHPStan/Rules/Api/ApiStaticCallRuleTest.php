<?php declare(strict_types = 1);

namespace PHPStan\Rules\Api;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use function sprintf;

/**
 * @extends RuleTestCase<ApiStaticCallRule>
 */
class ApiStaticCallRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new ApiStaticCallRule(new ApiRuleHelper(), self::createReflectionProvider());
	}

	public function testRuleInPhpStan(): void
	{
		$this->analyse([__DIR__ . '/data/static-call-in-phpstan.php'], []);
	}

	public function testRuleOutOfPhpStan(): void
	{
		$tip = sprintf(
			"If you think it should be covered by backward compatibility promise, open a discussion:\n   %s\n\n   See also:\n   https://phpstan.org/developing-extensions/backward-compatibility-promise",
			'https://github.com/phpstan/phpstan/discussions',
		);

		$this->analyse([__DIR__ . '/data/static-call-out-of-phpstan.php'], [
			[
				'Calling PHPStan\Command\CommandHelper::begin() is not covered by backward compatibility promise. The method might change in a minor PHPStan version.',
				17,
				$tip,
			],
			[
				'Calling PHPStan\Node\InClassNode::__construct() is not covered by backward compatibility promise. The method might change in a minor PHPStan version.',
				33,
				$tip,
			],
		]);
	}

}
