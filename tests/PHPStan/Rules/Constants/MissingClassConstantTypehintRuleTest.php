<?php declare(strict_types = 1);

namespace PHPStan\Rules\Constants;

use PHPStan\Rules\MissingTypehintCheck;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<MissingClassConstantTypehintRule>
 */
class MissingClassConstantTypehintRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new MissingClassConstantTypehintRule(new MissingTypehintCheck(true, []));
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/missing-class-constant-typehint.php'], [
			[
				'Constant MissingClassConstantTypehint\Foo::BAR type has no value type specified in iterable type array.',
				11,
				'See: https://phpstan.org/blog/solving-phpstan-no-value-type-specified-in-iterable-type',
			],
			[
				'Constant MissingClassConstantTypehint\Foo::BAZ with generic class MissingClassConstantTypehint\Bar does not specify its types: T',
				17,
			],
			[
				'Constant MissingClassConstantTypehint\Foo::LOREM type has no signature specified for callable.',
				20,
			],
		]);
	}

	#[RequiresPhp('>= 8.2')]
	public function testBug8957(): void
	{
		$this->analyse([__DIR__ . '/data/bug-8957.php'], []);
	}

	#[RequiresPhp('>= 8.3')]
	public function testRuleShouldNotApplyToNativeTypes(): void
	{
		$this->analyse([__DIR__ . '/data/class-constant-native-type.php'], [
			[
				'Constant ClassConstantNativeTypeForMissingTypehintRule\Foo::B type has no value type specified in iterable type array.',
				19,
				'See: https://phpstan.org/blog/solving-phpstan-no-value-type-specified-in-iterable-type',
			],
			[
				'Constant ClassConstantNativeTypeForMissingTypehintRule\Foo::D with generic class ClassConstantNativeTypeForMissingTypehintRule\Bar does not specify its types: T',
				24,
			],
		]);
	}

}
