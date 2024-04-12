<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<ReadOnlyByPhpDocPropertyAssignRefRule>
 */
class ReadOnlyByPhpDocPropertyAssignRefRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new ReadOnlyByPhpDocPropertyAssignRefRule(new PropertyReflectionFinder());
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/readonly-assign-ref-phpdoc.php'], [
			[
				'@readonly property ReadOnlyPropertyAssignRefPhpDoc\Foo::$foo is assigned by reference.',
				22,
			],
			[
				'@readonly property ReadOnlyPropertyAssignRefPhpDoc\Foo::$bar is assigned by reference.',
				23,
			],
			[
				'@readonly property ReadOnlyPropertyAssignRefPhpDoc\Foo::$bar is assigned by reference.',
				34,
			],
			[
				'@readonly property ReadOnlyPropertyAssignRefPhpDoc\Immutable::$foo is assigned by reference.',
				51,
			],
			[
				'@readonly property ReadOnlyPropertyAssignRefPhpDoc\Immutable::$bar is assigned by reference.',
				52,
			],
			[
				'@readonly property ReadOnlyPropertyAssignRefPhpDoc\A::$a is assigned by reference.',
				66,
			],
			[
				'@readonly property ReadOnlyPropertyAssignRefPhpDoc\B::$b is assigned by reference.',
				79,
			],
			[
				'@readonly property ReadOnlyPropertyAssignRefPhpDoc\A::$a is assigned by reference.',
				80,
			],
			[
				'@readonly property ReadOnlyPropertyAssignRefPhpDoc\C::$c is assigned by reference.',
				93,
			],
		]);
	}

	public function testRuleIgnoresNativeReadonly(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->analyse([__DIR__ . '/data/readonly-assign-ref-phpdoc-and-native.php'], []);
	}

	public function testBugInstanceofStaticVsThis(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->analyse([__DIR__ . '/data/bug-instanceof-static-vs-this-property-assign.php'], [
			[
				'@readonly property BugInstanceofStaticVsThisPropertyAssign\FooChild::$phpdocReadonlyProp is assigned by reference.',
				20,
			],
			[
				'@readonly property BugInstanceofStaticVsThisPropertyAssign\FooChild::$phpdocReadonlyProp is assigned by reference.',
				34,
			],
		]);
	}

}
