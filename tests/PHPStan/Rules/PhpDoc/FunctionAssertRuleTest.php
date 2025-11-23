<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassForbiddenNameCheck;
use PHPStan\Rules\ClassNameCheck;
use PHPStan\Rules\Generics\GenericObjectTypeCheck;
use PHPStan\Rules\MissingTypehintCheck;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<FunctionAssertRule>
 */
class FunctionAssertRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$reflectionProvider = self::createReflectionProvider();
		$container = self::getContainer();
		return new FunctionAssertRule(new AssertRuleHelper(
			$reflectionProvider,
			new UnresolvableTypeHelper(),
			new ClassNameCheck(
				new ClassCaseSensitivityCheck($reflectionProvider, true),
				new ClassForbiddenNameCheck($container),
				$reflectionProvider,
				$container,
			),
			new MissingTypehintCheck(true, []),
			new GenericObjectTypeCheck(),
			true,
			true,
		));
	}

	public function testRule(): void
	{
		require_once __DIR__ . '/data/function-assert.php';
		$this->analyse([__DIR__ . '/data/function-assert.php'], [
			[
				'Asserted type int for $i with type int does not narrow down the type.',
				8,
			],
			[
				'Asserted type int for $i with type int does not narrow down the type.',
				17,
			],
			[
				'Asserted type int for $i with type int does not narrow down the type.',
				26,
			],
			[
				'Asserted type int|string for $i with type int does not narrow down the type.',
				42,
			],
			[
				'Asserted type string for $i with type int can never happen.',
				49,
			],
			[
				'Assert references unknown parameter $j.',
				56,
			],
			[
				'Asserted negated type int for $i with type int can never happen.',
				63,
			],
			[
				'Asserted negated type string for $i with type int does not narrow down the type.',
				70,
			],
			[
				'PHPDoc tag @phpstan-assert for $array has no value type specified in iterable type list.',
				88,
				'See: https://phpstan.org/blog/solving-phpstan-no-value-type-specified-in-iterable-type',
			],
		]);
	}

}
