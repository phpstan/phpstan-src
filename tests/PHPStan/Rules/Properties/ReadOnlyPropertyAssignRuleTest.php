<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Reflection\ConstructorsHelper;
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
		return new ReadOnlyPropertyAssignRule(
			new PropertyReflectionFinder(),
			new ConstructorsHelper(
				self::getContainer(),
				[
					'ReadonlyPropertyAssign\\TestCase::setUp',
				],
			),
		);
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
			/*[
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
			],*/
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

	public function testFeature7648(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->analyse([__DIR__ . '/data/feature-7648.php'], [
			[
				'Readonly property Feature7648\Request::$offset is assigned outside of the constructor.',
				23,
			],
		]);
	}

	public function testReadOnlyClasses(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->analyse([__DIR__ . '/data/readonly-class-assign.php'], [
			[
				'Readonly property ReadonlyClassPropertyAssign\Foo::$foo is assigned outside of the constructor.',
				21,
			],
		]);
	}

	public function testBug6773(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->analyse([__DIR__ . '/data/bug-6773.php'], [
			[
				'Readonly property Bug6773\Repository::$data is assigned outside of the constructor.',
				16,
			],
		]);
	}

	public function testBugInstanceofStaticVsThis(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->analyse([__DIR__ . '/data/bug-instanceof-static-vs-this-property-assign.php'], [
			[
				'Readonly property BugInstanceofStaticVsThisPropertyAssign\FooChild::$nativeReadonlyProp is assigned outside of its declaring class.',
				15,
			],
			[
				'Readonly property BugInstanceofStaticVsThisPropertyAssign\FooChild::$nativeReadonlyProp is assigned outside of its declaring class.',
				29,
			],
		]);
	}

}
