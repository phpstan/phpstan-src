<?php declare(strict_types = 1);

namespace PHPStan\Rules\Api;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use function sprintf;

/**
 * @extends RuleTestCase<ApiAttributeRule>
 */
class ApiAttributeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new ApiAttributeRule(new ApiRuleHelper(), self::createReflectionProvider());
	}

	public function testRuleInPhpStan(): void
	{
		$this->analyse([__DIR__ . '/data/attribute-in-phpstan.php'], []);
	}

	public function testRuleOutOfPhpStan(): void
	{
		$tip = sprintf(
			"If you think it should be covered by backward compatibility promise, open a discussion:\n   %s\n\n   See also:\n   https://phpstan.org/developing-extensions/backward-compatibility-promise",
			'https://github.com/phpstan/phpstan/discussions',
		);

		$this->analyse([__DIR__ . '/data/attribute-out-of-phpstan.php'], [
			[
				'Using attribute PHPStan\DependencyInjection\AutowiredExtensions is not covered by backward compatibility promise. The attribute might change in a minor PHPStan version.',
				23,
				$tip,
			],
			[
				'Using attribute PHPStan\DependencyInjection\ContainerExtension is not covered by backward compatibility promise. The attribute might change in a minor PHPStan version.',
				60,
				$tip,
			],
			[
				'Using attribute PHPStan\DependencyInjection\ValidatesStubFiles is not covered by backward compatibility promise. The attribute might change in a minor PHPStan version.',
				66,
				$tip,
			],
		]);
	}

}
