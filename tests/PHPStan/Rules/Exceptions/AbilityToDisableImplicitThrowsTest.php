<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PHPStan\Rules\Comparison\ConstantConditionInTraitHelper;
use PHPStan\Rules\Comparison\ConstantConditionInTraitRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\CompositeRule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;
use function array_merge;

/**
 * @extends RuleTestCase<CompositeRule>
 */
class AbilityToDisableImplicitThrowsTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		// @phpstan-ignore argument.type
		return new CompositeRule([
			new CatchWithUnthrownExceptionRule(
				new DefaultExceptionTypeResolver(
					self::createReflectionProvider(),
					[],
					[],
					[],
					[],
				),
				true,
				self::getContainer()->getByType(ConstantConditionInTraitHelper::class),
			),
			new CatchWithThrownExceptionInTraitRule(
				self::getContainer()->getByType(ConstantConditionInTraitHelper::class),
			),
			new ConstantConditionInTraitRule(),
		]);
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

	#[RequiresPhp('>= 8.4.0')]
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

	public function testBug13806(): void
	{
		$this->analyse([__DIR__ . '/data/bug-13806.php'], [
			[
				'Dead catch - Throwable is never thrown in the try block.',
				8,
			],
			[
				'Dead catch - Throwable is never thrown in the try block.',
				53,
			],
			[
				'Dead catch - Throwable is never thrown in the try block.',
				64,
			],
		]);
	}

	public function testBug7799(): void
	{
		$this->analyse([__DIR__ . '/data/bug-7799.php'], [
			[
				'Dead catch - Exception is never thrown in the try block.',
				19,
			],
		]);
	}

	public function testBug10315(): void
	{
		$this->analyse([__DIR__ . '/data/bug-10315.php'], []);
	}

	public function testDeadCatchInTrait(): void
	{
		$this->analyse([__DIR__ . '/data/dead-catch-in-trait.php'], [
			[
				// dead in both FirstUser and SecondUser: reported once, on the trait
				'Dead catch - DeadCatchInTrait\AlphaException is never thrown in the try block.',
				36,
			],
			[
				// same catch as AlphaException on line 67, which is dead in ThrowsNeither
				// but alive in ThrowsAlphaOnly and therefore not reported
				'Dead catch - DeadCatchInTrait\BetaException is never thrown in the try block.',
				67,
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
