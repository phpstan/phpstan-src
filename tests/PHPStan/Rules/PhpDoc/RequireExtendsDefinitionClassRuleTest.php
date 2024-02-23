<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassForbiddenNameCheck;
use PHPStan\Rules\ClassNameCheck;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<RequireExtendsDefinitionClassRule>
 */
class RequireExtendsDefinitionClassRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$reflectionProvider = $this->createReflectionProvider();

		return new RequireExtendsDefinitionClassRule(
			new RequireExtendsCheck(
				new ClassNameCheck(
					new ClassCaseSensitivityCheck($reflectionProvider, true),
					new ClassForbiddenNameCheck(),
				),
				true,
			),
		);
	}

	public function testRule(): void
	{
		$enumError = 'PHPDoc tag @phpstan-require-extends cannot contain non-class type IncompatibleRequireExtends\SomeEnum.';
		$enumTip = null;
		if (PHP_VERSION_ID < 80100) {
			$enumError = 'PHPDoc tag @phpstan-require-extends contains unknown class IncompatibleRequireExtends\SomeEnum.';
			$enumTip = 'Learn more at https://phpstan.org/user-guide/discovering-symbols';
		}

		$this->analyse([__DIR__ . '/data/incompatible-require-extends.php'], [
			[
				'PHPDoc tag @phpstan-require-extends cannot contain non-class type IncompatibleRequireExtends\SomeTrait.',
				8,
			],
			[
				'PHPDoc tag @phpstan-require-extends cannot contain non-class type IncompatibleRequireExtends\SomeInterface.',
				13,
			],
			[
				$enumError,
				18,
				$enumTip,
			],
			[
				'PHPDoc tag @phpstan-require-extends contains unknown class IncompatibleRequireExtends\TypeDoesNotExist.',
				23,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'PHPDoc tag @phpstan-require-extends contains non-object type int.',
				34,
			],
			[
				'PHPDoc tag @phpstan-require-extends is only valid on trait or interface.',
				39,
			],
			[
				'PHPDoc tag @phpstan-require-extends is only valid on trait or interface.',
				44,
			],
			[
				'PHPDoc tag @phpstan-require-extends cannot contain final class IncompatibleRequireExtends\SomeFinalClass.',
				121,
			],
			[
				'PHPDoc tag @phpstan-require-extends contains non-object type IncompatibleRequireExtends\UnresolvableExtendsInterface&stdClass.',
				135,
			],
			[
				'PHPDoc tag @phpstan-require-extends can only be used once.',
				178,
			],
		]);
	}

}
