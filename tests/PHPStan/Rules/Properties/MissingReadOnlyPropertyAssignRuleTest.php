<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<MissingReadOnlyPropertyAssignRule>
 */
class MissingReadOnlyPropertyAssignRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new MissingReadOnlyPropertyAssignRule([
			'MissingReadOnlyPropertyAssign\\TestCase::setUp',
		]);
	}

	public function testRule(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->analyse([__DIR__ . '/data/missing-readonly-property-assign.php'], [
			[
				'Class MissingReadOnlyPropertyAssign\Foo has an uninitialized readonly property $unassigned. Assign it in the constructor.',
				14,
			],
			[
				'Class MissingReadOnlyPropertyAssign\Foo has an uninitialized readonly property $unassigned2. Assign it in the constructor.',
				16,
			],
			[
				'Access to an uninitialized readonly property MissingReadOnlyPropertyAssign\Foo::$readBeforeAssigned.',
				33,
			],
			[
				'Readonly property MissingReadOnlyPropertyAssign\Foo::$doubleAssigned is already assigned.',
				37,
			],
			[
				'Class MissingReadOnlyPropertyAssign\BarDoubleAssignInSetter has an uninitialized readonly property $foo. Assign it in the constructor.',
				53,
			],
			[
				'Access to an uninitialized readonly property MissingReadOnlyPropertyAssign\AssignOp::$foo.',
				85,
			],
			[
				'Access to an uninitialized readonly property MissingReadOnlyPropertyAssign\AssignOp::$bar.',
				87,
			],
			[
				'Readonly property MissingReadOnlyPropertyAssign\DoubleAssign::$foo is already assigned.',
				128,
			],
			[
				'Class MissingReadOnlyPropertyAssign\Foo has an maybe uninitialized readonly property $foo.',
				139,
			],
		]);
	}

}
