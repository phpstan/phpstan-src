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
		return new ApiInstanceofRule(new ApiRuleHelper(), self::createReflectionProvider());
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
		$instanceofTip = sprintf(
			"In case of questions how to solve this correctly, open a discussion:\n   %s\n\n   See also:\n   https://phpstan.org/developing-extensions/backward-compatibility-promise",
			'https://github.com/phpstan/phpstan/discussions',
		);

		$this->analyse([__DIR__ . '/data/instanceof-out-of-phpstan.php'], [
			[
				'Although PHPStan\Reflection\ClassReflection is covered by backward compatibility promise, this instanceof assumption might break because it\'s not guaranteed to always stay the same.',
				17,
				$instanceofTip,
			],
			[
				'Asking about instanceof PHPStan\Reflection\BetterReflection\SourceLocator\AutoloadSourceLocator is not covered by backward compatibility promise. The class might change in a minor PHPStan version.',
				21,
				$tip,
			],
			[
				'Although PHPStan\Reflection\ClassReflection is covered by backward compatibility promise, this instanceof assumption might break because it\'s not guaranteed to always stay the same.',
				41,
				$instanceofTip,
			],
		]);
	}

}
