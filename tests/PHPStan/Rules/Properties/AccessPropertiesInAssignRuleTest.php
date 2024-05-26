<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

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
			new AccessPropertiesRule($reflectionProvider, new RuleLevelHelper($reflectionProvider, true, false, true, false, false, true, false), true, true),
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
		if (PHP_VERSION_ID < 70400) {
			self::markTestSkipped('Test requires PHP 7.4.');
		}

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
		$this->analyse([__DIR__ . '/../../Analyser/data/bug-10477.php'], []);
	}

}
