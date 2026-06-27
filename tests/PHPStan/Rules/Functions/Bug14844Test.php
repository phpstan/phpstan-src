<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\FunctionCallParametersCheck;
use PHPStan\Rules\NullsafeCheck;
use PHPStan\Rules\PhpDoc\UnresolvableTypeHelper;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<CallToFunctionParametersRule>
 */
class Bug14844Test extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$broker = self::createReflectionProvider();
		return new CallToFunctionParametersRule(
			$broker,
			new FunctionCallParametersCheck(
				new RuleLevelHelper(
					$broker,
					checkNullables: true,
					checkThisOnly: false,
					checkUnionTypes: true,
					checkExplicitMixed: true,
					checkImplicitMixed: true,
					checkBenevolentUnionTypes: false,
					discoveringSymbolsTip: true,
				),
				new NullsafeCheck(),
				new UnresolvableTypeHelper(),
				new PropertyReflectionFinder(),
				$broker,
				checkArgumentTypes: true,
				checkArgumentsPassedByReference: true,
				checkExtraArguments: true,
				checkMissingTypehints: true,
				reportMixedTernaryAndCoalesce: true,
			),
		);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testBug14844(): void
	{
		$this->analyse([__DIR__ . '/../../Analyser/nsrt/bug-14844.php'], []);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testBug14844ClassConst(): void
	{
		// Same bug as testBug14844, but the array_map callback fetches a class
		// constant (`$type::FOO`) instead of an enum-case property. It also routes
		// through mapValueType and must not plant an ErrorType in the sentinel.
		$this->analyse([__DIR__ . '/data/bug-14844-class-const.php'], []);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testBug14844Siblings(): void
	{
		// Sibling ConstantArrayType operations that also touch the sealed
		// `[never, never]` unsealed sentinel must not plant an ErrorType there.
		$this->analyse([__DIR__ . '/data/bug-14844-siblings.php'], []);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/../../../../conf/bleedingEdge.neon',
		];
	}

}
