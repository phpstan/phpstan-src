<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Methods\MethodParameterComparisonHelper;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use function sprintf;
use const PHP_VERSION_ID;

/** @extends RuleTestCase<InheritingClassMethodRule> */
class InheritingClassMethodRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new InheritingClassMethodRule(
			new PhpVersion(PHP_VERSION_ID),
			$this->createReflectionProvider(),
			new MethodParameterComparisonHelper(new PhpVersion(PHP_VERSION_ID)),
		);
	}

	public function testRule(): void
	{
		$expectedErrors = [
			[
				sprintf(
					'Return type int of method InheritingClassMethods\ParentFoo::bar() is not %s with return type void of method InheritingClassMethods\FooInterface::bar().',
					PHP_VERSION_ID > 70400 ? 'covariant' : 'compatible',
				),
				17,
			],
			[
				'Method InheritingClassMethods\ParentFoo::bar() overrides method InheritingClassMethods\FooInterface::bar() but misses parameter #1 $i.',
				17,
			],
			[
				sprintf(
					'Return type int of method InheritingClassMethods\ParentFoo::bar() is not %s with return type void of method InheritingClassMethods\FooInterface::bar().',
					PHP_VERSION_ID > 70400 ? 'covariant' : 'compatible',
				),
				21,
			],
			[
				'Method InheritingClassMethods\ParentFoo::bar() overrides method InheritingClassMethods\FooInterface::bar() but misses parameter #1 $i.',
				21,
			],
		];

		if (PHP_VERSION_ID < 70400) {
			$expectedErrors[] = [
				'Return type InheritingClassMethods\B of method InheritingClassMethods\CovariantParent::bar() is not compatible with return type InheritingClassMethods\A of method InheritingClassMethods\CovariantInterface::bar().',
				39,
			];
		}

		$this->analyse([__DIR__ . '/data/inheriting-class-methods.php'], $expectedErrors);
	}

}
