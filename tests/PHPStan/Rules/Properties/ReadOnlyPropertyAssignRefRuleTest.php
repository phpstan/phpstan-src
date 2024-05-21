<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<ReadOnlyPropertyAssignRefRule>
 */
class ReadOnlyPropertyAssignRefRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new ReadOnlyPropertyAssignRefRule(new PropertyReflectionFinder());
	}

	public function testRule(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->analyse([__DIR__ . '/data/readonly-assign-ref.php'], [
			[
				'Readonly property ReadOnlyPropertyAssignRef\Foo::$foo is assigned by reference.',
				14,
			],
			[
				'Readonly property ReadOnlyPropertyAssignRef\Foo::$bar is assigned by reference.',
				15,
			],
			[
				'Readonly property ReadOnlyPropertyAssignRef\Foo::$bar is assigned by reference.',
				26,
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
				'Readonly property BugInstanceofStaticVsThisPropertyAssign\FooChild::$nativeReadonlyProp is assigned by reference.',
				19,
			],
			[
				'Readonly property BugInstanceofStaticVsThisPropertyAssign\FooChild::$nativeReadonlyProp is assigned by reference.',
				33,
			],
		]);
	}

}
