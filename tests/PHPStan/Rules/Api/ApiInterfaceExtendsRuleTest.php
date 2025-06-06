<?php declare(strict_types = 1);

namespace PHPStan\Rules\Api;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use function sprintf;

/**
 * @extends RuleTestCase<ApiInterfaceExtendsRule>
 */
class ApiInterfaceExtendsRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new ApiInterfaceExtendsRule(new ApiRuleHelper(), self::createReflectionProvider());
	}

	public function testRuleInPhpStan(): void
	{
		$this->analyse([__DIR__ . '/data/interface-extends-in-phpstan.php'], []);
	}

	public function testRuleOutOfPhpStan(): void
	{
		$tip = sprintf(
			"If you think it should be covered by backward compatibility promise, open a discussion:\n   %s\n\n   See also:\n   https://phpstan.org/developing-extensions/backward-compatibility-promise",
			'https://github.com/phpstan/phpstan/discussions',
		);

		$this->analyse([__DIR__ . '/data/interface-extends-out-of-phpstan.php'], [
			[
				'Extending PHPStan\DependencyInjection\Type\DynamicThrowTypeExtensionProvider is not covered by backward compatibility promise. The interface might change in a minor PHPStan version.',
				10,
				$tip,
			],
			[
				'Extending PHPStan\Type\Type is not covered by backward compatibility promise. The interface might change in a minor PHPStan version.',
				20,
				$tip,
			],
			[
				'Extending PHPStan\Reflection\ReflectionProvider is not covered by backward compatibility promise. The interface might change in a minor PHPStan version.',
				25,
				$tip,
			],
			[
				'Extending PHPStan\Reflection\ExtendedMethodReflection is not covered by backward compatibility promise. The interface might change in a minor PHPStan version.',
				30,
				$tip,
			],
		]);
	}

}
