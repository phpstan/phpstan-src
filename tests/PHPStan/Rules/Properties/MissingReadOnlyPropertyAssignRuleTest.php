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
			'MissingReadOnlyPropertyAssignPhpDoc\\TestCase::setUp',
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
		]);
	}

	public function testRulePhpDoc(): void
	{
		$this->analyse([__DIR__ . '/data/missing-readonly-property-assign-phpdoc.php'], [
			[
				'Class MissingReadOnlyPropertyAssignPhpDoc\Foo has an uninitialized readonly property $unassigned. Assign it in the constructor.',
				24,
			],
			[
				'Class MissingReadOnlyPropertyAssignPhpDoc\Foo has an uninitialized readonly property $unassigned2. Assign it in the constructor.',
				30,
			],
			[
				'Access to an uninitialized readonly property MissingReadOnlyPropertyAssignPhpDoc\Foo::$readBeforeAssigned.',
				54,
			],
			[
				'Readonly property MissingReadOnlyPropertyAssignPhpDoc\Foo::$doubleAssigned is already assigned.',
				58,
			],
			[
				'Class MissingReadOnlyPropertyAssignPhpDoc\BarDoubleAssignInSetter has an uninitialized readonly property $foo. Assign it in the constructor.',
				78,
			],
			[
				'Access to an uninitialized readonly property MissingReadOnlyPropertyAssignPhpDoc\AssignOp::$foo.',
				122,
			],
			[
				'Access to an uninitialized readonly property MissingReadOnlyPropertyAssignPhpDoc\AssignOp::$bar.',
				124,
			],
		]);
	}

}
