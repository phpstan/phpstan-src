<?php declare(strict_types = 1);

namespace PHPStan\Rules\Api;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use function sprintf;

/**
 * @extends RuleTestCase<ApiInstantiationRule>
 */
class ApiInstantiationRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new ApiInstantiationRule(
			new ApiRuleHelper(),
			$this->createReflectionProvider(),
		);
	}

	public function testRuleInPhpStan(): void
	{
		$this->analyse([__DIR__ . '/data/new-in-phpstan.php'], []);
	}

	public function testRuleOutOfPhpStan(): void
	{
		$tip = sprintf(
			"If you think it should be covered by backward compatibility promise, open a discussion:\n   %s\n\n   See also:\n   https://phpstan.org/developing-extensions/backward-compatibility-promise",
			'https://github.com/phpstan/phpstan/discussions',
		);
		$this->analyse([__DIR__ . '/data/new-out-of-phpstan.php'], [
			[
				'Creating new PHPStan\Type\FileTypeMapper is not covered by backward compatibility promise. The class might change in a minor PHPStan version.',
				17,
				$tip,
			],
			[
				'Creating new PHPStan\DependencyInjection\NeonAdapter is not covered by backward compatibility promise. The class might change in a minor PHPStan version.',
				18,
				$tip,
			],
		]);
	}

}
