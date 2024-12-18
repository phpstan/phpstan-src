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
				47,
			],
			[
				'@readonly property ReadonlyPropertyAssignPhpDoc\Foo::$phan is assigned outside of the constructor.',
				49,
			],
			[
				'@readonly property ReadonlyPropertyAssignPhpDoc\Foo::$bar is assigned outside of its declaring class.',
				61,
			],
			[
				'@readonly property ReadonlyPropertyAssignPhpDoc\Foo::$baz is assigned outside of its declaring class.',
				62,
			],
			[
				'@readonly property ReadonlyPropertyAssignPhpDoc\Foo::$psalm is assigned outside of its declaring class.',
				63,
			],
			[
				'@readonly property ReadonlyPropertyAssignPhpDoc\Foo::$phan is assigned outside of its declaring class.',
				64,
			],
			[
				'@readonly property ReadonlyPropertyAssignPhpDoc\Foo::$bar is assigned outside of its declaring class.',
				69,
			],
			[
				'@readonly property ReadonlyPropertyAssignPhpDoc\Foo::$psalm is assigned outside of its declaring class.',
				70,
			],
			[
				'@readonly property ReadonlyPropertyAssignPhpDoc\Foo::$phan is assigned outside of its declaring class.',
				71,
			],
			[
				'@readonly property ReadonlyPropertyAssignPhpDoc\Foo::$baz is assigned outside of its declaring class.',
				78,
			],
			[
				'@readonly property ReadonlyPropertyAssignPhpDoc\FooArrays::$details is assigned outside of the constructor.',
				97,
			],
			[
				'@readonly property ReadonlyPropertyAssignPhpDoc\FooArrays::$details is assigned outside of the constructor.',
				98,
			],
			[
				'@readonly property ReadonlyPropertyAssignPhpDoc\NotThis::$foo is not assigned on $this.',
				128,
			],
			[
				'@readonly property ReadonlyPropertyAssignPhpDoc\PostInc::$foo is assigned outside of the constructor.',
				144,
			],
			[
				'@readonly property ReadonlyPropertyAssignPhpDoc\PostInc::$foo is assigned outside of the constructor.',
				145,
			],
			[
				'@readonly property ReadonlyPropertyAssignPhpDoc\PostInc::$foo is assigned outside of the constructor.',
				147,
			],
			[
				'@readonly property ReadonlyPropertyAssignPhpDoc\ListAssign::$foo is assigned outside of the constructor.',
				168,
			],
			[
				'@readonly property ReadonlyPropertyAssignPhpDoc\ListAssign::$foo is assigned outside of the constructor.',
				173,
			],
			[
				'@readonly property ReadonlyPropertyAssignPhpDoc\Foo::$baz is assigned outside of its declaring class.',
				183,
			],
			[
				'@readonly property ReadonlyPropertyAssignPhpDoc\Foo::$baz is assigned outside of its declaring class.',
				184,
			],
			[
				'@readonly property ReadonlyPropertyAssignPhpDoc\Immutable::$foo is assigned outside of the constructor.',
				247,
			],
			[
				'@readonly property ReadonlyPropertyAssignPhpDoc\B::$b is assigned outside of the constructor.',
				279,
			],
			[
				'@readonly property ReadonlyPropertyAssignPhpDoc\A::$a is assigned outside of its declaring class.',
				280,
			],
			[
				'@readonly property ReadonlyPropertyAssignPhpDoc\C::$c is assigned outside of the constructor.',
				293,
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

	public function testFeature11775(): void
	{
		if (PHP_VERSION_ID < 70400) {
			$this->markTestSkipped('Test requires PHP 7.4.');
		}

		$this->analyse([__DIR__ . '/data/feature-11775.php'], [
			[
				'@readonly property Feature11775\FooImmutable::$i is assigned outside of the constructor.',
				22,
			],
			[
				'@readonly property Feature11775\FooReadonly::$i is assigned outside of the constructor.',
				43,
			],
		]);
	}

	public function testPropertyHooks(): void
	{
		if (PHP_VERSION_ID < 80400) {
			$this->markTestSkipped('Test requires PHP 8.4.');
		}

		$this->analyse([__DIR__ . '/data/property-hooks-readonly-by-phpdoc-assign.php'], [
			[
				'@readonly property PropertyHooksReadonlyByPhpDocAssign\Foo::$i is assigned outside of the constructor.',
				15,
			],
			[
				'@readonly property PropertyHooksReadonlyByPhpDocAssign\Foo::$j is assigned outside of the constructor.',
				17,
			],
		]);
	}

}
