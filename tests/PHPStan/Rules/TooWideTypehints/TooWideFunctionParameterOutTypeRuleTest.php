<?php declare(strict_types = 1);

namespace PHPStan\Rules\TooWideTypehints;

use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\Rules\Rule as TRule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<TooWideFunctionParameterOutTypeRule>
 */
class TooWideFunctionParameterOutTypeRuleTest extends RuleTestCase
{

	protected function getRule(): TRule
	{
		return new TooWideFunctionParameterOutTypeRule(new TooWideParameterOutTypeCheck(
			new TooWideTypeCheck(new PropertyReflectionFinder(), true, true),
		));
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/too-wide-function-parameter-out.php'], [
			[
				'Function TooWideFunctionParameterOut\doBar() never assigns null to &$p so it can be removed from the by-ref type.',
				10,
				'You can narrow the parameter out type with @param-out PHPDoc tag.',
			],
			[
				'Function TooWideFunctionParameterOut\doBaz() never assigns null to &$p so it can be removed from the @param-out type.',
				18,
			],
			[
				'Function TooWideFunctionParameterOut\doLorem() never assigns null to &$p so it can be removed from the by-ref type.',
				23,
				'You can narrow the parameter out type with @param-out PHPDoc tag.',
			],
		]);
	}

	public function testNestedTooWideType(): void
	{
		$this->analyse([__DIR__ . '/data/nested-too-wide-function-parameter-out-type.php'], [
			[
				'PHPDoc tag @param-out type array<array{int, bool}> of function NestedTooWideFunctionParameterOutType\doFoo() can be narrowed to array<array{int, false}>.',
				9,
				'Offset 1 (false) does not accept type bool.',
			],
		]);
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testBug15066(): void
	{
		$this->analyse([__DIR__ . '/data/bug-15066.php'], [
			[
				'Function Bug15066\\variadicNeverNull() never assigns null to &$refs so it can be removed from the by-ref type.',
				24,
				'You can narrow the parameter out type with @param-out PHPDoc tag.',
			],
			// rebinding the packed variable to a non-array is silent, to an array is not - see the fixture
			[
				'Function Bug15066\\variadicRebindOnlyString() never assigns null to &$refs so it can be removed from the by-ref type.',
				62,
				'You can narrow the parameter out type with @param-out PHPDoc tag.',
			],
		]);
	}

}
