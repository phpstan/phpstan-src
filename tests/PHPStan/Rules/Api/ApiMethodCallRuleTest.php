<?php declare(strict_types = 1);

namespace PHPStan\Rules\Api;

use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ApiMethodCallRule>
 */
class ApiMethodCallRuleTest extends RuleTestCase
{

	protected function getRule(): \PHPStan\Rules\Rule
	{
		return new ApiMethodCallRule(new ApiRuleHelper());
	}

	public function testRuleInPhpStan(): void
	{
		$this->analyse([__DIR__ . '/data/method-call-in-phpstan.php'], []);
	}

	public function testRuleOutOfPhpStan(): void
	{
		$tip = sprintf(
			"If you think it should be covered by backward compatibility promise, open a discussion:\n   %s\n\n   See also:\n   https://phpstan.org/developing-extensions/backward-compatibility-promise",
			'https://github.com/phpstan/phpstan/discussions'
		);

		$this->analyse([__DIR__ . '/data/method-call-out-of-phpstan.php'], [
			[
				'Calling PHPStan\Rules\Debug\DumpTypeRule::getNodeType() is not covered by backward compatibility promise. The method might change in a minor PHPStan version.',
				17,
				$tip,
			],
			[
				'Calling PHPStan\Type\Php\ArrayKeyDynamicReturnTypeExtension::isFunctionSupported() is not covered by backward compatibility promise. The method might change in a minor PHPStan version.',
				27,
				$tip,
			],
		]);
	}

}
