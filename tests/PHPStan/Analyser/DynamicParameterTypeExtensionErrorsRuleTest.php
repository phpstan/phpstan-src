<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Rules\FunctionCallParametersCheck;
use PHPStan\Rules\Methods\CallMethodsRule;
use PHPStan\Rules\Methods\MethodCallCheck;
use PHPStan\Rules\NullsafeCheck;
use PHPStan\Rules\PhpDoc\UnresolvableTypeHelper;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use PHPStan\Type\DynamicParameterTypeResolver;

/**
 * @extends RuleTestCase<CallMethodsRule>
 */
class DynamicParameterTypeExtensionErrorsRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$reflectionProvider = self::createReflectionProvider();
		$ruleLevelHelper = new RuleLevelHelper(
			$reflectionProvider,
			checkNullables: true,
			checkThisOnly: false,
			checkUnionTypes: true,
			checkExplicitMixed: true,
			checkImplicitMixed: false,
			checkBenevolentUnionTypes: false,
			discoveringSymbolsTip: true,
		);
		return new CallMethodsRule(
			new MethodCallCheck(
				$reflectionProvider,
				$ruleLevelHelper,
				checkFunctionNameCase: true,
				reportMagicMethods: true,
			),
			new FunctionCallParametersCheck(
				$ruleLevelHelper,
				new NullsafeCheck(),
				new UnresolvableTypeHelper(),
				new PropertyReflectionFinder(),
				$reflectionProvider,
				self::getContainer()->getByType(DynamicParameterTypeResolver::class),
				checkArgumentTypes: true,
				checkArgumentsPassedByReference: true,
				checkExtraArguments: true,
				checkMissingTypehints: true,
			),
		);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [__DIR__ . '/dynamic-parameter-type-extension-closures-errors.neon'];
	}

	public function testErrorCases(): void
	{
		$this->analyse([__DIR__ . '/data/dynamic-parameter-type-extension-closures-errors.php'], [
			[
				'Call to an undefined method DynamicParameterTypeExtensionClosuresErrors\Generic<int>::nonExistentMethod().',
				84,
			],
		]);
	}

}
