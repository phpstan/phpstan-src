<?php declare(strict_types = 1);

namespace PHPStan\Rules\Traits;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\AttributesCheck;
use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassForbiddenNameCheck;
use PHPStan\Rules\ClassNameCheck;
use PHPStan\Rules\FunctionCallParametersCheck;
use PHPStan\Rules\NullsafeCheck;
use PHPStan\Rules\PhpDoc\UnresolvableTypeHelper;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<TraitAttributesRule>
 */
class TraitAttributesRuleTest extends RuleTestCase
{

	private bool $checkExplicitMixed = false;

	private bool $checkImplicitMixed = false;

	protected function getRule(): Rule
	{
		$reflectionProvider = $this->createReflectionProvider();
		return new TraitAttributesRule(
			new AttributesCheck(
				$reflectionProvider,
				new FunctionCallParametersCheck(
					new RuleLevelHelper($reflectionProvider, true, false, true, $this->checkExplicitMixed, $this->checkImplicitMixed, false),
					new NullsafeCheck(),
					new PhpVersion(80000),
					new UnresolvableTypeHelper(),
					new PropertyReflectionFinder(),
					true,
					true,
					true,
					true,
				),
				new ClassNameCheck(
					new ClassCaseSensitivityCheck($reflectionProvider, false),
					new ClassForbiddenNameCheck(self::getContainer()),
				),
				true,
			),
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/trait-attributes.php'], [
			[
				'Attribute class TraitAttributes\AbstractAttribute is abstract.',
				8,
			],
			[
				'Attribute class TraitAttributes\MyTargettedAttribute does not have the class target.',
				20,
			],
		]);
	}

	public function testBug12011(): void
	{
		if (PHP_VERSION_ID < 80300) {
			$this->markTestSkipped('Test requires PHP 8.3.');
		}

		$this->checkExplicitMixed = true;
		$this->checkImplicitMixed = true;

		$this->analyse([__DIR__ . '/data/bug-12011.php'], [
			[
				'Parameter #1 $name of attribute class Bug12011Trait\Table constructor expects string|null, int given.',
				8,
			],
		]);
	}

	public function testBug12281(): void
	{
		$this->analyse([__DIR__ . '/data/bug-12281.php'], [
			[
				'Attribute class AllowDynamicProperties cannot be used with trait.',
				11,
			],
		]);
	}

}
