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
class CallToFunctionParametersRuleComposerPhp80RangeTest extends RuleTestCase
{

	public static function getComposerAutoloaderProjectPaths(): array
	{
		return [__DIR__ . '/../../Analyser/data/composer-require-php-8-0'];
	}

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
					checkExplicitMixed: false,
					checkImplicitMixed: false,
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
			),
		);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testNamedArgumentAfterUnpackedArgumentWhenComposerAllowsPhp80(): void
	{
		$this->analyse([__DIR__ . '/data/composer-php-version-range-unpacked-arguments.php'], [
			[
				'Missing parameter $j (int) in call to function ComposerPhpVersionRangeUnpackedArguments\\doFoo.',
				15,
			],
			[
				'Unpacked argument (...) cannot be followed by a non-unpacked argument.',
				15,
			],
		]);
	}

}
