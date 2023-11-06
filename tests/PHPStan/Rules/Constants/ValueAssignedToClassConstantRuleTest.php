<?php declare(strict_types = 1);

namespace PHPStan\Rules\Constants;

use PHPStan\Rules\Rule as TRule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<ValueAssignedToClassConstantRule>
 */
class ValueAssignedToClassConstantRuleTest extends RuleTestCase
{

	protected function getRule(): TRule
	{
		return new ValueAssignedToClassConstantRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/value-assigned-to-class-constant.php'], [
			[
				'PHPDoc tag @var for constant ValueAssignedToClassConstant\Foo::BAZ with type string is incompatible with value 1.',
				14,
			],
			[
				'PHPDoc tag @var for constant ValueAssignedToClassConstant\Foo::DOLOR with type ValueAssignedToClassConstant\Foo<int> is incompatible with value 1.',
				23,
			],
			[
				'PHPDoc tag @var for constant ValueAssignedToClassConstant\Bar::BAZ with type string is incompatible with value 2.',
				32,
			],
		]);
	}

	public function testBug7352(): void
	{
		$this->analyse([__DIR__ . '/data/bug-7352.php'], []);
	}

	public function testBug7352WithSubNamespace(): void
	{
		$this->analyse([__DIR__ . '/data/bug-7352-with-sub-namespace.php'], []);
	}

	public function testBug7273(): void
	{
		$this->analyse([__DIR__ . '/data/bug-7273.php'], []);
	}

	public function testBug7273b(): void
	{
		$this->analyse([__DIR__ . '/data/bug-7273b.php'], []);
	}

	public function testBug5655(): void
	{
		$this->analyse([__DIR__ . '/data/bug-5655.php'], []);
	}

	public function testNativeType(): void
	{
		if (PHP_VERSION_ID < 80300) {
			$this->markTestSkipped('Test requires PHP 8.3.');
		}

		$this->analyse([__DIR__ . '/data/value-assigned-to-class-constant-native-type.php'], [
			[
				'Constant ValueAssignedToClassConstantNativeType\Foo::BAR (int) does not accept value \'bar\'.',
				10,
			],
			[
				'Constant ValueAssignedToClassConstantNativeType\Bar::BAR (int<1, max>) does not accept value 0.',
				21,
			],
		]);
	}

}
