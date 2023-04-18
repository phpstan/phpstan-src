<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Reflection\ConstructorsHelper;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

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
				self::getContainer(),
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
				40,
			],
			[
				'@readonly property ReadonlyPropertyAssignPhpDoc\Foo::$bar is assigned outside of its declaring class.',
				53,
			],
			[
				'@readonly property ReadonlyPropertyAssignPhpDoc\Foo::$baz is assigned outside of its declaring class.',
				54,
			],
			[
				'@readonly property ReadonlyPropertyAssignPhpDoc\Foo::$psalm is assigned outside of its declaring class.',
				55,
			],
			[
				'@readonly property ReadonlyPropertyAssignPhpDoc\Foo::$bar is assigned outside of its declaring class.',
				60,
			],
			[
				'@readonly property ReadonlyPropertyAssignPhpDoc\Foo::$psalm is assigned outside of its declaring class.',
				61,
			],
			[
				'@readonly property ReadonlyPropertyAssignPhpDoc\Foo::$baz is assigned outside of its declaring class.',
				68,
			],
			[
				'@readonly property ReadonlyPropertyAssignPhpDoc\FooArrays::$details is assigned outside of the constructor.',
				87,
			],
			[
				'@readonly property ReadonlyPropertyAssignPhpDoc\FooArrays::$details is assigned outside of the constructor.',
				88,
			],
			[
				'@readonly property ReadonlyPropertyAssignPhpDoc\NotThis::$foo is not assigned on $this.',
				118,
			],
			[
				'@readonly property ReadonlyPropertyAssignPhpDoc\PostInc::$foo is assigned outside of the constructor.',
				134,
			],
			[
				'@readonly property ReadonlyPropertyAssignPhpDoc\PostInc::$foo is assigned outside of the constructor.',
				135,
			],
			[
				'@readonly property ReadonlyPropertyAssignPhpDoc\PostInc::$foo is assigned outside of the constructor.',
				137,
			],
			[
				'@readonly property ReadonlyPropertyAssignPhpDoc\ListAssign::$foo is assigned outside of the constructor.',
				158,
			],
			[
				'@readonly property ReadonlyPropertyAssignPhpDoc\ListAssign::$foo is assigned outside of the constructor.',
				163,
			],
			[
				'@readonly property ReadonlyPropertyAssignPhpDoc\Foo::$baz is assigned outside of its declaring class.',
				173,
			],
			[
				'@readonly property ReadonlyPropertyAssignPhpDoc\Foo::$baz is assigned outside of its declaring class.',
				174,
			],
			[
				'@readonly property ReadonlyPropertyAssignPhpDoc\Immutable::$foo is assigned outside of the constructor.',
				237,
			],
			[
				'@readonly property ReadonlyPropertyAssignPhpDoc\B::$b is assigned outside of the constructor.',
				269,
			],
			[
				'@readonly property ReadonlyPropertyAssignPhpDoc\A::$a is assigned outside of its declaring class.',
				270,
			],
			[
				'@readonly property ReadonlyPropertyAssignPhpDoc\C::$c is assigned outside of the constructor.',
				283,
			],
		]);
	}

	public function testRuleIgnoresNativeReadonly(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->analyse([__DIR__ . '/data/readonly-assign-phpdoc-and-native.php'], []);
	}

	public function testBug7361(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->analyse([__DIR__ . '/data/bug-7361.php'], [
			[
				'@readonly property Bug7361\Example::$foo is assigned outside of the constructor.',
				12,
			],
		]);
	}

	public function testFeature7648(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->analyse([__DIR__ . '/data/feature-7648.php'], []);
	}

}
