<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;
use function array_merge;

/**
 * @extends RuleTestCase<CatchWithUnthrownExceptionRule>
 */
class AbilityToDisableImplicitThrowsTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new CatchWithUnthrownExceptionRule(new DefaultExceptionTypeResolver(
			self::createReflectionProvider(),
			[],
			[],
			[],
			[],
		), true);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/ability-to-disable-implicit-throws.php'], [
			[
				'Dead catch - Throwable is never thrown in the try block.',
				17,
			],
		]);
	}

	#[RequiresPhp('>= 8.4')]
	public function testPropertyHooks(): void
	{
		$this->analyse([__DIR__ . '/data/unthrown-exception-property-hooks-implicit-throws-disabled.php'], [
			[
				'Dead catch - UnthrownExceptionPropertyHooksImplicitThrowsDisabled\MyCustomException is never thrown in the try block.',
				23,
			],
			[
				'Dead catch - UnthrownExceptionPropertyHooksImplicitThrowsDisabled\MyCustomException is never thrown in the try block.',
				38,
			],
			[
				'Dead catch - UnthrownExceptionPropertyHooksImplicitThrowsDisabled\MyCustomException is never thrown in the try block.',
				53,
			],
			[
				'Dead catch - UnthrownExceptionPropertyHooksImplicitThrowsDisabled\MyCustomException is never thrown in the try block.',
				68,
			],
			[
				'Dead catch - UnthrownExceptionPropertyHooksImplicitThrowsDisabled\MyCustomException is never thrown in the try block.',
				74,
			],
			[
				'Dead catch - UnthrownExceptionPropertyHooksImplicitThrowsDisabled\MyCustomException is never thrown in the try block.',
				94,
			],
			[
				'Dead catch - UnthrownExceptionPropertyHooksImplicitThrowsDisabled\MyCustomException is never thrown in the try block.',
				115,
			],
		]);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return array_merge(
			parent::getAdditionalConfigFiles(),
			[__DIR__ . '/data/ability-to-disable-implicit-throws.neon'],
		);
	}

}
