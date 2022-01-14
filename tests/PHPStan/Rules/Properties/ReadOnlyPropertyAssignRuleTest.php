<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<ReadOnlyPropertyAssignRule>
 */
class ReadOnlyPropertyAssignRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new ReadOnlyPropertyAssignRule(new PropertyReflectionFinder());
	}

	public function testRule(): void
	{
		if (PHP_VERSION_ID < 80100) {
			self::markTestSkipped('Test requires PHP 8.1');
		}

		$this->analyse([__DIR__ . '/data/readonly-assign.php'], [
			[
				'Readonly property ReadonlyPropertyAssign\Foo::$foo is assigned outside of the constructor.',
				21,
			],
			[
				'Readonly property ReadonlyPropertyAssign\Foo::$bar is assigned outside of its declaring class.',
				33,
			],
			[
				'Readonly property ReadonlyPropertyAssign\Foo::$baz is assigned outside of its declaring class.',
				34,
			],
			[
				'Readonly property ReadonlyPropertyAssign\Foo::$bar is assigned outside of its declaring class.',
				39,
			],
			[
				'Readonly property ReadonlyPropertyAssign\Foo::$baz is assigned outside of its declaring class.',
				46,
			],
			[
				'Readonly property ReadonlyPropertyAssign\FooArrays::$details is assigned outside of the constructor.',
				64,
			],
			[
				'Readonly property ReadonlyPropertyAssign\FooArrays::$details is assigned outside of the constructor.',
				65,
			],
			[
				'Readonly property ReadonlyPropertyAssign\NotThis::$foo is not assigned on $this.',
				90,
			],
			[
				'Readonly property ReadonlyPropertyAssign\PostInc::$foo is assigned outside of the constructor.',
				102,
			],
			[
				'Readonly property ReadonlyPropertyAssign\PostInc::$foo is assigned outside of the constructor.',
				103,
			],
			[
				'Readonly property ReadonlyPropertyAssign\PostInc::$foo is assigned outside of the constructor.',
				105,
			],
			[
				'Readonly property ReadonlyPropertyAssign\ListAssign::$foo is assigned outside of the constructor.',
				122,
			],
			[
				'Readonly property ReadonlyPropertyAssign\ListAssign::$foo is assigned outside of the constructor.',
				127,
			],
			[
				'Readonly property ReadonlyPropertyAssign\FooEnum::$name is assigned outside of the constructor.',
				140,
			],
			[
				'Readonly property ReadonlyPropertyAssign\FooEnum::$value is assigned outside of the constructor.',
				141,
			],
			[
				'Readonly property ReadonlyPropertyAssign\FooEnum::$name is assigned outside of its declaring class.',
				151,
			],
			[
				'Readonly property ReadonlyPropertyAssign\FooEnum::$value is assigned outside of its declaring class.',
				152,
			],
			[
				'Readonly property ReadonlyPropertyAssign\Foo::$baz is assigned outside of its declaring class.',
				162,
			],
			[
				'Readonly property ReadonlyPropertyAssign\Foo::$baz is assigned outside of its declaring class.',
				163,
			],
		]);
	}

}
