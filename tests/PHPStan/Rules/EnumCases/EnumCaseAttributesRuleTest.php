<?php declare(strict_types = 1);

namespace PHPStan\Rules\EnumCases;

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
use PHPStan\Type\ParameterClosureTypeHelper;

/**
 * @extends RuleTestCase<EnumCaseAttributesRule>
 */
class EnumCaseAttributesRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$reflectionProvider = $this->createReflectionProvider();
		return new EnumCaseAttributesRule(
			new AttributesCheck(
				$reflectionProvider,
				new FunctionCallParametersCheck(
					new RuleLevelHelper($reflectionProvider, true, false, true, false, false, true, false),
					new NullsafeCheck(),
					new PhpVersion(80100),
					new UnresolvableTypeHelper(),
					new PropertyReflectionFinder(),
					self::getContainer()->getByType(ParameterClosureTypeHelper::class),
					true,
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
		$this->analyse([__DIR__ . '/data/enum-case-attributes.php'], [
			[
				'Attribute class EnumCaseAttributes\AttributeWithPropertyTarget does not have the class constant target.',
				26,
			],
		]);
	}

}
