<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\FunctionCallParametersCheck;
use PHPStan\Rules\Methods\CallMethodsRule;
use PHPStan\Rules\Methods\MethodCallCheck;
use PHPStan\Rules\NullsafeCheck;
use PHPStan\Rules\PhpDoc\UnresolvableTypeHelper;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

class Bug9307CallMethodsRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$reflectionProvider = $this->createReflectionProvider();
		$ruleLevelHelper = new RuleLevelHelper($reflectionProvider, true, false, true, true, false, true, false);
		return new CallMethodsRule(
			new MethodCallCheck($reflectionProvider, $ruleLevelHelper, true, true),
			new FunctionCallParametersCheck($ruleLevelHelper, new NullsafeCheck(), new PhpVersion(PHP_VERSION_ID), new UnresolvableTypeHelper(), new PropertyReflectionFinder(), true, true, true, true, true),
		);
	}

	protected function shouldTreatPhpDocTypesAsCertain(): bool
	{
		return false;
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/bug-9307.php'], []);
	}

}
