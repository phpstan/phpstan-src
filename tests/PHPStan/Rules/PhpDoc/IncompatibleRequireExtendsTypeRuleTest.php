<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<IncompatibleRequireExtendsTypeRule>
 */
class IncompatibleRequireExtendsTypeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$reflectionProvider = $this->createReflectionProvider();

		return new IncompatibleRequireExtendsTypeRule(
			$reflectionProvider,
			new ClassCaseSensitivityCheck($reflectionProvider, true),
			new UnresolvableTypeHelper(),
			true,
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/incompatible-require-extends.php'], [
			[
				'PHPDoc tag @require-extends cannot contain non-class type IncompatibleRequireExtends\SomeTrait.',
				8,
			],
			[
				'PHPDoc tag @require-extends cannot contain non-class type IncompatibleRequireExtends\SomeInterface.',
				13,
			],
			[
				'PHPDoc tag @require-extends cannot contain non-class type IncompatibleRequireExtends\SomeEnum.',
				18,
			],
			[
				'PHPDoc tag @require-extends contains unknown class IncompatibleRequireExtends\TypeDoesNotExist.',
				23,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'PHPDoc tag @require-extends cannot contain generic type.',
				29,
			],
			[
				'PHPDoc tag @require-extends contains non-object type int.',
				34,
			],
			[
				'PHPDoc tag @require-extends is only valid on trait or interface.',
				39,
			],
			[
				'PHPDoc tag @require-extends is only valid on trait or interface.',
				44,
			],
			[
				'PHPDoc tag @require-extends cannot contain final class IncompatibleRequireExtends\SomeFinalClass.',
				121,
			],
			[
				'PHPDoc tag @require-extends cannot contain non-class type IncompatibleRequireExtends\UnresolvableExtendsInterface.',
				131,
			],
		]);
	}

}
