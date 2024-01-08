<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<IncompatibleRequireImplementsTypeRule>
 */
class IncompatibleRequireImplementsTypeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$reflectionProvider = $this->createReflectionProvider();

		return new IncompatibleRequireImplementsTypeRule(
			$reflectionProvider,
			new ClassCaseSensitivityCheck($reflectionProvider, true),
			new UnresolvableTypeHelper(),
			true,
		);
	}

	public function testRule(): void
	{
		$enumError = 'PHPDoc tag @require-implements cannot contain non-interface type IncompatibleRequireImplements\SomeEnum.';
		$enumTip = null;
		if (PHP_VERSION_ID < 80100) {
			$enumError = 'PHPDoc tag @require-implements contains unknown class IncompatibleRequireImplements\SomeEnum.';
			$enumTip = 'Learn more at https://phpstan.org/user-guide/discovering-symbols';
		}

		$expectedErrors = [
			[
				'PHPDoc tag @require-implements cannot contain non-interface type IncompatibleRequireImplements\SomeTrait.',
				8,
			],
			[
				$enumError,
				13,
				$enumTip,
			],
			[
				'PHPDoc tag @require-implements contains unknown class IncompatibleRequireImplements\TypeDoesNotExist.',
				18,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'PHPDoc tag @require-implements cannot contain generic type.',
				24,
			],
			[
				'PHPDoc tag @require-implements contains non-object type int.',
				29,
			],
			[
				'PHPDoc tag @require-implements contains unresolvable type.',
				34,
			],
			[
				'PHPDoc tag @require-implements is only valid on trait.',
				40,
			],
		];

		if (PHP_VERSION_ID > 80100) {
			$expectedErrors[] = [
				'PHPDoc tag @require-implements is only valid on trait.',
				45,
			];
		}

		$this->analyse([__DIR__ . '/data/incompatible-require-implements.php'], $expectedErrors);
	}

}
