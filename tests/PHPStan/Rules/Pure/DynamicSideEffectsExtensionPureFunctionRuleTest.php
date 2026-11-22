<?php declare(strict_types = 1);

namespace PHPStan\Rules\Pure;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<PureFunctionRule>
 */
class DynamicSideEffectsExtensionPureFunctionRuleTest extends RuleTestCase
{

	public function getRule(): Rule
	{
		return new PureFunctionRule(new FunctionPurityCheck($this->createReflectionProvider()));
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/dynamic-side-effects-extensions.php'], [
			[
				'Impure call to function DynamicSideEffectsExtensions\pureFunction() in pure function DynamicSideEffectsExtensionsPurity\calls().',
				15,
			],
			[
				'Impure call to method DynamicSideEffectsExtensions\Box::pure() in pure function DynamicSideEffectsExtensionsPurity\calls().',
				17,
			],
			[
				'Impure call to method DynamicSideEffectsExtensions\Box::pureStatic() in pure function DynamicSideEffectsExtensionsPurity\calls().',
				19,
			],
			[
				'Possibly impure instantiation of class DynamicSideEffectsExtensions\Made in pure function DynamicSideEffectsExtensionsPurity\calls().',
				21,
			],
			[
				'Impure instantiation of class DynamicSideEffectsExtensions\PureMade in pure function DynamicSideEffectsExtensionsPurity\calls().',
				22,
			],
		]);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/../../Analyser/dynamic-side-effects.neon',
		];
	}

}
