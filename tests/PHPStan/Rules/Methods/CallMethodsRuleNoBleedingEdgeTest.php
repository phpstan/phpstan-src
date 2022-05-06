<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\FunctionCallParametersCheck;
use PHPStan\Rules\NullsafeCheck;
use PHPStan\Rules\PhpDoc\UnresolvableTypeHelper;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<CallMethodsRule>
 */
class CallMethodsRuleNoBleedingEdgeTest extends RuleTestCase
{

	private bool $checkExplicitMixed;

	protected function getRule(): Rule
	{
		$reflectionProvider = $this->createReflectionProvider();
		$ruleLevelHelper = new RuleLevelHelper($reflectionProvider, true, false, true, $this->checkExplicitMixed);
		return new CallMethodsRule(
			new MethodCallCheck($reflectionProvider, $ruleLevelHelper, true, true),
			new FunctionCallParametersCheck($ruleLevelHelper, new NullsafeCheck(), new PhpVersion(PHP_VERSION_ID), new UnresolvableTypeHelper(), new PropertyReflectionFinder(), true, true, true, true),
		);
	}

	public function testGenericsInferCollection(): void
	{
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/generics-infer-collection.php'], [
			[
				'Parameter #1 $c of method GenericsInferCollection\Foo::doBar() expects GenericsInferCollection\ArrayCollection<int, int>, GenericsInferCollection\ArrayCollection<int, string> given.',
				43,
			],
		]);
	}

	public function testGenericsInferCollectionLevel8(): void
	{
		$this->checkExplicitMixed = false;
		$this->analyse([__DIR__ . '/data/generics-infer-collection.php'], [
			[
				'Parameter #1 $c of method GenericsInferCollection\Foo::doBar() expects GenericsInferCollection\ArrayCollection<int, int>, GenericsInferCollection\ArrayCollection<int, string> given.',
				43,
			],
		]);
	}

	public static function getAdditionalConfigFiles(): array
	{
		// no bleeding edge
		return [];
	}

}
