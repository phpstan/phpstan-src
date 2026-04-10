<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<AccessPropertiesInAssignRule>
 */
class AccessPropertiesInAssignRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$reflectionProvider = self::createReflectionProvider();
		return new AccessPropertiesInAssignRule(
			new AccessPropertiesCheck(
				$reflectionProvider,
				new RuleLevelHelper(
					$reflectionProvider,
					checkNullables: true,
					checkThisOnly: false,
					checkUnionTypes: true,
					checkExplicitMixed: false,
					checkImplicitMixed: false,
					checkBenevolentUnionTypes: false,
					discoveringSymbolsTip: true,
				),
				new PhpVersion(PHP_VERSION_ID),
				reportMagicProperties: true,
				checkDynamicProperties: true,
				checkNonStringableDynamicAccess: true,
			),
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

	public function testDynamicStringableAccess(): void
	{
		// All warnings are reported by the AccessPropertiesRule.
		// The AccessPropertiesInAssignRule does not report any warnings.
		$this->analyse([__DIR__ . '/data/dynamic-stringable-access.php'], []);
	}

	#[RequiresPhp('>= 8.0')]
	public function testDynamicStringableNullsafeAccess(): void
	{
		// All warnings are reported by the AccessPropertiesRule.
		// The AccessPropertiesInAssignRule does not report any warnings.
		$this->analyse([__DIR__ . '/data/dynamic-stringable-nullsafe-access.php'], []);
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
		$errors = [];
		if (PHP_VERSION_ID >= 80200) {
			$errors = [
				[
					'Access to private property ConflictingAnnotationProperty\PropertyWithAnnotation::$test.',
					27,
				],
			];
		}
		$this->analyse([__DIR__ . '/data/conflicting-annotation-property.php'], $errors);
	}

	public function testBug10477(): void
	{
		$this->analyse([__DIR__ . '/../../Analyser/nsrt/bug-10477.php'], []);
	}

	#[RequiresPhp('>= 8.4')]
	public function testAsymmetricVisibility(): void
	{
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

	public function testBug13123(): void
	{
		$this->analyse([__DIR__ . '/data/bug-13123.php'], []);
	}

	#[RequiresPhp('>= 8.5')]
	public function testBug14063(): void
	{
		$this->analyse([__DIR__ . '/data/bug-14063.php'], [
			[
				'Assign to protected(set) property Bug14063\Obj::$value.',
				31,
			],
			[
				'Assign to protected(set) property Bug14063\Obj::$value.',
				34,
			],
			[
				'Assign to protected(set) property Bug14063\Base::$value.',
				38,
			],
		]);
	}

	#[RequiresPhp('>= 8.5')]
	public function testCloneWith(): void
	{
		$this->analyse([__DIR__ . '/data/clone-with.php'], [
			[
				'Access to private property AccessPropertiesInAssignCloneWith\Foo::$priv.',
				26,
			],
			[
				'Access to protected property AccessPropertiesInAssignCloneWith\Foo::$prot.',
				26,
			],
			[
				'Access to private property AccessPropertiesInAssignCloneWith\FooReadonly::$priv.',
				56,
			],
			[
				'Access to protected property AccessPropertiesInAssignCloneWith\FooReadonly::$prot.',
				56,
			],
			[
				'Assign to protected(set) property AccessPropertiesInAssignCloneWith\FooReadonly::$pub.',
				56,
			],
		]);
	}

}
