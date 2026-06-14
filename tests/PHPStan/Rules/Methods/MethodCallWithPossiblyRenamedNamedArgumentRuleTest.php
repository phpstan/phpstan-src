<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\Php\PhpClassReflectionExtension;
use PHPStan\Rules\FunctionCallParametersCheck;
use PHPStan\Rules\NonStringableDynamicAccessCheck;
use PHPStan\Rules\NullsafeCheck;
use PHPStan\Rules\PhpDoc\UnresolvableTypeHelper;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\CompositeRule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<CompositeRule>
 */
#[RequiresPhp('>= 8.0')]
class MethodCallWithPossiblyRenamedNamedArgumentRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$reflectionProvider = self::createReflectionProvider();
		$ruleLevelHelper = new RuleLevelHelper($reflectionProvider, checkNullables: true, checkThisOnly: false, checkUnionTypes: true, checkExplicitMixed: true, checkImplicitMixed: false, checkBenevolentUnionTypes: false, discoveringSymbolsTip: true);
		$phpVersion = self::getContainer()->getByType(PhpVersion::class);
		$phpClassReflectionExtension = self::getContainer()->getByType(PhpClassReflectionExtension::class);

		// @phpstan-ignore argument.type
		return new CompositeRule([
			new CallMethodsRule(
				new MethodCallCheck($reflectionProvider, $ruleLevelHelper, true, true),
				new FunctionCallParametersCheck($ruleLevelHelper, new NullsafeCheck(), new UnresolvableTypeHelper(), new PropertyReflectionFinder(), $reflectionProvider, true, true, true, true),
				new NonStringableDynamicAccessCheck($ruleLevelHelper, true),
			),
			new OverridingMethodRule(
				$phpVersion,
				new MethodSignatureRule(new ParentMethodHelper($phpClassReflectionExtension), true, true, true),
				false,
				new MethodParameterComparisonHelper($phpVersion),
				new MethodVisibilityComparisonHelper(),
				new MethodPrototypeFinder($phpVersion, $phpClassReflectionExtension),
				false,
			),
			new MethodCallWithPossiblyRenamedNamedArgumentRule(),
		]);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/named-argument-renamed-parameter.php'], [
			[
				'Call to NamedArgumentRenamedParameter\Foo::doFoo() uses named argument for parameter $a, but NamedArgumentRenamedParameter\Bar renames it to $b.',
				25,
			],
		]);
	}

	public function testBug7434(): void
	{
		$this->analyse([__DIR__ . '/data/bug-7434.php'], [
			[
				'Call to Bug7434\Contract::method() uses named argument for parameter $val, but Bug7434\ImplementationWithDifferentName renames it to $wrong.',
				28,
			],
		]);
	}

}
