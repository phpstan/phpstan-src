<?php declare(strict_types = 1);

namespace PHPStan\Rules\TooWideTypehints;

use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<TooWideFunctionReturnTypehintRule>
 */
class TooWideFunctionReturnTypehintRuleComposerPhpVersionRangeTest extends RuleTestCase
{

	public static function getComposerAutoloaderProjectPaths(): array
	{
		return [__DIR__ . '/../../Analyser/data/composer-require-php-7-and-8'];
	}

	protected function getRule(): Rule
	{
		return new TooWideFunctionReturnTypehintRule(new TooWideTypeCheck(new PropertyReflectionFinder(), true, true));
	}

	public function testNativeTrueAndFalseNotSuggestedWhenComposerAllowsPhp7(): void
	{
		$this->analyse([__DIR__ . '/data/bug-13384c.php'], [
			[
				'Function Bug13384c\doFooPhpdoc() never returns false so the return type can be changed to true.',
				93,
			],
			[
				'Function Bug13384c\doFooPhpdoc2() never returns true so the return type can be changed to false.',
				100,
			],
			[
				'Function Bug13384c\returnsTrueUnionReturn() never returns int so it can be removed from the return type.',
				130,
			],
			[
				'Function Bug13384c\returnsTruePhpdocUnionReturn() never returns int so it can be removed from the return type.',
				137,
			],
		]);
	}

}
