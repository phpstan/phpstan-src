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
class CallToFunctionParametersRuleComposerPhpVersionRangeTest extends RuleTestCase
{

	public static function getComposerAutoloaderProjectPaths(): array
	{
		return [__DIR__ . '/../../Analyser/data/composer-require-php-7-and-8'];
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

	#[RequiresPhp('>= 8.0.0')]
	public function testNamedArgumentsWhenComposerAllowsPhp7(): void
	{
		$this->analyse([__DIR__ . '/data/composer-php-version-range-named-arguments.php'], [
			[
				'Named arguments are supported only on PHP 8.0 and later.',
				12,
			],
		]);
	}

}
