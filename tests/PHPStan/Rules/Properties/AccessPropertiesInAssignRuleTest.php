<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<AccessPropertiesInAssignRule>
 */
class AccessPropertiesInAssignRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$reflectionProvider = $this->createReflectionProvider();
		return new AccessPropertiesInAssignRule(
			new AccessPropertiesCheck($reflectionProvider, new RuleLevelHelper($reflectionProvider, true, false, true, false, false, false), new PhpVersion(PHP_VERSION_ID), true, true),
		);
	}

	public function testRule(): void
	{
		$tipText = 'Learn more: <fg=cyan>https://phpstan.org/blog/solving-phpstan-access-to-undefined-property</>';
		$this->analyse([__DIR__ . '/data/access-properties-assign.php'], [
			[
				'Access to an undefined property TestAccessPropertiesAssign\AccessPropertyWithDimFetch::$foo.',
				10,
				$tipText,
			],
			[
				'Access to an undefined property TestAccessPropertiesAssign\AccessPropertyWithDimFetch::$foo.',
				15,
				$tipText,
			],
		]);
	}

	public function testRuleAssignOp(): void
	{
		$tipText = 'Learn more: <fg=cyan>https://phpstan.org/blog/solving-phpstan-access-to-undefined-property</>';
		$this->analyse([__DIR__ . '/data/access-properties-assign-op.php'], [
			[
				'Access to an undefined property TestAccessProperties\AssignOpNonexistentProperty::$flags.',
				15,
				$tipText,
			],
		]);
	}

	public function testRuleExpressionNames(): void
	{
		$tipText = 'Learn more: <fg=cyan>https://phpstan.org/blog/solving-phpstan-access-to-undefined-property</>';
		$this->analyse([__DIR__ . '/data/properties-from-variable-into-object.php'], [
			[
				'Access to an undefined property PropertiesFromVariableIntoObject\Foo::$noop.',
				26,
				$tipText,
			],
		]);
	}

	public function testRuleExpressionNames2(): void
	{
		$tipText = 'Learn more: <fg=cyan>https://phpstan.org/blog/solving-phpstan-access-to-undefined-property</>';
		$this->analyse([__DIR__ . '/data/properties-from-array-into-object.php'], [
			[
				'Access to an undefined property PropertiesFromArrayIntoObject\Foo::$noop.',
				42,
				$tipText,
			],
			[
				'Access to an undefined property PropertiesFromArrayIntoObject\Foo::$noop.',
				54,
				$tipText,
			],
			[
				'Access to an undefined property PropertiesFromArrayIntoObject\Foo::$noop.',
				69,
				$tipText,
			],
			[
				'Access to an undefined property PropertiesFromArrayIntoObject\Foo::$noop.',
				110,
				$tipText,
			],
		]);
	}

	public function testBug4492(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4492.php'], []);
	}

	public function testObjectShapes(): void
	{
		$tipText = 'Learn more: <fg=cyan>https://phpstan.org/blog/solving-phpstan-access-to-undefined-property</>';
		$this->analyse([__DIR__ . '/data/properties-object-shapes.php'], [
			[
				'Access to an undefined property object{foo: int, bar?: string}::$bar.',
				19,
				$tipText,
			],
			[
				'Access to an undefined property object{foo: int, bar?: string}::$baz.',
				20,
				$tipText,
			],
		]);
	}

	public function testConflictingAnnotationProperty(): void
	{
		$this->analyse([__DIR__ . '/data/conflicting-annotation-property.php'], []);
	}

	public function testBug10477(): void
	{
		$this->analyse([__DIR__ . '/../../Analyser/nsrt/bug-10477.php'], []);
	}

	public function testAsymmetricVisibility(): void
	{
		if (PHP_VERSION_ID < 80400) {
			$this->markTestSkipped('Test requires PHP 8.4.');
		}

		$this->analyse([__DIR__ . '/data/write-asymmetric-visibility.php'], [
			[
				'Assign to private(set) property $this(WriteAsymmetricVisibility\Bar)::$a.',
				26,
			],
			[
				'Assign to private(set) property WriteAsymmetricVisibility\Foo::$a.',
				34,
			],
			[
				'Assign to protected(set) property WriteAsymmetricVisibility\Foo::$b.',
				35,
			],
			[
				'Access to private property $c of parent class WriteAsymmetricVisibility\ReadonlyProps.',
				64,
			],
			[
				'Assign to protected(set) property WriteAsymmetricVisibility\ReadonlyProps::$a.',
				70,
			],
			[
				'Access to protected property WriteAsymmetricVisibility\ReadonlyProps::$b.',
				71,
			],
			[
				'Access to private property WriteAsymmetricVisibility\ReadonlyProps::$c.',
				72,
			],
			[
				'Assign to private(set) property WriteAsymmetricVisibility\ArrayProp::$a.',
				83,
			],
		]);
	}

}
