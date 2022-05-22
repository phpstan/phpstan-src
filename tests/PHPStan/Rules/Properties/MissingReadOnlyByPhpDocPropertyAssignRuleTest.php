<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Reflection\ConstructorsHelper;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<MissingReadOnlyByPhpDocPropertyAssignRule>
 */
class MissingReadOnlyByPhpDocPropertyAssignRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new MissingReadOnlyByPhpDocPropertyAssignRule(
			new ConstructorsHelper([
				'MissingReadOnlyPropertyAssignPhpDoc\\TestCase::setUp',
			]),
		);
	}

	public function testRule(): void
	{
		if (PHP_VERSION_ID < 70400) {
			$this->markTestSkipped('Test requires PHP 7.4.');
		}

		$this->analyse([__DIR__ . '/data/missing-readonly-property-assign-phpdoc.php'], [
			[
				'Class MissingReadOnlyPropertyAssignPhpDoc\Foo has an uninitialized @readonly property $unassigned. Assign it in the constructor.',
				16,
			],
			[
				'Class MissingReadOnlyPropertyAssignPhpDoc\Foo has an uninitialized @readonly property $unassigned2. Assign it in the constructor.',
				19,
			],
			[
				'Access to an uninitialized @readonly property MissingReadOnlyPropertyAssignPhpDoc\Foo::$readBeforeAssigned.',
				36,
			],
			[
				'@readonly property MissingReadOnlyPropertyAssignPhpDoc\Foo::$doubleAssigned is already assigned.',
				40,
			],
			[
				'Class MissingReadOnlyPropertyAssignPhpDoc\BarDoubleAssignInSetter has an uninitialized @readonly property $foo. Assign it in the constructor.',
				57,
			],
			[
				'Access to an uninitialized @readonly property MissingReadOnlyPropertyAssignPhpDoc\AssignOp::$foo.',
				92,
			],
			[
				'Access to an uninitialized @readonly property MissingReadOnlyPropertyAssignPhpDoc\AssignOp::$bar.',
				94,
			],
			[
				'Class MissingReadOnlyPropertyAssignPhpDoc\Immutable has an uninitialized @readonly property $unassigned. Assign it in the constructor.',
				119,
			],
			[
				'Class MissingReadOnlyPropertyAssignPhpDoc\Immutable has an uninitialized @readonly property $unassigned2. Assign it in the constructor.',
				121,
			],
			[
				'Access to an uninitialized @readonly property MissingReadOnlyPropertyAssignPhpDoc\Immutable::$readBeforeAssigned.',
				131,
			],
			[
				'@readonly property MissingReadOnlyPropertyAssignPhpDoc\Immutable::$doubleAssigned is already assigned.',
				135,
			],
			[
				'Class MissingReadOnlyPropertyAssignPhpDoc\FooTraitClass has an uninitialized @readonly property $unassigned. Assign it in the constructor.',
				156,
			],
			[
				'Class MissingReadOnlyPropertyAssignPhpDoc\FooTraitClass has an uninitialized @readonly property $unassigned2. Assign it in the constructor.',
				159,
			],
			[
				'Access to an uninitialized @readonly property MissingReadOnlyPropertyAssignPhpDoc\FooTraitClass::$readBeforeAssigned.',
				188,
			],
			[
				'@readonly property MissingReadOnlyPropertyAssignPhpDoc\FooTraitClass::$doubleAssigned is already assigned.',
				192,
			],
		]);
	}

}
