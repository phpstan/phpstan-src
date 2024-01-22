<?php declare(strict_types = 1);

namespace PHPStan\Rules\Constants;

use PHPStan\Rules\MissingTypehintCheck;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<MissingClassConstantTypehintRule>
 */
class MissingClassConstantTypehintRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new MissingClassConstantTypehintRule(new MissingTypehintCheck(true, true, true, true, []));
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
				'You can turn this off by setting <fg=cyan>checkGenericClassInNonGenericObjectType: false</> in your <fg=cyan>%configurationFile%</>.',
			],
			[
				'Constant MissingClassConstantTypehint\Foo::LOREM type has no signature specified for callable.',
				20,
			],
		]);
	}

	public function testBug8957(): void
	{
		if (PHP_VERSION_ID < 80200) {
			$this->markTestSkipped('This test needs PHP 8.2');
		}
		$this->analyse([__DIR__ . '/data/bug-8957.php'], []);
	}

	public function testRuleShouldNotApplyToNativeTypes(): void
	{
		if (PHP_VERSION_ID < 80300) {
			$this->markTestSkipped('This test needs PHP 8.3');
		}

		$this->analyse([__DIR__ . '/data/class-constant-native-type.php'], [
			[
				'Constant ClassConstantNativeTypeForMissingTypehintRule\Foo::B type has no value type specified in iterable type array.',
				19,
				'See: https://phpstan.org/blog/solving-phpstan-no-value-type-specified-in-iterable-type',
			],
			[
				'Constant ClassConstantNativeTypeForMissingTypehintRule\Foo::D with generic class ClassConstantNativeTypeForMissingTypehintRule\Bar does not specify its types: T',
				24,
				'You can turn this off by setting <fg=cyan>checkGenericClassInNonGenericObjectType: false</> in your <fg=cyan>%configurationFile%</>.',
			],
		]);
	}

}
