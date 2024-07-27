<?php declare(strict_types = 1);

namespace PHPStan\Rules\Api;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use function sprintf;

/**
 * @extends RuleTestCase<ApiClassImplementsRule>
 */
class ApiClassImplementsRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new ApiClassImplementsRule(new ApiRuleHelper(), $this->createReflectionProvider());
	}

	public function testRuleInPhpStan(): void
	{
		$this->analyse([__DIR__ . '/data/class-implements-in-phpstan.php'], []);
	}

	public function testRuleOutOfPhpStan(): void
	{
		$tip = sprintf(
			"If you think it should be covered by backward compatibility promise, open a discussion:\n   %s\n\n   See also:\n   https://phpstan.org/developing-extensions/backward-compatibility-promise",
			'https://github.com/phpstan/phpstan/discussions',
		);

		$this->analyse([__DIR__ . '/data/class-implements-out-of-phpstan.php'], [
			[
				'Implementing PHPStan\DependencyInjection\Type\DynamicThrowTypeExtensionProvider is not covered by backward compatibility promise. The interface might change in a minor PHPStan version.',
				19,
				$tip,
			],
			[
				'Implementing PHPStan\Type\Type is not covered by backward compatibility promise. The interface might change in a minor PHPStan version.',
				53,
				$tip,
			],
			[
				'Implementing PHPStan\Reflection\ReflectionProvider is not covered by backward compatibility promise. The interface might change in a minor PHPStan version.',
				338,
				$tip,
			],
			[
				'Implementing PHPStan\Analyser\Scope is not covered by backward compatibility promise. The interface might change in a minor PHPStan version.',
				343,
				$tip,
			],
			[
				'Implementing PHPStan\Reflection\FunctionReflection is not covered by backward compatibility promise. The interface might change in a minor PHPStan version.',
				348,
				$tip,
			],
			[
				'Implementing PHPStan\Reflection\ExtendedMethodReflection is not covered by backward compatibility promise. The interface might change in a minor PHPStan version.',
				352,
				$tip,
			],
		]);
	}

}
