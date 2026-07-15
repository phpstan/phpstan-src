<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassForbiddenNameCheck;
use PHPStan\Rules\ClassNameCheck;
use PHPStan\Rules\NonStringableDynamicAccessCheck;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<AccessStaticPropertiesInAssignRule>
 */
class AccessStaticPropertiesInAssignRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$reflectionProvider = self::createReflectionProvider();
		$ruleLevelHelper = new RuleLevelHelper(
			$reflectionProvider,
			checkNullables: true,
			checkThisOnly: false,
			checkUnionTypes: true,
			checkExplicitMixed: false,
			checkImplicitMixed: false,
			checkBenevolentUnionTypes: false,
			discoveringSymbolsTip: true,
		);
		return new AccessStaticPropertiesInAssignRule(
			new AccessStaticPropertiesCheck(
				$reflectionProvider,
				$ruleLevelHelper,
				new ClassNameCheck(
					new ClassCaseSensitivityCheck($reflectionProvider, true),
					new ClassForbiddenNameCheck(self::getContainer()),
					$reflectionProvider,
					self::getContainer(),
				),
				new PhpVersion(PHP_VERSION_ID),
				new NonStringableDynamicAccessCheck($ruleLevelHelper, true),
				discoveringSymbolsTip: true,
			),
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/access-static-properties-assign.php'], [
			[
				'Access to an undefined static property TestAccessStaticPropertiesAssign\AccessStaticPropertyWithDimFetch::$foo.',
				10,
			],
			[
				'Access to an undefined static property TestAccessStaticPropertiesAssign\AccessStaticPropertyWithDimFetch::$foo.',
				15,
			],
		]);
	}

	public function testRuleAssignOp(): void
	{
		$this->analyse([__DIR__ . '/data/access-static-properties-assign-op.php'], [
			[
				'Access to an undefined static property AccessStaticProperties\AssignOpNonexistentProperty::$flags.',
				15,
			],
		]);
	}

	public function testRuleExpressionNames(): void
	{
		$this->analyse([__DIR__ . '/data/properties-from-array-into-static-object.php'], [
			[
				'Access to an undefined static property PropertiesFromArrayIntoStaticObject\Foo::$noop.',
				29,
			],
		]);
	}

	public function testBug2861(): void
	{
		$this->analyse([__DIR__ . '/data/bug-2861-assign.php'], []);
	}

	#[RequiresPhp('>= 8.5.0')]
	public function testAsymmetricVisibility(): void
	{
		$this->analyse([__DIR__ . '/data/static-properties-asymmetric-visibility.php'], [
			[
				'Access to private(set) property $foo of class StaticPropertiesAsymmetricVisibility\Foo.',
				25,
			],
			[
				'Access to private(set) property $foo of class StaticPropertiesAsymmetricVisibility\Foo.',
				32,
			],
			[
				'Access to protected(set) property $bar of class StaticPropertiesAsymmetricVisibility\Foo.',
				33,
			],
		]);
	}

}
