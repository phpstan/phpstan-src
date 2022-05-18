<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ReadOnlyByPhpDocPropertyAssignRule>
 */
class ReadOnlyByPhpDocPropertyAssignRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new ReadOnlyByPhpDocPropertyAssignRule(new PropertyReflectionFinder());
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/readonly-assign-phpdoc.php'], [
			[
				'Readonly property ReadonlyPropertyAssignPhpDoc\Foo::$foo is assigned outside of the constructor.',
				33,
			],
			[
				'Readonly property ReadonlyPropertyAssignPhpDoc\Foo::$bar is assigned outside of its declaring class.',
				45,
			],
			[
				'Readonly property ReadonlyPropertyAssignPhpDoc\Foo::$baz is assigned outside of its declaring class.',
				46,
			],
			[
				'Readonly property ReadonlyPropertyAssignPhpDoc\Foo::$bar is assigned outside of its declaring class.',
				51,
			],
			[
				'Readonly property ReadonlyPropertyAssignPhpDoc\Foo::$baz is assigned outside of its declaring class.',
				58,
			],
			[
				'Readonly property ReadonlyPropertyAssignPhpDoc\FooArrays::$details is assigned outside of the constructor.',
				77,
			],
			[
				'Readonly property ReadonlyPropertyAssignPhpDoc\FooArrays::$details is assigned outside of the constructor.',
				78,
			],
			[
				'Readonly property ReadonlyPropertyAssignPhpDoc\NotThis::$foo is not assigned on $this.',
				108,
			],
			[
				'Readonly property ReadonlyPropertyAssignPhpDoc\PostInc::$foo is assigned outside of the constructor.',
				124,
			],
			[
				'Readonly property ReadonlyPropertyAssignPhpDoc\PostInc::$foo is assigned outside of the constructor.',
				125,
			],
			[
				'Readonly property ReadonlyPropertyAssignPhpDoc\PostInc::$foo is assigned outside of the constructor.',
				127,
			],
			[
				'Readonly property ReadonlyPropertyAssignPhpDoc\ListAssign::$foo is assigned outside of the constructor.',
				148,
			],
			[
				'Readonly property ReadonlyPropertyAssignPhpDoc\ListAssign::$foo is assigned outside of the constructor.',
				153,
			],
			[
				'Readonly property ReadonlyPropertyAssignPhpDoc\Foo::$baz is assigned outside of its declaring class.',
				163,
			],
			[
				'Readonly property ReadonlyPropertyAssignPhpDoc\Foo::$baz is assigned outside of its declaring class.',
				164,
			],
		]);
	}

}
