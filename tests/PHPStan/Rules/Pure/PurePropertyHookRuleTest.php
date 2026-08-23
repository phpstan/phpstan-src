<?php declare(strict_types = 1);

namespace PHPStan\Rules\Pure;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<PurePropertyHookRule>
 */
class PurePropertyHookRuleTest extends RuleTestCase
{

	public function getRule(): Rule
	{
		return new PurePropertyHookRule(new FunctionPurityCheck());
	}

	#[RequiresPhp('>= 8.4.0')]
	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/pure-property-hook.php'], [
			[
				'Impure echo in pure get hook for property PurePropertyHook\Foo::$pureGetWithSideEffect.',
				15,
			],
			[
				'Get hook for property PurePropertyHook\Foo::$impureGetWithoutSideEffect is marked as impure but does not have any side effects.',
				28,
			],
			[
				'Set hook for property PurePropertyHook\Foo::$pureSet is marked as pure but returns void.',
				50,
			],
			[
				'Impure property assignment in pure set hook for property PurePropertyHook\Foo::$pureSet.',
				51,
			],
			[
				'Get hook for property PurePropertyHook\NotFinal::$finalImpureGetWithoutSideEffect is marked as impure but does not have any side effects.',
				74,
			],
			[
				'Set hook for property PurePropertyHook\AbstractGetHookFollowedBySetHook::$mixedHooks is marked as pure but returns void.',
				85,
			],
			[
				'Impure echo in pure set hook for property PurePropertyHook\AbstractGetHookFollowedBySetHook::$mixedHooks.',
				86,
			],
		]);
	}

}
