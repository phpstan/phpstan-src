<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Reflection\ConstructorsHelper;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ReadOnlyByPhpDocPropertyAssignRule>
 */
class ReadOnlyByPhpDocPropertyAssignRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new ReadOnlyByPhpDocPropertyAssignRule(
			new PropertyReflectionFinder(),
			new ConstructorsHelper(
				[
					'ReadonlyPropertyAssignPhpDoc\\TestCase::setUp',
				],
			),
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/readonly-assign-phpdoc.php'], [
			[
				'@readonly property ReadonlyPropertyAssignPhpDoc\Foo::$foo is assigned outside of the constructor.',
				33,
			],
			[
				'@readonly property ReadonlyPropertyAssignPhpDoc\Foo::$bar is assigned outside of its declaring class.',
				45,
			],
			[
				'@readonly property ReadonlyPropertyAssignPhpDoc\Foo::$baz is assigned outside of its declaring class.',
				46,
			],
			[
				'@readonly property ReadonlyPropertyAssignPhpDoc\Foo::$bar is assigned outside of its declaring class.',
				51,
			],
			[
				'@readonly property ReadonlyPropertyAssignPhpDoc\Foo::$baz is assigned outside of its declaring class.',
				58,
			],
			[
				'@readonly property ReadonlyPropertyAssignPhpDoc\FooArrays::$details is assigned outside of the constructor.',
				77,
			],
			[
				'@readonly property ReadonlyPropertyAssignPhpDoc\FooArrays::$details is assigned outside of the constructor.',
				78,
			],
			[
				'@readonly property ReadonlyPropertyAssignPhpDoc\NotThis::$foo is not assigned on $this.',
				108,
			],
			[
				'@readonly property ReadonlyPropertyAssignPhpDoc\PostInc::$foo is assigned outside of the constructor.',
				124,
			],
			[
				'@readonly property ReadonlyPropertyAssignPhpDoc\PostInc::$foo is assigned outside of the constructor.',
				125,
			],
			[
				'@readonly property ReadonlyPropertyAssignPhpDoc\PostInc::$foo is assigned outside of the constructor.',
				127,
			],
			[
				'@readonly property ReadonlyPropertyAssignPhpDoc\ListAssign::$foo is assigned outside of the constructor.',
				148,
			],
			[
				'@readonly property ReadonlyPropertyAssignPhpDoc\ListAssign::$foo is assigned outside of the constructor.',
				153,
			],
			[
				'@readonly property ReadonlyPropertyAssignPhpDoc\Foo::$baz is assigned outside of its declaring class.',
				163,
			],
			[
				'@readonly property ReadonlyPropertyAssignPhpDoc\Foo::$baz is assigned outside of its declaring class.',
				164,
			],
			[
				'@readonly property ReadonlyPropertyAssignPhpDoc\Immutable::$foo is assigned outside of the constructor.',
				227,
			],
		]);
	}

}
